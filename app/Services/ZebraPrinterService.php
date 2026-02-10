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

            // **DEBUG: Log current database values**
            Log::info("=== CURRENT DATABASE VALUES ===");
            foreach (['stock_no', 'desc', 'barcode', 'price', 'dwmtmk', 'deptcat', 'rfid'] as $field) {
                $layout = $getL($field);
                if ($layout) {
                    Log::info("{$field}:", [
                        'x' => $layout->x_pos,
                        'y' => $layout->y_pos,
                        'font_size' => $layout->font_size,
                        'height' => $layout->height,
                        'width' => $layout->width,
                    ]);
                }
            }

            // **1. STOCK NUMBER**
            $lStock = $getL('stock_no');
            if ($lStock && !empty($record->barcode)) {
                $x = $lStock->x_pos;
                $y = $lStock->y_pos;
                $fontH = $lStock->font_size; // Use exact DB value
                $fontW = max(2, (int)($fontH * 0.7));
                $zpl .= "\n^FO{$x},{$y}^A0N,{$fontH},{$fontW}^FD{$record->barcode}^FS";
            }

            // **2. DESCRIPTION**
            $lDesc = $getL('desc');
            if ($lDesc) {
                $descValue = substr($record->custom_description ?? 'No Description', 0, 20);
                $x = $lDesc->x_pos;
                $y = $lDesc->y_pos;
                $fontH = $lDesc->font_size; // Use exact DB value
                $fontW = max(2, (int)($fontH * 0.7));
                $zpl .= "\n^FO{$x},{$y}^A0N,{$fontH},{$fontW}^FD{$descValue}^FS";
            }

            // **3. BARCODE - Use EXACT database values**
            $lBarcode = $getL('barcode');
            if ($lBarcode && !empty($record->barcode)) {
                $barcodeX = $lBarcode->x_pos;
                $barcodeY = $lBarcode->y_pos;
                
                // **USE EXACT VALUES FROM DATABASE**
                $barcodeHeight = $lBarcode->height; // Exact height from DB
                $moduleWidth = $lBarcode->width;    // Exact width from DB (can be decimal)
                
                Log::info("Barcode using DB values:", [
                    'height' => $barcodeHeight,
                    'width' => $moduleWidth,
                    'position' => "({$barcodeX},{$barcodeY})"
                ]);
                
                // Code 128 with exact values from DB
                $zpl .= "\n^FO{$barcodeX},{$barcodeY}^BY{$moduleWidth},2.0";
                $zpl .= "^BCN,{$barcodeHeight},N,N,N,N^FD{$record->barcode}^FS";
            }

            // **4. PRICE**
            $lPrice = $getL('price');
            if ($lPrice) {
                $priceVal = '$' . number_format($record->retail_price ?? 0, 2);
                $x = $lPrice->x_pos;
                $y = $lPrice->y_pos;
                $fontH = $lPrice->font_size; // Use exact DB value
                $fontW = max(2, (int)($fontH * 0.7));
                $zpl .= "\n^FO{$x},{$y}^A0N,{$fontH},{$fontW}^FD{$priceVal}^FS";
            }

            // **5. METAL WEIGHT**
            $lDwmtmk = $getL('dwmtmk');
            if ($lDwmtmk) {
                $dwValue = ($record->metal_weight ?? '') . 'g ' . ($record->metal_type ?? '');
                if (!empty(trim($dwValue))) {
                    $x = $lDwmtmk->x_pos;
                    $y = $lDwmtmk->y_pos;
                    $fontH = $lDwmtmk->font_size; // Use exact DB value
                    $fontW = max(2, (int)($fontH * 0.7));
                    $zpl .= "\n^FO{$x},{$y}^A0N,{$fontH},{$fontW}^FD{$dwValue}^FS";
                }
            }

            // **6. CATEGORY**
            $lDeptcat = $getL('deptcat');
            if ($lDeptcat) {
                $catValue = $record->category ?? 'N/A';
                $x = $lDeptcat->x_pos;
                $y = $lDeptcat->y_pos;
                $fontH = $lDeptcat->font_size; // Use exact DB value
                $fontW = max(2, (int)($fontH * 0.7));
                $zpl .= "\n^FO{$x},{$y}^A0N,{$fontH},{$fontW}^FD{$catValue}^FS";
            }

            // **7. RFID**
            $lRfid = $getL('rfid');
            if ($lRfid && !empty($record->rfid_code)) {
                $rfidValue = substr($record->rfid_code, -8);
                $x = $lRfid->x_pos;
                $y = $lRfid->y_pos;
                $fontH = $lRfid->font_size; // Use exact DB value
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
                'y_pos' => 12,
                'height' => 4,      // **MATCHES DATABASE DEFAULT**
                'width' => 0.2,     // **MATCHES DATABASE DEFAULT**
                'font_size' => 1,
            ],
            'price' => [
                'x_pos' => 60,
                'y_pos' => 19,
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
                'y_pos' => 30,
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
        
        Log::info("Default layout set: barcode height=4, width=0.2");
        return true;
    }
    /**
     * Save layout from UI/designer to database
     */
    public function saveLayoutFromDesigner($fieldId, $data)
    {
        try {
            $layout = LabelLayout::where('field_id', $fieldId)->first();
            
            if (!$layout) {
                Log::error("Layout field not found: {$fieldId}");
                return false;
            }
            
            // Update with values from designer
            $layout->update([
                'x_pos' => $data['x_pos'] ?? $layout->x_pos,
                'y_pos' => $data['y_pos'] ?? $layout->y_pos,
                'font_size' => $data['font_size'] ?? $layout->font_size,
                'height' => $data['height'] ?? $layout->height,
                'width' => $data['width'] ?? $layout->width,
            ]);
            
            Log::info("Layout saved for {$fieldId}:", $data);
            return true;
            
        } catch (\Exception $e) {
            Log::error("Save layout error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Save all layouts at once from designer
     */
    public function saveAllLayouts($layoutsData)
    {
        try {
            foreach ($layoutsData as $fieldId => $data) {
                $this->saveLayoutFromDesigner($fieldId, $data);
            }
            return true;
        } catch (\Exception $e) {
            Log::error("Save all layouts error: " . $e->getMessage());
            return false;
        }
    }
}