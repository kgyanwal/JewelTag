<?php

use App\Http\Controllers\LabelLayoutController;
use Illuminate\Support\Facades\Route;
use App\Models\Sale;
use App\Models\InventoryAudit;
use App\Http\Controllers\Api\InventoryAuditController;


Route::redirect('/', '/admin');
Route::get('/login', function () {
    return redirect()->route('filament.admin.auth.login');
})->name('login');
Route::get('/sales/{record}/receipt', function (Sale $record) {
    // 🔹 Crucial: Load the customer and items before sending to view
    $record->load(['customer', 'items']);
    return view('receipts.sale', ['sale' => $record]);
})->name('sales.receipt');

// Label layout management routes
Route::prefix('label-layout')->group(function () {
    Route::post('/set-defaults', [LabelLayoutController::class, 'setDefaultLayout']);
    Route::get('/current', [LabelLayoutController::class, 'getLayouts']);
    Route::put('/update/{fieldId}', [LabelLayoutController::class, 'updateLayout']);
    Route::post('/save-all', [LabelLayoutController::class, 'saveAllLayouts']);
});



Route::post('/inventory/scan', [InventoryAuditController::class, 'recordScan'])
     ->middleware('auth:sanctum'); // Ensure the scanner is logged in
// This creates the URL: yourdomain.com/admin/inventory/audit/{id}
Route::get('/admin/inventory/audit/{audit}', function (InventoryAudit $audit) {
    return view('admin.inventory.audit', ['audit' => $audit]);
})->name('inventory.audit')->middleware(['auth']);