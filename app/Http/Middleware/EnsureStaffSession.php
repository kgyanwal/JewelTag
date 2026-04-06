<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
class EnsureStaffSession
{
public function handle(Request $request, Closure $next)
{
    $user = auth()->user();

    if (!$user) {
        return redirect()->to(filament()->getLoginUrl());
    }

    // 🚀 THE FIX: Explicitly allow the PIN page to load even if we are "switching"
    if ($request->routeIs('filament.admin.pages.pin-code-auth')) {
        return $next($request);
    }

    if (!Session::has('active_staff_id')) {
        return redirect()->route('filament.admin.pages.pin-code-auth', [
            'next' => $request->fullUrl()
        ]);
    }
if (tenant() && tenant('is_active') === false) {
        // If they are not active, log them out and show a billing error
        auth()->logout();
        abort(403, 'ACCOUNT SUSPENDED: Please update your billing information or contact support to restore access.');
    }
    return $next($request);
}

}
