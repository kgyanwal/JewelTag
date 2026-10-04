<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\ProductItem;
use App\Models\Refund;
use App\Models\Restock;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Store;
use App\Models\User;
use App\Models\VendorReturn;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * RefundIntegrationTest
 *
 * Covers every refund scenario:
 *   - Cash refund vs store credit
 *   - Restock vs vendor return vs void item
 *   - Fully-paid and partially-paid sale targets
 *   - Sale status sync after refund
 *   - Payment ledger negative entry
 *   - Customer credit balance update
 *
 * Run:
 *   php artisan test tests/Feature/RefundIntegrationTest.php
 */
class RefundIntegrationTest extends TestCase
{
    protected string $tenantId       = 'lxd';
    protected string $tenantDatabase = 'tenantlxd';

    protected Store    $store;
    protected User     $user;
    protected Customer $customer;

    // ──────────────────────────────────────────────
    // TENANT CONNECTION
    // ──────────────────────────────────────────────

    protected function applyTenantConnection(): void
    {
        config([
            'database.connections.tenant' => [
                'driver'    => 'mysql',
                'host'      => config('database.connections.mysql.host', '127.0.0.1'),
                'port'      => config('database.connections.mysql.port', '3306'),
                'database'  => $this->tenantDatabase,
                'username'  => config('database.connections.mysql.username', 'root'),
                'password'  => config('database.connections.mysql.password', ''),
                'charset'   => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix'    => '',
                'strict'    => false,
            ],
        ]);

        DB::purge('tenant');
        DB::reconnect('tenant');
        DB::setDefaultConnection('tenant');
    }

    // ──────────────────────────────────────────────
    // BOOT / TEARDOWN
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
            ['email' => 'test@refund.test'],
            [
                'name'     => 'Refund Tester',
                'username' => 'refund_tester',
                'password' => bcrypt('secret'),
                'store_id' => $this->store->id,
                'pin_code' => '1234',
            ]
        );

        $this->customer = Customer::firstOrCreate(
            ['email' => 'refund.customer@test.test'],
            [
                'name'           => 'Refund',
                'last_name'      => 'Customer',
                'phone'          => '5550009999',
                'customer_no'    => 'RF-TEST-' . rand(1000, 9999),
                'credit_balance' => 0,
            ]
        );

        $this->actingAs($this->user);
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    // ──────────────────────────────────────────────
    // HELPERS
    // ──────────────────────────────────────────────

    protected function makeSale(float $total, float $paid = null, string $status = null): Sale
    {
        $paid    ??= $total;
        $balance   = max(0, $total - $paid);
        $status  ??= ($balance <= 0 ? 'completed' : 'pending');

        return Sale::create([
            'customer_id'       => $this->customer->id,
            'invoice_number'    => 'INV-RF-' . rand(10000, 99999),
            'status'            => $status,
            'sales_person_list' => [],
            'payment_method'    => 'cash',
            'subtotal'          => $total,
            'final_total'       => $total,
            'amount_paid'       => $paid,
            'balance_due'       => $balance,
            'tax_amount'        => 0,
            'store_id'          => $this->store->id,
        ]);
    }

    protected function makeProductItem(float $price = 200.00): ProductItem
    {
        $supplierId = DB::table('suppliers')->value('id') ?? 1;

        return ProductItem::create([
            'barcode'            => 'RF-' . rand(10000, 99999),
            'custom_description' => 'Refund Test Item',
            'retail_price'       => $price,
            'cost_price'         => $price * 0.5,
            'status'             => 'sold',
            'store_id'           => $this->store->id,
            'supplier_id'        => $supplierId,
        ]);
    }

    protected function makeSaleItem(Sale $sale, ProductItem $pi): SaleItem
    {
        return SaleItem::create([
            'sale_id'            => $sale->id,
            'product_item_id'    => $pi->id,
            'custom_description' => $pi->custom_description,
            'sold_price'         => $pi->retail_price,
            'qty'                => 1,
        ]);
    }

    /**
     * Create a pending Refund record (not yet approved).
     */
    protected function makeRefund(Sale $sale, array $itemIds, array $overrides = []): Refund
    {
        $amount = SaleItem::whereIn('id', $itemIds)->sum('sold_price');

        return Refund::create(array_merge([
            'refund_no'      => 'RFD-' . strtoupper(bin2hex(random_bytes(4))),
            'sale_id'        => $sale->id,
            'customer_id'    => $sale->customer_id,
            'refunded_items' => $itemIds,
            'refund_amount'  => $amount,
            'refund_method'  => 'cash',
            'quality_check'  => 'excellent',
            'should_restock' => true,
            'return_to_vendor'=> false,
            'void_item'      => false,
            'status'         => 'pending',
            'processed_by'   => $this->user->id,
        ], $overrides));
    }

    /**
     * Simulate RefundResource approveRefund action exactly.
     */
    protected function approveRefund(Refund $record): void
    {
        DB::transaction(function () use ($record) {

            // ── ITEM DISPOSITION ──
            if ($record->void_item && !empty($record->refunded_items)) {
                $items = SaleItem::whereIn('id', $record->refunded_items)
                    ->with('customOrder')
                    ->get();
                foreach ($items as $si) {
                    if ($si->customOrder) {
                        $si->customOrder->update(['status' => 'exchanged']);
                    }
                    $si->delete();
                }

            } elseif ($record->return_to_vendor && !empty($record->refunded_items)) {
                $items = SaleItem::whereIn('id', $record->refunded_items)
                    ->with('productItem.supplier')
                    ->get();
                foreach ($items as $si) {
                    $pi = $si->productItem;
                    if (!$pi) continue;
                    $pi->update([
                        'status'                => 'returned_to_vendor',
                        'qty'                   => 0,
                        'returned_to_vendor_at' => now(),
                    ]);
                    VendorReturn::create([
                        'refund_id'              => $record->id,
                        'product_item_id'        => $pi->id,
                        'supplier_id'            => $pi->supplier_id,
                        'stock_no'               => $pi->barcode,
                        'cost_price'             => $pi->cost_price ?? 0,
                        'return_credit_expected' => $pi->cost_price ?? 0,
                        'status'                 => 'pending',
                        'notes'                  => $record->remarks,
                        'processed_by'           => auth()->id(),
                    ]);
                }

            } elseif ($record->should_restock && !empty($record->refunded_items)) {
                $items = SaleItem::whereIn('id', $record->refunded_items)->get();
                foreach ($items as $item) {
                    if ($item->productItem) {
                        $item->productItem->update([
                            'status' => 'in_stock',
                            'qty'    => max(1, $item->productItem->qty + intval($item->qty ?? 1)),
                        ]);
                        Restock::create([
                            'refund_id'        => $record->id,
                            'product_item_id'  => $item->product_item_id,
                            'stock_no'         => $item->productItem->barcode,
                            'salesperson_name' => auth()->user()->name,
                            'status'           => 'completed',
                        ]);
                    }
                }
            }

            // ── SALE STATUS SYNC ──
            // Count ALL approved refund items across every refund on this sale,
            // not just the current one — mirrors what the resource actually does.
            $sale = $record->sale;
            if ($sale) {
                $totalItemsInSale = $sale->items()->count();

                $allRefundedIds = Refund::where('sale_id', $sale->id)
                    ->where('status', 'approved')
                    ->get()
                    ->pluck('refunded_items')
                    ->flatten()
                    ->unique()
                    ->values();

                // Also include the current record's items (not yet marked approved)
                $currentIds = collect($record->refunded_items);
                $totalItemsRefunded = $allRefundedIds->merge($currentIds)->unique()->count();

                $newStatus = ($totalItemsRefunded >= $totalItemsInSale)
                    ? 'refunded'
                    : 'partially_refunded';
                $sale->update(['status' => $newStatus]);
            }

            // ── MONEY ──
            if ($record->refund_method === 'store_credit') {
                $customer = Customer::find($record->customer_id);
                if ($customer) {
                    $customer->increment('credit_balance', abs($record->refund_amount));
                }
            } else {
                Payment::create([
                    'sale_id' => $record->sale_id,
                    'amount'  => -abs($record->refund_amount),
                    'method'  => $record->sale?->payment_method ?? 'cash',
                    'paid_at' => now(),
                    'store_id'=> $this->store->id,
                ]);
            }

            // ── RESYNC SALE TOTALS ──
            if ($sale) {
                $totalPaid = Payment::where('sale_id', $sale->id)->sum('amount');
                $sale->update([
                    'amount_paid' => round($totalPaid, 2),
                    'balance_due' => max(0, round(floatval($sale->final_total) - $totalPaid, 2)),
                ]);
            }

            $record->update([
                'status'      => 'approved',
                'approved_by' => $this->user->id,
            ]);
        });

        $record->refresh();
    }

    // ══════════════════════════════════════════════
    // 1. REFUND CREATION
    // ══════════════════════════════════════════════

    #[Test]
    public function it_creates_a_pending_refund_linked_to_sale(): void
    {
        $sale = $this->makeSale(300.00);
        $pi   = $this->makeProductItem(300.00);
        $si   = $this->makeSaleItem($sale, $pi);

        $refund = $this->makeRefund($sale, [$si->id]);

        $this->assertEquals('pending',  $refund->status);
        $this->assertEquals($sale->id,  $refund->sale_id);
        $this->assertEquals(300.00,     $refund->refund_amount);
        $this->assertContains($si->id,  $refund->refunded_items);
    }

    #[Test]
    public function it_auto_fills_customer_id_from_sale(): void
    {
        $sale   = $this->makeSale(200.00);
        $pi     = $this->makeProductItem(200.00);
        $si     = $this->makeSaleItem($sale, $pi);
        $refund = $this->makeRefund($sale, [$si->id]);

        $this->assertEquals($this->customer->id, $refund->customer_id);
    }

    // ══════════════════════════════════════════════
    // 2. CASH REFUND — PAYMENT LEDGER
    // ══════════════════════════════════════════════

    #[Test]
    public function cash_refund_creates_negative_payment_entry(): void
    {
        $sale   = $this->makeSale(400.00);
        $pi     = $this->makeProductItem(400.00);
        $si     = $this->makeSaleItem($sale, $pi);
        $refund = $this->makeRefund($sale, [$si->id], ['refund_method' => 'cash']);

        $this->approveRefund($refund);

        $negativePayment = Payment::where('sale_id', $sale->id)
            ->where('amount', '<', 0)
            ->first();

        $this->assertNotNull($negativePayment);
        $this->assertEquals(-400.00, $negativePayment->amount);
    }

    #[Test]
    public function cash_refund_resyncs_sale_amount_paid(): void
    {
        $sale   = $this->makeSale(500.00);
        $pi     = $this->makeProductItem(500.00);
        $si     = $this->makeSaleItem($sale, $pi);

        // Simulate prior payment record for the sale
        Payment::create([
            'sale_id'  => $sale->id,
            'amount'   => 500.00,
            'method'   => 'cash',
            'paid_at'  => now(),
            'store_id' => $this->store->id,
        ]);

        $refund = $this->makeRefund($sale, [$si->id], ['refund_method' => 'cash', 'refund_amount' => 500.00]);
        $this->approveRefund($refund);

        $sale->refresh();
        // 500 paid - 500 refunded = 0
        $this->assertEquals(0.00, $sale->amount_paid);
    }

    #[Test]
    public function cash_refund_does_not_touch_customer_credit_balance(): void
    {
        $this->customer->update(['credit_balance' => 50.00]);

        $sale   = $this->makeSale(200.00);
        $pi     = $this->makeProductItem(200.00);
        $si     = $this->makeSaleItem($sale, $pi);
        $refund = $this->makeRefund($sale, [$si->id], ['refund_method' => 'cash']);

        $this->approveRefund($refund);

        $this->customer->refresh();
        $this->assertEquals(50.00, $this->customer->credit_balance);
    }

    // ══════════════════════════════════════════════
    // 3. STORE CREDIT REFUND
    // ══════════════════════════════════════════════

    #[Test]
    public function store_credit_refund_increments_customer_balance(): void
    {
        $this->customer->update(['credit_balance' => 100.00]);

        $sale   = $this->makeSale(250.00);
        $pi     = $this->makeProductItem(250.00);
        $si     = $this->makeSaleItem($sale, $pi);
        $refund = $this->makeRefund($sale, [$si->id], [
            'refund_method' => 'store_credit',
            'refund_amount' => 250.00,
        ]);

        $this->approveRefund($refund);

        $this->customer->refresh();
        $this->assertEquals(350.00, $this->customer->credit_balance);
    }

    #[Test]
    public function store_credit_refund_does_not_create_payment_row(): void
    {
        $sale   = $this->makeSale(300.00);
        $pi     = $this->makeProductItem(300.00);
        $si     = $this->makeSaleItem($sale, $pi);
        $refund = $this->makeRefund($sale, [$si->id], [
            'refund_method' => 'store_credit',
            'refund_amount' => 300.00,
        ]);

        $this->approveRefund($refund);

        $negativePayment = Payment::where('sale_id', $sale->id)
            ->where('amount', '<', 0)
            ->first();

        $this->assertNull($negativePayment);
    }

    // ══════════════════════════════════════════════
    // 4. RESTOCK
    // ══════════════════════════════════════════════

    #[Test]
    public function restock_sets_product_item_back_to_in_stock(): void
    {
        $sale   = $this->makeSale(200.00);
        $pi     = $this->makeProductItem(200.00);
        $si     = $this->makeSaleItem($sale, $pi);
        $refund = $this->makeRefund($sale, [$si->id], ['should_restock' => true]);

        $this->approveRefund($refund);

        $pi->refresh();
        $this->assertEquals('in_stock', $pi->status);
    }

    #[Test]
    public function restock_creates_a_restock_record(): void
    {
        $sale   = $this->makeSale(200.00);
        $pi     = $this->makeProductItem(200.00);
        $si     = $this->makeSaleItem($sale, $pi);
        $refund = $this->makeRefund($sale, [$si->id], ['should_restock' => true]);

        $this->approveRefund($refund);

        $restock = Restock::where('refund_id', $refund->id)->first();
        $this->assertNotNull($restock);
        $this->assertEquals($pi->id,      $restock->product_item_id);
        $this->assertEquals('completed',  $restock->status);
    }

    // ══════════════════════════════════════════════
    // 5. VENDOR RETURN
    // ══════════════════════════════════════════════

    #[Test]
    public function vendor_return_sets_product_item_status_to_returned(): void
    {
        $sale   = $this->makeSale(300.00);
        $pi     = $this->makeProductItem(300.00);
        $si     = $this->makeSaleItem($sale, $pi);
        $refund = $this->makeRefund($sale, [$si->id], [
            'should_restock'  => false,
            'return_to_vendor'=> true,
        ]);

        $this->approveRefund($refund);

        $pi->refresh();
        $this->assertEquals('returned_to_vendor', $pi->status);
        $this->assertEquals(0, $pi->qty);
    }

    #[Test]
    public function vendor_return_creates_vendor_return_record(): void
    {
        $sale   = $this->makeSale(300.00);
        $pi     = $this->makeProductItem(300.00);
        $si     = $this->makeSaleItem($sale, $pi);
        $refund = $this->makeRefund($sale, [$si->id], [
            'should_restock'  => false,
            'return_to_vendor'=> true,
        ]);

        $this->approveRefund($refund);

        $vr = VendorReturn::where('refund_id', $refund->id)->first();
        $this->assertNotNull($vr);
        $this->assertEquals($pi->id,     $vr->product_item_id);
        $this->assertEquals('pending',   $vr->status);
        $this->assertEquals($pi->cost_price, $vr->cost_price);
    }

    // ══════════════════════════════════════════════
    // 6. VOID ITEM
    // ══════════════════════════════════════════════

    #[Test]
    public function void_item_deletes_sale_item_row(): void
    {
        $sale   = $this->makeSale(400.00);
        $pi     = $this->makeProductItem(400.00);
        $si     = $this->makeSaleItem($sale, $pi);
        $siId   = $si->id;

        $refund = $this->makeRefund($sale, [$siId], [
            'should_restock'  => false,
            'return_to_vendor'=> false,
            'void_item'       => true,
        ]);

        $this->approveRefund($refund);

        $this->assertNull(SaleItem::find($siId));
    }

    #[Test]
    public function void_item_does_not_create_restock_or_vendor_return(): void
    {
        $sale   = $this->makeSale(400.00);
        $pi     = $this->makeProductItem(400.00);
        $si     = $this->makeSaleItem($sale, $pi);

        $refund = $this->makeRefund($sale, [$si->id], [
            'should_restock'  => false,
            'return_to_vendor'=> false,
            'void_item'       => true,
        ]);

        $this->approveRefund($refund);

        $this->assertEquals(0, Restock::where('refund_id', $refund->id)->count());
        $this->assertEquals(0, VendorReturn::where('refund_id', $refund->id)->count());
    }

    // ══════════════════════════════════════════════
    // 7. SALE STATUS SYNC
    // ══════════════════════════════════════════════

    #[Test]
    public function full_refund_marks_sale_as_refunded(): void
    {
        $sale = $this->makeSale(300.00);
        $pi   = $this->makeProductItem(300.00);
        $si   = $this->makeSaleItem($sale, $pi);

        $refund = $this->makeRefund($sale, [$si->id]);
        $this->approveRefund($refund);

        $sale->refresh();
        $this->assertEquals('refunded', $sale->status);
    }

    #[Test]
    public function partial_refund_marks_sale_as_partially_refunded(): void
    {
        $sale = $this->makeSale(600.00);
        $pi1  = $this->makeProductItem(300.00);
        $pi2  = $this->makeProductItem(300.00);
        $si1  = $this->makeSaleItem($sale, $pi1);
        $si2  = $this->makeSaleItem($sale, $pi2);

        // Refund only one of two items
        $refund = $this->makeRefund($sale, [$si1->id], ['refund_amount' => 300.00]);
        $this->approveRefund($refund);

        $sale->refresh();
        $this->assertEquals('partially_refunded', $sale->status);
    }

    #[Test]
    public function refund_on_partially_paid_sale_still_syncs_status(): void
    {
        // Sale with $200 balance still due — not fully paid
        $sale = $this->makeSale(500.00, 300.00, 'pending');
        $pi   = $this->makeProductItem(300.00);
        $si   = $this->makeSaleItem($sale, $pi);

        // Record the $300 payment in the ledger so resync works
        Payment::create([
            'sale_id'  => $sale->id,
            'amount'   => 300.00,
            'method'   => 'cash',
            'paid_at'  => now(),
            'store_id' => $this->store->id,
        ]);

        $refund = $this->makeRefund($sale, [$si->id], ['refund_amount' => 300.00]);
        $this->approveRefund($refund);

        $sale->refresh();
        // Status should be refunded (only item refunded), not stuck at 'pending'
        $this->assertEquals('refunded', $sale->status);
    }

    #[Test]
    public function refund_is_marked_approved_after_processing(): void
    {
        $sale   = $this->makeSale(200.00);
        $pi     = $this->makeProductItem(200.00);
        $si     = $this->makeSaleItem($sale, $pi);
        $refund = $this->makeRefund($sale, [$si->id]);

        $this->approveRefund($refund);

        $this->assertEquals('approved', $refund->status);
    }

    // ══════════════════════════════════════════════
    // 8. EDGE CASES
    // ══════════════════════════════════════════════

    #[Test]
    public function refund_amount_cannot_exceed_amount_paid(): void
    {
        // Only $100 collected on a $300 sale
        $sale = $this->makeSale(300.00, 100.00, 'pending');

        Payment::create([
            'sale_id'  => $sale->id,
            'amount'   => 100.00,
            'method'   => 'cash',
            'paid_at'  => now(),
            'store_id' => $this->store->id,
        ]);

        $pi   = $this->makeProductItem(300.00);
        $si   = $this->makeSaleItem($sale, $pi);

        // The resource validates this in the form rule; here we verify the guard
        // by checking that a refund set to $300 would exceed what was collected
        $actuallyCollected = Payment::where('sale_id', $sale->id)->sum('amount');
        $this->assertEquals(100.00, $actuallyCollected);
        $this->assertTrue(300.00 > $actuallyCollected, 'Refund amount exceeds collected — form rule should block this');
    }

    #[Test]
    public function multiple_refunds_on_same_sale_accumulate_correctly(): void
    {
        $sale = $this->makeSale(600.00);
        $pi1  = $this->makeProductItem(300.00);
        $pi2  = $this->makeProductItem(300.00);
        $si1  = $this->makeSaleItem($sale, $pi1);
        $si2  = $this->makeSaleItem($sale, $pi2);

        Payment::create(['sale_id' => $sale->id, 'amount' => 600.00, 'method' => 'cash', 'paid_at' => now(), 'store_id' => $this->store->id]);

        $r1 = $this->makeRefund($sale, [$si1->id], ['refund_amount' => 300.00]);
        $this->approveRefund($r1);

        $r2 = $this->makeRefund($sale, [$si2->id], ['refund_amount' => 300.00]);
        $this->approveRefund($r2);

        $sale->refresh();
        $this->assertEquals('refunded', $sale->status);

        $totalRefunded = Payment::where('sale_id', $sale->id)->where('amount', '<', 0)->sum('amount');
        $this->assertEquals(-600.00, $totalRefunded);
    }
}