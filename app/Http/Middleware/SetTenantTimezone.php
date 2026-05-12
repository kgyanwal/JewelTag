<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Store;

class SetTenantTimezone
{
   public function handle(Request $request, Closure $next)
{
    if (function_exists('tenancy') && tenancy()->initialized) {
        $store = Store::first();
        if ($store && $store->timezone) {
            config(['app.timezone' => $store->timezone]);
            date_default_timezone_set($store->timezone);
        }
    }

    // Also try to initialize from domain if not yet initialized
    if (function_exists('tenancy') && !tenancy()->initialized) {
        try {
            $domain = \Stancl\Tenancy\Database\Models\Domain::where('domain', $request->getHost())->first();
            if ($domain) {
                tenancy()->initialize($domain->tenant);
                $store = Store::first();
                if ($store && $store->timezone) {
                    config(['app.timezone' => $store->timezone]);
                    date_default_timezone_set($store->timezone);
                }
            }
        } catch (\Exception $e) {
            // silent
        }
    }

    return $next($request);
}

    
}