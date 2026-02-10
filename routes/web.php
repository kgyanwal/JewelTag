<?php

use Illuminate\Support\Facades\Route;
use App\Models\Sale;

Route::redirect('/', '/admin');

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
