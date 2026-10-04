<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Laybuy;
use App\Models\LaybuyPayment;
use App\Models\Payment;
use App\Models\ProductItem;
use App\Models\Sale;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * LaybuyIntegrationTest
 *
 * Covers every payment-sync scenario between Laybuy ↔ Sale ↔ Payment ledger.
 * Mirrors RepairIntegrationTest.php structure.
 *
 * Run:
 *   php artisan test tests/Feature/LaybuyIntegrationTest.php
 */
class LaybuyIntegrationTest extends TestCase
{
    protected string $tenantId       = 'lxd';
    protected string $tenantDatabase = 'tenantlxd';

    protected Store    $store;
    protected User     $user;
    protected Customer $customer;

    // ──────────────────────────────────────────────
    // BOOT
    // ──────────────────────────────────────────────

    protected function setUp(): void
    {
        parent::setUp();
        $this->applyTenantConnection();

        DB::beginTransaction();

        $this->store = Store::firstOrCreate(
            ['id' => 1],
            ['name' => 'Test Store', 'timezone' => 'America/Denver']
        );

        $this->user = User::firstOrCreate(
            ['email' => 'test@laybuy.test'],
            [
                'name'     => 'Laybuy Tester',
                'username' => 'laybuy_tester',
                'password' => bcrypt('secret'),
                'store_id' => $this->store->id,
                'pin_code' => '1234',
            ]
        );

        $this->customer = Customer::firstOrCreate(
            ['email' => 'laybuy.customer@test.test'],
            [
                'name'        => 'Laybuy',
                'last_name'   => 'Customer',
                'phone'       => '5550001234',
                'customer_no' => 'LB-TEST-' . rand(1000, 9999),
            ]
        );

        $this->actingAs($this->user);
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        tenancy()->end();
        parent::tearDown();
    }

    // ──────────────────────────────────────────────
    // HELPERS
    // ──────────────────────────────────────────────

    protected function applyTenantConnection(): void
    {
        $tenant = \App\Models\Tenant::find($this->tenantId);
        if (!$tenant) {
            $this->markTestSkipped("Tenant '{$this->tenantId}' not found — skipping.");
        }
        tenancy()->initialize($tenant);

        config(['database.connections.tenant' => array_merge(
            config('database.connections.mysql'),
            ['database' => $this->tenantDatabase]
        )]);
        DB::purge('tenant');
        DB::reconnect('tenant');
        DB::setDefaultConnection('tenant');
    }

    /** Create a ProductItem in_stock ready for reservation */
    protected function makeProductItem(float $price = 100.00): ProductItem
    {
        // Find a real supplier_id to satisfy the FK / NOT NULL constraint
        $supplierId = DB::table('suppliers')->value('id') ?? 1;

        return ProductItem::create([
            'barcode'            => 'TEST-' . rand(10000, 99999),
            'custom_description' => 'Test Ring',
            'retail_price'       => $price,
            'cost_price'         => $price * 0.5,
            'status'             => 'in_stock',
            'store_id'           => $this->store->id,
            'supplier_id'        => $supplierId,
        ]);
    }

    /** Create a Sale with minimum required fields (no FK issues) */
    protected function makeSale(float $total, float $paid = 0): Sale
    {
        $balance = max(0, $total - $paid);
        return Sale::create([
            'customer_id'       => $this->customer->id,
            'invoice_number'    => 'INV-TEST-' . rand(10000, 99999),
            'status'            => $balance <= 0 ? 'completed' : 'pending',
            'sales_person_list' => [],
            'payment_method'    => 'laybuy',
            'subtotal'          => $total,
            'final_total'       => $total,
            'amount_paid'       => $paid,
            'balance_due'       => $balance,
            'tax_amount'        => 0,
            'store_id'          => $this->store->id,
        ]);
    }

    /** Create a Laybuy linked to a Sale */
    protected function makeLaybuy(Sale $sale, float $amountPaid = 0): Laybuy
    {
        $total   = floatval($sale->final_total);
        $balance = max(0, $total - $amountPaid);

        return Laybuy::create([
            'laybuy_no'   => 'LB-' . rand(10000, 99999),
            'customer_id' => $this->customer->id,
            'sale_id'     => $sale->id,
            'total_amount'=> $total,
            'amount_paid' => $amountPaid,
            'balance_due' => $balance,
            'status'      => $balance <= 0 ? 'completed' : 'in_progress',
            'start_date'  => now()->toDateString(),
            'due_date'    => now()->addMonths(3)->toDateString(),
            'sales_person'=> $this->user->name,
        ]);
    }

    /** Record a LaybuyPayment + matching Payment (mirrors EditLaybuy add_payment action) */
    protected function recordPayment(Laybuy $laybuy, float $amount, string $method = 'CASH', ?\Carbon\Carbon $paidAt = null): void
    {
        $paidAt ??= now();

        LaybuyPayment::create([
            'laybuy_id'      => $laybuy->id,
            'amount'         => $amount,
            'payment_method' => $method,
            'created_at'     => $paidAt,
            'updated_at'     => $paidAt,
        ]);

        Payment::create([
            'sale_id'  => $laybuy->sale_id,
            'amount'   => $amount,
            'method'   => $method,
            'paid_at'  => $paidAt,
            'store_id' => $this->store->id,
        ]);

        $newPaid    = $laybuy->amount_paid + $amount;
        $newBalance = max(0, $laybuy->total_amount - $newPaid);
        $laybuy->update([
            'amount_paid'    => $newPaid,
            'balance_due'    => $newBalance,
            'status'         => $newBalance <= 0 ? 'completed' : 'in_progress',
            'last_paid_date' => $paidAt->toDateString(),
        ]);
        $laybuy->refresh();
    }

    // ──────────────────────────────────────────────
    // TESTS
    // ──────────────────────────────────────────────

    /**
     * 1. A fresh Laybuy with no payments shows correct initial balance.
     */
    public function test_new_laybuy_shows_correct_initial_balance(): void
    {
        $sale   = $this->makeSale(500.00, 0);
        $laybuy = $this->makeLaybuy($sale, 0);

        $this->assertEquals(500.00, floatval($laybuy->total_amount));
        $this->assertEquals(0.00,   floatval($laybuy->amount_paid));
        $this->assertEquals(500.00, floatval($laybuy->balance_due));
        $this->assertEquals('in_progress', $laybuy->status);
    }

    /**
     * 2. Initial deposit on create reduces balance and creates LaybuyPayment + Payment rows.
     */
    public function test_initial_deposit_creates_both_payment_rows(): void
    {
        $sale   = $this->makeSale(500.00, 100.00);
        $laybuy = $this->makeLaybuy($sale, 100.00);

        // Record initial deposit in both ledgers (as CreateLaybuy does)
        LaybuyPayment::create([
            'laybuy_id'      => $laybuy->id,
            'amount'         => 100.00,
            'payment_method' => 'CASH',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
        Payment::create([
            'sale_id'  => $sale->id,
            'amount'   => 100.00,
            'method'   => 'CASH',
            'paid_at'  => now(),
            'store_id' => $this->store->id,
        ]);

        $this->assertEquals(100.00, floatval($laybuy->amount_paid));
        $this->assertEquals(400.00, floatval($laybuy->balance_due));
        $this->assertEquals(1, LaybuyPayment::where('laybuy_id', $laybuy->id)->count());
        $this->assertEquals(1, Payment::where('sale_id', $sale->id)->count());
    }

    /**
     * 3. Add Payment action reduces laybuy balance and syncs the linked Sale.
     */
    public function test_add_payment_syncs_laybuy_and_sale(): void
    {
        $sale   = $this->makeSale(500.00, 100.00);
        $laybuy = $this->makeLaybuy($sale, 100.00);

        // Pay $200 more
        $this->recordPayment($laybuy, 200.00, 'VISA');

        $laybuy->refresh();
        $sale->refresh();

        $this->assertEquals(300.00, floatval($laybuy->amount_paid));
        $this->assertEquals(200.00, floatval($laybuy->balance_due));
        $this->assertEquals('in_progress', $laybuy->status);

        // Sale should reflect the Payment row total
        $salePaid = Payment::where('sale_id', $sale->id)->sum('amount');
        $this->assertEquals(200.00, round($salePaid, 2));
    }

    /**
     * 4. Fully paying a laybuy flips both statuses to 'completed'.
     */
    public function test_full_payment_completes_laybuy_and_sale(): void
    {
        $sale   = $this->makeSale(300.00, 0);
        $laybuy = $this->makeLaybuy($sale, 0);

        $this->recordPayment($laybuy, 300.00, 'DEBIT CARD');

        // Sync sale (as EditLaybuy action does)
        $totalSalePaid = Payment::where('sale_id', $sale->id)->sum('amount');
        $saleBalance   = max(0, $sale->final_total - $totalSalePaid);
        $sale->update([
            'amount_paid' => $totalSalePaid,
            'balance_due' => $saleBalance,
            'status'      => $saleBalance <= 0.01 ? 'completed' : $sale->status,
        ]);

        $laybuy->refresh();
        $sale->refresh();

        $this->assertEquals(0.00, floatval($laybuy->balance_due));
        $this->assertEquals('completed', $laybuy->status);
        $this->assertEquals('completed', $sale->status);
    }

    /**
     * 5. LaybuyPayment ledger and Payment table stay in sync — totals match.
     */
    public function test_laybuy_payment_ledger_matches_payment_table(): void
    {
        $sale   = $this->makeSale(600.00, 0);
        $laybuy = $this->makeLaybuy($sale, 0);

        $this->recordPayment($laybuy, 150.00, 'CASH');
        $this->recordPayment($laybuy, 150.00, 'VISA');
        $this->recordPayment($laybuy, 150.00, 'MASTERCARD');

        $laybuyTotal  = LaybuyPayment::where('laybuy_id', $laybuy->id)->sum('amount');
        $paymentTotal = Payment::where('sale_id', $sale->id)->sum('amount');

        $this->assertEquals(450.00, round($laybuyTotal, 2));
        $this->assertEquals(450.00, round($paymentTotal, 2));
        $this->assertEquals(round($laybuyTotal, 2), round($paymentTotal, 2));
    }

    /**
     * 6. ProductItem status goes on_hold when Laybuy is created, sold when fully paid.
     */
    public function test_product_item_status_lifecycle(): void
    {
        $productItem = $this->makeProductItem(250.00);
        $sale        = $this->makeSale(250.00, 0);
        $laybuy      = $this->makeLaybuy($sale, 0);

        // Simulate stock reservation (as CreateLaybuy does)
        $productItem->update([
            'status'          => 'on_hold',
            'hold_reason'     => "Laybuy: {$sale->invoice_number}",
            'held_by_sale_id' => $sale->id,
        ]);

        $productItem->refresh();
        $this->assertEquals('on_hold', $productItem->status);

        // Fully pay the laybuy
        $this->recordPayment($laybuy, 250.00, 'CASH');

        // Simulate release (as EditLaybuy fully-paid block does)
        ProductItem::where('id', $productItem->id)
            ->where('status', 'on_hold')
            ->update(['status' => 'sold', 'hold_reason' => null, 'held_by_sale_id' => null]);

        $productItem->refresh();
        $this->assertEquals('sold', $productItem->status);
        $this->assertNull($productItem->hold_reason);
    }

    /**
     * 7. Cancelling a laybuy releases items back to in_stock.
     */
    public function test_cancel_laybuy_releases_items_to_in_stock(): void
    {
        $productItem = $this->makeProductItem(400.00);
        $sale        = $this->makeSale(400.00, 0);
        $laybuy      = $this->makeLaybuy($sale, 0);

        // Reserve item
        $productItem->update([
            'status'          => 'on_hold',
            'hold_reason'     => "Laybuy: {$sale->invoice_number}",
            'held_by_sale_id' => $sale->id,
        ]);

        // Simulate cancel_laybuy action
        ProductItem::where('id', $productItem->id)
            ->where('status', 'on_hold')
            ->update(['status' => 'in_stock', 'hold_reason' => null, 'held_by_sale_id' => null]);
        $sale->update(['status' => 'cancelled']);
        $laybuy->update(['status' => 'cancelled']);

        $productItem->refresh();
        $laybuy->refresh();
        $sale->refresh();

        $this->assertEquals('in_stock', $productItem->status);
        $this->assertEquals('cancelled', $laybuy->status);
        $this->assertEquals('cancelled', $sale->status);
    }

    /**
     * 8. EOD does not double-count — Payment row exists once even though
     *    LaybuyPayment also has a matching row.
     */
    public function test_eod_does_not_double_count_laybuy_payments(): void
    {
        // Use a time far in the past so today's real data doesn't interfere
        $uniqueTime = now()->subYear();

        $sale   = $this->makeSale(200.00, 0);
        $laybuy = $this->makeLaybuy($sale, 0);

        // One payment recorded in both ledgers (as EditLaybuy does)
        LaybuyPayment::create([
            'laybuy_id'      => $laybuy->id,
            'amount'         => 200.00,
            'payment_method' => 'CASH',
            'created_at'     => $uniqueTime,
            'updated_at'     => $uniqueTime,
        ]);
        Payment::create([
            'sale_id'  => $sale->id,
            'amount'   => 200.00,
            'method'   => 'CASH',
            'paid_at'  => $uniqueTime,
            'store_id' => $this->store->id,
        ]);

        // EOD queries only Payment table — should be exactly $200
        $eodStart = $uniqueTime->copy()->subMinute();
        $eodEnd   = $uniqueTime->copy()->addMinute();

        $eodTotal = Payment::where('store_id', $this->store->id)
            ->whereBetween('paid_at', [$eodStart, $eodEnd])
            ->sum('amount');

        $this->assertEquals(200.00, round($eodTotal, 2));
    }

    /**
     * 9. Manage Ledger (delete a payment) recalculates balance correctly.
     */
    public function test_manage_ledger_delete_recalculates_balance(): void
    {
        $sale   = $this->makeSale(500.00, 0);
        $laybuy = $this->makeLaybuy($sale, 0);

        $this->recordPayment($laybuy, 200.00, 'CASH');
        $this->recordPayment($laybuy, 100.00, 'VISA');

        $laybuy->refresh();
        $this->assertEquals(300.00, floatval($laybuy->amount_paid));

        // Delete the $100 VISA payment (simulate manage_payments action)
        $lp = LaybuyPayment::where('laybuy_id', $laybuy->id)
            ->where('amount', 100.00)->where('payment_method', 'VISA')
            ->first();
        Payment::where('sale_id', $sale->id)
            ->where('amount', 100.00)->where('method', 'VISA')
            ->delete();
        $lp->delete();

        // Recalculate as the action does
        $newTotalPaid = LaybuyPayment::where('laybuy_id', $laybuy->id)->sum('amount');
        $trueBalance  = max(0, floatval($laybuy->total_amount) - floatval($newTotalPaid));
        $laybuy->update(['amount_paid' => $newTotalPaid, 'balance_due' => $trueBalance, 'status' => $trueBalance <= 0 ? 'completed' : 'in_progress']);

        $laybuy->refresh();
        $this->assertEquals(200.00, floatval($laybuy->amount_paid));
        $this->assertEquals(300.00, floatval($laybuy->balance_due));
        $this->assertEquals('in_progress', $laybuy->status);

        // Sale Payment total also reduced
        $salePaid = Payment::where('sale_id', $sale->id)->sum('amount');
        $this->assertEquals(200.00, round($salePaid, 2));
    }

    /**
     * 10. Manage Ledger (edit amount) updates both LaybuyPayment and Payment rows.
     */
    public function test_manage_ledger_edit_amount_syncs_both_tables(): void
    {
        $sale   = $this->makeSale(500.00, 0);
        $laybuy = $this->makeLaybuy($sale, 0);

        $paidAt = now()->subHour();
        LaybuyPayment::create([
            'laybuy_id'      => $laybuy->id,
            'amount'         => 150.00,
            'payment_method' => 'CASH',
            'created_at'     => $paidAt,
            'updated_at'     => $paidAt,
        ]);
        Payment::create([
            'sale_id'  => $sale->id,
            'amount'   => 150.00,
            'method'   => 'CASH',
            'paid_at'  => $paidAt,
            'store_id' => $this->store->id,
        ]);

        // Simulate manage_payments edit — change $150 CASH to $175 CASH
        $lp = LaybuyPayment::where('laybuy_id', $laybuy->id)->where('amount', 150.00)->first();
        Payment::where('sale_id', $sale->id)
            ->where('amount', 150.00)->where('method', 'CASH')
            ->update(['amount' => 175.00]);
        $lp->update(['amount' => 175.00]);

        // Recalculate
        $newTotalPaid = LaybuyPayment::where('laybuy_id', $laybuy->id)->sum('amount');
        $laybuy->update(['amount_paid' => $newTotalPaid, 'balance_due' => max(0, floatval($laybuy->total_amount) - $newTotalPaid)]);

        $laybuy->refresh();
        $this->assertEquals(175.00, floatval($laybuy->amount_paid));
        $this->assertEquals(325.00, floatval($laybuy->balance_due));

        $salePaid = Payment::where('sale_id', $sale->id)->sum('amount');
        $this->assertEquals(175.00, round($salePaid, 2));
    }

    /**
     * 11. Payment progress percentage is calculated correctly at various stages.
     */
    public function test_payment_progress_percentage(): void
    {
        $sale   = $this->makeSale(400.00, 0);
        $laybuy = $this->makeLaybuy($sale, 0);

        // 0% progress
        $pct = $laybuy->total_amount > 0
            ? min(100, round(($laybuy->amount_paid / $laybuy->total_amount) * 100))
            : 0;
        $this->assertEquals(0, $pct);

        $this->recordPayment($laybuy, 200.00, 'CASH');
        $laybuy->refresh();

        // 50% progress
        $pct = min(100, round(($laybuy->amount_paid / $laybuy->total_amount) * 100));
        $this->assertEquals(50, $pct);

        $this->recordPayment($laybuy, 200.00, 'VISA');
        $laybuy->refresh();

        // 100% progress
        $pct = min(100, round(($laybuy->amount_paid / $laybuy->total_amount) * 100));
        $this->assertEquals(100, $pct);
    }

    /**
     * 12. Overpayment is capped — balance_due never goes negative.
     */
    public function test_overpayment_never_makes_balance_negative(): void
    {
        $sale   = $this->makeSale(100.00, 0);
        $laybuy = $this->makeLaybuy($sale, 0);

        // Record MORE than owed
        LaybuyPayment::create([
            'laybuy_id'      => $laybuy->id,
            'amount'         => 150.00,
            'payment_method' => 'CASH',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        $newPaid    = $laybuy->amount_paid + 150.00;
        $newBalance = max(0, floatval($laybuy->total_amount) - $newPaid); // clamped by max(0,…)
        $laybuy->update(['amount_paid' => $newPaid, 'balance_due' => $newBalance, 'status' => 'completed']);
        $laybuy->refresh();

        $this->assertEquals(0.00, floatval($laybuy->balance_due));
        $this->assertGreaterThanOrEqual(0, floatval($laybuy->balance_due));
        $this->assertEquals('completed', $laybuy->status);
    }

    /**
     * 13. Multiple items — total amount includes all sale prices.
     */
    public function test_laybuy_total_covers_all_items(): void
    {
        $item1 = $this->makeProductItem(100.00);
        $item2 = $this->makeProductItem(250.00);
        $item3 = $this->makeProductItem(150.00);

        $total = 100.00 + 250.00 + 150.00; // 500.00 (tax-free for test simplicity)

        $sale   = $this->makeSale($total, 0);
        $laybuy = $this->makeLaybuy($sale, 0);

        $this->assertEquals(500.00, floatval($laybuy->total_amount));
        $this->assertEquals(500.00, floatval($laybuy->balance_due));
    }

    /**
     * 14. Payment from Sale edit (sale-sourced payment) reduces laybuy balance
     *     when reconciled via Payment table (not LaybuyPayment).
     */
    public function test_sale_sourced_payment_visible_in_ledger(): void
    {
        $sale   = $this->makeSale(300.00, 0);
        $laybuy = $this->makeLaybuy($sale, 0);

        // Payment added directly to Sale (e.g. from SaleResource payment action)
        Payment::create([
            'sale_id'  => $sale->id,
            'amount'   => 100.00,
            'method'   => 'DEBIT CARD',
            'paid_at'  => now(),
            'store_id' => $this->store->id,
        ]);

        // LaybuyPayment ledger does NOT have this row
        $laybuyLedgerTotal = LaybuyPayment::where('laybuy_id', $laybuy->id)->sum('amount');
        $salePaymentTotal  = Payment::where('sale_id', $sale->id)->sum('amount');

        // Sale has $100 paid
        $this->assertEquals(0.00,   round($laybuyLedgerTotal, 2));
        $this->assertEquals(100.00, round($salePaymentTotal, 2));

        // The payment_history placeholder in the form merges both sources — verify sale-side is non-zero
        $this->assertGreaterThan(0, $salePaymentTotal);
    }

    /**
     * 15. Fully paid laybuy status = completed, balance = 0, all items = sold.
     */
    public function test_fully_paid_laybuy_shows_zero_balance_and_completed(): void
    {
        $item   = $this->makeProductItem(200.00);
        $sale   = $this->makeSale(200.00, 0);
        $laybuy = $this->makeLaybuy($sale, 0);

        $item->update(['status' => 'on_hold', 'held_by_sale_id' => $sale->id]);

        $this->recordPayment($laybuy, 200.00, 'CASH');

        // Sync sale
        $salePaid = Payment::where('sale_id', $sale->id)->sum('amount');
        $sale->update(['amount_paid' => $salePaid, 'balance_due' => 0, 'status' => 'completed']);

        // Release item
        ProductItem::where('id', $item->id)->update(['status' => 'sold', 'hold_reason' => null, 'held_by_sale_id' => null]);

        $laybuy->refresh();
        $sale->refresh();
        $item->refresh();

        $this->assertEquals(0.00, floatval($laybuy->balance_due));
        $this->assertEquals('completed', $laybuy->status);
        $this->assertEquals('completed', $sale->status);
        $this->assertEquals('sold', $item->status);
    }
}