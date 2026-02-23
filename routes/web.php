<?php

use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\PreventAccessFromTenantDomains;

/*
|--------------------------------------------------------------------------
| Web Routes (Central / Landlord Only)
|--------------------------------------------------------------------------
*/

Route::middleware([
    'web',
    PreventAccessFromTenantDomains::class, // 🚀 Ensures tenant subdomains can't hit landlord routes
])->group(function () {

    // 1. Redirect the main domain to the Master Panel
    Route::redirect('/', '/master');

    // 2. Dynamic Store Creation Route
    Route::get('/create-store/{store_name}', function ($store_name) {
        // Create the Tenant record
        $tenant = App\Models\Tenant::create(['id' => $store_name]);

        // 🚀 SMART DOMAIN LOGIC
        // Detects if we are on local or production
        $baseDomain = app()->isLocal() ? 'localhost' : 'jeweltag.us';
        $fullDomain = $store_name . '.' . $baseDomain;

        $tenant->domains()->create(['domain' => $fullDomain]);

        $protocol = app()->isLocal() ? 'http' : 'https';
        $port = app()->isLocal() ? ':8001' : '';

        return "Success! Store '{$store_name}' created. Visit {$protocol}://{$fullDomain}{$port}/admin";
    });
});

