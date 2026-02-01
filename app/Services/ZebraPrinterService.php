<?php

namespace App\Services;

use App\Models\ProductItem;
use App\Models\LabelLayout;
use Illuminate\Support\Facades\Log;

class ZebraPrinterService
{
    /**
     * Generates the ZPL string for the browser to handle.
     * Matches exact positioning and concatenation requirements.
     */
    public function generateZpl(ProductItem $record, bool $useRFID = true): string
    {
        try {
            // Fetch layouts from DB
            $layouts = LabelLayout::all()->keyBy('field_id');
            
            // ---------------- CORE DATA ----------------
            $stockNo = (string)($record->barcode ?? 'N/A');
            $price   = '$' . number_format($record->retail_price, 2);
            $desc    = strtoupper(substr($record->custom_description ?? 'JEWELRY', 0, 15));
            
            // Generate EPC Hex if missing
            $epcHex  = $record->rfid_code ?: strtoupper(str_pad(dechex($record->id), 24, '0', STR_PAD_LEFT));

            if (empty($record->rfid_code)) {
                $record->update(['rfid_code' => $epcHex]);
            }

            // ---------------- CONCATENATED VALUES ----------------
            // C1: Diamond weight + Metal Weight + Metal Karat
            $c1 = strtoupper(implode(' | ', array_filter([
                $record->diamond_weight ? "{$record->diamond_weight} CTW" : null,
                $record->metal_weight ? "{$record->metal_weight}g" : null,
                $record->metal_type,
            ])));

            // C2: Sub Department + Category
            $c2 = strtoupper(implode(' / ', array_filter([
                $record->sub_department,
                $record->category,
            ])));

            // C3: RFID tag number
            $c3 = "RFID: " . ($record->rfid_code ?? $epcHex);

            // ---------------- POSITIONING LOGIC ----------------
            $getPos = fn($id, $defX, $defY) => [
                'x' => (int)($layouts->get($id)->x_pos ?? $defX),
                'y' => (int)($layouts->get($id)->y_pos ?? $defY)
            ];

            // Mapping exact IDs to designer positions
            $pStock   = $getPos('stock_no', 65, 6);
            $pDesc    = $getPos('desc', 65, 9);
            $pBarcode = $getPos('barcode', 65, 12);
            $pPrice   = $getPos('price', 65, 20);
            $pC1      = $getPos('dwmtmk', 65, 22);  // Matched to Designer ID
            $pC2      = $getPos('deptcat', 65, 24); // Matched to Designer ID
            $pC3      = $getPos('rfid', 65, 26);    // Matched to Designer ID

            // ---------------- START ZPL STRING ----------------
            $zpl = "^XA^CI28^MMT^MTT^MD30^PW450^LL400^LS0^PR2";
            
            if ($useRFID) { 
                $zpl .= "\n^RS8,,,1,N^FS\n^RFW,H,1,0,12^FD{$epcHex}^FS"; 
            }

            // Side 1 / Identity
            $zpl .= "\n^FO{$pStock['x']},{$pStock['y']}^A0N,3,3^FD{$stockNo}^FS";
            $zpl .= "\n^FO{$pDesc['x']},{$pDesc['y']}^A0N,2,2^FD{$desc}^FS";
            $zpl .= "\n^FO{$pBarcode['x']},{$pBarcode['y']}^BY2^BCN,50,Y,N,N^FD{$stockNo}^FS";

            // Side 2 / Details & Price
            $zpl .= "\n^FO{$pPrice['x']},{$pPrice['y']}^A0N,3,3^FD{$price}^FS";
            $zpl .= "\n^FO{$pC1['x']},{$pC1['y']}^A0N,2,2^FD{$c1}^FS";
            $zpl .= "\n^FO{$pC2['x']},{$pC2['y']}^A0N,2,2^FD{$c2}^FS";
            $zpl .= "\n^FO{$pC3['x']},{$pC3['y']}^A0N,2,2^FD{$c3}^FS";

            $zpl .= "\n^PQ1,0,1,Y\n^XZ";

            return $zpl;

        } catch (\Exception $e) { 
            Log::error("Zebra ZPL Generation Error: " . $e->getMessage()); 
            return "^XA^FO50,50^A0N,40,40^FDError Generating ZPL^FS^XZ"; 
        }
    }

    public function checkRFIDPrinterStatus(): array 
    {
        return ['connected' => true];
    }
}