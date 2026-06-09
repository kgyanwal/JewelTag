<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class ShopifyService
{
    protected string $baseUrl    = '';
    protected string $shopDomain = '';
    protected array  $headers    = [];
    protected bool   $configured = false;
    protected string $apiVersion = '2024-01';

    public function __construct()
    {
        $raw    = DB::table('site_settings')->where('key', 'shopify_config')->value('value');
        $config = $raw ? json_decode($raw, true) : [];

        $storeUrl    = trim($config['store_url']    ?? '');
        $accessToken = trim($config['access_token'] ?? '');
        $apiVersion  = trim($config['api_version']  ?? '2024-01');

        // ── Sanitize URL ────────────────────────────────────────────
        // Strip https://, http://, trailing slashes so it's always
        // just: your-store.myshopify.com
        $storeUrl = str_replace(['https://', 'http://'], '', $storeUrl);
        $storeUrl = rtrim($storeUrl, '/');

        $this->configured = !empty($storeUrl) && !empty($accessToken);

        if ($this->configured) {
            $this->shopDomain = $storeUrl;
            $this->apiVersion = $apiVersion;

            // Versioned base URL for products, inventory etc
            $this->baseUrl = "https://{$storeUrl}/admin/api/{$apiVersion}";

            $this->headers = [
                'X-Shopify-Access-Token' => $accessToken,
                'Content-Type'           => 'application/json',
            ];
        }
    }

    // ── Is Shopify configured for this tenant? ────────────────────
    public function isConfigured(): bool
    {
        return $this->configured;
    }

    // ── Test the connection ───────────────────────────────────────
    // shop.json is at /admin/api/{version}/shop.json
    public function testConnection(): array
    {
        $this->guard();

        $response = Http::withHeaders($this->headers)
            ->timeout(10)
            ->get("{$this->baseUrl}/shop.json");

        if ($response->status() === 401) {
            throw new \Exception('401 Unauthorized — Token is invalid or expired. Go to Shopify Admin → Apps → your app → reinstall and copy the new token.');
        }

        if ($response->status() === 403) {
            throw new \Exception('403 Forbidden — Token lacks required scopes. Enable write_products and write_inventory in your Shopify app, then reinstall.');
        }

        if (!$response->successful()) {
            throw new \Exception('Connection failed — HTTP ' . $response->status() . ': ' . $response->body());
        }

        return $response->json('shop') ?? [];
    }

    // ── Create a new product on Shopify ──────────────────────────
    public function createProduct(array $payload): array
    {
        $this->guard();

        $response = Http::withHeaders($this->headers)
            ->timeout(15)
            ->post("{$this->baseUrl}/products.json", ['product' => $payload]);

        if ($response->status() === 401) {
            throw new \Exception('401 Unauthorized — check your Shopify access token.');
        }

        if (!$response->successful()) {
            throw new \Exception('Shopify create failed (' . $response->status() . '): ' . $response->body());
        }

        return $response->json('product') ?? [];
    }

    // ── Update an existing product ───────────────────────────────
    public function updateProduct(string $shopifyId, array $payload): array
    {
        $this->guard();

        $response = Http::withHeaders($this->headers)
            ->timeout(15)
            ->put("{$this->baseUrl}/products/{$shopifyId}.json", ['product' => $payload]);

        if ($response->status() === 401) {
            throw new \Exception('401 Unauthorized — check your Shopify access token.');
        }

        if ($response->status() === 404) {
            throw new \Exception('404 — Product not found on Shopify. It may have been deleted. Remove the shopify_product_id from this item and push again.');
        }

        if (!$response->successful()) {
            throw new \Exception('Shopify update failed (' . $response->status() . '): ' . $response->body());
        }

        return $response->json('product') ?? [];
    }

    // ── Set inventory quantity ────────────────────────────────────
    public function updateInventory(string $inventoryItemId, int $qty): void
    {
        $this->guard();

        // Cache location ID for 24 hours to avoid hitting rate limits
        $locationId = cache()->remember(
            'shopify_location_id_' . md5($this->shopDomain),
            86400,
            function () {
                $response = Http::withHeaders($this->headers)
                    ->timeout(10)
                    ->get("{$this->baseUrl}/locations.json");

                if (!$response->successful()) {
                    throw new \Exception('Could not fetch Shopify locations: ' . $response->body());
                }

                $locations = $response->json('locations') ?? [];
                if (empty($locations)) {
                    throw new \Exception('No locations found in your Shopify store.');
                }

                return $locations[0]['id'];
            }
        );

        if (!$locationId) {
            throw new \Exception('Shopify Sync Failed: Could not determine store location ID.');
        }

        $response = Http::withHeaders($this->headers)
            ->timeout(10)
            ->post("{$this->baseUrl}/inventory_levels/set.json", [
                'location_id'       => $locationId,
                'inventory_item_id' => $inventoryItemId,
                'available'         => $qty,
            ]);

        if (!$response->successful()) {
            throw new \Exception('Inventory update failed (' . $response->status() . '): ' . $response->body());
        }
    }

    // ── Delete a product from Shopify ─────────────────────────────
    public function deleteProduct(string $shopifyId): void
    {
        $this->guard();

        $response = Http::withHeaders($this->headers)
            ->timeout(10)
            ->delete("{$this->baseUrl}/products/{$shopifyId}.json");

        // 404 is fine — product already gone
        if (!$response->successful() && $response->status() !== 404) {
            throw new \Exception('Shopify delete failed (' . $response->status() . '): ' . $response->body());
        }
    }

    // ── Get all products (for sync checks) ───────────────────────
    public function getProducts(int $limit = 50): array
    {
        $this->guard();

        $response = Http::withHeaders($this->headers)
            ->timeout(15)
            ->get("{$this->baseUrl}/products.json", ['limit' => $limit]);

        if (!$response->successful()) {
            throw new \Exception('Could not fetch products: ' . $response->body());
        }

        return $response->json('products') ?? [];
    }

    // ── Guard: throw if not configured ───────────────────────────
    private function guard(): void
    {
        if (!$this->configured) {
            throw new \Exception(
                'Shopify is not configured for this store. ' .
                'Go to Admin → Utilities & Configuration → ☁️ Integrations → Shopify Integration.'
            );
        }
    }
}