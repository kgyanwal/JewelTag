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
    Log::info('EnsureStaffSession user', ['user' => $user]);

    if (!$user) {
        Log::warning('User not authenticated');
        return redirect()->to(filament()->getLoginUrl());
    }

    if (!$user->hasAnyRole(['Superadmin', 'Administration', 'Manager', 'Sales', 'Sales Associate'])) {
        Log::warning('User role not allowed', ['roles' => $user->roles->pluck('name')]);
        auth()->logout();
        Session::flush();
        abort(403, 'Your account does not have panel access.');
    }

    if ($request->routeIs('filament.admin.pages.pin-code-auth')) {
        return $next($request);
    }

    if (!Session::has('active_staff_id')) {
        Log::info('Staff not pinned in, redirecting', ['next' => $request->fullUrl()]);
        return redirect()->route('filament.admin.pages.pin-code-auth', [
            'next' => $request->fullUrl()
        ]);
    }

    return $next($request);
}
}
