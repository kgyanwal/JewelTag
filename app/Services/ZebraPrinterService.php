<?php

namespace App\Services;

use App\Models\ProductItem;
use App\Models\LabelLayout;
use Illuminate\Support\Facades\Log;

class ZebraPrinterService
{
    protected $ZEBRA_PRINTER_IP;

    public function __construct() {
        $this->ZEBRA_PRINTER_IP = config('services.zebra.ip', '192.168.1.60');
    }

    public function printJewelryTag(ProductItem $record, $useRFID = true) {
        try {
            $zpl = $this->getZplCode($record, $useRFID);
            if (empty($zpl)) return false;

            $socket = @fsockopen($this->ZEBRA_PRINTER_IP, 9100, $errno, $errstr, 3);
            if (!$socket) return false;

            fwrite($socket, $zpl . "\n");
            fflush($socket);
            fclose($socket);
            return true;
        } catch (\Exception $e) {
            Log::error("Zebra Print Error: " . $e->getMessage());
            return false;
        }
    }

    public function getZplCode(ProductItem $record, $useRFID = true) {
        try {
            $layouts = LabelLayout::all()->keyBy('field_id');
            
            $getL = function($id) use ($layouts) {
                return $layouts->get($id);
            };

            $zpl = "^XA^CI28^MD30^PW900^LL150^LS0^PR2";
            
            if ($useRFID && !empty($record->rfid_code)) { 
                $epc = str_pad(strtoupper(preg_replace('/[^A-F0-9]/', '', $record->rfid_code)), 24, '0', STR_PAD_RIGHT);
                $zpl .= "\n^RS8,,,1,N^RFW,E,1,2,12^FD{$epc}^FS^RFE,E,1,2^FS";
            }

            // **1. STOCK NUMBER (y=6)**
            $lStock = $getL('stock_no');
            if ($lStock && !empty($record->barcode)) {
                $x = $lStock->x_pos ?? 60;
                $y = $lStock->y_pos ?? 6;
                $fontH = max(3, ($lStock->font_size ?? 1) + 2);
                $fontW = max(2, (int)($fontH * 0.7));
                $zpl .= "\n^FO{$x},{$y}^A0N,{$fontH},{$fontW}^FD{$record->barcode}^FS";
            }

            // **2. DESCRIPTION (y=9)**
            $lDesc = $getL('desc');
            if ($lDesc) {
                $descValue = substr($record->custom_description ?? 'No Description', 0, 20);
                $x = $lDesc->x_pos ?? 60;
                $y = $lDesc->y_pos ?? 9;
                $fontH = max(2, ($lDesc->font_size ?? 1) + 1);
                $fontW = max(2, (int)($fontH * 0.7));
                $zpl .= "\n^FO{$x},{$y}^A0N,{$fontH},{$fontW}^FD{$descValue}^FS";
            }

            // **3. BARCODE - FIXED: VERY SMALL HEIGHT**
            $lBarcode = $getL('barcode');
            if ($lBarcode && !empty($record->barcode)) {
                $barcodeX = $lBarcode->x_pos ?? 60;
                $barcodeY = $lBarcode->y_pos ?? 11;
                
                // **CRITICAL FIX: USE VERY SMALL BARCODE HEIGHT**
                // Total barcode = bars + text below
                // For small label, we need TINY barcode
                $barcodeHeight = 8; // **ONLY 8 DOTS FOR BARS!**
                
                $moduleWidth = 0.15; // Very narrow
                
                // **Option 1: Code 128 with NO human-readable text below**
                // ^B3 = Code 128, N=normal, N=height, N=no interpretation line, N=no quiet zone
                $zpl .= "\n^FO{$barcodeX},{$barcodeY}^BY{$moduleWidth},2.0";
                $zpl .= "^B3N,N,{$barcodeHeight},N,N,N^FD{$record->barcode}^FS";
                
                // **Option 2: If Option 1 doesn't work, try this even smaller:**
                // $zpl .= "^FO{$barcodeX},{$barcodeY}^BY{$moduleWidth},2.0";
                // $zpl .= "^B3N,N,6,N,N,N^FD{$record->barcode}^FS";
                
                Log::info("Barcode settings:", [
                    'height' => $barcodeHeight,
                    'position' => "({$barcodeX},{$barcodeY})",
                    'total_estimated' => $barcodeHeight + 5 // Small text if any
                ]);
            }

            // **4. PRICE (y=18)**
            $lPrice = $getL('price');
            if ($lPrice) {
                $priceVal = '$' . number_format($record->retail_price ?? 0, 2);
                $x = $lPrice->x_pos ?? 60;
                $y = $lPrice->y_pos ?? 18;
                $fontH = max(3, ($lPrice->font_size ?? 1) + 2);
                $fontW = max(2, (int)($fontH * 0.7));
                $zpl .= "\n^FO{$x},{$y}^A0N,{$fontH},{$fontW}^FD{$priceVal}^FS";
            }

            // **5. METAL WEIGHT (y=22)**
            $lDwmtmk = $getL('dwmtmk');
            if ($lDwmtmk) {
                $dwValue = ($record->metal_weight ?? '') . 'g ' . ($record->metal_type ?? '');
                if (!empty(trim($dwValue))) {
                    $x = $lDwmtmk->x_pos ?? 60;
                    $y = $lDwmtmk->y_pos ?? 22;
                    $fontH = max(2, ($lDwmtmk->font_size ?? 1) + 1);
                    $fontW = max(2, (int)($fontH * 0.7));
                    $zpl .= "\n^FO{$x},{$y}^A0N,{$fontH},{$fontW}^FD{$dwValue}^FS";
                }
            }

            // **6. CATEGORY (y=24)**
            $lDeptcat = $getL('deptcat');
            if ($lDeptcat) {
                $catValue = $record->category ?? 'N/A';
                $x = $lDeptcat->x_pos ?? 60;
                $y = $lDeptcat->y_pos ?? 24;
                $fontH = max(2, ($lDeptcat->font_size ?? 1) + 1);
                $fontW = max(2, (int)($fontH * 0.7));
                $zpl .= "\n^FO{$x},{$y}^A0N,{$fontH},{$fontW}^FD{$catValue}^FS";
            }

            // **7. RFID (y=26)**
            $lRfid = $getL('rfid');
            if ($lRfid && !empty($record->rfid_code)) {
                $rfidValue = substr($record->rfid_code, -8);
                $x = $lRfid->x_pos ?? 60;
                $y = $lRfid->y_pos ?? 26;
                $fontH = max(2, ($lRfid->font_size ?? 1) + 1);
                $fontW = max(2, (int)($fontH * 0.7));
                $zpl .= "\n^FO{$x},{$y}^A0N,{$fontH},{$fontW}^FD{$rfidValue}^FS";
            }
            
            $zpl .= "\n^PQ1,0,1,Y^XZ";
            
            return $zpl;
        } catch (\Exception $e) { 
            Log::error("ZPL Generation Error: " . $e->getMessage());
            return ""; 
        }
    }
    
    /**
     * Set default label layout positions
     */
    public function setDefaultLayout()
    {
        $defaults = [
            'stock_no' => [
                'x_pos' => 60,
                'y_pos' => 6,
                'height' => 0,
                'width' => 0,
                'font_size' => 1,
            ],
            'desc' => [
                'x_pos' => 60,
                'y_pos' => 9,
                'height' => 0,
                'width' => 0,
                'font_size' => 1,
            ],
            'barcode' => [
                'x_pos' => 60,
                'y_pos' => 11,
                'height' => 8,  // **CHANGED: Much smaller height for barcode**
                'width' => 0.15, // **CHANGED: Narrower width**
                'font_size' => 1,
            ],
            'price' => [
                'x_pos' => 60,
                'y_pos' => 18,
                'height' => 0,
                'width' => 0,
                'font_size' => 1,
            ],
            'dwmtmk' => [
                'x_pos' => 60,
                'y_pos' => 22,
                'height' => 0,
                'width' => 0,
                'font_size' => 1,
            ],
            'deptcat' => [
                'x_pos' => 60,
                'y_pos' => 24,
                'height' => 0,
                'width' => 0,
                'font_size' => 1,
            ],
            'rfid' => [
                'x_pos' => 60,
                'y_pos' => 26,
                'height' => 0,
                'width' => 0,
                'font_size' => 1,
            ],
        ];
        
        foreach ($defaults as $fieldId => $data) {
            LabelLayout::updateOrCreate(
                ['field_id' => $fieldId],
                $data
            );
        }
        
        Log::info("Default label layout set successfully with small barcode");
        return true;
    }
}