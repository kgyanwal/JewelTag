<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;

class TwoFactorController extends Controller
{
    public function __construct(
        protected TwoFactorService $twoFactor
    ) {}

    // ── Get logged-in auth user ──────────────────────────────────────────────
    protected function staffUser(): ?User
    {
        return auth()->user();
    }

    // ── CHALLENGE PAGE ───────────────────────────────────────────────────────

    public function showChallenge()
    {
        $user = $this->staffUser();

        if (!$user) {
            return redirect()->route('filament.admin.auth.login');
        }

        $method      = $user->two_factor_method;
        $store       = \App\Models\Store::first();
        $storePhone  = $store?->phone;
        $maskedPhone = ($method === 'sms' && $storePhone)
            ? $this->twoFactor->maskPhone($storePhone)
            : null;

        $backupCodesEnabled   = false;
        $backupCodesRemaining = 0;
        $backupCodesExpired   = false;

        if (function_exists('tenant') && tenant()) {
            $tenantData = \Illuminate\Support\Facades\DB::connection('mysql')
                ->table('tenants')
                ->where('id', tenant()->id)
                ->first();

            $backupCodesEnabled = (bool) ($tenantData->backup_codes_enabled ?? false);

            if ($backupCodesEnabled) {
                if (!empty($tenantData->backup_codes_expires_at) &&
                    now()->isAfter($tenantData->backup_codes_expires_at)) {
                    $backupCodesExpired   = true;
                    $backupCodesEnabled   = false;
                    $backupCodesRemaining = 0;
                } else {
                    $stored = json_decode($tenantData->two_factor_backup_codes ?? '[]', true);
                    if (!empty($stored)) {
                        // Show daily attempts remaining (8 per day)
                        $attemptKey           = 'backup_code_attempts_' . tenant()->id . '_' . $user->id . '_' . now()->format('Y-m-d');
                        $usedToday            = Cache::get($attemptKey, 0);
                        $backupCodesRemaining = max(0, 8 - $usedToday);
                    }
                }
            }
        }

        return view('auth.two-factor-challenge', compact(
            'method',
            'maskedPhone',
            'backupCodesEnabled',
            'backupCodesRemaining',
            'backupCodesExpired'
        ));
    }

    public function verifyChallenge(Request $request)
    {
        $request->validate([
            'code'           => 'required|string|min:1|max:20',
            'trust_device'   => 'nullable|boolean',
            'trust_hours'    => 'nullable|integer|min:1|max:720',
            'is_backup_code' => 'nullable|boolean',
        ]);

        $user         = $this->staffUser();
        $isBackupCode = $request->boolean('is_backup_code');

        if (!$user) {
            return redirect()->route('filament.admin.auth.login');
        }

        if (!$this->twoFactor->verify($user, $request->code, $isBackupCode)) {
            $errorMsg = $isBackupCode
                ? 'Invalid backup code. Please try again.'
                : 'Invalid or expired code. Please try again.';

            return back()->withErrors(['code' => $errorMsg])->withInput();
        }

        // Mark 2FA verified for this session
        Session::put('two_factor_verified', $user->id);
        Session::put('two_factor_sms_sent', false);

        $response = redirect()->intended(filament()->getPanel('admin')->getUrl());

        // ── Trusted device cookie ────────────────────────────────────────────
        if ($request->boolean('trust_device')) {
            $hours  = (int) ($request->input('trust_hours', 24)); // default 24 hours
            $token  = $this->twoFactor->trustedToken($user);
            $cookie = Cookie::make(
                'jt_trusted_' . $user->id,
                $token,
                $hours * 60,           // minutes
                '/',
                null,
                app()->isProduction(), // secure only in production
                true,                  // httpOnly
                false,
                'Strict'
            );
            return $response->withCookie($cookie);
        }

        return $response;
    }

    public function resendSms()
    {
        $user = $this->staffUser();

        if (!$user || $user->two_factor_method !== 'sms') {
            return back()->withErrors(['code' => 'SMS not available.']);
        }

        $result = $this->twoFactor->sendSmsOtp($user);

        if ($result['success']) {
            Session::put('two_factor_sms_sent', true);
            return back()->with('status', 'New code sent to ' . $result['phone']);
        }

        $message = match ($result['error'] ?? '') {
            'no_phone'     => 'No store phone number configured.',
            'rate_limited' => 'Too many attempts. Wait 10 minutes.',
            default        => 'Failed to send. Try again.',
        };

        return back()->withErrors(['code' => $message]);
    }

    // ── SETUP PAGE ───────────────────────────────────────────────────────────

    public function showSetup()
    {
        $user = $this->staffUser();

        if (!$user) {
            return redirect()->route('filament.admin.auth.login');
        }

        if ($user->two_factor_confirmed && $user->two_factor_method) {
            return redirect()->route('two-factor.challenge');
        }

        $secret = $user->two_factor_secret ?? $this->twoFactor->generateTotpSecret();

        if (!$user->two_factor_secret) {
            $user->update(['two_factor_secret' => $secret]);
        }

        $uri    = $this->twoFactor->getTotpUri($user, $secret);
        $qrCode = $this->twoFactor->generateQrCodeSvg($uri);

        $store       = \App\Models\Store::first();
        $maskedPhone = $store?->phone
            ? $this->twoFactor->maskPhone($store->phone)
            : null;

        return view('auth.two-factor-setup', compact('secret', 'qrCode', 'maskedPhone', 'user'));
    }

    public function confirmSetup(Request $request)
    {
        $request->validate([
            'method' => 'required|in:totp,sms',
            'code'   => 'required|digits:6',
        ]);

        $user   = $this->staffUser();
        $method = $request->method;

        if (!$user) {
            return redirect()->route('filament.admin.auth.login');
        }

        if ($method === 'totp') {
            if (!$this->twoFactor->verifyTotp($user, $request->code)) {
                return back()->withErrors([
                    'code' => 'Invalid code. Make sure your authenticator app time is correct.',
                ]);
            }
            $user->update(['two_factor_method' => 'totp', 'two_factor_confirmed' => true]);
        }

        if ($method === 'sms') {
            if (!$this->twoFactor->verifySmsOtp($user, $request->code)) {
                return back()->withErrors(['code' => 'Invalid or expired SMS code.']);
            }
            $user->update(['two_factor_method' => 'sms', 'two_factor_confirmed' => true]);
        }

        Session::put('two_factor_verified', $user->id);

        return redirect()->intended(filament()->getPanel('admin')->getUrl());
    }

    public function sendSetupSms()
    {
        $user  = $this->staffUser();
        $store = \App\Models\Store::first();

        if (!$user) {
            return redirect()->route('filament.admin.auth.login');
        }

        if (empty($store?->phone)) {
            return back()->withErrors([
                'sms_error' => 'No phone number configured for this store. Add it in Store Settings.',
            ]);
        }

        $result = $this->twoFactor->sendSmsOtp($user);

        if ($result['success']) {
            return back()->with('sms_status', 'Code sent to ' . $result['phone']);
        }

        $message = match ($result['error'] ?? '') {
            'no_phone'     => 'No store phone configured.',
            'rate_limited' => 'Too many attempts. Wait 10 minutes.',
            default        => 'Failed to send SMS. Try again.',
        };

        return back()->withErrors(['sms_error' => $message]);
    }

    // ── BACKUP CODES PAGE ────────────────────────────────────────────────────

    public function showBackupCodes()
    {
        $user = $this->staffUser();

        if (!$user) {
            return redirect()->route('filament.admin.auth.login');
        }

        $newCodes           = Session::pull('two_factor_backup_codes_new', []);
        $backupCodesEnabled = true;

        if (function_exists('tenant') && tenant()) {
            $backupCodesEnabled = tenant()->backup_codes_enabled ?? true;
        }

        $remaining = $this->twoFactor->backupCodesRemaining($user);

        return view('auth.two-factor-backup-codes', compact(
            'newCodes', 'backupCodesEnabled', 'remaining', 'user'
        ));
    }

    public function regenerateBackupCodes()
    {
        $user = $this->staffUser();

        if (!$user) {
            return redirect()->route('filament.admin.auth.login');
        }

        $newCodes = $this->twoFactor->saveBackupCodes($user);
        Session::put('two_factor_backup_codes_new', $newCodes);

        return redirect()->route('two-factor.backup-codes')
            ->with('status', 'New backup codes generated.');
    }

    // ── BACK TO PIN ──────────────────────────────────────────────────────────

   public function back()
{
    Session::forget([
        'two_factor_verified',
        'two_factor_sms_sent',
        'active_staff_id',
        'active_staff_name',
        'active_staff_role',
        'pin_verified_at',
    ]);

    auth()->logout();
    Session::invalidate();
    Session::regenerateToken();

    return redirect()->route('filament.admin.auth.login');
}

    public function resetMethod()
{
    $user = $this->staffUser();
 
    if (!$user) {
        return redirect()->route('filament.admin.auth.login');
    }
 
    // Clear 2FA setup — forces fresh setup on next middleware check
    $user->update([
        'two_factor_secret'    => null,
        'two_factor_method'    => null,
        'two_factor_confirmed' => false,
        'two_factor_code'      => null,
        'two_factor_expires_at'=> null,
    ]);
 
    // Clear 2FA session so middleware sends to setup
    Session::forget(['two_factor_verified', 'two_factor_sms_sent']);
 
    \Illuminate\Support\Facades\Log::info("2FA method reset by user {$user->id} ({$user->name})");
 
    // Redirect to setup page
    return redirect()->route('two-factor.setup')
        ->with('status', 'Your 2FA method has been reset. Please set up your new method.');
}
}