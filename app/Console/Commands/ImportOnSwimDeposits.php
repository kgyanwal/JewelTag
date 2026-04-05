<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sale;
use App\Models\DepositSale;
use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;

class ImportOnSwimDeposits extends Command
{
    protected $signature = 'import:onswim-deposits {tenant} {--rollback} {--dry-run}';
    protected $description = 'Import OnSwim Deposit Sales with robust matching, SQL constraint fixes, and optional dry-run.';

    private int   $matched  = 0;
    private int   $skipped  = 0;
    private int   $created  = 0;
    private int   $updated  = 0;

    public function handle()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1G');

        $tenantId   = $this->argument('tenant');
        $isDryRun   = $this->option('dry-run');

        try {
            $tenant = Tenant::findOrFail($tenantId);
            tenancy()->initialize($tenant);

            // Authenticate admin to satisfy potential activity_logs foreign key constraints
            $adminUser = User::first();
            if ($adminUser) {
                auth()->login($adminUser);
            }

            // Path & File Logic
            $directoryPath = storage_path("/{$tenantId}/data");
            $yesterday = Carbon::yesterday()->format('Y_m_d'); 
            $fileName = "deposits_{$yesterday}.csv";
            $filePath = "{$directoryPath}/{$fileName}";

            if (!File::exists($filePath)) {
                $this->newLine();
                $this->error("❌ FILE NOT FOUND: {$filePath}");
                return Command::FAILURE;
            }

        } catch (\Exception $e) {
            $this->error("Initialization Error: " . $e->getMessage());
            return Command::FAILURE;
        }

        // Handle Rollback
        if ($this->option('rollback')) {
            $this->warn("Rolling back (deleting) ALL deposit sales for tenant: {$tenantId}...");
            if ($this->confirm('Are you sure? This cannot be undone.', false)) {
                Schema::disableForeignKeyConstraints();
                DepositSale::truncate();
                Schema::enableForeignKeyConstraints();
                $this->info("Deposit sales table truncated.");
            }
            return Command::SUCCESS;
        }

        // Ensure fallback customer exists to prevent NOT NULL customer_id constraint errors
        $walkInCustomer = Customer::withoutEvents(fn() => 
            Customer::firstOrCreate(
                ['customer_no' => 'WALKIN'], 
                ['name' => 'Walk-in', 'last_name' => 'Customer', 'is_active' => true]
            )
        );

        if ($isDryRun) {
            $this->newLine();
            $this->warn("🛠️  DRY RUN ENABLED: No changes will be saved to the database.");
            $this->newLine();
        }

        // ── Read CSV ──────────────────────────────────────────────
        $file   = fopen($filePath, 'r');
        $header = array_map('trim', fgetcsv($file));

        $required = ['Job No.', 'Invoice Total', 'Payment Total', 'Balance', 'Date Sold'];
        $missing  = array_diff($required, $header);
        if (!empty($missing)) {
            $this->error("CSV missing columns: " . implode(', ', $missing));
            fclose($file);
            return Command::FAILURE;
        }

        $rows = [];
        while (($row = fgetcsv($file)) !== false) {
            if (count($row) !== count($header)) continue;
            $data = array_combine($header, $row);
            if (empty(trim($data['Job No.'] ?? ''))) continue;
            $rows[] = $data;
        }
        fclose($file);

        $total = count($rows);
        $this->info("Processing {$total} rows from {$fileName}...");
        $bar = $this->output->createProgressBar($total);

        // ── Build Job No → Sale lookup map ────────────────────────
        $jobToSale = [];
        Sale::select('id', 'invoice_number', 'customer_id')->chunk(500, function ($sales) use (&$jobToSale) {
            foreach ($sales as $sale) {
                $inv = $sale->invoice_number ?? '';
                $numeric = preg_replace('/[^0-9]/', '', $inv);
                if (!empty($numeric)) $jobToSale[(int)$numeric] = $sale;
            }
        });

        // Start transaction
        DB::connection('tenant')->beginTransaction();

        try {
            foreach ($rows as $data) {
                $jobNo = trim($data['Job No.']);
                $numericJob = (int) preg_replace('/[^0-9]/', '', $jobNo);
                
                // 1. Try to link the exact Sale
                $sale = $jobToSale[$numericJob] ?? Sale::where('invoice_number', 'like', "%{$jobNo}")->first();

                // 2. Resolve Customer ID (Strict NOT NULL fallback)
                if ($sale) {
                    $customerId = $sale->customer_id;
                    $this->matched++; // Matched directly to a Sale
                } else {
                    $customer = $this->findCustomer($data);
                    $customerId = $customer ? $customer->id : $walkInCustomer->id;
                    $this->skipped++; // Skipped linking to a sale, but will still import
                }

                $invoiceTotal = $this->cleanMoney($data['Invoice Total']);
                $amountPaid   = $this->cleanMoney($data['Payment Total']);
                $balance      = $this->cleanMoney($data['Balance']);
                $status       = $balance <= 0.50 ? 'completed' : 'active';

                // Format Staff JSON array and primary Sales Person string
                $staffRaw     = trim($data['Staff'] ?? 'System');
                $staffList    = array_values(array_filter(preg_split('/[\/,]+/', $staffRaw)));
                $salesPerson  = !empty($staffList) ? $staffList[0] : 'System';

                $payload = [
                    'customer_id'    => $customerId,
                    'sale_id'        => $sale ? $sale->id : null,
                    'total_amount'   => $invoiceTotal,
                    'amount_paid'    => $amountPaid,
                    'balance_due'    => max(0, $balance),
                    'status'         => $status,
                    'sales_person'   => $salesPerson,
                    'staff_list'     => $staffList, // Array casts natively to JSON column
                    'start_date'     => $this->parseDate($data['Date Sold']),
                    'last_paid_date' => $this->parseDate($data['Last Date Paid'] ?? null),
                    'due_date'       => $this->parseDate($data['Date Required'] ?? null),
                    'notes'          => "Imported from Onswim. Job #{$jobNo}.",
                ];

                if (!$isDryRun) {
                    $depositNo = 'DEP-' . $jobNo;
                    $exists = DepositSale::where('deposit_no', $depositNo)->exists();
                    
                    // withoutEvents prevents model observers from interfering
                    DepositSale::withoutEvents(function() use ($depositNo, $payload) {
                        DepositSale::updateOrCreate(
                            ['deposit_no' => $depositNo], // Unique constraints
                            $payload
                        );
                    });
                    
                    // Sync the main sale record balance if a sale was found
                    if ($sale) {
                        Sale::withoutEvents(fn() => 
                            $sale->update([
                                'amount_paid' => max($sale->amount_paid, $amountPaid), 
                                'balance_due' => max(0, $balance)
                            ])
                        );
                    }

                    $exists ? $this->updated++ : $this->created++;
                }

                $bar->advance();
            }

            if ($isDryRun) {
                DB::connection('tenant')->rollBack();
                $bar->finish();
                $this->newLine(2);
                $this->info("✅ DRY RUN COMPLETE. No data modified.");
            } else {
                DB::connection('tenant')->commit();
                $bar->finish();
                $this->newLine(2);
                $this->info("✅ SUCCESS: Deposits synchronized.");
            }

        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            $this->error("\nDatabase Error: " . $e->getMessage());
            return Command::FAILURE;
        }

        $this->table(['Metric', 'Count'], [
            ['Total Rows', $total], 
            ['Matched to Sales', $this->matched], 
            ['Unlinked (Fallback Customer)', $this->skipped],
            ['Created', $this->created], 
            ['Updated/Overwritten', $this->updated]
        ]);

        return Command::SUCCESS;
    }

    /**
     * Robust customer search matching the Sales script logic
     */
    /**
     * Robust customer search for Deposits CSV (which only has full names)
     */
    private function findCustomer($data)
    {
        $fullName = trim($data['Customer'] ?? '');
        
        if (empty($fullName)) {
            return null;
        }

        // 1. Try matching the exact full string against first and last name combined
        $found = Customer::withoutGlobalScopes()
            ->where(DB::raw("CONCAT(name, ' ', COALESCE(last_name, ''))"), 'LIKE', "%{$fullName}%")
            ->orWhere('name', 'LIKE', "%{$fullName}%")
            ->first();

        if ($found) return $found;

        // 2. Split the name into First and Last and try to match
        $parts = explode(' ', $fullName);
        $firstName = $fullName;
        $lastName = '';

        if (count($parts) >= 2) {
            $firstName = array_shift($parts); // Grabs the first word
            $lastName  = implode(' ', $parts);  // Combines the rest for the last name

            $found = Customer::withoutGlobalScopes()
                ->where('name', 'LIKE', "%{$firstName}%")
                ->where('last_name', 'LIKE', "%{$lastName}%")
                ->first();

            if ($found) return $found;
        }

        // 3. IF NOT FOUND: Create a basic customer so we don't lose their name!
        return Customer::withoutEvents(fn() => 
            Customer::create([
                'customer_no' => 'DEP-CUST-' . strtoupper(substr(md5($fullName . time()), 0, 8)),
                'name'        => $firstName,
                'last_name'   => empty($lastName) ? '.' : $lastName, // Fallback if no last name
                'is_active'   => true,
            ])
        );
    }

    private function cleanMoney($value): float {
        $clean = str_replace(['$', ',', ' ', '-'], '', trim((string)($value ?? '0')));
        return ($clean === '') ? 0.00 : (float)$clean;
    }

    private function parseDate($date): ?string {
        if (empty($date) || $date === '-') return null;
        try { return Carbon::parse($date)->format('Y-m-d'); }
        catch (\Exception $e) { return null; }
    }
}