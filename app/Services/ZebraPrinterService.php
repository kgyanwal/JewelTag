<?php

namespace App\Services;

use App\Models\ProductItem;
use App\Models\LabelLayout;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ZebraPrinterService
{
    protected $ZEBRA_PRINTER_IP;

    public function __construct() {
        $dbIp = DB::table('site_settings')
            ->where('key', 'zebra_printer_ip')
            ->value('value');
            
        $this->ZEBRA_PRINTER_IP = $dbIp ?: config('services.zebra.ip', '192.168.1.60');
    }

    /**
     * Returns the currently active IP for UI notifications.
     */
    public function getPrinterIp() {
        return $this->ZEBRA_PRINTER_IP;
    }

    public function printJewelryTag(ProductItem $record, $useRFID = true) {
        return $this->bulkPrintJewelryTags(collect([$record]), $useRFID);
    }

    public function getZplCode(ProductItem $record, $useRFID = true) {
        try {
            $layouts = LabelLayout::all()->keyBy('field_id');
            
            $getL = function($id) use ($layouts) {
                return $layouts->get($id);
            };

            // 💎 PRODUCTION CALIBRATION (PW900 / LL150)
            $zpl = "^XA^CI28^MD30^PW900^LL150^LS0^PR2";

            // --- RFID CHIP ENCODING ---
            if ($useRFID && !empty($record->rfid_code)) { 
                $epc = str_pad(strtoupper(preg_replace('/[^A-F0-9]/', '', $record->rfid_code)), 24, '0', STR_PAD_LEFT);
                $zpl .= "\n^RS8,,,1,N^RFW,E,1,2,12^FD{$epc}^FS^RFE,E,1,2^FS";
            }

            // --- LINE 1: STOCK NUMBER ---
            $lStock = $getL('stock_no');
            if ($lStock && !empty($record->barcode)) {
                $fH = $lStock->font_size;
                $fW = max(2, (int)($fH * ($lStock->is_bold ? 0.9 : 0.7)));
                $zpl .= "\n^FO{$lStock->x_pos},{$lStock->y_pos}^A0N,{$fH},{$fW}^FD{$record->barcode}^FS";
            }

            // --- LINE 2: METAL TYPE, DIAMOND WEIGHT, METAL WEIGHT ---
            $lDwmtmk = $getL('dwmtmk');
            if ($lDwmtmk) {
                $parts = [];
                if (!empty($record->metal_type) && $record->metal_type !== "0") $parts[] = $record->metal_type;
                if (!empty($record->diamond_weight) && $record->diamond_weight !== "0") $parts[] = $record->diamond_weight;
                if (!empty($record->metal_weight) && $record->metal_weight !== "0") $parts[] = $record->metal_weight . "g";

                $dwValue = substr(implode(' ', $parts), 0, 20); 
                if (!empty($dwValue)) {
                    $fH = $lDwmtmk->font_size;
                    $fW = max(2, (int)($fH * ($lDwmtmk->is_bold ? 0.9 : 0.7)));
                    $zpl .= "\n^FO{$lDwmtmk->x_pos},{$lDwmtmk->y_pos}^A0N,{$fH},{$fW}^FD{$dwValue}^FS";
                }
            }

            // --- LINE 3: BARCODE (BARS) ---
            $lBarcode = $getL('barcode');
            if ($lBarcode && !empty($record->barcode)) {
                $bW = ($lBarcode->width > 1) ? 1 : $lBarcode->width;
                $zpl .= "\n^FO{$lBarcode->x_pos},{$lBarcode->y_pos}^BY{$bW},2.0";
                $zpl .= "^BCN,{$lBarcode->height},N,N,N,N^FD{$record->barcode}^FS";
            }

            // --- LINE 4: PRICE ---
            $lPrice = $getL('price');
            if ($lPrice) {
                $priceVal = '$' . number_format($record->retail_price ?? 0, 2);
                $fH = $lPrice->font_size;
                $fW = max(2, (int)($fH * ($lPrice->is_bold ? 0.9 : 0.7)));
                $zpl .= "\n^FO{$lPrice->x_pos},{$lPrice->y_pos}^A0N,{$fH},{$fW}^FD{$priceVal}^FS";
            }

            // --- LINE 5 & 6: DESCRIPTION (20 Chars Each) ---
            $lDesc = $getL('desc');
            if ($lDesc && !empty($record->custom_description)) {
                $fullDesc = trim($record->custom_description);
                
                $wrapped = wordwrap($fullDesc, 20, "\n", true);
                $lines = array_filter(explode("\n", $wrapped), fn($value) => !is_null($value) && $value !== '');
                $lines = array_values($lines);

                // ✅ FIX: Use same font width formula (0.7/0.9 ratio) as all other fields
                $fH = $lDesc->font_size;
                $fW = max(2, (int)($fH * ($lDesc->is_bold ? 0.9 : 0.7)));

                $yOffset = 0;
                foreach ($lines as $line) {
                    $currentY = $lDesc->y_pos + $yOffset;
                    $zpl .= "\n^FT{$lDesc->x_pos},{$currentY}^A0N,{$fH},{$fW}^FD{$line}^FS";
                    $yOffset += $lDesc->font_size;
                }
            }

            /* --- FUTURE USE: LINE 7 (DEPT/CAT) ---
            $lDeptcat = $getL('deptcat');
            if ($lDeptcat) {
                $catParts = [];
                $c1 = trim((string)($record->category ?? ''));
                if ($c1 !== "" && $c1 !== "0") $catParts[] = $c1;
                $catValue = substr(implode(' ', $catParts), 0, 20);
                if (!empty($catValue)) {
                    $fH = $lDeptcat->font_size;
                    $fW = max(2, (int)($fH * ($lDeptcat->is_bold ? 0.9 : 0.7)));
                    $zpl .= "\n^FO{$lDeptcat->x_pos},{$lDeptcat->y_pos}^A0N,{$fH},{$fW}^FD{$catValue}^FS";
                }
            }
            */

            // --- LINE 8: RFID VISUAL CODE ---
            $lRfid = $getL('rfid');
            if ($lRfid && !empty($record->rfid_code)) {
                $rfidValue = substr($record->rfid_code, -8);
                $fH = $lRfid->font_size;
                $fW = max(2, (int)($fH * ($lRfid->is_bold ? 0.9 : 0.7)));
                $zpl .= "\n^FO{$lRfid->x_pos},{$lRfid->y_pos}^A0N,{$fH},{$fW}^FD{$rfidValue}^FS";
            }
            
            $zpl .= "\n^PQ1,0,1,Y^XZ";
            return $zpl;
        } catch (\Exception $e) { 
            Log::error("ZPL Generation Error: " . $e->getMessage());
            return ""; 
        }
    }

    public function bulkPrintJewelryTags($records, $useRFID = true) 
    {
        $printType = count($records) > 1 ? 'bulk' : ($useRFID ? 'rfid' : 'barcode');

        try {
            $combinedZpl = "";
            foreach ($records as $record) {
                $zpl = $this->getZplCode($record, $useRFID);
                if (!empty($zpl)) {
                    $combinedZpl .= $zpl . "\n";
                }
            }

            if (empty(trim($combinedZpl))) {
                $this->logPrint($records, $printType, 'failed', 'No ZPL generated');
                return false;
            }

           $timeout = 10;
            $socket = @fsockopen($this->ZEBRA_PRINTER_IP, 9100, $errno, $errstr, $timeout);

            if (!$socket) {
                Log::error("Zebra Bulk Connection Failed: $errstr ($errno)");
                $this->logPrint($records, $printType, 'failed', "$errstr ($errno)");
                return false;
            }

            stream_set_timeout($socket, $timeout);

            $length = strlen($combinedZpl);
            $chunkSize = 8192;
            $written = 0;
            for ($i = 0; $i < $length; $i += $chunkSize) {
                $bytesWritten = fwrite($socket, substr($combinedZpl, $i, $chunkSize));
                if ($bytesWritten === false) {
                    Log::error("Zebra socket write failed mid-stream at offset {$i}");
                    break;
                }
                $written += $bytesWritten;
            }

            if ($written < $length) {
                Log::warning("Zebra print incomplete: wrote {$written} of {$length} bytes");
            }

            fflush($socket);
            // Let the printer fully consume the buffer before we tear down the
            // connection — closing immediately after fflush can truncate the
            // job on some Zebra firmware, producing a blank/partial label.
            usleep(200000); // 200ms
            fclose($socket);

            $this->logPrint($records, $printType, 'success');
            return true;
        } catch (\Exception $e) {
            Log::error("Zebra Bulk Print Error: " . $e->getMessage());
            $this->logPrint($records, $printType, 'failed', $e->getMessage());
            return false;
        }
    }

    protected function logPrint($records, string $printType, string $status, ?string $errorMessage = null): void
    {
        try {
            $user = auth()->user();
            foreach ($records as $record) {
                \App\Models\PrintLog::create([
                    'product_item_id' => $record->id ?? null,
                    'barcode'         => $record->barcode ?? null,
                    'rfid_code'       => $record->rfid_code ?? null,
                    'print_type'      => $printType,
                    'printer_ip'      => $this->ZEBRA_PRINTER_IP,
                    'user_id'         => $user?->id,
                    'user_name'       => $user?->username ?? $user?->name,
                    'status'          => $status,
                    'error_message'   => $errorMessage,
                ]);
            }
        } catch (\Exception $e) {
            Log::error("PrintLog write failed: " . $e->getMessage());
        }
    }

    public function setDefaultLayout() {
        // Label canvas: PW900 x LL150 (3in x 0.5in @ 300dpi)
        $labelHeight = 150;
        $marginTop   = (int) round($labelHeight * 0.06);   // ~9 dots
        $marginBot   = (int) round($labelHeight * 0.06);   // ~9 dots
        $usableH     = $labelHeight - $marginTop - $marginBot;

        // Rows stacked in the left text column: stock_no, desc, price, dwmtmk
        // Ratio of usable height each row consumes (must sum to <= 1)
        $rows = [
            'stock_no' => ['ratio' => 0.20, 'font_ratio' => 0.16, 'bold' => true],
            'desc'     => ['ratio' => 0.28, 'font_ratio' => 0.12, 'bold' => false],
            'price'    => ['ratio' => 0.24, 'font_ratio' => 0.16, 'bold' => true],
            'dwmtmk'   => ['ratio' => 0.28, 'font_ratio' => 0.10, 'bold' => false],
        ];

        $defaults = [];
        $cursorY  = $marginTop;

        foreach ($rows as $field => $cfg) {
            $slotHeight = (int) round($usableH * $cfg['ratio']);
            $fontSize   = max(10, (int) round($labelHeight * $cfg['font_ratio']));

            $defaults[$field] = [
                'x_pos'     => 20,
                'y_pos'     => $cursorY,
                'font_size' => $fontSize,
                'is_bold'   => $cfg['bold'],
                'height'    => 0,
                'width'     => 0,
            ];

            $cursorY += $slotHeight;
        }

        // RFID / category share the last line (rarely both printed at once)
        $lastLineY = $labelHeight - $marginBot - (int) round($labelHeight * 0.10);
        $defaults['rfid'] = [
            'x_pos' => 20, 'y_pos' => $lastLineY,
            'font_size' => max(10, (int) round($labelHeight * 0.09)),
            'is_bold' => false, 'height' => 0, 'width' => 0,
        ];
        $defaults['deptcat'] = [
            'x_pos' => 20, 'y_pos' => $lastLineY,
            'font_size' => max(10, (int) round($labelHeight * 0.09)),
            'is_bold' => false, 'height' => 0, 'width' => 0,
        ];

        // Barcode sits in its own right-side column
        $defaults['barcode'] = [
            'x_pos' => 400, 'y_pos' => (int) round($labelHeight * 0.20),
            'font_size' => 1, 'is_bold' => false,
            'height' => (int) round($labelHeight * 0.40), 'width' => 2,
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