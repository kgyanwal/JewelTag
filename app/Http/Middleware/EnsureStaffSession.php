<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\Services\TwoFactorService;

class EnsureStaffSession
{
    public function __construct(
        protected TwoFactorService $twoFactor
    ) {}

 public function handle(Request $request, Closure $next)
{
    $user = auth()->user();

    if (!$user) {
        return redirect()->to(filament()->getLoginUrl());
    }

    // Always allow these routes
    if ($request->routeIs('filament.admin.pages.pin-code-auth') ||
        $request->routeIs('two-factor.*') ||
        $request->routeIs('filament.admin.auth.logout')) {
        return $next($request);
    }

    // Check tenant active status
    if (tenant() && tenant('is_active') === false) {
        auth()->logout();
        abort(403, 'ACCOUNT SUSPENDED: Please contact support.');
    }

    // ── STEP 1: 2FA CHECK FIRST — before PIN ────────────────────────────────
    $twoFactorEnabled = tenant()->two_factor_enabled ?? true;

    if ($twoFactorEnabled) {
        $cookieName      = 'jt_trusted_' . $user->id;
        $trustedToken    = hash('sha256', $user->id . $user->password . config('app.key'));
        $alreadyVerified = session('two_factor_verified') === $user->id;
        $trustedDevice   = $request->cookie($cookieName) === $trustedToken;

        if (!$alreadyVerified && !$trustedDevice) {
            // Not set up → setup page
            if (!$user->two_factor_confirmed || !$user->two_factor_method) {
                return redirect()->route('two-factor.setup');
            }

            // SMS — send OTP once
           if ($user->two_factor_method === 'sms') {
    if (!session('two_factor_sms_sent')) {
        app(TwoFactorService::class)->sendSmsOtp($user);
        session(['two_factor_sms_sent' => true]);
    }
}

            return redirect()->route('two-factor.challenge');
        }
    }

    // ── STEP 2: PIN CHECK — after 2FA verified ───────────────────────────────
    if (!Session::has('active_staff_id')) {
        return redirect()->route('filament.admin.pages.pin-code-auth', [
            'next' => $request->fullUrl(),
        ]);
    }

    return $next($request);
}
}