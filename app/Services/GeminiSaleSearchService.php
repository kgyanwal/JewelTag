<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class GeminiSaleSearchService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/';

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
    }

    public function parse(string $naturalQuery): array
    {
        $today     = Carbon::now()->format('Y-m-d');
        $thisMonth = Carbon::now()->format('Y-m');

        // Pull staff names and payment methods from DB to give Gemini real context
        $staffNames = DB::table('users')->pluck('name')->implode(', ');
        $paymentMethods = DB::table('site_settings')->where('key','payment_methods')->value('value') ?? '["CASH","VISA","MASTERCARD","LAYBUY"]';

        $prompt = <<<PROMPT
You are a smart search assistant for a jewelry POS system called JewelTag.
Today's date is {$today}.

Your job is to convert a natural language search query into a structured JSON filter object.

Available staff names: {$staffNames}
Available payment methods: {$paymentMethods}

Return ONLY a raw JSON object with any combination of these fields (omit fields that aren't mentioned):
{
  "keyword": "string — item description, invoice number, or customer name keyword",
  "invoice_number": "string — exact or partial invoice number",
  "staff_name": "string — must match one of the available staff names exactly",
  "first_name": "string — customer first name",
  "last_name": "string — customer last name",
  "phone": "string — phone number digits only",
  "payment_method": "string — must be one of the available payment methods, use LAYBUY for layaway/laybuy",
  "date_from": "YYYY-MM-DD",
  "date_to": "YYYY-MM-DD",
  "job_type": "string — one of: Resize, Solder / Weld, Bail Change, Shortening, Stone Setting, Engraving, Polishing / Rhodium",
  "min_amount": number,
  "max_amount": number,
  "has_balance": true
}

Date interpretation rules:
- "today" = {$today}
- "yesterday" = date minus 1 day
- "this week" = Monday to today
- "last week" = previous Monday to Sunday
- "this month" = {$thisMonth}-01 to {$today}
- "last month" = first to last day of previous month
- "last 30 days" = today minus 30 days to today
- "last 7 days" = today minus 7 days to today

Examples:
Query: "Anthony's sales last week over $1000"
Result: {"staff_name": "Anthony", "date_from": "...", "date_to": "...", "min_amount": 1000}

Query: "All laybuy customers who haven't paid in 30 days"
Result: {"payment_method": "LAYBUY", "date_from": "...", "date_to": "...", "has_balance": true}

Query: "Diamond ring sales this month by Sarah"
Result: {"keyword": "diamond ring", "staff_name": "Sarah", "date_from": "...", "date_to": "..."}

Query: "Cash sales over $500 last week"
Result: {"payment_method": "CASH", "min_amount": 500, "date_from": "...", "date_to": "..."}

Query: "Resize jobs by Javier"
Result: {"job_type": "Resize", "staff_name": "Javier"}

Query: "Unpaid balances this month"
Result: {"date_from": "...", "date_to": "...", "has_balance": true}

Now parse this query: "{$naturalQuery}"

Return ONLY raw JSON. No markdown. No explanation.
PROMPT;

        $model = 'gemini-2.5-pro';
        $url   = "{$this->baseUrl}{$model}:generateContent?key={$this->apiKey}";

        try {
            $response = Http::timeout(15)->post($url, [
                'contents' => [
                    ['parts' => [['text' => $prompt]]]
                ],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                    'temperature'      => 0.1,
                ],
            ]);

            if ($response->failed()) {
                Log::error('GeminiSaleSearch API Error: ' . $response->body());
                return ['error' => 'AI service unavailable. Please use manual filters.'];
            }

            $text = $response->json('candidates.0.content.parts.0.text');
            $json = preg_replace('/^```json\s*|```$/m', '', trim($text));
            $parsed = json_decode($json, true);

            if (!$parsed || !is_array($parsed)) {
                return ['error' => 'Could not understand that query. Try: "Anthony sales last week" or "unpaid laybuy this month"'];
            }

            return $parsed;

        } catch (\Exception $e) {
            Log::error('GeminiSaleSearch Exception: ' . $e->getMessage());
            return ['error' => 'AI search failed: ' . $e->getMessage()];
        }
    }
}