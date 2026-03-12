<?php

use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\PreventAccessFromTenantDomains;
use App\Models\Tenant;

$centralDomains = ['localhost', '127.0.0.1', 'jeweltag.us'];

foreach ($centralDomains as $domain) {
    Route::domain($domain)->middleware(['web'])->group(function () {

        // Landing page
        Route::get('/', function () {
            return view('welcome'); // Central landing page
        });

        // Filament Welcome Page route
        Route::get('/welcome-page', function () {
            return \App\Filament\Master\Pages\Welcome::render();
        });

        // Master Login
        Route::get('/master-login', function () {
            return redirect('/master/login');
        });

        // Create Store
        Route::middleware([PreventAccessFromTenantDomains::class])->group(function () {
            Route::get('/create-store/{store_name}', function ($store_name) {
                $tenant = Tenant::create(['id' => $store_name]);
                $baseDomain = app()->isLocal() ? 'localhost' : 'jeweltag.us';
                $fullDomain = $store_name . '.' . $baseDomain;
                $tenant->domains()->create(['domain' => $fullDomain]);
                return "Success! Store '{$store_name}' created.";
            });
        });
    });
}