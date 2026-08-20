<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\{TextInput, Section, Grid, Placeholder};
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;

/**
 * STANDALONE TEST PAGE — completely isolated from SaleResource, CreateSale,
 * EditSale, and Laybuy. Nothing here touches your live sales flow. Safe to
 * click around in freely. Only visible to Superadmin.
 *
 * Purpose: prove the Publish -> Status -> Cancel flow actually works with
 * your real sandbox credentials and dummy device before wiring it into
 * the real checkout screen.
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
        $this->form->fill([
            'test_amount' => '1.00',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Valor Sandbox Credentials')
                ->description('Confirm these with PayKoncept before testing. Stored only for this test session, not saved to settings yet.')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('valor_app_id')->label('App ID')
                            ->default(fn() => DB::table('site_settings')->where('key', 'valor_app_id')->value('value')),
                        TextInput::make('valor_app_key')->label('App Key')
                            ->default(fn() => DB::table('site_settings')->where('key', 'valor_app_key')->value('value')),
                        TextInput::make('valor_epi')->label('EPI (Device ID)')
                            ->default(fn() => DB::table('site_settings')->where('key', 'valor_epi')->value('value')),
                        TextInput::make('valor_channel_id')->label('Channel ID')
                            ->default(fn() => DB::table('site_settings')->where('key', 'valor_channel_id')->value('value')),
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

    public function publishTest(): void
    {
        $d = $this->data;
        $reqTxnId = 'TEST-' . now()->format('His');
        $amountCents = (int) round(((float) $d['test_amount']) * 100);

        $payload = [
            'appid'      => $d['valor_app_id'],
            'appkey'     => $d['valor_app_key'],
            'epi'        => $d['valor_epi'],
            'txn_type'   => 'vc_publish',
            'channel_id' => $d['valor_channel_id'],
            'version'    => '2',
            'payload'    => [
                'TRAN_MODE'  => '1',
                'TRAN_CODE'  => '1',
                'AMOUNT'     => (string) $amountCents,
                'REQ_TXN_ID' => $reqTxnId,
            ],
        ];

        try {
            $response = Http::acceptJson()->asJson()->timeout(15)
                ->post($this->baseUrl() . '/?status', $payload);

            $this->currentReqTxnId = $reqTxnId;
            $this->lastResponse = json_encode($response->json() ?? ['raw' => $response->body()], JSON_PRETTY_PRINT);

            Log::info('Valor test publish', ['request' => $payload, 'response' => $response->json()]);

            Notification::make()
                ->title('Published — check the terminal now')
                ->body("REQ_TXN_ID: {$reqTxnId}. Ask the customer/tester to tap the test card on the terminal, then click 'Check Status' below.")
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

        $d = $this->data;
        $payload = [
            'appid'      => $d['valor_app_id'],
            'appkey'     => $d['valor_app_key'],
            'epi'        => $d['valor_epi'],
            'txn_type'   => 'vc_status',
            'req_txn_id' => $this->currentReqTxnId,
        ];

        try {
            $response = Http::acceptJson()->asJson()->timeout(15)
                ->post($this->baseUrl() . '/?txn_status', $payload);

            $body = $response->json() ?? ['raw' => $response->body()];
            $this->lastResponse = json_encode($body, JSON_PRETTY_PRINT);

            Log::info('Valor test status', ['request' => $payload, 'response' => $body]);

            $state = $body['STATE'] ?? $body['state'] ?? null;
            if ($state === '0') {
                Notification::make()->title('✅ APPROVED')->body('Txn ID: ' . ($body['TXN_ID'] ?? '—'))->success()->send();
            } elseif ($state === '-1') {
                Notification::make()->title('❌ DECLINED')->body($body['ERROR_MSG'] ?? $body['AUTH_RSP_TEXT'] ?? '')->danger()->send();
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
        $d = $this->data;
        $payload = [
            'appid'      => $d['valor_app_id'],
            'appkey'     => $d['valor_app_key'],
            'epi'        => $d['valor_epi'],
            'txn_type'   => 'vc_cancel',
            'channel_id' => $d['valor_channel_id'],
        ];

        try {
            $response = Http::acceptJson()->asJson()->timeout(15)
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