<?php

namespace App\Services;

use App\Models\ProductItem;

class ZebraPrinterService
{
    protected string $printerIp = '192.168.1.50'; 

    public function printJewelryTag(ProductItem $record): bool
    {
        try {
            // Prepare Dynamic Data
            $stockNo = $record->barcode ?? 'N/A';
            $price = '$' . number_format($record->retail_price, 2);
            $metal = $record->metal_type ?? '10K Gold';
            $size = $record->size ? 'SZ' . $record->size : '';
            $weight = $record->metal_weight ? $record->metal_weight . ' CTW' : '';
            $desc = strtoupper(substr($record->custom_description ?? 'DIAMOND', 0, 15));

            /**
             * ZPL for Butterfly Tag (Dual Side)
             * ^FO(X,Y) -> X is horizontal, Y is vertical
             * Side 1 (Left): Stock No, Metal, Barcode
             * Side 2 (Right): Price, Specs (Size/Weight), Description
             */
            $zpl = "^XA
                ^CI28
                
                -- SIDE 1 (LEFT PART OF TAG) --
                ^FO50,30^A0N,35,35^FD{$stockNo}^FS
                ^FO50,75^A0N,35,35^FD{$metal}^FS
                ^FO30,120^BY2,3,60^BCN,60,N,N,N^FD{$stockNo}^FS
                
                -- SIDE 2 (RIGHT PART OF TAG) --
                ^FO450,30^A0N,45,45^FD{$price}^FS
                ^FO450,85^A0N,35,35^FD{$metal} {$size} {$weight}^FS
                ^FO450,135^A0N,35,35^FD{$desc}^FS
                
                ^XZ";

            $socket = @fsockopen($this->printerIp, 9100, $errno, $errstr, 3);
            if (!$socket) {
                return false;
            }

            fwrite($socket, $zpl);
            fclose($socket);

            return true;
        } catch (\Exception $e) {
            \Log::error("Zebra Print Error: " . $e->getMessage());
            return false;
        }
    }
}