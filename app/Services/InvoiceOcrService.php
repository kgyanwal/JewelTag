<?php

namespace App\Services;

use Codesmiths\LaravelOcrSpace\Facades\OcrSpace;
use Codesmiths\LaravelOcrSpace\OcrSpaceOptions;
use Codesmiths\LaravelOcrSpace\Enums\OcrSpaceEngine;

class InvoiceOcrService
{
    public function extractDataFromImage(string $path): array
    {
        // 🔹 Engine 2 is best for the symbols ($) and numbers on your invoices
        $options = OcrSpaceOptions::make()
            ->OCREngine(OcrSpaceEngine::Engine2)
            ->isTable(true)
            ->scale(true);

        $result = OcrSpace::parseImageFile($path, $options);
        
        // Error handling
        if ($result->getIsErroredOnProcessing()) {
            throw new \Exception("OCR Error: " . $result->getErrorMessage());
        }

        // Extracting text from the first page result
        $parsedResults = $result->getParsedResults();
        $text = $parsedResults->first()->getParsedText() ?? '';

        return $this->parseRoyalGrillzInvoice($text);
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