<?php

namespace App\Services;

use App\Models\User;
use Aws\Sns\SnsClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use OTPHP\TOTP;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class TwoFactorService
{
    // ── TOTP (Auth App) ──────────────────────────────────────────────────────

    public function generateTotpSecret(): string
    {
        return TOTP::create()->getSecret();
    }

    public function getTotpUri(User $user, string $secret): string
    {
        $totp = TOTP::createFromSecret($secret);
        $totp->setLabel($user->email ?? $user->name);
        $totp->setIssuer('JewelTag');
        return $totp->getProvisioningUri();
    }

    public function generateQrCodeSvg(string $uri): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);
        return $writer->writeString($uri);
    }

    /**
     * Verify a 6-digit TOTP code against a given secret.
     * Allows ±2 time windows (±60 seconds) for clock drift.
     * Always uses UTC (time() is timezone-independent).
     */
    public function verifyTotpSecret(string $secret, string $code): bool
    {
        $code = preg_replace('/\D/', '', $code);

        if ($secret === '' || strlen($code) !== 6) {
            return false;
        }

        $totp = TOTP::createFromSecret($secret);
        $now  = time();

        foreach ([-2, -1, 0, 1, 2] as $offset) {
            if (hash_equals($totp->at($now + ($offset * 30)), $code)) {
                return true;
            }
        }

        Log::warning('TOTP failed', ['server_utc' => gmdate('c')]);

        return false;
    }

    /**
     * Verify a TOTP code against the secret saved on the user.
     */
    public function verifyTotp(User $user, string $code): bool
    {
        $secret = (string) $user->two_factor_secret;

        if ($secret === '') {
            Log::warning("TOTP: No secret found for user {$user->id}");
            return false;
        }

        return $this->verifyTotpSecret($secret, $code);
    }

    // ── SMS OTP ──────────────────────────────────────────────────────────────

    public function sendSmsOtp(User $user): array
    {
        // Get phone from STORE, not user
        $store = \App\Models\Store::first();
        $phone = $store?->phone;

        if (empty($phone)) {
            return ['success' => false, 'error' => 'no_phone'];
        }

        // Rate limit per user
        $key      = 'otp_rate_' . $user->id;
        $attempts = Cache::get($key, 0);
        if ($attempts >= 5) {
            return ['success' => false, 'error' => 'rate_limited'];
        }
        Cache::put($key, $attempts + 1, now()->addMinutes(10));

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->update([
            'two_factor_code'       => Hash::make($code),
            'two_factor_expires_at' => now()->addMinutes(10),
        ]);

        $sent = $this->dispatchSns($phone, $code, $store);

        if (!$sent) {
            return ['success' => false, 'error' => 'sms_failed'];
        }

        Log::info("2FA SMS sent to store phone for user {$user->id} ({$user->name})");

        return ['success' => true, 'phone' => $this->maskPhone($phone)];
    }

    public function verifySmsOtp(User $user, string $code): bool
    {
        if (empty($user->two_factor_code) || empty($user->two_factor_expires_at)) {
            return false;
        }

        if (now()->utc()->isAfter($user->two_factor_expires_at)) {
            Log::warning("2FA SMS expired for user {$user->id}");
            return false;
        }

        if (!Hash::check($code, $user->two_factor_code)) {
            Log::warning("2FA SMS wrong code for user {$user->id} IP:" . request()->ip());
            return false;
        }

        $user->update([
            'two_factor_code'       => null,
            'two_factor_expires_at' => null,
        ]);

        Log::info("2FA SMS verified for user {$user->id} ({$user->name})");

        return true;
    }

    // ── BACKUP CODES ─────────────────────────────────────────────────────────

    /**
     * Generate 8 backup codes for the user.
     * Each code is 10 chars: XXXXX-XXXXX format.
     */
    public function generateBackupCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $raw     = strtoupper(bin2hex(random_bytes(5)));
            $codes[] = substr($raw, 0, 5) . '-' . substr($raw, 5, 5);
        }
        return $codes;
    }

    /**
     * Save hashed backup codes to user.
     * Returns the plain codes to show to user (only shown once).
     */
    public function saveBackupCodes(User $user): array
    {
        $codes  = $this->generateBackupCodes();
        $hashed = array_map(fn($c) => Hash::make($c), $codes);

        $user->update(['two_factor_backup_codes' => json_encode($hashed)]);

        Log::info("2FA backup codes generated for user {$user->id}");

        return $codes; // Plain codes — show once only
    }

    /**
     * Verify a store backup code (stored on the tenant record).
     * Codes are reusable until expiry; attempts are limited to 8 per day.
     */
    public function verifyBackupCode(User $user, string $code): bool
    {
        if (!function_exists('tenant') || !tenant()) {
            return false;
        }

        $tenantData = DB::connection('mysql')
            ->table('tenants')
            ->where('id', tenant()->id)
            ->first();

        if (!$tenantData) {
            return false;
        }

        // Check enabled
        if (!($tenantData->backup_codes_enabled ?? true)) {
            Log::warning("2FA: Backup codes disabled for tenant {$tenantData->id}");
            return false;
        }

        // Check expiry
        if (!empty($tenantData->backup_codes_expires_at) &&
            now()->isAfter($tenantData->backup_codes_expires_at)) {
            Log::warning("2FA: Backup codes expired for tenant {$tenantData->id}");
            return false;
        }

        // Daily attempt limit (8 per day)
        $attemptKey = 'backup_code_attempts_' . tenant()->id . '_' . $user->id . '_' . now()->format('Y-m-d');
        $attempts   = Cache::get($attemptKey, 0);

        if ($attempts >= 8) {
            Log::warning("2FA: Backup code daily limit reached for user {$user->id} tenant {$tenantData->id}");
            return false;
        }

        // Get stored codes
        $stored = json_decode($tenantData->two_factor_backup_codes ?? '[]', true);

        if (empty($stored)) {
            return false;
        }

        // Normalize input
        $normalized = strtoupper(str_replace(['-', ' '], '', trim($code)));

        foreach ($stored as $hashedCode) {
            if (Hash::check($normalized, $hashedCode) ||
                Hash::check($code, $hashedCode) ||
                Hash::check(trim($code), $hashedCode)) {

                // Matched — increment attempt counter but DON'T remove code
                Cache::put($attemptKey, $attempts + 1, now()->endOfDay());

                Log::warning("2FA: Backup code used by user {$user->id} ({$user->name}) for tenant {$tenantData->id} — attempt " . ($attempts + 1) . "/8 today");

                return true;
            }
        }

        // Wrong code — still increment to prevent brute force
        Cache::put($attemptKey, $attempts + 1, now()->endOfDay());

        Log::warning("2FA: Wrong backup code by user {$user->id} for tenant {$tenantData->id} — attempt " . ($attempts + 1) . "/8 today");

        return false;
    }

    /**
     * Count remaining backup codes for a user.
     */
    public function backupCodesRemaining(User $user): int
    {
        $stored = json_decode($user->two_factor_backup_codes ?? '[]', true);
        return is_array($stored) ? count($stored) : 0;
    }

    // ── Unified verify ───────────────────────────────────────────────────────

    /**
     * Verify code using whichever method the user has set up.
     */
    public function verify(User $user, string $code, bool $isBackupCode = false): bool
    {
        if ($isBackupCode) {
            return $this->verifyBackupCode($user, $code);
        }

       if ($user->two_factor_method === 'totp') {
    return $this->verifyTotp($user, $code) || $this->verifySmsOtp($user, $code);
}

        if ($user->two_factor_method === 'sms') {
            return $this->verifySmsOtp($user, $code);
        }

        return false;
    }

    // ── Trusted device ───────────────────────────────────────────────────────

    public function trustedToken(User $user): string
    {
        return hash('sha256', $user->id . $user->password . config('app.key'));
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    protected function dispatchSns(string $phone, string $code, ?\App\Models\Store $store = null): bool
    {
        try {
            $settings  = DB::table('site_settings')->pluck('value', 'key');
            $accessKey = $settings['aws_sms_access_key_id']     ?? config('services.sns.key');
            $secretKey = $settings['aws_sms_secret_access_key'] ?? config('services.sns.secret');
            $region    = $settings['aws_sms_default_region']    ?? 'us-east-2';

            // Use store name as sender ID (max 11 chars, alphanumeric)
            $storeName = $store?->name ?? 'JewelTag';
            $senderId  = substr(preg_replace('/[^A-Za-z0-9]/', '', $storeName), 0, 11) ?: 'JewelTag';

            $sns = new SnsClient([
                'version'     => 'latest',
                'region'      => $region,
                'credentials' => ['key' => $accessKey, 'secret' => $secretKey],
            ]);

            $sns->publish([
                'Message'     => "{$senderId} login code: {$code}\nExpires in 10 min. Do NOT share.",
                'PhoneNumber' => $this->formatPhone($phone),
                'MessageAttributes' => [
                    'AWS.SNS.SMS.SMSType' => [
                        'DataType'    => 'String',
                        'StringValue' => 'Transactional',
                    ],
                    'AWS.SNS.SMS.SenderID' => [
                        'DataType'    => 'String',
                        'StringValue' => $senderId,
                    ],
                ],
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('2FA SNS Error: ' . $e->getMessage());
            return false;
        }
    }

    protected function formatPhone(string $phone): string
    {
        $d = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($d) === 10) return '+1' . $d;
        if (strlen($d) === 11 && $d[0] === '1') return '+' . $d;
        return '+' . $d;
    }

    public function maskPhone(string $phone): string
    {
        $d = preg_replace('/[^0-9]/', '', $phone);
        return '(***) ***-' . substr($d, -4);
    }
}