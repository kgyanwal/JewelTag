<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        // The dd() statement has been removed.
        if (! auth()->check() || ! ($user && $user->hasAnyRole(['Superadmin', 'User']))) {
            abort(403);
        }

        return $next($request);
    }
}