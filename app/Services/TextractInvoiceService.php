<?php

namespace App\Services;

use Aws\Textract\TextractClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

class TextractInvoiceService
{
    public function extractDataFromImage(string $path): array
    {
        if (!file_exists($path)) {
            throw new Exception("OCR Error: The image file could not be found.");
        }

        try {
            // 1. Fetch the active tenant's AWS settings safely
            $settings = DB::table('site_settings')->pluck('value', 'key');

            // 2. Instantiate the client locally with tenant credentials
            $client = new TextractClient([
                'version' => 'latest',
                'region'  => $settings['aws_default_region'] ?? config('services.aws.region', 'us-east-1'),
                'credentials' => [
                    'key'    => $settings['aws_access_key_id'] ?? config('services.aws.key'),
                    'secret' => $settings['aws_secret_access_key'] ?? config('services.aws.secret'),
                ],
            ]);

            // 3. Read the image file into bytes
            $bytes = file_get_contents($path);

            // 4. Call the specialized AnalyzeExpense API
            $result = $client->analyzeExpense([
                'Document' => ['Bytes' => $bytes]
            ]);

            return $this->parseTextractResponse($result->toArray());

        } catch (Exception $e) {
            Log::error("Textract Error: " . $e->getMessage());
            throw new Exception("AWS Textract Failed: Ensure your API keys are correct and the image is readable.");
        }
    }

    private function parseTextractResponse(array $response): array
    {
        $invoiceNumber = null;
        $costPrice = 0;
        $description = '';
        $styleCode = null;
        $qty = 1;

        if (empty($response['ExpenseDocuments'])) {
            return [];
        }

        $document = $response['ExpenseDocuments'][0];

        // 1. Get Summary Fields (Invoice Number)
        foreach ($document['SummaryFields'] ?? [] as $field) {
            $type = $field['Type']['Text'] ?? '';
            $value = $field['ValueDetection']['Text'] ?? '';

            if (in_array($type, ['INVOICE_RECEIPT_ID', 'RECEIPT_NUMBER'])) {
                $invoiceNumber = $value;
            }
        }

        // 2. Get Line Items
        if (!empty($document['LineItemGroups'][0]['LineItems'])) {
            foreach ($document['LineItemGroups'][0]['LineItems'] as $item) {
                $tempDesc = '';
                $tempPrice = 0;

                foreach ($item['LineItemExpenseFields'] ?? [] as $field) {
                    $type = $field['Type']['Text'] ?? '';
                    $value = $field['ValueDetection']['Text'] ?? '';

                    if (in_array($type, ['ITEM', 'EXPENSE_ROW'])) {
                        $tempDesc = $value; // Grabs the massive squashed string
                    }
                    if (in_array($type, ['PRICE', 'UNIT_PRICE'])) {
                        $tempPrice = preg_replace('/[^0-9.]/', '', $value);
                    }
                    if ($type === 'PRODUCT_CODE') {
                        $styleCode = $value;
                    }
                    if ($type === 'QUANTITY') {
                        $qty = (int) preg_replace('/[^0-9]/', '', $value);
                    }
                }

                if ($tempPrice > 0) {
                    $description = $tempDesc;
                    $costPrice = $tempPrice;
                    break; 
                }
            }
        }

        /* -------------------------------------------------------------------------- */
        /* 3. SMART JEWELRY PARSING (Extracts specs from the squashed string)         */
        /* -------------------------------------------------------------------------- */
        $metalType = null;
        $diamondWeight = null;
        $size = null;

        $upperDesc = strtoupper($description);

        if (preg_match('/\b(10|14|18|22|24)\s*(K|KT)\b/i', $upperDesc, $matches)) {
            $metalType = $matches[1] . 'K'; 
        }

        if (preg_match('/([0-9]*\.[0-9]+|[0-9]+)\s*(CTW|CT)\b/i', $upperDesc, $matches)) {
            $diamondWeight = $matches[1];
        }

        if (preg_match('/([0-9]*\.[0-9]+|[0-9]+)\s*("|INCH|MM)/i', $upperDesc, $matches)) {
            $size = $matches[1] . str_replace('INCH', '"', $matches[2]); 
        }

        if (!$styleCode && preg_match('/\b([A-Z]+-[0-9]+)\b/', $upperDesc, $matches)) {
            $styleCode = $matches[1];
        }

        /* -------------------------------------------------------------------------- */
        /* 4. DESCRIPTION CLEANER (Aggressive filtering)                              */
        /* -------------------------------------------------------------------------- */
        $cleanDesc = $description;
        
        // 1. Remove standalone Line Numbers, Qty, and Item Numbers from the beginning
        // This safely strips "17 1.00 1.00 131772" but leaves "10KT" intact.
        $cleanDesc = preg_replace('/^(?:\s*\d+(?:\.\d+)?\b)+/', '', $cleanDesc);

        // 2. ALWAYS remove the Style Code pattern (Letters-Numbers like GNSDN-10641)
        $cleanDesc = preg_replace('/\b[A-Z]+-\d+\b/i', '', $cleanDesc);

        // 3. Remove the exact captured style code or item number just to be safe
        if ($styleCode) {
            $cleanDesc = str_ireplace($styleCode, '', $cleanDesc);
        }

        // 4. Remove "Type" and "Treat" columns that bled into the text (e.g., "10K YELLOW")
        $cleanDesc = preg_replace('/\b(10K|14K|18K|22K|24K)\s+(YELLOW|WHITE|ROSE)\b/i', '', $cleanDesc);

        // 5. Remove words from the table header that might have been caught
        $cleanDesc = preg_replace('/\b(Price|Extended|Sales)\b/i', '', $cleanDesc);
        
        // 6. Remove any formatted prices or decimals (e.g., 2,049.80, 0.00, 1.00)
        $cleanDesc = preg_replace('/\b\d{1,3}(,\d{3})*\.\d{2}\b/', '', $cleanDesc);

        // 7. Clean up excess multi-spaces left behind by the deleted words
        $cleanDesc = preg_replace('/\s+/', ' ', $cleanDesc);
        $cleanDesc = trim($cleanDesc);

        return [
            'supplier_code'      => $invoiceNumber ?? $styleCode, 
            'cost_price'         => floatval($costPrice),
            'custom_description' => $cleanDesc,
            'qty'                => $qty > 0 ? $qty : 1,
            'metal_type'         => $metalType,
            'diamond_weight'     => $diamondWeight,
            'size'               => $size,
        ];
    }

    public function extractBulkDataFromImage(string $path): array
    {
        if (!file_exists($path)) {
            throw new Exception("OCR Error: The image file could not be found.");
        }

        try {
            $settings = DB::table('site_settings')->pluck('value', 'key');

            $client = new TextractClient([
                'version' => 'latest',
                'region'  => $settings['aws_default_region'] ?? config('services.aws.region', 'us-east-1'),
                'credentials' => [
                    'key'    => $settings['aws_access_key_id'] ?? config('services.aws.key'),
                    'secret' => $settings['aws_secret_access_key'] ?? config('services.aws.secret'),
                ],
            ]);

            $bytes = file_get_contents($path);

            $result = $client->analyzeExpense([
                'Document' => ['Bytes' => $bytes]
            ]);

            return $this->parseBulkTextractResponse($result->toArray());

        } catch (Exception $e) {
            Log::error("Textract Bulk Error: " . $e->getMessage());
            throw new Exception("AWS Textract Failed: Ensure your API keys are correct and the image is readable.");
        }
    }

    private function parseBulkTextractResponse(array $response): array
    {
        $invoiceNumber = null;
        $vendorName = 'Unknown Vendor';
        $parsedItems = [];

        if (empty($response['ExpenseDocuments'])) {
            return ['vendor_name' => $vendorName, 'items' => []];
        }

        $document = $response['ExpenseDocuments'][0];

        // 1. Get Summary Fields (Invoice Number & Vendor Name)
        foreach ($document['SummaryFields'] ?? [] as $field) {
            $type = $field['Type']['Text'] ?? '';
            $value = $field['ValueDetection']['Text'] ?? '';

            if (in_array($type, ['INVOICE_RECEIPT_ID', 'RECEIPT_NUMBER'])) {
                $invoiceNumber = $value;
            }
            if ($type === 'VENDOR_NAME') {
                $vendorName = $value;
            }
            if (stripos($vendorName, 'SILVERINE') !== false) {
                    $vendorName = 'SILVERINE';
                }
        }

        // 2. Loop Through All Line Items
        if (!empty($document['LineItemGroups'][0]['LineItems'])) {
            foreach ($document['LineItemGroups'][0]['LineItems'] as $item) {
                $description = '';
                $costPrice = 0;
                $styleCode = null;
                $qty = 1;

                foreach ($item['LineItemExpenseFields'] ?? [] as $field) {
                    $type = $field['Type']['Text'] ?? '';
                    $value = $field['ValueDetection']['Text'] ?? '';

                    if (in_array($type, ['ITEM', 'EXPENSE_ROW'])) {
                        $description = $value;
                    }
                    if (in_array($type, ['PRICE', 'UNIT_PRICE'])) {
                        $costPrice = preg_replace('/[^0-9.]/', '', $value);
                    }
                    if ($type === 'PRODUCT_CODE') {
                        $styleCode = $value;
                    }
                    if ($type === 'QUANTITY') {
                        $qty = (int) preg_replace('/[^0-9]/', '', $value);
                    }
                }

                // If we found a price or a style code, it's a valid row!
                if ($costPrice > 0 || !empty($styleCode)) {
                    
                    /* --- Apply Smart Parsing per row --- */
                    $metalType = null;
                    $diamondWeight = null;
                    $size = null;

                    $upperDesc = strtoupper($description);

                    if (preg_match('/\b(10|14|18|22|24)\s*(K|KT)\b/i', $upperDesc, $matches)) $metalType = $matches[1] . 'K';
                    if (preg_match('/\b(925|STERLING)\b/i', $upperDesc)) $metalType = '925 Silver'; // Added for Silverine

                    if (preg_match('/([0-9]*\.[0-9]+|[0-9]+)\s*(CTW|CT)\b/i', $upperDesc, $matches)) $diamondWeight = $matches[1];
                    if (preg_match('/([0-9]*\.[0-9]+|[0-9]+)\s*("|INCH|MM)/i', $upperDesc, $matches)) $size = $matches[1] . str_replace('INCH', '"', $matches[2]);
                    if (!$styleCode && preg_match('/\b([A-Z0-9]+-[0-9]+)\b/i', $upperDesc, $matches)) $styleCode = $matches[1];

                    /* --- Aggressive Cleaning per row --- */
                    $cleanDesc = $description;
                    $cleanDesc = preg_replace('/^(?:\s*\d+(?:\.\d+)?\b)+/', '', $cleanDesc);
                    $cleanDesc = preg_replace('/\b[A-Z0-9]+-\d+\b/i', '', $cleanDesc);
                    if ($styleCode) $cleanDesc = str_ireplace($styleCode, '', $cleanDesc);
                    
                    // Added 925-MOIS, PC, and SILVER to the strip list for Silverine invoices
                    $cleanDesc = preg_replace('/\b(10K|14K|18K|22K|24K|925-MOIS|MOIS|PC)\s*(YELLOW|WHITE|ROSE|SILVER)?\b/i', '', $cleanDesc);
                    $cleanDesc = preg_replace('/\b(Price|Extended|Sales|Amount|Total|Weight|Qty|Metal)\b/i', '', $cleanDesc);
                    $cleanDesc = preg_replace('/\b\d{1,3}(,\d{3})*\.\d{2}\b/', '', $cleanDesc);
                    $cleanDesc = preg_replace('/\s+/', ' ', $cleanDesc);

                    // Add to our bulk array
                    $parsedItems[] = [
                        'supplier_code'      => $styleCode ?? $invoiceNumber, 
                        'cost_price'         => floatval($costPrice),
                        'custom_description' => trim($cleanDesc),
                        'qty'                => $qty > 0 ? $qty : 1,
                        'metal_type'         => $metalType,
                        'diamond_weight'     => $diamondWeight,
                        'size'               => $size,
                    ];
                }
            }
        }

        return [
            'vendor_name'    => $vendorName,
            'invoice_number' => $invoiceNumber,
            'items'          => $parsedItems
        ];
    }
}