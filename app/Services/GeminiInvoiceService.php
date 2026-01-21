<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class GeminiInvoiceService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/';

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
    }

    public function process(string $filePath)
    {
        if (!File::exists($filePath)) {
            Log::error("Gemini Scan: File does not exist at {$filePath}");
            return null;
        }

        $fileData = base64_encode(File::get($filePath));
        $mimeType = File::mimeType($filePath);

        // 🔹 UPDATED: Use Gemini 2.5 Pro or 3 Flash for 2026
        // If gemini-2.5-pro gives issues, try 'gemini-3-flash-preview'
        $model = 'gemini-2.5-pro'; 

        $prompt = "Act as an expert jewelry accountant. Analyze this invoice. Extract data into this JSON format:
        {
            \"vendor_name\": \"String\",
            \"invoice_number\": \"String\",
            \"items\": [
                {
                    \"description\": \"Full description\",
                    \"quantity\": 1,
                    \"cost_price\": 0.00,
                    \"style_code\": \"String\",
                    \"metal_type\": \"10KT, 14KT, etc\",
                    \"metal_weight\": 0.00,
                    \"stone_weight\": \"CT value\"
                }
            ]
        }
        Return ONLY raw JSON. No markdown backticks.";

        $url = "{$this->baseUrl}{$model}:generateContent?key={$this->apiKey}";

        $response = Http::post($url, [
            'contents' => [
                ['parts' => [
                    ['text' => $prompt],
                    ['inline_data' => [
                        'mimeType' => $mimeType, 
                        'data' => $fileData
                    ]]
                ]]
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'temperature' => 0.1,
            ]
        ]);

        if ($response->failed()) {
            Log::error("Gemini API Error ({$model}): " . $response->body());
            return null;
        }

        $text = $response->json('candidates.0.content.parts.0.text');
        
        // Clean markdown if AI ignores instructions
        $json = preg_replace('/^```json\s*|```$/m', '', $text);
        
        return json_decode($json, true);
    }
}