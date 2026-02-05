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

    public function getZplCode(ProductItem $record, bool $useRFID = true): string
    {
        try {
            $layouts = LabelLayout::all()->keyBy('field_id');
            $stockNo = (string)($record->barcode ?? 'N/A');
            $price   = '$' . number_format($record->retail_price, 2);
            $desc    = strtoupper(substr($record->custom_description ?? 'JEWELRY', 0, 15));
            $metal   = strtoupper($record->metal_weight . "g " . $record->metal_type);
            $cat     = strtoupper($record->category);
            $rfidNum = "RFID: " . substr($record->rfid_code, -8);

            $getL = fn($id) => $layouts->get($id);
            $zpl = "^XA^CI28^MD30^PW900^LL150^LS0^PR2"; 
            
            if ($useRFID && !empty($record->rfid_code)) { 
                $zpl .= "\n^RS8,,,1,N^FS\n^RFW,H,1,0,12^FD{$record->rfid_code}^FS"; 
            }

            $textMap = [
                'stock_no' => ['v' => $stockNo, 'side' => 1],
                'desc'     => ['v' => $desc,    'side' => 1],
                'price'    => ['v' => $price,   'side' => 2],
                'dwmtmk'   => ['v' => $metal,   'side' => 2],
                'deptcat'  => ['v' => $cat,     'side' => 2],
                'rfid'     => ['v' => $rfidNum, 'side' => 2]
            ];

            foreach ($textMap as $id => $data) {
                $l = $getL($id);
                $x = ($data['side'] === 2 ? 450 : 0) + ($l->x_pos ?? 65);
                $y = ($l->y_pos ?? 30) % 150; 
                $h = ($l->font_size ?? 2) * 10; 
                $w = ($l->is_bold ?? false) ? ($h + 2) : $h; 
                $zpl .= "\n^FO{$x},{$y}^A0N,{$h},{$w}^FD{$data['v']}^FS";
            }

            $b = $getL('barcode');
            $zpl .= "\n^BY" . ($b->width ?? 1) . ",2.0^FO" . ($b->x_pos ?? 65) . "," . (($b->y_pos ?? 100) % 150);
            $zpl .= "^BCN," . ($b->height ?? 35) . ",N,N,N,A^FD{$stockNo}^FS";
            $zpl .= "\n^PQ1,0,1,Y^XZ";

            return $zpl;
        } catch (\Exception $e) {
            Log::error("Zebra ZPL Gen Error: " . $e->getMessage());
            return "";
        }
    }

    public function printJewelryTag(ProductItem $record, bool $useRFID = true): bool {
        return false; // Deprecated: Cloud cannot use Sockets
    }
}