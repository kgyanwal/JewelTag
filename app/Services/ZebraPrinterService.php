<?php

namespace App\Services;

use App\Models\ProductItem;
use App\Models\LabelLayout;
use Illuminate\Support\Facades\Log;

class ZebraPrinterService
{
    protected string $printerIp = '192.168.1.60'; 

    public function printJewelryTag(ProductItem $record, bool $useRFID = true): bool
    {
        try {
            $layouts = LabelLayout::all()->keyBy('field_id');
            $stockNo = (string)($record->barcode ?? 'N/A');
            $price   = '$' . number_format($record->retail_price, 2);
            $desc    = strtoupper(substr($record->custom_description ?? 'JEWELRY', 0, 15));
            $epcHex  = $record->rfid_code ?: strtoupper(str_pad(dechex($record->id), 24, '0', STR_PAD_LEFT));

            if (empty($record->rfid_code)) {
            $record->update(['rfid_code' => $epcHex]);
        }
            $getPos = fn($id, $defX, $defY) => [
                'x' => (int)($layouts->get($id)->x_pos ?? $defX),
                'y' => (int)($layouts->get($id)->y_pos ?? $defY)
            ];

            $pStock = $getPos('stock_no', 65, 6);
            $pDesc  = $getPos('desc', 65, 12);
            $pPrice = $getPos('price', 65, 20);
            $pC1    = $getPos('custom1', 65, 22);
            $pC2    = $getPos('custom2', 65, 24);
            $pC3    = $getPos('custom3', 65, 26);

            $zpl = "^XA^CI28^MMT^MTT^MD30^PW450^LL400^LS0^PR2";
            if ($useRFID) { $zpl .= "\n^RS8,,,1,N^FS\n^RFW,H,1,0,12^FD{$epcHex}^FS"; }

            $zpl .= "\n^FO{$pStock['x']},{$pStock['y']}^A0N,3,3^FD{$stockNo}^FS";
            $zpl .= "\n^FO{$pDesc['x']},{$pDesc['y']}^A0N,2,2^FD{$desc}^FS";
            $zpl .= "\n^FO{$pPrice['x']},{$pPrice['y']}^A0N,3,3^FD{$price}^FS";
            $zpl .= "\n^FO{$pC1['x']},{$pC1['y']}^A0N,2,2^FDLINE 4^FS";
            $zpl .= "\n^FO{$pC2['x']},{$pC2['y']}^A0N,2,2^FDLINE 5^FS";
            $zpl .= "\n^FO{$pC3['x']},{$pC3['y']}^A0N,2,2^FDLINE 6^FS";

            $zpl .= "\n^PQ1,0,1,Y\n^XZ";
            return $this->sendToPrinter($zpl);
        } catch (\Exception $e) { Log::error("Zebra Error: " . $e->getMessage()); return false; }
    }

    private function sendToPrinter(string $zpl): bool {
        $socket = @fsockopen($this->printerIp, 9100, $errno, $errstr, 3);
        if (!$socket) return false;
        fwrite($socket, $zpl . "\n"); fflush($socket); fclose($socket); return true;
    }

    public function checkRFIDPrinterStatus(): array {
        $socket = @fsockopen($this->printerIp, 9100, $errno, $errstr, 2);
        if ($socket) { fclose($socket); return ['connected' => true]; }
        return ['connected' => false];
    }
}