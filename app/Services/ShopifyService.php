<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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

        $storeUrl = str_replace(['https://', 'http://'], '', $storeUrl);
        $storeUrl = rtrim($storeUrl, '/');

        $this->configured = !empty($storeUrl) && !empty($accessToken);

        if ($this->configured) {
            $this->shopDomain = $storeUrl;
            $this->apiVersion = $apiVersion;
            $this->baseUrl    = "https://{$storeUrl}/admin/api/{$apiVersion}";
            $this->headers    = [
                'X-Shopify-Access-Token' => $accessToken,
                'Content-Type'           => 'application/json',
            ];
        }
    }

    public function isConfigured(): bool
    {
        return $this->configured;
    }

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

    public function updateInventory(string $inventoryItemId, int $qty): void
    {
        $this->guard();

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

    // ── Upload a video to Shopify via staged uploads + GraphQL ───────────────
    // Shopify REST API does NOT support videos — requires 3 GraphQL steps:
    //   1. stagedUploadsCreate  → get a temporary S3 URL from Shopify
    //   2. PUT video bytes      → upload directly to that S3 URL
    //   3. productAppendMedia   → tell Shopify to attach the staged file to the product
    public function attachVideoToProduct(string $shopifyProductId, string $localFilePath): void
    {
        $this->guard();

        if (!file_exists($localFilePath)) {
            throw new \Exception("Video file not found: {$localFilePath}");
        }

        $filename  = basename($localFilePath);
        $mimeType  = mime_content_type($localFilePath) ?: 'video/mp4';
        $fileSize  = filesize($localFilePath);
        $graphUrl  = "https://{$this->shopDomain}/admin/api/{$this->apiVersion}/graphql.json";

        // Step 1: Request staged upload URL
        $stagedResponse = Http::withHeaders($this->headers)
            ->timeout(30)
            ->post($graphUrl, [
                'query' => '
                    mutation stagedUploadsCreate($input: [StagedUploadInput!]!) {
                        stagedUploadsCreate(input: $input) {
                            stagedTargets {
                                url
                                resourceUrl
                                parameters { name value }
                            }
                            userErrors { field message }
                        }
                    }
                ',
                'variables' => [
                    'input' => [[
                        'filename'   => $filename,
                        'mimeType'   => $mimeType,
                        'fileSize'   => (string) $fileSize,
                        'httpMethod' => 'PUT',
                        'resource'   => 'VIDEO',
                    ]],
                ],
            ]);

        if (!$stagedResponse->successful()) {
            throw new \Exception('Shopify staged upload request failed: ' . $stagedResponse->body());
        }

        $stagedData = $stagedResponse->json();
        $userErrors = $stagedData['data']['stagedUploadsCreate']['userErrors'] ?? [];
        if (!empty($userErrors)) {
            throw new \Exception('Shopify staged upload error: ' . json_encode($userErrors));
        }

        $target      = $stagedData['data']['stagedUploadsCreate']['stagedTargets'][0] ?? null;
        $uploadUrl   = $target['url'] ?? null;
        $resourceUrl = $target['resourceUrl'] ?? null;
        $params      = collect($target['parameters'] ?? [])->pluck('value', 'name')->toArray();

        if (!$uploadUrl || !$resourceUrl) {
            throw new \Exception('Shopify did not return a staged upload URL.');
        }

        // Step 2: PUT video to staged S3 URL
        $multipart = [];
foreach ($params as $name => $value) {
    $multipart[] = [
        'name'     => $name,
        'contents' => $value,
    ];
}
$multipart[] = [
    'name'     => 'file',
    'contents' => fopen($localFilePath, 'r'),
    'filename' => $filename,
];

$putResponse = \Illuminate\Support\Facades\Http::asMultipart()
    ->timeout(120)
    ->post($uploadUrl, $multipart);

if ($putResponse->status() >= 400) {
    throw new \Exception('Video upload to Shopify S3 failed: HTTP ' . $putResponse->status() . ' — ' . $putResponse->body());
}

        if (!$putResponse->successful() && $putResponse->status() !== 200) {
            throw new \Exception('Video upload to Shopify S3 failed: HTTP ' . $putResponse->status());
        }

        // Step 3: Attach staged video to product
       $appendResponse = Http::withHeaders($this->headers)
    ->timeout(30)
    ->post($graphUrl, [
        'query' => '
            mutation productCreateMedia($productId: ID!, $media: [CreateMediaInput!]!) {
                productCreateMedia(productId: $productId, media: $media) {
                    media {
                        id
                        mediaContentType
                        status
                    }
                    mediaUserErrors { field message code }
                }
            }
        ',
        'variables' => [
            'productId' => 'gid://shopify/Product/' . $shopifyProductId,
            'media'     => [[
                'originalSource'   => $resourceUrl,
                'mediaContentType' => 'VIDEO',
            ]],
        ],
    ]);

if (!$appendResponse->successful()) {
    throw new \Exception('Shopify productCreateMedia failed: ' . $appendResponse->body());
}

$appendData   = $appendResponse->json();
$appendErrors = $appendData['data']['productCreateMedia']['mediaUserErrors'] ?? [];
if (!empty($appendErrors)) {
    throw new \Exception('Shopify media error: ' . json_encode($appendErrors));
}

\Illuminate\Support\Facades\Log::info('Shopify video attached', [
    'product_id' => $shopifyProductId,
    'media'      => $appendData['data']['productCreateMedia']['media'] ?? [],
]);

        $appendData   = $appendResponse->json();
        $appendErrors = $appendData['data']['productAppendMedia']['userErrors'] ?? [];
        if (!empty($appendErrors)) {
            throw new \Exception('Shopify media append error: ' . json_encode($appendErrors));
        }
    }

    // ── Attach all videos from a ProductItem — silently logs per-file failures
    public function attachVideosFromProductItem(string $shopifyProductId, array $videoPaths): void
    {
        foreach ($videoPaths as $videoPath) {
            if (!is_string($videoPath) || empty($videoPath)) continue;

            $fullPath = Storage::disk('public')->path($videoPath);
            if (!file_exists($fullPath)) {
                Log::warning("Shopify video not found on disk: {$videoPath}");
                continue;
            }

            try {
                $this->attachVideoToProduct($shopifyProductId, $fullPath);
                usleep(500000); // respect Shopify 2 req/sec GraphQL rate limit
            } catch (\Exception $e) {
                Log::warning("Shopify video attach failed for {$videoPath}: " . $e->getMessage());
            }
        }
    }

    public function deleteProduct(string $shopifyId): void
    {
        $this->guard();

        $response = Http::withHeaders($this->headers)
            ->timeout(10)
            ->delete("{$this->baseUrl}/products/{$shopifyId}.json");

        if (!$response->successful() && $response->status() !== 404) {
            throw new \Exception('Shopify delete failed (' . $response->status() . '): ' . $response->body());
        }
    }

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