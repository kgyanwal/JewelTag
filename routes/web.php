<?php

use App\Http\Controllers\LabelLayoutController;
use App\Http\Controllers\Api\InventoryAuditController;
use App\Models\Sale;
use App\Models\InventoryAudit;
use Illuminate\Support\Facades\Route;

// --- EXISTING CORE ROUTES ---
Route::redirect('/', '/admin');

Route::get('/login', function () {
    return redirect()->route('filament.admin.auth.login');
})->name('login');

Route::get('/sales/{record}/receipt', function (Sale $record) {
    // 🔹 Crucial: Load the customer and items before sending to view
    $record->load(['customer', 'items']);
    return view('receipts.sale', ['sale' => $record]);
})->name('sales.receipt');

// --- EXISTING LABEL LAYOUT ROUTES ---
Route::prefix('label-layout')->group(function () {
    Route::post('/set-defaults', [LabelLayoutController::class, 'setDefaultLayout']);
    Route::get('/current', [LabelLayoutController::class, 'getLayouts']);
    Route::put('/update/{fieldId}', [LabelLayoutController::class, 'updateLayout']);
    Route::post('/save-all', [LabelLayoutController::class, 'saveAllLayouts']);
});

// --- NEW INVENTORY AUDIT ROUTES ---

/**
 * 1. The UI Route (This fixes your 404)
 * This is the page you visit to see the scanning interface.
 */
Route::get('/admin/inventory/audit/{audit}', function (InventoryAudit $audit) {
    return view('admin.inventory.audit', ['audit' => $audit]);
})->name('inventory.audit')->middleware(['auth']);

/**
 * 2. The Scanner API Routes
 * These handle the data sent from the Zebra RFD9090 trigger.
 * Note: We bypass CSRF so the handheld device doesn't get blocked.
 */
Route::post('/inventory/scan', [InventoryAuditController::class, 'recordScan'])
     ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

Route::post('/inventory/complete/{id}', [InventoryAuditController::class, 'completeAudit'])
     ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);