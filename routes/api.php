<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CrmExportController;

// Protect the route so only valid API keys can access it
Route::middleware('auth:sanctum')->prefix('v1/crm')->group(function () {
    Route::get('/daily-export', [CrmExportController::class, 'export']);
});