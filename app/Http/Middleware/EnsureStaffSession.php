<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class EnsureStaffSession
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->to(filament()->getLoginUrl());
        }

        // Only Superadmin, Administration, Manager, Sales, Sales Associate can access panel
        if (!$user->hasAnyRole(['Superadmin', 'Administration', 'Manager', 'Sales', 'Sales Associate'])) {
            auth()->logout();
            Session::flush();
            abort(403, 'Your account does not have panel access.');
        }

        // Allow PIN page
        if ($request->routeIs('filament.admin.pages.pin-code-auth')) {
            return $next($request);
        }

        // Redirect if staff not pinned in
        if (!Session::has('active_staff_id')) {
            return redirect()->route('filament.admin.pages.pin-code-auth', [
                'next' => $request->fullUrl()
            ]);
        }

        return $next($request);
    }
}
