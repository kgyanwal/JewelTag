<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Check if user is logged in
        if (! auth()->check()) {
            return redirect()->route('filament.admin.auth.login');
        }

        $user = auth()->user();

        // 2. Check if user has the correct Role
        // We added 'Superadmin' to match your database role
        if (! $user || ! $user->hasAnyRole(['Superadmin', 'Admin', 'Manager', 'User'])) {
            abort(403, 'You do not have permission to access this panel.');
        }

        return $next($request);
    }
}