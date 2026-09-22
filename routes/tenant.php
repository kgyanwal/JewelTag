<?php

declare(strict_types=1);

use App\Models\Sale;
use App\Models\InventoryAudit;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\LabelLayoutController;
use App\Http\Controllers\Api\InventoryAuditController;
use App\Http\Controllers\TwoFactorController;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
*/

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {

    // ── STORAGE ───────────────────────────────────────────────────────────────
    Route::get('/storage/{path}', function ($path) {
        if (!Storage::disk('public')->exists($path)) abort(404);
        return response()->file(Storage::disk('public')->path($path));
    })->where('path', '.*')->name('tenant.storage');

    // ── AUTH & REDIRECTS ──────────────────────────────────────────────────────
    Route::get('/login', function () {
        return redirect()->route('filament.admin.auth.login');
    })->name('login');

    Route::get('/', function () {
        return redirect('/admin');
    });

    // ── RECEIPTS ──────────────────────────────────────────────────────────────
    Route::get('/sales/{record}/receipt', [ReceiptController::class, 'show'])
        ->name('sales.receipt')->middleware(['auth']);

    Route::get('/receipt/{sale}', [ReceiptController::class, 'show'])
        ->name('receipt.show');

    Route::get('/repairs/{repair}/print', [ReceiptController::class, 'printRepair'])
        ->name('repair.print')->middleware(['auth']);

    Route::get('/sales/{record}/payment-receipt/{source}/{payment_id}', [ReceiptController::class, 'paymentReceipt'])
        ->name('sales.payment-receipt')->middleware(['auth']);

    Route::get('/custom-order-receipt/{customOrder}', [ReceiptController::class, 'customOrderReceipt'])
        ->name('custom-orders.deposit-receipt');

    Route::get('/laybuys/{laybuy}/print', [ReceiptController::class, 'printLaybuy'])
        ->name('laybuy.print')->middleware(['auth']);

    Route::get('/exchanges/{exchange}/print', function (\App\Models\Exchange $exchange) {
        $exchange->load(['customer', 'store', 'originalSale', 'newSale.items.productItem', 'requester', 'approver']);
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('receipts.exchanges_receipt', compact('exchange'));
        $pdf->setPaper('letter', 'portrait');
        return $pdf->stream("EXCHANGE_{$exchange->exchange_no}.pdf");
    })->name('exchange.print')->middleware(['auth']);

    // ── LABEL LAYOUTS ─────────────────────────────────────────────────────────
    Route::prefix('label-layout')->group(function () {
        Route::post('/set-defaults', [LabelLayoutController::class, 'setDefaultLayout']);
        Route::get('/current', [LabelLayoutController::class, 'getLayouts']);
        Route::put('/update/{fieldId}', [LabelLayoutController::class, 'updateLayout']);
        Route::post('/save-all', [LabelLayoutController::class, 'saveAllLayouts']);
    });

    // ── INVENTORY AUDIT ───────────────────────────────────────────────────────
    Route::get('/admin/inventory/audit/{audit}', function (InventoryAudit $audit) {
        return view('admin.inventory.audit', ['audit' => $audit]);
    })->name('inventory.audit')->middleware('auth');

    Route::post('/inventory/scan', [InventoryAuditController::class, 'recordScan'])
         ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

    Route::post('/inventory/complete/{id}', [InventoryAuditController::class, 'completeAudit'])
         ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

    // ── CRM API ───────────────────────────────────────────────────────────────
    Route::middleware('auth:sanctum')->prefix('api/v1/crm')->group(function () {
        Route::get('/daily-export', [\App\Http\Controllers\Api\CrmExportController::class, 'export']);
    });

    // ── WEBCAM CAPTURE ────────────────────────────────────────────────────────
    Route::post('/repair-webcam-capture', function (\Illuminate\Http\Request $request) {
        $request->validate(['image' => 'required|string']);

        $imageData = $request->input('image');

        if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $matches)) {
            $extension = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
            $imageData = substr($imageData, strpos($imageData, ',') + 1);
        } else {
            $extension = 'jpg';
        }

        $decoded = base64_decode($imageData);
        if ($decoded === false) {
            return response()->json(['error' => 'Invalid image data'], 422);
        }

        $filename = 'repair-intake-photos/' . \Illuminate\Support\Str::uuid() . '.' . $extension;
        \Illuminate\Support\Facades\Storage::disk('public')->put($filename, $decoded);

        return response()->json(['path' => $filename]);
    })->name('repair.webcam.capture')->middleware(['web', 'auth']);

    // ── TWO FACTOR AUTHENTICATION ─────────────────────────────────────────────
    // MUST be inside this middleware group so tenant() is initialized

    // Challenge — verify code every login
    Route::get('/two-factor-challenge', [TwoFactorController::class, 'showChallenge'])
        ->name('two-factor.challenge')->middleware('auth');

    Route::post('/two-factor-challenge', [TwoFactorController::class, 'verifyChallenge'])
        ->name('two-factor.verify')->middleware('auth');

    Route::post('/two-factor-resend', [TwoFactorController::class, 'resendSms'])
        ->name('two-factor.resend')->middleware('auth');

    // Setup — first time configuration
    Route::get('/two-factor-setup', [TwoFactorController::class, 'showSetup'])
        ->name('two-factor.setup')->middleware('auth');

    Route::post('/two-factor-setup/confirm', [TwoFactorController::class, 'confirmSetup'])
        ->name('two-factor.setup.confirm')->middleware('auth');

    Route::post('/two-factor-setup/send-sms', [TwoFactorController::class, 'sendSetupSms'])
        ->name('two-factor.setup.send-sms')->middleware('auth');

    // Backup codes
    Route::get('/two-factor-backup-codes', [TwoFactorController::class, 'showBackupCodes'])
        ->name('two-factor.backup-codes')->middleware('auth');

    Route::post('/two-factor-backup-codes/regenerate', [TwoFactorController::class, 'regenerateBackupCodes'])
        ->name('two-factor.backup-codes.regenerate')->middleware('auth');

    // Back to PIN
    Route::post('/two-factor-back', [TwoFactorController::class, 'back'])
        ->name('two-factor.back')->middleware('auth');


        Route::get('/two-factor-reset', [TwoFactorController::class, 'resetMethod'])
    ->name('two-factor.reset')->middleware('auth');
});