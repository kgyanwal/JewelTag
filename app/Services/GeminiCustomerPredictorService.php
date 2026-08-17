<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiCustomerPredictorService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/';

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
    }

    public function predict(array $customerData): ?array
    {
        $model = 'gemini-2.5-pro';
        $url   = "{$this->baseUrl}{$model}:generateContent?key={$this->apiKey}";

        $prompt = "You are an expert jewelry retail analyst. Based on this customer's purchase history, predict their future spending and behaviour.

Customer Data:
- Name: {$customerData['name']}
- Total purchases: {$customerData['total_purchases']}
- Total spent (lifetime): \${$customerData['total_spent']}
- Average sale value: \${$customerData['avg_sale']}
- First purchase: {$customerData['first_purchase']}
- Last purchase: {$customerData['last_purchase']}
- Days since last purchase: {$customerData['days_since_last']}
- Purchase frequency: {$customerData['purchase_frequency_days']} days average between purchases
- Months as customer: {$customerData['months_as_customer']}
- Most bought category: {$customerData['top_category']}
- Payment preference: {$customerData['payment_method']}
- Has layaway history: {$customerData['has_laybuy']}
- Month they buy most: {$customerData['peak_month']}

Return ONLY a raw JSON object. No markdown. No explanation. Like this:
{
  \"predicted_spend_12mo\": 4200,
  \"confidence\": \"high\",
  \"risk_level\": \"low\",
  \"churn_risk\": false,
  \"sentence\": \"Sarah will likely spend \$4,200 more in the next 12 months.\",
  \"risk_sentence\": \"\",
  \"insight_sentence\": \"She buys every 3-4 months, mostly in December and May.\",
  \"action_sentence\": \"Send her a personal message in late November before her peak buying period.\"
}

Rules for the sentence field:
- Use the customer's first name only
- State a specific dollar amount (round to nearest \$100)
- Say 'likely spend \$X more in the next 12 months'
- Be direct. One sentence only.

Rules for risk_sentence:
- Only fill if churn_risk is true
- Example: 'Sarah hasn't visited in 8 months — she usually comes every 3.'
- Leave empty string if no risk.

Rules for insight_sentence:
- One specific behavioural insight based on the data
- Example: 'She buys every 3-4 months, mostly in December and May.'

Rules for action_sentence:
- One concrete action staff should take
- Example: 'Send her a personal message in late November before her peak buying period.'

Return ONLY raw JSON. No markdown backticks.";

        try {
            $response = Http::timeout(20)->post($url, [
                'contents' => [
                    ['parts' => [['text' => $prompt]]]
                ],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                    'temperature'      => 0.2,
                ],
            ]);

            if ($response->failed()) {
                Log::error("GeminiCustomerPredictor API Error: " . $response->body());
                return null;
            }

            $text = $response->json('candidates.0.content.parts.0.text');
            $json = preg_replace('/^```json\s*|```$/m', '', trim($text));
            $data = json_decode($json, true);

            if (!$data || !isset($data['sentence'])) {
                Log::error("GeminiCustomerPredictor: Invalid response — " . $text);
                return null;
            }

            return $data;

        } catch (\Exception $e) {
            Log::error("GeminiCustomerPredictor Exception: " . $e->getMessage());
            return null;
        }
    }
}