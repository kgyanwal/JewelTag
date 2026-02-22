<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes (Central / Landlord Only)
|--------------------------------------------------------------------------
*/

// 1. Redirect the main domain to the Master Panel
Route::redirect('/', '/master');

// 2. Simple Store Creation Route (For testing/internal use)
Route::get('/create-store/{store_name}', function ($store_name) {
    // This creates the tenant entry in the MASTER database
    $tenant = App\Models\Tenant::create(['id' => $store_name]);

    // This assigns the domain in the MASTER database
    $tenant->domains()->create(['domain' => $store_name . '.localhost']);

    return "Success! Store '{$store_name}' created. Visit http://{$store_name}.localhost:8001/admin";
});

// Note: Receipt, Audit, and Label routes are REMOVED because 
// they now live in tenant.php and run on the store's private database.