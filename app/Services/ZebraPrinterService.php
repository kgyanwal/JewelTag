<?php

namespace App\Services;

use App\Models\ProductItem;
use App\Models\LabelLayout;
use App\Models\InventorySetting;
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

            // 💎 PRODUCTION CALIBRATION (PW900 / LL150)
            $zpl = "^XA^CI28^MD30^PW900^LL150^LS0^PR2";

            if ($useRFID && !empty($record->rfid_code)) { 
                $epc = str_pad(strtoupper(preg_replace('/[^A-F0-9]/', '', $record->rfid_code)), 24, '0', STR_PAD_LEFT);
                $zpl .= "\n^RS8,,,1,N^RFW,E,1,2,12^FD{$epc}^FS^RFE,E,1,2^FS";
            }

            // **1. STOCK NUMBER**
            $lStock = $getL('stock_no');
            if ($lStock && !empty($record->barcode)) {
                $fH = $lStock->font_size;
                $fW = max(2, (int)($fH * ($lStock->is_bold ? 0.9 : 0.7)));
                $zpl .= "\n^FO{$lStock->x_pos},{$lStock->y_pos}^A0N,{$fH},{$fW}^FD{$record->barcode}^FS";
            }

            // **2. DESCRIPTION**
            $lDesc = $getL('desc');
            if ($lDesc) {
                $descValue = substr(trim($record->custom_description ?? ''), 0, 20);
                if (!empty($descValue) && $descValue !== "0") {
                    $fH = $lDesc->font_size;
                    $fW = max(2, (int)($fH * 0.7));
                    $zpl .= "\n^FO{$lDesc->x_pos},{$lDesc->y_pos}^A0N,{$fH},{$fW}^FD{$descValue}^FS";
                }
            }

            // **3. BARCODE (FORCED HEIGHT 4, NO STRETCH)**
            $lBarcode = $getL('barcode');
            if ($lBarcode && !empty($record->barcode)) {
                // 🚀 FIX: Force width to 1.0 to stop stretching on small tags.
                $bW = ($lBarcode->width > 1) ? 1 : $lBarcode->width;
                $zpl .= "\n^FO{$lBarcode->x_pos},{$lBarcode->y_pos}^BY{$bW},2.0";
                $zpl .= "^BCN,{$lBarcode->height},N,N,N,N^FD{$record->barcode}^FS";
            }

            // **4. PRICE**
            $lPrice = $getL('price');
            if ($lPrice) {
                $priceVal = '$' . number_format($record->retail_price ?? 0, 2);
                $fH = $lPrice->font_size;
                $fW = max(2, (int)($fH * ($lPrice->is_bold ? 0.9 : 0.7)));
                $zpl .= "\n^FO{$lPrice->x_pos},{$lPrice->y_pos}^A0N,{$fH},{$fW}^FD{$priceVal}^FS";
            }

            // **5. METAL/STONE (COMPOSITE)**
            $lDwmtmk = $getL('dwmtmk');
            if ($lDwmtmk) {
                $parts = [];
                if (!empty($record->diamond_weight) && $record->diamond_weight !== "0") $parts[] = $record->diamond_weight;
                if (!empty($record->metal_type) && $record->metal_type !== "0") $parts[] = $record->metal_type;
                if (!empty($record->department) && $record->department !== "0") $parts[] = $record->department;
                if (!empty($record->metal_weight) && $record->metal_weight !== "0") $parts[] = $record->metal_weight;

                $dwValue = substr(implode(' ', $parts), 0, 20);
                if (!empty($dwValue)) {
                    $fH = $lDwmtmk->font_size;
                    $fW = max(2, (int)($fH * ($lDwmtmk->is_bold ? 0.9 : 0.7)));
                    $zpl .= "\n^FO{$lDwmtmk->x_pos},{$lDwmtmk->y_pos}^A0N,{$fH},{$fW}^FD{$dwValue}^FS";
                }
            }

            // **6. CATEGORY (CONCATENATE: Category + Sub-Department)**
            $lDeptcat = $getL('deptcat');
            if ($lDeptcat) {
                $catParts = [];
                
                // 🚀 FAILSAFE LOOKUP: Match UI behavior
                $c1 = trim((string)($record->category ?? ''));
                $c2 = trim((string)($record->sub_department ?? ''));

                if ($c1 !== "" && $c1 !== "0") $catParts[] = $c1;
                if ($c2 !== "" && $c2 !== "0") $catParts[] = $c2;
                
                $catValue = substr(implode(' ', $catParts), 0, 20);

                // FINAL LOG: If this says EMPTY now, the database is definitely not saving your UI selections.
                Log::info("ZPL PRINT ATTEMPT for {$record->barcode} | Value: " . ($catValue ?: 'EMPTY'));

                if (!empty($catValue)) {
                    $fH = $lDeptcat->font_size;
                    $fW = max(2, (int)($fH * ($lDeptcat->is_bold ? 0.9 : 0.7)));
                    $zpl .= "\n^FO{$lDeptcat->x_pos},{$lDeptcat->y_pos}^A0N,{$fH},{$fW}^FD{$catValue}^FS";
                }
            }

            // **7. RFID**
            $lRfid = $getL('rfid');
            if ($lRfid && !empty($record->rfid_code)) {
                $rfidValue = substr($record->rfid_code, -8);
                $fH = $lRfid->font_size;
                $fW = max(2, (int)($fH * 0.7));
                $zpl .= "\n^FO{$lRfid->x_pos},{$lRfid->y_pos}^A0N,{$fH},{$fW}^FD{$rfidValue}^FS";
            }
            
            $zpl .= "\n^PQ1,0,1,Y^XZ";
            return $zpl;
        } catch (\Exception $e) { 
            Log::error("ZPL Generation Error: " . $e->getMessage());
            return ""; 
        }
    }

    public function setDefaultLayout() {
        $defaults = [
            'stock_no' => ['x_pos' => 60, 'y_pos' => 6, 'height' => 0, 'width' => 0, 'font_size' => 15, 'is_bold' => true],
            'desc'     => ['x_pos' => 60, 'y_pos' => 10, 'height' => 0, 'width' => 0, 'font_size' => 12, 'is_bold' => false],
            'barcode'  => ['x_pos' => 60, 'y_pos' => 13, 'height' => 4, 'width' => 1, 'font_size' => 1, 'is_bold' => false],
            'price'    => ['x_pos' => 60, 'y_pos' => 20, 'height' => 0, 'width' => 0, 'font_size' => 15, 'is_bold' => true],
            'dwmtmk'   => ['x_pos' => 60, 'y_pos' => 24, 'height' => 0, 'width' => 0, 'font_size' => 12, 'is_bold' => false],
            'deptcat'  => ['x_pos' => 60, 'y_pos' => 26, 'height' => 0, 'width' => 0, 'font_size' => 12, 'is_bold' => false],
            'rfid'     => ['x_pos' => 60, 'y_pos' => 28, 'height' => 0, 'width' => 0, 'font_size' => 12, 'is_bold' => false],
        ];
        foreach ($defaults as $fieldId => $data) {
            LabelLayout::updateOrCreate(['field_id' => $fieldId], $data);
        }
        return true;
    }

    public function saveLayoutFromDesigner($fieldId, $data) {
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

    public function saveAllLayouts($layoutsData) {
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