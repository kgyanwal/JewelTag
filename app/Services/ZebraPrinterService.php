<?php

namespace App\Services;

use App\Models\ProductItem;
use Illuminate\Support\Facades\Log;

class ZebraPrinterService
{
    protected string $printerIp = '192.168.1.60'; 

    public function printJewelryTag(ProductItem $record, bool $useRFID = true): bool
    {
        try {
            $stockNo = (string)($record->barcode ?? 'N/A');
            $price   = '$' . number_format($record->retail_price, 2);
            $desc    = strtoupper(substr($record->custom_description ?? 'JEWELRY', 0, 20));
            $epcHex  = $record->rfid_epc ?: strtoupper(str_pad(dechex($record->id), 24, '0', STR_PAD_LEFT));

            $zpl = "^XA^CI28^MMT^MTT^MD30^PW450^LL250^LS0";
            if ($useRFID) {
                $zpl .= "\n^RS8,,,1,N^FS"; 
                $zpl .= "\n^RFW,H,1,0,12^FD{$epcHex}^FS"; 
            }

            $zpl .= "\n^FO65,5^A0N,3,3^FD{$stockNo}^FS";
            $zpl .= "\n^FO65,8^A0N,2,2^FD{$desc}^FS";
            $zpl .= "\n^FO65,10^A0N,2,2^FD{$price}+barcode^FS";

            $zpl .= "\n^FO65,18^A0N,3,3^FD{$price}+1P^FS";
            $zpl .= "\n^FO65,21^A0N,2,2^FD{$price}+2P^FS";
            $zpl .= "\n^FO65,23^A0N,2,2^FD{$desc}+3P^FS";


            $zpl .= "\n^PQ1,0,1,Y\n^XZ";

            Log::info("Sending ZPL to Zebra: " . $zpl);

            return $this->sendToPrinter($zpl);
        } catch (\Exception $e) {
            Log::error("Zebra Error: " . $e->getMessage());
            return false;
        }
    }

    private function sendToPrinter(string $zpl): bool
    {
        $socket = @fsockopen($this->printerIp, 9100, $errno, $errstr, 3);
        if (!$socket) return false;
        fwrite($socket, $zpl . "\n");
        fclose($socket);
        return true;
    }

    public function checkRFIDPrinterStatus(): array
    {
        $socket = @fsockopen($this->printerIp, 9100, $errno, $errstr, 2);
        return ['connected' => (bool)$socket];
    }
}