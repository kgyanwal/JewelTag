<?php

declare(strict_types=1);

use App\Models\Sale;
use App\Models\InventoryAudit;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\LabelLayoutController;
use App\Http\Controllers\Api\InventoryAuditController;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {

    // 1. THE PRODUCTION LOGO FIX
    Route::get('/storage/{path}', function ($path) {
        if (!Storage::disk('public')->exists($path)) abort(404);
        return response()->file(Storage::disk('public')->path($path));
    })->where('path', '.*')->name('tenant.storage');

    Route::redirect('/', '/admin');
Route::get('/login', function () {
        return redirect()->route('filament.admin.auth.login');
    })->name('login');
    // 2. STORE-SPECIFIC FEATURES
    Route::get('/sales/{record}/receipt', function (Sale $record) {
        $record->load(['customer', 'items']);
        return view('receipts.sale', ['sale' => $record]);
    })->name('sales.receipt');

    Route::get('/receipt/{sale}', [ReceiptController::class, 'show'])->name('receipt.show');

    // Label Layouts
    Route::prefix('label-layout')->group(function () {
        Route::post('/set-defaults', [LabelLayoutController::class, 'setDefaultLayout']);
        Route::get('/current', [LabelLayoutController::class, 'getLayouts']);
        Route::put('/update/{fieldId}', [LabelLayoutController::class, 'updateLayout']);
        Route::post('/save-all', [LabelLayoutController::class, 'saveAllLayouts']);
    });

    // Inventory Audit & Zebra Scanning
    Route::get('/admin/inventory/audit/{audit}', function (InventoryAudit $audit) {
        return view('admin.inventory.audit', ['audit' => $audit]);
    })->name('inventory.audit');

    Route::post('/inventory/scan', [InventoryAuditController::class, 'recordScan'])
         ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

    Route::post('/inventory/complete/{id}', [InventoryAuditController::class, 'completeAudit'])
         ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
});