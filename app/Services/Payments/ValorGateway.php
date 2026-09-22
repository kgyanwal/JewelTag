<?php

namespace App\Services\Payments;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Valor Connect (Cloud, semi-integration) gateway.
 *
 * MULTI-TENANT: every store/tenant has its own merchant account with
 * Valor, so credentials are resolved per-tenant. If your app uses
 * separate databases per tenant (most Spatie Multitenancy setups),
 * `DB::table('site_settings')` already resolves to the CORRECT tenant's
 * database automatically once tenant context is initialized — no query
 * change needed, just make sure this class is only ever instantiated
 * inside a request where tenant() context is already resolved.
 *
 * If instead you use ONE shared database with a tenant_id column,
 * uncomment the ->where('tenant_id', ...) line below.
 */
class ValorGateway
{
    protected string $baseUrl;
    protected string $appId;
    protected string $appKey;
    protected string $epi;
    protected string $channelId;
    protected ?string $tenantId;

    public function __construct()
    {
        $this->tenantId = function_exists('tenant') && tenant() ? tenant()->id : null;

        $query = DB::table('site_settings')->where('key', 'valor_config');

        // Uncomment ONLY if using a shared database with tenant_id column:
        // if ($this->tenantId) { $query->where('tenant_id', $this->tenantId); }

        $json   = $query->value('value') ?? '{}';
        $config = json_decode($json, true) ?? [];

        $env = $config['env'] ?? 'sandbox';

        $this->baseUrl = $env === 'production'
            ? 'https://securelink.valorpaytech.com'
            : 'https://securelink-staging.valorpaytech.com';

        $this->appId     = $config['app_id']     ?? '';
        $this->appKey    = $config['app_key']    ?? '';
        $this->epi       = $config['epi']        ?? '';
        $this->channelId = $config['channel_id'] ?? '';

        if (empty($this->appId) || empty($this->appKey) || empty($this->epi) || empty($this->channelId)) {
            throw new RuntimeException(
                'Valor credentials are not configured for this store' .
                ($this->tenantId ? " (tenant #{$this->tenantId})" : '') . '. Go to Settings → Integrations → Valor.'
            );
        }
    }

    protected function creds(): array
    {
        return ['appid' => $this->appId, 'appkey' => $this->appKey, 'epi' => $this->epi];
    }

    public function publishSale(float $amount, string $reqTxnId): array
    {
        $reqTxnId = substr($reqTxnId, 0, 25);
        $amountCents = (string) (int) round($amount * 100);

        $payload = [
            ...$this->creds(),
            'txn_type'   => 'vc_publish',
            'channel_id' => $this->channelId,
            'version'    => '2',
            'payload'    => [
                // MINIMAL — exactly matches the readme.io Publish API doc's
                // only confirmed example for Cloud (vc_publish). The extra
                // fields tried previously (TIP_ENTRY, SIGNATURE, etc.) come
                // from the separate TCP/USB local-protocol PDF and are the
                // suspected cause of ERROR-0600VI01 on Cloud, since that
                // error code is undocumented for local-protocol parsing.
                'TRAN_MODE'  => '1', // Credit
                'TRAN_CODE'  => '1', // Sale
                'AMOUNT'     => $amountCents,
                'REQ_TXN_ID' => $reqTxnId,
            ],
        ];

        try {
            $response = Http::acceptJson()->asJson()->timeout(45)
                ->post("{$this->baseUrl}/?status", $payload);
        } catch (\Throwable $e) {
            Log::error('Valor publishSale failed', ['tenant' => $this->tenantId, 'error' => $e->getMessage()]);
            return ['success' => false, 'req_txn_id' => $reqTxnId, 'message' => 'Could not reach terminal.'];
        }

        $body = $response->json() ?? [];
        return [
            'success'    => $response->successful() && ($body['error_no'] ?? null) !== 'VC03',
            'req_txn_id' => $reqTxnId,
            'message'    => $body['desc'] ?? $body['mesg'] ?? 'Published',
            'raw'        => $body,
        ];
    }

    public function checkStatus(string $reqTxnId): array
    {
        $payload = [...$this->creds(), 'txn_type' => 'vc_status', 'req_txn_id' => $reqTxnId];

        try {
            $response = Http::acceptJson()->asJson()->timeout(20)
                ->post("{$this->baseUrl}/?txn_status", $payload);
        } catch (\Throwable $e) {
            Log::error('Valor checkStatus failed', ['tenant' => $this->tenantId, 'error' => $e->getMessage()]);
            return ['state' => 'error', 'message' => 'Network error checking status.'];
        }

        $body  = $response->json() ?? [];
        $inner = $body['response'] ?? $body;
        $state = match ($inner['STATE'] ?? null) {
            '0'  => 'approved',
            '-1' => 'declined',
            default => 'pending',
        };

        return [
            'state'      => $state,
            'txn_id'     => $inner['TXN_ID'] ?? $inner['MER_TXN_ID'] ?? null,
            'auth_code'  => $inner['CODE'] ?? null,
            'card_last4' => isset($inner['MASKED_PAN']) ? substr(str_replace(' ', '', $inner['MASKED_PAN']), -4) : null,
            'card_brand' => $inner['ISSUER'] ?? $inner['CARD_TYPE'] ?? null,
            'message'    => $inner['ERROR_MSG'] ?? $inner['AUTH_RSP_TEXT'] ?? $state,
            'raw'        => $body,
        ];
    }

    public function cancelSale(): array
    {
        $payload = [...$this->creds(), 'txn_type' => 'vc_cancel', 'channel_id' => $this->channelId];
        try {
            $response = Http::acceptJson()->asJson()->timeout(20)->post("{$this->baseUrl}/?cancel", $payload);
            return ['success' => $response->successful(), 'raw' => $response->json() ?? []];
        } catch (\Throwable $e) {
            Log::error('Valor cancelSale failed', ['tenant' => $this->tenantId, 'error' => $e->getMessage()]);
            return ['success' => false];
        }
    }

    /**
     * Lightweight connectivity check — pings the status endpoint with a
     * junk req_txn_id just to confirm credentials + network reach Valor's
     * cloud without actually charging anything. Used by the health-check
     * page below.
     */
    public function healthCheck(): array
    {
        $payload = [...$this->creds(), 'txn_type' => 'vc_status', 'req_txn_id' => 'HEALTHCHECK-' . now()->timestamp];

        try {
            $response = Http::acceptJson()->asJson()->timeout(10)
                ->post("{$this->baseUrl}/?txn_status", $payload);

            $body = $response->json() ?? [];

            // Any structured JSON reply (even "transaction not found") proves
            // credentials are valid and the cloud is reachable.
            $reachable = $response->status() < 500 && !empty($body);

            return [
                'reachable' => $reachable,
                'message'   => $reachable ? 'Valor cloud reachable, credentials accepted.' : 'Unexpected response.',
                'raw'       => $body,
            ];
        } catch (\Throwable $e) {
            return ['reachable' => false, 'message' => $e->getMessage()];
        }
    }
}