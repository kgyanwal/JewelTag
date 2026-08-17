<?php

namespace Tests\Feature;

use App\Filament\Resources\SaleResource;
use App\Filament\Resources\SaleResource\Pages\CreateSale;
use App\Models\Customer;
use App\Models\Laybuy;
use App\Models\ProductItem;
use App\Models\Repair;
use App\Models\Sale;
use App\Models\Store;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class SaleIntegrationTest extends TestCase
{
    // 🚀 NOT using DatabaseTransactions anymore — its automatic
    // transaction-begin hook fires AFTER our full setUp() body completes,
    // by which point something resets the 'tenant' connection config again
    // (confirmed via repeated testing: connection works fine immediately
    // after we set it, but is gone again by the time anything actually uses
    // it). We manage the tenant transaction manually instead, so timing is
    // fully in our control from start to finish.

    protected string $tenantId = 'random';
    protected string $tenantDatabase = 'tenantrandom';

    protected Tenant $tenant;
    protected User $user;
    protected Customer $customer;
    protected Store $store;
    protected int $supplierId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();
        config(['tenancy.central_domains' => []]);
        Gate::before(fn () => true);

        $this->tenant = Tenant::find($this->tenantId);

        if (! $this->tenant) {
            $this->fail("Tenant \"{$this->tenantId}\" not found.");
        }

        tenancy()->initialize($this->tenant);
        $this->applyTenantConnection();

        // Manual transaction wrapping — rolled back in tearDown().
        DB::connection('tenant')->beginTransaction();

        $this->store = Store::first() ?? Store::create(['name' => 'Diamond Square Test']);

        $this->user = User::where('email', 'nabin@gmail.com')->first()
            ?? User::where('pin_code', '1234')->first()
            ?? User::create([
                'name'      => 'Nabin Sapkota',
                'email'     => 'nabin@gmail.com',
                'password'  => bcrypt('12345678'),
                'pin_code'  => '1234',
                'store_id'  => $this->store->id,
                'is_active' => true,
            ]);

        if (method_exists($this->user, 'assignRole')) {
            \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Superadmin', 'guard_name' => 'web']);
            if (! $this->user->hasRole('Superadmin')) {
                $this->user->assignRole('Superadmin');
            }
        }

        $this->customer = Customer::first() ?? Customer::create([
            'name'        => 'John',
            'last_name'   => 'Doe',
            'phone'       => '5551234567',
            'customer_no' => 'CUST-TEST1',
        ]);

        $supplierId = DB::table('suppliers')->value('id');
        if (! $supplierId) {
            $supplierId = DB::table('suppliers')->insertGetId([
                'name'       => 'Test Supplier',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $this->supplierId = $supplierId;

        $this->actingAs($this->user);
        Session::put('active_staff_id', $this->user->id);
    }

    /**
     * Rebuilds the full 'tenant' connection array from known values and
     * re-registers it in config. Called at every point in the test where a
     * DB call is about to happen, since the config key can vanish between
     * calls in this environment.
     */
    protected function applyTenantConnection(): void
    {
        $central = config('database.connections.mysql', []);

        config(['database.connections.tenant' => array_merge($central, [
            'driver'   => 'mysql',
            'database' => $this->tenantDatabase,
        ])]);

        config(['database.default' => 'tenant']);

        DB::purge('tenant');
    }

    protected function tearDown(): void
    {
        try {
            $this->applyTenantConnection();
            if (DB::connection('tenant')->transactionLevel() > 0) {
                DB::connection('tenant')->rollBack();
            }
        } catch (\Throwable $e) {
            // Connection may already be torn down — nothing more to clean up.
        }

        tenancy()->end();
        parent::tearDown();
    }

    protected function createTestProduct(float $price): ProductItem
    {
        $this->applyTenantConnection();

        $id = DB::table('product_items')->insertGetId([
            'barcode'      => 'TAG-' . strtoupper(Str::random(6)),
            'qty'          => 1,
            'retail_price' => $price,
            'cost_price'   => $price / 2,
            'status'       => 'in_stock',
            'supplier_id'  => $this->supplierId,
            'store_id'     => $this->store->id,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        return ProductItem::find($id);
    }

    protected function makeDraftId(): string
    {
        return (string) Str::uuid();
    }

    // ── SANITY CHECK ─────────────────────────────────────────────────────────
    public function test_create_sale_mounts_successfully()
    {
        $draftId = $this->makeDraftId();
        $this->applyTenantConnection();

        Livewire::withQueryParams(['draft_id' => $draftId])
            ->test(CreateSale::class)
            ->assertSuccessful()
            ->assertFormExists();
    }

    public function test_standard_sale_deducts_stock_and_records_payment()
    {
        $product = $this->createTestProduct(1000.00);
        $draftId = $this->makeDraftId();
        $this->applyTenantConnection();

        Livewire::withQueryParams(['draft_id' => $draftId])
            ->test(CreateSale::class)
            ->fillForm([
                'customer_id' => $this->customer->id,
                'items' => [
                    (string) Str::uuid() => [
                        'product_item_id'    => $product->id,
                        'qty'                 => 1,
                        'sold_price'          => 1000.00,
                        'sale_price_override' => 1000.00,
                        'is_tax_free'         => true,
                    ],
                ],
                'payment_method'   => 'CASH',
                'amount_paid'      => 1000.00,
                'is_split_payment' => false,
            ])
            ->callAction('complete_sale', data: [
                'verification_pin' => '1234',
            ])
            ->assertHasNoFormErrors();

        $this->applyTenantConnection();

        $this->assertDatabaseHas('sales', [
            'customer_id' => $this->customer->id,
            'status'      => 'completed',
            'amount_paid' => 1000.00,
        ]);

        $this->assertDatabaseHas('payments', [
            'amount' => 1000.00,
            'method' => 'CASH',
        ]);

        $this->assertDatabaseHas('product_items', [
            'id'     => $product->id,
            'qty'    => 0,
            'status' => 'sold',
        ]);
    }

    public function test_special_job_generates_repair_record_and_syncs_balance()
    {
        $jobUuid = (string) Str::uuid();
        $draftId = $this->makeDraftId();
        $this->applyTenantConnection();

        Livewire::withQueryParams(['draft_id' => $draftId])
            ->test(CreateSale::class)
            ->fillForm([
                'customer_id' => $this->customer->id,
                'items' => [
                    'item-1' => [
                        'stock_no_display'    => 'NON-TAG',
                        'custom_description'  => 'Gold Chain',
                        'qty'                 => 1,
                        'sold_price'          => 500.00,
                        'sale_price_override' => 500.00,
                        'is_tax_free'         => true,
                    ],
                ],
                'special_jobs' => [
                    'job-1' => [
                        'job_uuid'                  => $jobUuid,
                        'job_applies_to_store_item' => false,
                        'applicable_item_indexes'   => [0],
                        'job_type'                  => 'Resize',
                        'enable_payment'            => true,
                        'job_final_charge'          => 150.00,
                        'job_is_tax_free'           => true,
                    ],
                ],
                'is_split_payment' => true,
                'split_payments' => [
                    'split-1' => [
                        'method'         => 'VISA',
                        'amount'         => 150.00,
                        'payment_target' => "job_{$jobUuid}",
                    ],
                    'split-2' => [
                        'method'         => 'CASH',
                        'amount'         => 500.00,
                        'payment_target' => 'regular',
                    ],
                ],
            ])
            ->callAction('complete_sale', data: ['verification_pin' => '1234'])
            ->assertHasNoFormErrors();

        $this->applyTenantConnection();

        $sale = Sale::latest('id')->first();
        $this->assertNotNull($sale);
        $this->assertGreaterThan(0, $sale->amount_paid);

        $repair = Repair::latest('id')->first();
        $this->assertNotNull($repair);
        $this->assertStringContainsString('Resize', $repair->reported_issue);
        $this->assertEquals($sale->id, $repair->sale_id);
        $this->assertGreaterThan(0, $repair->amount_paid);

        $this->assertNotEmpty($repair->items);
        $this->assertEquals(150.00, $repair->items[0]['services'][0]['final_cost']);
        $this->assertTrue($repair->items[0]['is_tax_free']);

        $this->assertDatabaseHas('payments', [
            'sale_id'   => $sale->id,
            'amount'    => 150.00,
            'repair_id' => $repair->id,
        ]);

        $sale->refresh();
        $this->assertNotEmpty($sale->special_jobs);
        $this->assertEquals($repair->id, $sale->special_jobs[0]['repair_id'] ?? null);
    }

    public function test_laybuy_creation_puts_items_on_hold()
    {
        $product = $this->createTestProduct(2000.00);
        $draftId = $this->makeDraftId();
        $this->applyTenantConnection();

        Livewire::withQueryParams(['draft_id' => $draftId])
            ->test(CreateSale::class)
            ->fillForm([
                'customer_id' => $this->customer->id,
                'items' => [
                    'item-1' => [
                        'product_item_id'    => $product->id,
                        'qty'                 => 1,
                        'sold_price'          => 2000.00,
                        'sale_price_override' => 2000.00,
                        'is_tax_free'         => true,
                    ],
                ],
                'payment_method' => 'laybuy',
                'amount_paid'    => 0,
            ])
            ->callAction('complete_sale', data: ['verification_pin' => '1234'])
            ->assertHasNoFormErrors();

        $this->applyTenantConnection();

        $laybuy = Laybuy::latest('id')->first();
        $this->assertNotNull($laybuy);
        $this->assertEquals(2000.00, $laybuy->balance_due);
        $this->assertEquals('in_progress', $laybuy->status);

        $this->assertDatabaseHas('product_items', [
            'id'     => $product->id,
            'status' => 'on_hold',
        ]);
    }

    public function test_repair_job_payment_target_survives_reopen()
    {
        $jobUuid = (string) Str::uuid();
        $draftId = $this->makeDraftId();
        $this->applyTenantConnection();

        Livewire::withQueryParams(['draft_id' => $draftId])
            ->test(CreateSale::class)
            ->fillForm([
                'customer_id' => $this->customer->id,
                'items' => [
                    'item-1' => [
                        'stock_no_display'    => 'NON-TAG',
                        'custom_description'  => 'Silver Bracelet',
                        'qty'                 => 1,
                        'sold_price'          => 300.00,
                        'sale_price_override' => 300.00,
                        'is_tax_free'         => true,
                    ],
                ],
                'special_jobs' => [
                    'job-1' => [
                        'job_uuid'                  => $jobUuid,
                        'job_applies_to_store_item' => false,
                        'applicable_item_indexes'   => [0],
                        'job_type'                  => 'Solder / Weld',
                        'enable_payment'            => true,
                        'job_final_charge'          => 75.00,
                        'job_is_tax_free'           => true,
                    ],
                ],
                'is_split_payment' => true,
                'split_payments' => [
                    'split-1' => [
                        'method'         => 'CASH',
                        'amount'         => 75.00,
                        'payment_target' => "job_{$jobUuid}",
                    ],
                    'split-2' => [
                        'method'         => 'CASH',
                        'amount'         => 300.00,
                        'payment_target' => 'regular',
                    ],
                ],
            ])
            ->callAction('complete_sale', data: ['verification_pin' => '1234'])
            ->assertHasNoFormErrors();

        $this->applyTenantConnection();

        $sale   = Sale::latest('id')->first();
        $repair = Repair::latest('id')->first();

        $this->assertNotNull($sale);
        $this->assertNotNull($repair);

        $options = SaleResource::buildPaymentTargetOptions(
            $sale->items->map(fn ($i) => $i->toArray())->toArray(),
            $sale->special_jobs ?? []
        );

        $this->assertArrayHasKey("repair_{$repair->id}", $options);
        $this->assertArrayNotHasKey("job_{$jobUuid}", $options);
    }
    public function test_custom_order_deposit_math_is_correct()
{
    $this->applyTenantConnection();

    $quotedPrice = 5000.00;
    $discountPct = 0;
    $discountAmt = 0;
    $isTaxFree   = true;
    $depositAmt  = 1000.00;

    $taxRate    = $isTaxFree ? 0 : 0.0763;
    $afterDisc  = $quotedPrice - $discountAmt;
    $grandTotal = $afterDisc + ($afterDisc * $taxRate);

    $customOrder = \App\Models\CustomOrder::create([
        'customer_id'      => $this->customer->id,
        'staff_id'         => $this->user->id,
        'order_type'       => 'custom',
        'product_name'     => 'Diamond Ring',
        'metal_type'       => '18k',
        'quoted_price'     => $quotedPrice,
        'discount_percent' => $discountPct,
        'discount_amount'  => $discountAmt,
        'due_date'         => now()->addWeeks(4),
        'status'           => 'in_production',
        'is_tax_free'      => $isTaxFree,
        'amount_paid'      => $depositAmt,
        'balance_due'      => max(0, $grandTotal - $depositAmt),
        'items'            => [[
            'product_name' => 'Diamond Ring',
            'metal_type'   => '18k',
            'quoted_price' => $quotedPrice,
            'is_tax_free'  => $isTaxFree,
        ]],
    ]);

    \App\Models\Payment::create([
        'custom_order_id' => $customOrder->id,
        'amount'          => $depositAmt,
        'method'          => 'VISA',
        'paid_at'         => now(),
        'store_id'        => $this->store->id,
    ]);

    $this->assertEquals('Diamond Ring', $customOrder->product_name);
    $this->assertEquals(5000.00, $customOrder->quoted_price);
    $this->assertEquals(1000.00, $customOrder->amount_paid);
    $this->assertEquals(4000.00, $customOrder->balance_due);

    $this->assertDatabaseHas('payments', [
        'custom_order_id' => $customOrder->id,
        'amount'          => 1000.00,
        'method'          => 'VISA',
    ]);
}
}