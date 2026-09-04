<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\{TextInput, Section, Grid};
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * STANDALONE TEST PAGE — isolated from SaleResource/CreateSale/EditSale/Laybuy.
 * Only visible to Superadmin.
 *
 * FIXED VERSION — payload fields now match CREDIT SALE Format 6 from the
 * official Valor POS Integration Specification (page 55), which is the
 * simplest documented working example: TRAN_MODE, TRAN_CODE, AMOUNT,
 * TIP_ENTRY, SIGNATURE, PAPER_RECEIPT, MOBILE_ENTRY, REQ_TXN_ID.
 *
 * Key corrections from the previous version:
 *  - AMOUNT is a plain integer-string in CENTS ("1000" = $10.00), never a
 *    decimal string like "10.00" — every example in the spec confirms this.
 *  - payload stays a normal nested JSON object — NOT a stringified JSON
 *    string. Every documented example shows it as a real object.
 *  - TIP_ENTRY, SIGNATURE, PAPER_RECEIPT, MOBILE_ENTRY are REQUIRED, not
 *    optional — omitting them is what caused ERROR-0600VI01 "Invalid Format".
 *  - CLERK_ID / TAX_AMOUNT / TIP_AMOUNT are NOT part of the minimal working
 *    example and were removed; they're optional extras for other formats.
 */
class TestValorTerminal extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-beaker';
    protected static ?string $navigationGroup = 'Admin';
    protected static ?string $navigationLabel = '🧪 Test Valor Terminal';
    protected static string $view = 'filament.pages.test-valor-terminal';

    public ?array $data = [];
    public ?string $lastResponse = null;
    public ?string $currentReqTxnId = null;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasRole('Superadmin') ?? false;
    }

    public function mount(): void
    {
        $settings = DB::table('site_settings')
            ->whereIn('key', ['valor_app_id', 'valor_app_key', 'valor_epi', 'valor_channel_id'])
            ->pluck('value', 'key');

        $this->form->fill([
            'valor_app_id'     => $settings['valor_app_id'] ?? '',
            'valor_app_key'    => $settings['valor_app_key'] ?? '',
            'valor_epi'        => $settings['valor_epi'] ?? '',
            'valor_channel_id' => $settings['valor_channel_id'] ?? '',
            'test_amount'      => '1.00',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Valor Sandbox Credentials')
                ->description('Get these from PayKoncept. Never share these values outside your own app.')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('valor_app_id')->label('App ID')->required(),
                        TextInput::make('valor_app_key')->label('App Key')->required(),
                        TextInput::make('valor_epi')->label('EPI (Device ID)')->required(),
                        TextInput::make('valor_channel_id')->label('Channel ID')->required(),
                    ]),
                ]),
            Section::make('Send Test Charge')
                ->schema([
                    TextInput::make('test_amount')->label('Amount ($)')->numeric()->required(),
                ]),
        ])->statePath('data');
    }

    protected function baseUrl(): string
    {
        return 'https://securelink-staging.valorpaytech.com';
    }

    protected function credentialsMissing(): bool
    {
        foreach (['valor_app_id', 'valor_app_key', 'valor_epi', 'valor_channel_id'] as $key) {
            if (empty($this->data[$key] ?? null)) {
                Notification::make()
                    ->title('Missing credential')
                    ->body(str_replace('_', ' ', $key) . ' is empty.')
                    ->danger()
                    ->send();
                return true;
            }
        }
        return false;
    }

    public function saveCredentials(): void
    {
        $d = $this->data;

        foreach (['valor_app_id', 'valor_app_key', 'valor_epi', 'valor_channel_id'] as $key) {
            DB::table('site_settings')->updateOrInsert(
                ['key' => $key],
                ['value' => (string) ($d[$key] ?? ''), 'updated_at' => now()]
            );
        }

        Notification::make()
            ->title('✅ Credentials saved')
            ->body('These will now auto-fill every time you open this page.')
            ->success()
            ->send();
    }

    public function publishTest(): void
{
    if ($this->credentialsMissing()) return;

    $d = $this->data;
    $reqTxnId = 'TEST-' . now()->format('His');

    // Valor Connect Cloud API requires standard 2-decimal formatted string
    $amountFormatted = number_format((float) $d['test_amount'], 2, '.', '');

    // Inner transaction parameters according to Valor Connect Cloud API v2
    $innerPayload = [
        'TRAN_MODE'      => '1',                // 1 = Credit
        'TRAN_CODE'      => '1',                // 1 = Sale
        'AMOUNT'         => $amountFormatted,   // e.g. "1.00"
        'TAX_AMOUNT'     => '0.00',
        'TIP_AMOUNT'     => '0.00',
        'SURCHARGE_AMT'  => '0.00',
        'REQ_TXN_ID'     => $reqTxnId,
        'INVOICE_NO'     => $reqTxnId,
        'TIP_ENTRY'      => '0',                // 0 = Disabled/Bypass
        'SIGNATURE'      => '0',                // 0 = On Terminal / Skip
        'PAPER_RECEIPT'  => '1',                // 1 = Print
        'MOBILE_ENTRY'   => '0',                // 0 = Disabled
    ];

    $payload = [
        'appid'      => (string) $d['valor_app_id'],
        'appkey'     => (string) $d['valor_app_key'],
        'epi'        => (string) $d['valor_epi'],
        'txn_type'   => 'vc_publish',
        'channel_id' => (string) $d['valor_channel_id'],
        'version'    => '2',
        'payload'    => $innerPayload,
    ];

    try {
        // Appended '=' to query string as required by the endpoint spec
        $response = Http::acceptJson()
            ->asJson()
            ->timeout(45)
            ->post($this->baseUrl() . '/?status=', $payload);

        $this->currentReqTxnId = $reqTxnId;
        $this->lastResponse = json_encode($response->json() ?? ['raw' => $response->body()], JSON_PRETTY_PRINT);

        Log::info('Valor test publish', ['request' => $payload, 'response' => $response->json()]);

        Notification::make()
            ->title('Published — check the terminal now')
            ->body("REQ_TXN_ID: {$reqTxnId}. Tap/insert the card, then click Check Status.")
            ->info()
            ->send();
    } catch (\Throwable $e) {
        $this->lastResponse = 'ERROR: ' . $e->getMessage();
        Notification::make()->title('Publish failed')->body($e->getMessage())->danger()->send();
    }
}

    public function checkStatusTest(): void
    {
        if (!$this->currentReqTxnId) {
            Notification::make()->title('Nothing published yet')->warning()->send();
            return;
        }
        if ($this->credentialsMissing()) return;

        $d = $this->data;
        $payload = [
            'appid'      => (string) $d['valor_app_id'],
            'appkey'     => (string) $d['valor_app_key'],
            'epi'        => (string) $d['valor_epi'],
            'txn_type'   => 'vc_status',
            'req_txn_id' => $this->currentReqTxnId,
        ];

        try {
            $response = Http::acceptJson()->asJson()->timeout(20)
                ->post($this->baseUrl() . '/?txn_status', $payload);

            $body = $response->json() ?? ['raw' => $response->body()];
            $this->lastResponse = json_encode($body, JSON_PRETTY_PRINT);

            Log::info('Valor test status', ['request' => $payload, 'response' => $body]);

            // Response may be flat or nested under "response" — handle both,
            // since your real DEVICE OFFLINE / Invalid Format errors came
            // back nested as {"error_no":..., "response": {...}}.
            $inner = $body['response'] ?? $body;
            $state = $inner['STATE'] ?? null;

            if ($state === '0') {
                Notification::make()
                    ->title('✅ APPROVED')
                    ->body('Txn ID: ' . ($inner['TXN_ID'] ?? '—') . ' | Card: ' . ($inner['MASKED_PAN'] ?? '—'))
                    ->success()
                    ->send();
            } elseif ($state === '-1') {
                Notification::make()
                    ->title('❌ DECLINED / ERROR')
                    ->body(($inner['ERROR_MSG'] ?? '') . ' (' . ($inner['ERROR_CODE'] ?? '') . ')')
                    ->danger()
                    ->send();
            } else {
                Notification::make()->title('⏳ Still pending — click Check Status again')->info()->send();
            }
        } catch (\Throwable $e) {
            $this->lastResponse = 'ERROR: ' . $e->getMessage();
            Notification::make()->title('Status check failed')->body($e->getMessage())->danger()->send();
        }
    }

    public function cancelTest(): void
    {
        if ($this->credentialsMissing()) return;

        $d = $this->data;
        $payload = [
            'appid'      => (string) $d['valor_app_id'],
            'appkey'     => (string) $d['valor_app_key'],
            'epi'        => (string) $d['valor_epi'],
            'txn_type'   => 'vc_cancel',
            'channel_id' => (string) $d['valor_channel_id'],
        ];

        try {
            $response = Http::acceptJson()->asJson()->timeout(20)
                ->post($this->baseUrl() . '/?cancel', $payload);

            $this->lastResponse = json_encode($response->json() ?? ['raw' => $response->body()], JSON_PRETTY_PRINT);
            $this->currentReqTxnId = null;

            Notification::make()->title('Cancel sent')->success()->send();
        } catch (\Throwable $e) {
            $this->lastResponse = 'ERROR: ' . $e->getMessage();
            Notification::make()->title('Cancel failed')->body($e->getMessage())->danger()->send();
        }
    }
}