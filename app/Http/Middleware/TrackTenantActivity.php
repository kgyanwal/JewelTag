<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TrackTenantActivity
{
    public function handle(Request $request, Closure $next)
    {
        if (function_exists('tenant') && tenant() && auth()->check()) {
            $cacheKey = 'tenant_activity_' . tenant('id');
            if (!cache()->has($cacheKey)) {
                tenant()->update(['last_login_at' => now()]);
                cache()->put($cacheKey, true, now()->addMinutes(5));
            }
        }

        return $next($request);
    }
}
