<?php

namespace App\Services;

use App\Models\ProductItem;

class ZebraPrinterService
{
    protected string $printerIp = '192.168.1.50'; 
    protected string $printerModel = 'ZD420-RFID'; // Example RFID printer model

    public function printJewelryTag(ProductItem $record, bool $useRFID = false): bool
    {
        try {
            // Prepare Dynamic Data
            $stockNo = $record->barcode ?? 'N/A';
            $price = '$' . number_format($record->retail_price, 2);
            $metal = $record->metal_type ?? '10K Gold';
            $size = $record->size ? 'SZ' . $record->size : '';
            $weight = $record->metal_weight ? $record->metal_weight . ' CTW' : '';
            $desc = strtoupper(substr($record->custom_description ?? 'DIAMOND', 0, 15));
            
            // Generate unique EPC for RFID
            $epc = $this->generateRFIDEPC($record);
            
            // Set RFID data
            $rfidData = "STOCK:{$stockNo}|PRICE:{$price}|ID:{$record->id}";

            if ($useRFID) {
                // ZPL for RFID Tag Printing
                $zpl = $this->generateRFIDZPL($stockNo, $price, $metal, $size, $weight, $desc, $epc, $rfidData);
            } else {
                // Standard barcode printing
                $zpl = $this->generateBarcodeZPL($stockNo, $price, $metal, $size, $weight, $desc);
            }

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

    /**
     * Generate RFID EPC (Electronic Product Code)
     */
    private function generateRFIDEPC(ProductItem $record): string
    {
        // Example: Company prefix (3 chars) + Item ID + Check digit
        $companyPrefix = 'JWL'; // Jeweltag
        $itemId = str_pad($record->id, 8, '0', STR_PAD_LEFT);
        $dateCode = date('mdY');
        
        return $companyPrefix . $itemId . $dateCode;
    }

    /**
     * Generate ZPL for RFID Tag
     */
    private function generateRFIDZPL(
        string $stockNo, 
        string $price, 
        string $metal, 
        string $size, 
        string $weight, 
        string $desc, 
        string $epc,
        string $rfidData
    ): string {
        return "^XA
            ^CI28
            ^RS8,,,^RFW,H^FD{$rfidData}^FS
            
            -- EPC Encoding --
            ^RS,,,^RFR,H,0,12,^FN1^FS
            ^RFW,H,1,12,2^FD{$epc}^FS
            
            -- SIDE 1 (LEFT PART OF TAG) --
            ^FO50,30^A0N,35,35^FD{$stockNo}^FS
            ^FO50,75^A0N,35,35^FD{$metal}^FS
            ^FO30,120^BY2,3,60^BCN,60,N,N,N^FD{$stockNo}^FS
            
            -- SIDE 2 (RIGHT PART OF TAG) --
            ^FO450,30^A0N,45,45^FD{$price}^FS
            ^FO450,85^A0N,35,35^FD{$metal} {$size} {$weight}^FS
            ^FO450,135^A0N,35,35^FD{$desc}^FS
            
            -- RFID Indicator --
            ^FO350,180^A0N,25,25^FDRFID ENABLED^FS
            
            ^XZ";
    }

    /**
     * Generate ZPL for Standard Barcode
     */
    private function generateBarcodeZPL(
        string $stockNo, 
        string $price, 
        string $metal, 
        string $size, 
        string $weight, 
        string $desc
    ): string {
        return "^XA
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
    }

    /**
     * Check RFID Printer Status
     */
    public function checkRFIDPrinterStatus(): array
    {
        try {
            $socket = @fsockopen($this->printerIp, 9100, $errno, $errstr, 3);
            if (!$socket) {
                return ['connected' => false, 'error' => $errstr];
            }

            // Send status check command
            fwrite($socket, "~HQES\r\n");
            
            // Wait for response
            stream_set_timeout($socket, 2);
            $response = fread($socket, 1024);
            fclose($socket);

            return [
                'connected' => true,
                'response' => $response,
                'rfid_supported' => str_contains($response, 'RFID'),
                'printer_ready' => !str_contains($response, 'PAUSED')
            ];
        } catch (\Exception $e) {
            return ['connected' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Print RFID tag with custom data
     */
    public function printCustomRFIDTag(array $data): bool
    {
        $zpl = "^XA
            ^CI28
            ^RS8,,,^RFW,H^FD{$data['rfid_data']}^FS
            
            -- EPC Encoding --
            ^RS,,,^RFR,H,0,12,^FN1^FS
            ^RFW,H,1,12,2^FD{$data['epc']}^FS
            
            -- Visual Design --
            ^FO50,30^A0N,40,40^FD{$data['title']}^FS
            ^FO50,80^A0N,30,30^FD{$data['subtitle']}^FS
            ^FO50,130^BY2,3,60^BCN,60,N,N,N^FD{$data['barcode']}^FS
            ^FO50,200^A0N,25,25^FD{$data['footer']}^FS
            
            ^XZ";

        return $this->sendToPrinter($zpl);
    }

    /**
     * Test RFID tag writing
     */
    public function testRFIDWrite(): bool
    {
        $testEPC = 'TEST' . date('YmdHis');
        $testData = 'TEST:RFID|DATE:' . date('Y-m-d H:i:s');
        
        $zpl = "^XA
            ^CI28
            ^RS8,,,^RFW,H^FD{$testData}^FS
            ^RS,,,^RFR,H,0,12,^FN1^FS
            ^RFW,H,1,12,2^FD{$testEPC}^FS
            ^FO50,50^A0N,40,40^FDRFID TEST SUCCESSFUL^FS
            ^XZ";

        return $this->sendToPrinter($zpl);
    }

    /**
     * Send ZPL to printer
     */
    private function sendToPrinter(string $zpl): bool
    {
        try {
            $socket = @fsockopen($this->printerIp, 9100, $errno, $errstr, 3);
            if (!$socket) {
                \Log::error("Printer connection failed: {$errstr}");
                return false;
            }

            fwrite($socket, $zpl);
            fclose($socket);
            return true;
        } catch (\Exception $e) {
            \Log::error("Print error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Read RFID tag data
     */
    public function readRFIDTag(): ?array
    {
        try {
            $socket = @fsockopen($this->printerIp, 9100, $errno, $errstr, 3);
            if (!$socket) {
                return null;
            }

            // Send RFID read command
            fwrite($socket, "^XA^RFR,H^FS^XZ\r\n");
            
            // Wait for response
            stream_set_timeout($socket, 5);
            $response = fread($socket, 1024);
            fclose($socket);

            // Parse response
            if (preg_match('/\^FD(.+?)\^FS/', $response, $matches)) {
                return [
                    'raw_data' => $response,
                    'tag_data' => $matches[1],
                    'epc' => $this->extractEPC($response),
                    'success' => true
                ];
            }

            return ['success' => false, 'raw_data' => $response];
        } catch (\Exception $e) {
            \Log::error("RFID Read Error: " . $e->getMessage());
            return null;
        }
    }

    private function extractEPC(string $response): ?string
    {
        if (preg_match('/\^FD(.{24})\^FS/', $response, $matches)) {
            return $matches[1];
        }
        return null;
    }
}