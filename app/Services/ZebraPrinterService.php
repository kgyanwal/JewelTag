<?php

namespace App\Services;

use App\Models\ProductItem;
use App\Models\LabelLayout;
use Illuminate\Support\Facades\Log;

class ZebraPrinterService
{
    protected string $ZEBRA_PRINTER_IP;

    public function __construct() {
        $this->ZEBRA_PRINTER_IP = config('services.zebra.ip', '192.168.1.60');
    }

    public function printJewelryTag(ProductItem $record, bool $useRFID = true): bool
    {
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

    public function getZplCode(ProductItem $record, bool $useRFID = true): string
    {
        try {
            $layouts = LabelLayout::all()->keyBy('field_id');
            $getL = fn($id) => $layouts->get($id);

            // ZPL Header for 3.0" x 0.5" Label
            $zpl = "^XA^CI28^MD30^PW900^LL150^LS0^PR2"; 
            
            if ($useRFID && !empty($record->rfid_code)) { 
                $epc = str_pad(strtoupper(preg_replace('/[^A-F0-9]/', '', $record->rfid_code)), 24, '0', STR_PAD_RIGHT);
                $zpl .= "\n^RS8,,,1,N^RFW,E,1,2,12^FD{$epc}^FS^RFE,E,1,2^FS";
            }

            $textMap = [
                'stock_no' => ['v' => $record->barcode, 'side' => 1],
                'desc'     => ['v' => substr($record->custom_description, 0, 15), 'side' => 1],
                'price'    => ['v' => '$'.number_format($record->retail_price, 2), 'side' => 2],
                'dwmtmk'   => ['v' => $record->metal_weight.'g '.$record->metal_type, 'side' => 2],
                'deptcat'  => ['v' => $record->category, 'side' => 2],
                'rfid'     => ['v' => substr($record->rfid_code, -8), 'side' => 2]
            ];

            foreach ($textMap as $id => $data) {
                $l = $getL($id);
                $x = ($data['side'] === 2 ? 450 : 0) + ($l->x_pos ?? 65);
                $y = ($l->y_pos ?? 30) % 150; 
                
                // 🔹 RESTORED: Font Multiplier for 300 DPI clarity
                $h = (int)($l->font_size ?? 2) * 10; 
                $w = ($l->is_bold ?? false) ? (int)($h * 1.2) : $h; 
                $zpl .= "\n^FO{$x},{$y}^A0N,{$h},{$w}^FD{$data['v']}^FS";
            }

            $b = $getL('barcode');
            $zpl .= "\n^BY" . ($b->width ?? 2) . ",2.0^FO" . ($b->x_pos ?? 65) . "," . (($b->y_pos ?? 100) % 150);
            $zpl .= "^BCN," . ($b->height ?? 35) . ",N,N,N,A^FD{$record->barcode}^FS";
            
            $zpl .= "\n^PQ1,0,1,Y^XZ";
            return $zpl;
        } catch (\Exception $e) { return ""; }
    }
}