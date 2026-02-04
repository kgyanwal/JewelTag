<?php

use Illuminate\Support\Facades\Route;
use App\Models\Sale;

Route::redirect('/', '/admin');

Route::get('/sales/{record}/receipt', function (Sale $record) {
    // 🔹 Crucial: Load the customer and items before sending to view
    $record->load(['customer', 'items']);
    return view('receipts.sale', ['sale' => $record]);
})->name('sales.receipt');

