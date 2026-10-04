<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomOrder;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * CustomOrderIntegrationTest
 *
 * Covers payment-sync scenarios between CustomOrder ↔ Payment ledger ↔ Sale.
 * Mirrors LaybuyIntegrationTest.php / RepairIntegrationTest.php structure.
 *
 * Run:
 *   php artisan test tests/Feature/CustomOrderIntegrationTest.php
 */
class CustomOrderIntegrationTest extends TestCase
{
    protected string $tenantId       = 'lxd';
    protected string $tenantDatabase = 'tenantlxd';

    protected Store    $store;
    protected User     $user;
    protected Customer $customer;

    // ──────────────────────────────────────────────
    // BOOT
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
            ['email' => 'test@customorder.test'],
            [
                'name'     => 'CustomOrder Tester',
                'username' => 'customorder_tester',
                'password' => bcrypt('secret'),
                'store_id' => $this->store->id,
                'pin_code' => '1234',
            ]
        );

        $this->customer = Customer::firstOrCreate(
            ['email' => 'customorder.customer@test.test'],
            [
                'name'        => 'Custom',
                'last_name'   => 'Customer',
                'phone'       => '5550005678',
                'customer_no' => 'CO-TEST-' . rand(1000, 9999),
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

    /**
     * Build a CustomOrder with sane defaults.
     */
    protected function makeOrder(array $overrides = []): CustomOrder
    {
        $quotedPrice     = $overrides['quoted_price']     ?? 500.00;
        $discountAmount  = $overrides['discount_amount']  ?? 0.00;
        $warrantyCharge  = $overrides['warranty_charge']  ?? 0.00;
        $tradeInValue    = $overrides['trade_in_value']   ?? 0.00;
        $isTaxFree       = $overrides['is_tax_free']      ?? true;   // avoid needing site_settings
        $amountPaid      = $overrides['amount_paid']      ?? 0.00;

        // grand_total = quoted_price - discount_amount + warranty_charge - trade_in_value
        $grandTotal  = $quotedPrice - $discountAmount + $warrantyCharge - $tradeInValue;
        $balanceDue  = max(0, $grandTotal - $amountPaid);

        return CustomOrder::create(array_merge([
            'order_no'        => 'CO-' . rand(10000, 99999),
            'customer_id'     => $this->customer->id,
            'staff_id'        => $this->user->id,
            'order_type'      => 'ring',
            'product_name'    => 'Test Ring',
            'metal_type'      => 'gold',
            'quoted_price'    => $quotedPrice,
            'discount_percent'=> 0,
            'discount_amount' => $discountAmount,
            'warranty_charge' => $warrantyCharge,
            'has_warranty'    => $warrantyCharge > 0,
            'trade_in_value'  => $tradeInValue,
            'has_trade_in'    => $tradeInValue > 0,
            'due_date'        => now()->addMonths(2)->toDateString(),
            'design_notes'    => 'Integration test order',
            'status'          => $overrides['status'] ?? 'quoted',
            'is_tax_free'     => $isTaxFree,
            'amount_paid'     => $amountPaid,
            'balance_due'     => $balanceDue,
            'items'           => [],
        ], $overrides));
    }

    /**
     * Record a deposit/payment against a CustomOrder,
     * mirroring what CreateCustomOrder::afterCreate() / recordPaymentAction() do.
     */
    protected function recordPayment(
        CustomOrder    $order,
        float          $amount,
        string         $method  = 'CASH',
        ?\Carbon\Carbon $paidAt = null
    ): void {
        $paidAt ??= now();

        Payment::create([
            'sale_id'         => $order->sale_id,   // null until converted
            'custom_order_id' => $order->id,
            'amount'          => $amount,
            'method'          => $method,
            'paid_at'         => $paidAt,
            'store_id'        => $this->store->id,
        ]);

        $newPaid    = $order->amount_paid + $amount;
        $newBalance = max(0, $order->balance_due - $amount);

        $order->update([
            'amount_paid' => $newPaid,
            'balance_due' => $newBalance,
            'status'      => $newBalance <= 0 ? 'received' : $order->status,
        ]);

        $order->refresh();
    }

    /**
     * Simulate createSaleDirectly() — creates a Sale from a fully-paid CustomOrder,
     * links it, and stamps the existing payments with sale_id.
     */
    protected function convertToSale(CustomOrder $order): Sale
    {
        $sale = Sale::create([
            'customer_id'       => $order->customer_id,
            'invoice_number'    => 'INV-CO-' . rand(10000, 99999),
            'status'            => 'completed',
            'sales_person_list' => [],
            'payment_method'    => 'custom_order',
            'subtotal'          => $order->quoted_price,
            'final_total'       => $order->quoted_price - $order->discount_amount
                                   + $order->warranty_charge - $order->trade_in_value,
            'amount_paid'       => $order->amount_paid,
            'balance_due'       => 0,
            'tax_amount'        => 0,
            'store_id'          => $this->store->id,
        ]);

        $order->update(['sale_id' => $sale->id, 'status' => 'completed']);
        $order->refresh();

        // Backfill sale_id on existing payment rows
        Payment::where('custom_order_id', $order->id)
            ->whereNull('sale_id')
            ->update(['sale_id' => $sale->id]);

        return $sale;
    }

    // ══════════════════════════════════════════════
    // 1. CREATION / INITIAL DEPOSIT
    // ══════════════════════════════════════════════

    /** @test */
    public function it_creates_a_custom_order_with_correct_balance(): void
    {
        $order = $this->makeOrder(['quoted_price' => 800.00, 'amount_paid' => 0]);

        $this->assertEquals(800.00, $order->quoted_price);
        $this->assertEquals(0.00,   $order->amount_paid);
        $this->assertEquals(800.00, $order->balance_due);
        $this->assertEquals('quoted', $order->status);
    }

    /** @test */
    public function it_records_initial_deposit_and_links_to_custom_order(): void
    {
        $order = $this->makeOrder(['quoted_price' => 600.00]);
        $this->recordPayment($order, 200.00, 'CASH');

        $order->refresh();
        $this->assertEquals(200.00, $order->amount_paid);
        $this->assertEquals(400.00, $order->balance_due);

        $payment = Payment::where('custom_order_id', $order->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals(200.00, $payment->amount);
        $this->assertEquals('CASH', $payment->method);
        $this->assertNull($payment->sale_id);   // no sale yet
    }

    /** @test */
    public function it_records_deposit_via_card_payment(): void
    {
        $order = $this->makeOrder(['quoted_price' => 1000.00]);
        $this->recordPayment($order, 300.00, 'CREDIT CARD');

        $payment = Payment::where('custom_order_id', $order->id)->first();
        $this->assertEquals('CREDIT CARD', $payment->method);
        $this->assertEquals(300.00, $payment->amount);
    }

    // ══════════════════════════════════════════════
    // 2. BALANCE CALCULATION VARIANTS
    // ══════════════════════════════════════════════

    /** @test */
    public function it_applies_discount_amount_to_balance(): void
    {
        $order = $this->makeOrder([
            'quoted_price'   => 500.00,
            'discount_amount'=> 50.00,
        ]);

        // grand_total = 500 - 50 = 450
        $this->assertEquals(450.00, $order->balance_due);
    }

    /** @test */
    public function it_adds_warranty_charge_to_balance(): void
    {
        $order = $this->makeOrder([
            'quoted_price'  => 500.00,
            'warranty_charge'=> 75.00,
            'has_warranty'  => true,
        ]);

        // grand_total = 500 + 75 = 575
        $this->assertEquals(575.00, $order->balance_due);
    }

    /** @test */
    public function it_subtracts_trade_in_value_from_balance(): void
    {
        $order = $this->makeOrder([
            'quoted_price' => 800.00,
            'trade_in_value'=> 150.00,
            'has_trade_in' => true,
        ]);

        // grand_total = 800 - 150 = 650
        $this->assertEquals(650.00, $order->balance_due);
    }

    /** @test */
    public function it_combines_discount_warranty_and_trade_in(): void
    {
        $order = $this->makeOrder([
            'quoted_price'   => 1000.00,
            'discount_amount'=> 100.00,
            'warranty_charge'=> 50.00,
            'trade_in_value' => 200.00,
        ]);

        // 1000 - 100 + 50 - 200 = 750
        $this->assertEquals(750.00, $order->balance_due);
    }

    // ══════════════════════════════════════════════
    // 3. MULTIPLE PAYMENTS / PARTIAL PAYMENTS
    // ══════════════════════════════════════════════

    /** @test */
    public function it_accumulates_multiple_payments_correctly(): void
    {
        $order = $this->makeOrder(['quoted_price' => 900.00]);

        $this->recordPayment($order, 300.00);
        $this->recordPayment($order, 300.00);

        $order->refresh();
        $this->assertEquals(600.00, $order->amount_paid);
        $this->assertEquals(300.00, $order->balance_due);
        $this->assertEquals(2, Payment::where('custom_order_id', $order->id)->count());
    }

    /** @test */
    public function it_marks_order_received_when_fully_paid(): void
    {
        $order = $this->makeOrder(['quoted_price' => 400.00]);

        $this->recordPayment($order, 400.00);

        $order->refresh();
        $this->assertEquals(0.00,       $order->balance_due);
        $this->assertEquals(400.00,     $order->amount_paid);
        $this->assertEquals('received', $order->status);
    }

    /** @test */
    public function it_does_not_allow_negative_balance(): void
    {
        $order = $this->makeOrder(['quoted_price' => 300.00]);

        // Overpayment — balance should clamp at 0
        $this->recordPayment($order, 400.00);

        $order->refresh();
        $this->assertEquals(0.00, $order->balance_due);
    }

    /** @test */
    public function it_splits_payment_across_two_methods(): void
    {
        $order = $this->makeOrder(['quoted_price' => 500.00]);

        $this->recordPayment($order, 200.00, 'CASH');
        $this->recordPayment($order, 300.00, 'EFTPOS');

        $order->refresh();
        $this->assertEquals(500.00,     $order->amount_paid);
        $this->assertEquals(0.00,       $order->balance_due);
        $this->assertEquals('received', $order->status);

        $payments = Payment::where('custom_order_id', $order->id)->get();
        $this->assertCount(2, $payments);
        $this->assertEquals(200.00, $payments->where('method', 'CASH')->sum('amount'));
        $this->assertEquals(300.00, $payments->where('method', 'EFTPOS')->sum('amount'));
    }

    // ══════════════════════════════════════════════
    // 4. STATUS LIFECYCLE
    // ══════════════════════════════════════════════

    /** @test */
    public function it_starts_with_quoted_status(): void
    {
        $order = $this->makeOrder(['status' => 'quoted']);
        $this->assertEquals('quoted', $order->status);
    }

    /** @test */
    public function it_progresses_through_status_to_in_production(): void
    {
        $order = $this->makeOrder(['status' => 'approved']);
        $order->update(['status' => 'in_production']);
        $order->refresh();

        $this->assertEquals('in_production', $order->status);
    }

    /** @test */
    public function it_can_be_set_to_received_and_completed(): void
    {
        $order = $this->makeOrder(['status' => 'in_production']);
        $order->update(['status' => 'received']);
        $order->refresh();
        $this->assertEquals('received', $order->status);

        $order->update(['status' => 'completed']);
        $order->refresh();
        $this->assertEquals('completed', $order->status);
    }

    // ══════════════════════════════════════════════
    // 5. SALE CREATION (createSaleDirectly)
    // ══════════════════════════════════════════════

    /** @test */
    public function it_converts_fully_paid_order_to_sale(): void
    {
        $order = $this->makeOrder(['quoted_price' => 600.00]);
        $this->recordPayment($order, 600.00);
        $order->refresh();

        $sale = $this->convertToSale($order);
        $order->refresh();

        $this->assertNotNull($sale->id);
        $this->assertEquals($order->sale_id, $sale->id);
        $this->assertEquals('completed',     $order->status);
        $this->assertEquals('completed',     $sale->status);
        $this->assertEquals(600.00,          $sale->final_total);
    }

    /** @test */
    public function it_backfills_sale_id_onto_existing_payment_rows(): void
    {
        $order = $this->makeOrder(['quoted_price' => 700.00]);
        $this->recordPayment($order, 350.00);
        $this->recordPayment($order, 350.00);

        $sale = $this->convertToSale($order);

        $paymentCount = Payment::where('custom_order_id', $order->id)
            ->where('sale_id', $sale->id)
            ->count();

        $this->assertEquals(2, $paymentCount);
    }

    /** @test */
    public function it_links_sale_and_custom_order_via_foreign_key(): void
    {
        $order = $this->makeOrder(['quoted_price' => 500.00]);
        $this->recordPayment($order, 500.00);

        $sale = $this->convertToSale($order);
        $order->refresh();

        $this->assertEquals($sale->id, $order->sale_id);

        $fetchedSale = Sale::find($order->sale_id);
        $this->assertNotNull($fetchedSale);
        $this->assertEquals(500.00, $fetchedSale->amount_paid);
    }

    /** @test */
    public function it_carries_discount_into_sale_final_total(): void
    {
        $order = $this->makeOrder([
            'quoted_price'   => 1000.00,
            'discount_amount'=> 100.00,
        ]);
        // grand_total = 900
        $this->recordPayment($order, 900.00);

        $sale = $this->convertToSale($order);

        $this->assertEquals(900.00, $sale->final_total);
    }

    // ══════════════════════════════════════════════
    // 6. PAYMENT LEDGER INTEGRITY
    // ══════════════════════════════════════════════

    /** @test */
    public function payment_rows_belong_to_store(): void
    {
        $order = $this->makeOrder(['quoted_price' => 300.00]);
        $this->recordPayment($order, 150.00);

        $payment = Payment::where('custom_order_id', $order->id)->first();
        $this->assertEquals($this->store->id, $payment->store_id);
    }

    /** @test */
    public function payment_paid_at_is_recorded(): void
    {
        $order  = $this->makeOrder(['quoted_price' => 200.00]);
        $paidAt = now()->subDay();
        $this->recordPayment($order, 200.00, 'CASH', $paidAt);

        $payment = Payment::where('custom_order_id', $order->id)->first();
        $this->assertNotNull($payment->paid_at);
        $this->assertEquals(
            $paidAt->toDateString(),
            \Carbon\Carbon::parse($payment->paid_at)->toDateString()
        );
    }

    /** @test */
    public function payment_sum_matches_order_amount_paid(): void
    {
        $order = $this->makeOrder(['quoted_price' => 750.00]);

        $this->recordPayment($order, 250.00);
        $this->recordPayment($order, 250.00);
        $this->recordPayment($order, 250.00);

        $order->refresh();
        $ledgerTotal = Payment::where('custom_order_id', $order->id)->sum('amount');

        $this->assertEquals($order->amount_paid, $ledgerTotal);
        $this->assertEquals(750.00, $ledgerTotal);
    }
}