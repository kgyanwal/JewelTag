<?php

namespace App\Services;

use Codesmiths\LaravelOcrSpace\Facades\OcrSpace;
use Codesmiths\LaravelOcrSpace\OcrSpaceOptions;
use Codesmiths\LaravelOcrSpace\Enums\OcrSpaceEngine;
use Illuminate\Support\Facades\Log;

class InvoiceOcrService
{
    public function extractDataFromImage(string $path): array
    {
        // 1. Pre-Flight Check: Ensure the file exists
        if (!file_exists($path)) {
            throw new \Exception("OCR Error: The image file could not be found.");
        }

        // 2. Pre-Flight Check: OCR Space has a strict 5MB limit for free accounts.
        // Filesize is in bytes. 1048576 bytes = 1 MB.
        $filesizeMB = filesize($path) / 1048576;
        if ($filesizeMB > 5) {
             throw new \Exception("OCR Error: File is too large (" . round($filesizeMB, 2) . "MB). OCR Space limit is 5MB. Please upload a smaller image.");
        }

        try {
            // 🔹 Engine 2 is best for the symbols ($) and numbers on your invoices
            $options = OcrSpaceOptions::make()
                ->OCREngine(OcrSpaceEngine::Engine2)
                ->isTable(true)
                ->scale(true);

            // This is where the package normally crashes if the API response is a string error.
            $result = OcrSpace::parseImageFile($path, $options);
            
            // Check if the API reported an internal error normally
            if (method_exists($result, 'getIsErroredOnProcessing') && $result->getIsErroredOnProcessing()) {
                throw new \Exception("OCR API Error: " . $result->getErrorMessage());
            }

            // Extracting text safely
            $parsedResults = $result->getParsedResults();
            
            if (!$parsedResults || $parsedResults->isEmpty()) {
                 throw new \Exception("OCR Error: No text could be read from this image.");
            }
            
            $text = $parsedResults->first()->getParsedText() ?? '';

            return $this->parseRoyalGrillzInvoice($text);

        } catch (\TypeError $e) {
            // THIS catches the "Cannot access offset of type string" error specifically.
            // It usually means your API key is invalid, missing, rate-limited, or the API returned a raw HTML/text error.
            Log::error("OCR Package TypeError: " . $e->getMessage());
            throw new \Exception("OCR Service Unavailable: The OCR server rejected the request. Check your API Key in your .env file (OCR_SPACE_API_KEY) and ensure your image isn't too large.");
        } catch (\Exception $e) {
            // Catch any other generic errors
            Log::error("OCR Extraction Failed: " . $e->getMessage());
            throw new \Exception("Failed to read invoice: " . $e->getMessage());
        }
    }

    private function parseRoyalGrillzInvoice(string $text): array
    {
        // Matches "Invoice no.: 5444"
        preg_match('/Invoice no.:\s*(\d+)/', $text, $invMatch);

        // Matches "$225.00"
        preg_match('/\$(\d+\.\d{2})/', $text, $priceMatch);

        // Finds the description line (e.g., BRACELET or ENGRAVE)
        $lines = explode("\n", $text);
        $description = collect($lines)->first(fn($line) => 
            str_contains(strtoupper($line), 'ENGRAVE') || 
            str_contains(strtoupper($line), 'BRACELET')
        );

        return [
            'supplier_code' => $invMatch[1] ?? null,
            'cost_price' => $priceMatch[1] ?? null,
            'custom_description' => trim($description ?? 'Scanned Item'),
        ];
    }
}