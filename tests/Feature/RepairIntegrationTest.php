<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Repair;
use App\Models\Sale;
use App\Models\Store;
use App\Models\Tenant;
use App\Models\User;
use App\Filament\Resources\RepairResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Tests\TestCase;

class RepairIntegrationTest extends TestCase
{
    protected string $tenantId       = 'lxd';
    protected string $tenantDatabase = 'tenantlxd';

    protected Tenant   $tenant;
    protected User     $user;
    protected Customer $customer;
    protected Store    $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();
        config(['tenancy.central_domains' => []]);
        Gate::before(fn () => true);

        $this->tenant = Tenant::find($this->tenantId);
        if (!$this->tenant) $this->fail("Tenant \"{$this->tenantId}\" not found.");

        tenancy()->initialize($this->tenant);
        $this->applyTenantConnection();

        DB::connection('tenant')->beginTransaction();

        $this->store    = Store::first() ?? Store::create(['name' => 'Repair Test Store']);
        $this->user     = User::where('email', 'nabin@gmail.com')->first()
            ?? User::create([
                'name' => 'Nabin Sapkota', 'email' => 'nabin@gmail.com',
                'password' => bcrypt('12345678'), 'pin_code' => '1234',
                'store_id' => $this->store->id, 'is_active' => true,
            ]);
        $this->customer = Customer::first() ?? Customer::create([
            'name' => 'Jane', 'last_name' => 'Smith',
            'phone' => '5559876543', 'customer_no' => 'CUST-RPR1',
        ]);

        $this->actingAs($this->user);
        Session::put('active_staff_id', $this->user->id);
    }

    protected function applyTenantConnection(): void
    {
        $central = config('database.connections.mysql', []);
        config(['database.connections.tenant' => array_merge($central, [
            'driver' => 'mysql', 'database' => $this->tenantDatabase,
        ])]);
        config(['database.default' => 'tenant']);
        DB::purge('tenant');
    }

    protected function tearDown(): void
    {
        try {
            $this->applyTenantConnection();
            if (DB::connection('tenant')->transactionLevel() > 0)
                DB::connection('tenant')->rollBack();
        } catch (\Throwable $e) {}
        tenancy()->end();
        parent::tearDown();
    }

    protected function makeRepair(float $cost = 200.00, ?int $saleId = null): Repair
    {
        $this->applyTenantConnection();
        return Repair::create([
            'repair_no'   => 'RPR-' . strtoupper(Str::random(5)),
            'customer_id' => $this->customer->id,
            'store_id'    => $this->store->id,
            'status'      => 'received',
            'sale_id'     => $saleId,
            'items'       => [[
                'item_description' => 'Gold Ring',
                'reported_issue'   => 'Resize to 7',
                'is_warranty'      => false,
                'is_tax_free'      => true,
                'services'         => [[
                    'job_type'       => 'Resize',
                    'estimated_cost' => $cost,
                    'final_cost'     => $cost,
                ]],
            ]],
        ]);
    }

    protected function makeSale(float $total = 300.00): Sale
    {
        $this->applyTenantConnection();
        return Sale::create([
            'invoice_number'    => 'L-TEST-' . strtoupper(Str::random(6)),
            'customer_id'       => $this->customer->id,
            'store_id'          => $this->store->id,
            'final_total'       => $total,
            'amount_paid'       => $total,
            'balance_due'       => 0,
            'status'            => 'completed',
            'sales_person_list' => [],
            'payment_method'    => 'CASH',
        ]);
    }

    // ── 1. calculateRepairTotal returns correct amounts ──────────────────────
    public function test_calculate_repair_total_with_no_payments()
    {
        $repair = $this->makeRepair(200.00);
        $this->applyTenantConnection();

        $calc = RepairResource::calculateRepairTotal($repair);

        $this->assertEquals(200.00, $calc['total']);
        $this->assertEquals(0.00,   $calc['paid']);
        $this->assertEquals(200.00, $calc['balance']);
    }

    // ── 2. Payment via repair_id is counted ─────────────────────────────────
    public function test_payment_linked_by_repair_id_reduces_balance()
    {
        $repair = $this->makeRepair(500.00);
        $this->applyTenantConnection();

        Payment::create([
            'repair_id' => $repair->id,
            'amount'    => 200.00,
            'method'    => 'CASH',
            'paid_at'   => now(),
            'store_id'  => $this->store->id,
        ]);

        $calc = RepairResource::calculateRepairTotal($repair->fresh());

        $this->assertEquals(500.00, $calc['total']);
        $this->assertEquals(200.00, $calc['paid']);
        $this->assertEquals(300.00, $calc['balance']);
    }

    // ── 3. Payment linked only via sale_id (no repair_id) is also counted ───
    public function test_payment_linked_by_sale_id_only_reduces_repair_balance()
    {
        $sale = $this->makeSale(300.00);
        $this->applyTenantConnection();

        $repair = $this->makeRepair(300.00, $sale->id);
        $this->applyTenantConnection();

        Payment::create([
            'sale_id'   => $sale->id,
            'repair_id' => null,
            'amount'    => 300.00,
            'method'    => 'VISA',
            'paid_at'   => now(),
            'store_id'  => $this->store->id,
        ]);

        $calc = RepairResource::calculateRepairTotal($repair->fresh());

        $this->assertEquals(300.00, $calc['total']);
        $this->assertEquals(300.00, $calc['paid']);
        $this->assertEquals(0.00,   $calc['balance']);
    }

    // ── 4. Add Deposit stamps both repair_id AND sale_id ────────────────────
    public function test_add_deposit_stamps_sale_id_on_payment()
    {
        $sale = $this->makeSale(400.00);
        $sale->update(['amount_paid' => 0, 'balance_due' => 400.00, 'status' => 'pending']);
        $this->applyTenantConnection();

        $repair = $this->makeRepair(400.00, $sale->id);
        $this->applyTenantConnection();

        $freshRecord = $repair->fresh();
        Payment::create([
            'repair_id' => $freshRecord->id,
            'sale_id'   => $freshRecord->sale_id,
            'amount'    => 150.00,
            'method'    => 'DEBIT CARD',
            'paid_at'   => now(),
            'store_id'  => $this->store->id,
        ]);

        $this->applyTenantConnection();

        $this->assertDatabaseHas('payments', [
            'repair_id' => $repair->id,
            'sale_id'   => $sale->id,
            'amount'    => 150.00,
            'method'    => 'DEBIT CARD',
        ]);
    }

    // ── 5. Deduplication blocks double-submit within 15 seconds ─────────────
    public function test_duplicate_deposit_within_15_seconds_is_blocked()
    {
        $repair = $this->makeRepair(500.00);
        $this->applyTenantConnection();

        $amt    = 250.00;
        $method = 'CASH';

        Payment::create([
            'repair_id' => $repair->id,
            'amount'    => $amt,
            'method'    => $method,
            'paid_at'   => now(),
            'store_id'  => $this->store->id,
        ]);

        $alreadyExists = Payment::where('repair_id', $repair->id)
            ->where('amount', $amt)
            ->where('method', $method)
            ->where('paid_at', '>=', now()->subSeconds(15))
            ->exists();

        $this->assertTrue($alreadyExists);
        $this->assertEquals(1, Payment::where('repair_id', $repair->id)->count());
    }

    // ── 6. createSaleFromRepair re-points all payments to the new sale ───────
    public function test_create_sale_from_repair_repoints_payments()
    {
        $repair = $this->makeRepair(600.00);
        $this->applyTenantConnection();

        Payment::create(['repair_id' => $repair->id, 'amount' => 200.00, 'method' => 'CASH', 'paid_at' => now(), 'store_id' => $this->store->id]);
        Payment::create(['repair_id' => $repair->id, 'amount' => 400.00, 'method' => 'VISA', 'paid_at' => now(), 'store_id' => $this->store->id]);

        $this->applyTenantConnection();

        $sale = RepairResource::createSaleFromRepair($repair->fresh());

        $this->applyTenantConnection();

        $unlinked = Payment::where('repair_id', $repair->id)->whereNull('sale_id')->count();
        $this->assertEquals(0, $unlinked);

        $linked = Payment::where('repair_id', $repair->id)->where('sale_id', $sale->id)->count();
        $this->assertEquals(2, $linked);
        $this->assertEquals($sale->id, $repair->fresh()->sale_id);
    }

    // ── 7. EOD query counts each payment row exactly once ───────────────────
    public function test_eod_does_not_double_count_repair_payments()
    {
        $repair = $this->makeRepair(300.00);
        $this->applyTenantConnection();

        $sale = $this->makeSale(300.00);
        $this->applyTenantConnection();

        $uniqueTime = now()->subYear();

        Payment::create([
            'repair_id' => $repair->id,
            'sale_id'   => $sale->id,
            'amount'    => 300.00,
            'method'    => 'CASH',
            'paid_at'   => $uniqueTime,
            'store_id'  => $this->store->id,
        ]);

        $this->applyTenantConnection();

        $start    = $uniqueTime->copy()->subMinute();
        $end      = $uniqueTime->copy()->addMinute();
        $payments = Payment::whereBetween('paid_at', [$start, $end])->get();
        $total    = $payments->sum('amount');

        $this->assertEquals(300.00, $total);
        $this->assertEquals(1, $payments->count());
    }

    // ── 8. Sale sync after Add Deposit updates sale.amount_paid ─────────────
    public function test_add_deposit_syncs_sale_totals()
    {
        $sale = $this->makeSale(500.00);
        $sale->update(['amount_paid' => 0, 'balance_due' => 500.00, 'status' => 'pending']);
        $this->applyTenantConnection();

        $repair = $this->makeRepair(500.00, $sale->id);
        $this->applyTenantConnection();

        Payment::create([
            'repair_id' => $repair->id,
            'sale_id'   => $sale->id,
            'amount'    => 500.00,
            'method'    => 'AMEX',
            'paid_at'   => now(),
            'store_id'  => $this->store->id,
        ]);

        $totalPaid  = Payment::where(function ($q) use ($sale, $repair) {
            $q->where('sale_id', $sale->id)->orWhere('repair_id', $repair->id);
        })->sum('amount');

        $newBalance = max(0, round($sale->final_total - $totalPaid, 2));
        $sale->update([
            'amount_paid' => round($totalPaid, 2),
            'balance_due' => $newBalance,
            'status'      => $newBalance <= 0.01 ? 'completed' : $sale->status,
        ]);

        $this->applyTenantConnection();
        $sale->refresh();

        $this->assertEquals(500.00,      $sale->amount_paid);
        $this->assertEquals(0.00,        $sale->balance_due);
        $this->assertEquals('completed', $sale->status);
    }

    // ── 9. Bill to POS links all prior deposits to new sale ─────────────────
    public function test_bill_to_pos_links_existing_deposits_to_sale()
    {
        $repair = $this->makeRepair(800.00);
        $this->applyTenantConnection();

        Payment::create(['repair_id' => $repair->id, 'amount' => 300.00, 'method' => 'CASH', 'paid_at' => now(), 'store_id' => $this->store->id]);
        Payment::create(['repair_id' => $repair->id, 'amount' => 500.00, 'method' => 'VISA', 'paid_at' => now(), 'store_id' => $this->store->id]);

        $this->applyTenantConnection();

        $sale = RepairResource::createSaleFromRepair($repair->fresh());

        $this->applyTenantConnection();

        $allLinked = Payment::where('repair_id', $repair->id)->where('sale_id', $sale->id)->sum('amount');

        $this->assertEquals(800.00,      $allLinked);
        $this->assertEquals('completed', $sale->status);
        $this->assertEquals(800.00,      $sale->amount_paid);
        $this->assertEquals(0.00,        $sale->balance_due);
    }

    // ── 10. Fully paid repair shows zero balance ─────────────────────────────
    public function test_fully_paid_repair_shows_zero_balance()
    {
        $repair = $this->makeRepair(250.00);
        $this->applyTenantConnection();

        Payment::create([
            'repair_id' => $repair->id,
            'amount'    => 250.00,
            'method'    => 'MASTERCARD',
            'paid_at'   => now(),
            'store_id'  => $this->store->id,
        ]);

        $calc = RepairResource::calculateRepairTotal($repair->fresh());

        $this->assertEquals(250.00, $calc['paid']);
        $this->assertEquals(0.00,   $calc['balance']);
    }
}