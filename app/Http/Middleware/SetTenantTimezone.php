<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Store;

class SetTenantTimezone
{
    public function handle(Request $request, Closure $next)
    {
        // Check if a tenant is initialized
        if (tenancy()->initialized) {
            // Get the timezone from your Store model
            $store = Store::first(); 
            
            if ($store && $store->timezone) {
                // This overrides config('app.timezone') for the duration of this request only
                config(['app.timezone' => $store->timezone]);
                date_default_timezone_set($store->timezone);
            }
        }

        return $next($request);
    }
}