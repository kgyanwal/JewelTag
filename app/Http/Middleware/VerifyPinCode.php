<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifyPinCode
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        // Check if user is logged in, has a PIN, but session is not verified
        if ($user && $user->pin_code && !session('pin_verified')) {
            // 🔹 MUST use the full panel route name: filament.admin.pages.pin-code-auth
            if (!$request->routeIs('filament.admin.pages.pin-code-auth')) {
                return redirect()->route('filament.admin.pages.pin-code-auth', [
                    'next' => $request->fullUrl()
                ]);
            }
        }

        return $next($request);
    }
}