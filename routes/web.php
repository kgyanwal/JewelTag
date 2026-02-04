<?php

use Illuminate\Support\Facades\Route;
use App\Models\Sale;
use App\Filament\Pages\PinCodeAuth;
Route::redirect('/', '/admin');

Route::get('/sales/{record}/receipt', function (Sale $record) {
    // 🔹 Crucial: Load the customer and items before sending to view
    $record->load(['customer', 'items']);
    return view('receipts.sale', ['sale' => $record]);
})->name('sales.receipt');

Route::middleware(['web'])
    ->prefix('admin')
    ->group(function () {
        // GET route for page
        Route::get('/pin-code-auth', PinCodeAuth::class)
            ->name('filament.admin.pages.pin-code-auth');

        // POST route for Livewire verify method
        Route::post('/pin-code-auth', [PinCodeAuth::class, 'verify']);
    });