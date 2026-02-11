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
            if (!$socket) {
                Log::error("Zebra Connection Failed: $errstr ($errno)");
                return false;
            }

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

            // 💎 PRODUCTION CALIBRATION
            $zpl = "^XA^CI28^MD30^PW900^LL150^LS0^PR2";

            if ($useRFID && !empty($record->rfid_code)) { 
                // 🚀 FIXED: Pad the 8-char DB value to 24-char for antenna alignment
                $epc = str_pad(strtoupper(preg_replace('/[^A-F0-9]/', '', $record->rfid_code)), 24, '0', STR_PAD_LEFT);
                $zpl .= "\n^RS8,,,1,N^RFW,E,1,2,12^FD{$epc}^FS^RFE,E,1,2^FS";
            }

            // **1. STOCK NUMBER (BOLD CAPABLE)**
            $lStock = $getL('stock_no');
            if ($lStock && !empty($record->barcode)) {
                $x = $lStock->x_pos;
                $y = $lStock->y_pos;
                $fontH = $lStock->font_size;
                $ratio = ($lStock->is_bold) ? 0.9 : 0.7;
                $fontW = max(2, (int)($fontH * $ratio));
                $zpl .= "\n^FO{$x},{$y}^A0N,{$fontH},{$fontW}^FD{$record->barcode}^FS";
            }

            // **2. DESCRIPTION**
            $lDesc = $getL('desc');
            if ($lDesc) {
                $descValue = substr($record->custom_description ?? 'No Description', 0, 20);
                $x = $lDesc->x_pos;
                $y = $lDesc->y_pos;
                $fontH = $lDesc->font_size;
                $fontW = max(2, (int)($fontH * 0.7));
                $zpl .= "\n^FO{$x},{$y}^A0N,{$fontH},{$fontW}^FD{$descValue}^FS";
            }

            // **3. BARCODE**
            $lBarcode = $getL('barcode');
            if ($lBarcode && !empty($record->barcode)) {
                $barcodeX = $lBarcode->x_pos;
                $barcodeY = $lBarcode->y_pos;
                $barcodeHeight = $lBarcode->height; 
                $moduleWidth = $lBarcode->width;    
                
                $zpl .= "\n^FO{$barcodeX},{$barcodeY}^BY{$moduleWidth},2.0";
                $zpl .= "^BCN,{$barcodeHeight},N,N,N,N^FD{$record->barcode}^FS";
            }

            // **4. PRICE (BOLD CAPABLE)**
            $lPrice = $getL('price');
            if ($lPrice) {
                $priceVal = '$' . number_format($record->retail_price ?? 0, 2);
                $x = $lPrice->x_pos;
                $y = $lPrice->y_pos;
                $fontH = $lPrice->font_size;
                $ratio = ($lPrice->is_bold) ? 0.9 : 0.7;
                $fontW = max(2, (int)($fontH * $ratio));
                $zpl .= "\n^FO{$x},{$y}^A0N,{$fontH},{$fontW}^FD{$priceVal}^FS";
            }

            // **5. METAL WEIGHT / STONE**
            $lDwmtmk = $getL('dwmtmk');
            if ($lDwmtmk) {
                // FIXED: Filter out "0" and ignore extra 'g' if 'gm' exists in DB
                $weight = ($record->metal_weight !== "0" && filled($record->metal_weight)) ? $record->metal_weight : '';
                $type = ($record->metal_type !== "0" && filled($record->metal_type)) ? ' ' . $record->metal_type : '';
                $dwValue = trim($weight . $type);
                
                if (filled($dwValue)) {
                    $x = $lDwmtmk->x_pos; $y = $lDwmtmk->y_pos;
                    $fontH = $lDwmtmk->font_size;
                    $ratio = ($lDwmtmk->is_bold) ? 0.9 : 0.7;
                    $fontW = max(2, (int)($fontH * $ratio));
                    $zpl .= "\n^FO{$x},{$y}^A0N,{$fontH},{$fontW}^FD{$dwValue}^FS";
                }
            }

            // **6. CATEGORY / DEPARTMENT**
            $lDeptcat = $getL('deptcat');
            if ($lDeptcat) {
                // FIXED: Ignore "0" values from database strings
                $dept = ($record->department !== "0" && filled($record->department)) ? $record->department : '';
                $cat = ($record->category !== "0" && filled($record->category)) ? $record->category : '';
                $catValue = trim($dept . ($dept && $cat ? " / " : "") . $cat);
                
                if (filled($catValue)) {
                    $x = $lDeptcat->x_pos; $y = $lDeptcat->y_pos;
                    $fontH = $lDeptcat->font_size;
                    $ratio = ($lDeptcat->is_bold) ? 0.9 : 0.7;
                    $fontW = max(2, (int)($fontH * $ratio));
                    $zpl .= "\n^FO{$x},{$y}^A0N,{$fontH},{$fontW}^FD{$catValue}^FS";
                }
            }

            // **7. RFID**
            $lRfid = $getL('rfid');
            if ($lRfid && !empty($record->rfid_code)) {
                $rfidValue = substr($record->rfid_code, -8);
                $x = $lRfid->x_pos; $y = $lRfid->y_pos;
                $fontH = $lRfid->font_size;
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
            'stock_no' => ['x_pos' => 60, 'y_pos' => 6, 'height' => 0, 'width' => 0, 'font_size' => 1, 'is_bold' => true],
            'desc'     => ['x_pos' => 60, 'y_pos' => 9, 'height' => 0, 'width' => 0, 'font_size' => 1, 'is_bold' => false],
            'barcode'  => ['x_pos' => 60, 'y_pos' => 12, 'height' => 4, 'width' => 0.2, 'font_size' => 1, 'is_bold' => false],
            'price'    => ['x_pos' => 60, 'y_pos' => 19, 'height' => 0, 'width' => 0, 'font_size' => 1, 'is_bold' => true],
            'dwmtmk'   => ['x_pos' => 60, 'y_pos' => 22, 'height' => 0, 'width' => 0, 'font_size' => 1, 'is_bold' => false],
            'deptcat'  => ['x_pos' => 60, 'y_pos' => 24, 'height' => 0, 'width' => 0, 'font_size' => 1, 'is_bold' => false],
            'rfid'     => ['x_pos' => 60, 'y_pos' => 30, 'height' => 0, 'width' => 0, 'font_size' => 1, 'is_bold' => false],
        ];
        
        foreach ($defaults as $fieldId => $data) {
            LabelLayout::updateOrCreate(['field_id' => $fieldId], $data);
        }
        return true;
    }

    public function saveLayoutFromDesigner($fieldId, $data)
    {
        try {
            $layout = LabelLayout::where('field_id', $fieldId)->first();
            if (!$layout) return false;
            
            $layout->update([
                'x_pos'     => $data['x_pos'] ?? $layout->x_pos,
                'y_pos'     => $data['y_pos'] ?? $layout->y_pos,
                'font_size' => $data['font_size'] ?? $layout->font_size,
                'height'    => $data['height'] ?? $layout->height,
                'width'     => $data['width'] ?? $layout->width,
                'is_bold'   => isset($data['is_bold']) ? $data['is_bold'] : $layout->is_bold, 
            ]);
            return true;
        } catch (\Exception $e) {
            Log::error("Save layout error: " . $e->getMessage());
            return false;
        }
    }
    
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