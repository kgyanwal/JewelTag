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
    // 🚀 Added --date flag to the signature
    protected $signature = 'import:onswim-deposits {tenant} {--date= : The date of the CSV file} {--rollback} {--dry-run}';
    protected $description = 'Import OnSwim Deposit Sales with robust matching and dynamic file locator.';

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

            $adminUser = User::first();
            if ($adminUser) { auth()->login($adminUser); }

            // ── DYNAMIC FILE PATH LOGIC (MATCHES YOUR OTHER SCRIPTS) ──────────
            $directoryPath = storage_path("/{$tenantId}/data");
            $filePath = null;
            $fileName = null;

            if ($this->option('date')) {
                $manualDate = $this->option('date');
                // Check common naming patterns for deposits
                $possible = [
                    "{$directoryPath}/deposits_{$manualDate}.csv", 
                    "{$directoryPath}/deposit_sales_{$manualDate}.csv",
                    "{$directoryPath}/deposit_sales_dsq_{$manualDate}.csv"
                ];
                foreach ($possible as $path) {
                    if (File::exists($path)) {
                        $filePath = $path;
                        $fileName = basename($path);
                        break;
                    }
                }
            } else {
                // Smart search: Grab any file starting with 'deposit' or 'laybuys'
                $allFiles = File::glob("{$directoryPath}/*.csv");
                $validFiles = [];
                foreach ($allFiles as $f) {
                    $base = strtolower(basename($f));
                    if (str_starts_with($base, 'deposit') || str_starts_with($base, 'laybuy')) {
                        $validFiles[] = $f;
                    }
                }
                
                if (!empty($validFiles)) {
                    usort($validFiles, function($a, $b) {
                        return File::lastModified($b) <=> File::lastModified($a);
                    });
                    $filePath = $validFiles[0]; 
                    $fileName = basename($filePath);
                }
            }

            if (!$filePath) {
                $this->error("❌ NO FILES FOUND in {$directoryPath}");
                $this->line("Search patterns: 'deposits_*.csv' or 'deposit_sales_*.csv'");
                return Command::FAILURE;
            }

            $this->info("📥 Using file: {$fileName}");

        } catch (\Exception $e) {
            $this->error("Initialization Error: " . $e->getMessage());
            return Command::FAILURE;
        }

       // Handle Rollback
        if ($this->option('rollback')) {
            $this->warn("⚠️  WARNING: You are about to delete ALL deposit sales for tenant: {$tenantId}");
            
            if ($this->confirm('Are you sure? This will empty the deposit_sales table completely.', false)) {
                // 🚀 UNIVERSAL CLEANUP: No file required
                Schema::disableForeignKeyConstraints();
                
                $count = DepositSale::count();
                DepositSale::truncate();
                
                Schema::enableForeignKeyConstraints();
                
                $this->info("✅ SUCCESS: {$count} deposit records have been removed.");
            }
            return Command::SUCCESS;
        }

        $walkInCustomer = Customer::withoutEvents(fn() => 
            Customer::firstOrCreate(['customer_no' => 'WALKIN'], ['name' => 'Walk-in', 'last_name' => 'Customer', 'is_active' => true])
        );

        if ($isDryRun) {
            $this->warn("🛠️  DRY RUN ENABLED: No changes will be saved.");
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
        $this->info("Processing {$total} rows...");
        $bar = $this->output->createProgressBar($total);

        // Start transaction
        DB::connection('tenant')->beginTransaction();

        try {
            foreach ($rows as $data) {
                $jobNo = trim($data['Job No.']);
                $cleanJobNo = preg_replace('/[^0-9]/', '', $jobNo);
                
                // 🚀 AGGRESSIVE FUZZY MATCHING (Exactly like the logic we discussed)
                $sale = Sale::where('invoice_number', 'LIKE', "%{$cleanJobNo}%")->first();

                if ($sale) {
                    $customerId = $sale->customer_id;
                    $this->matched++;
                } else {
                    $customer = $this->findCustomer($data);
                    $customerId = $customer ? $customer->id : $walkInCustomer->id;
                    $this->skipped++;
                }

                $invoiceTotal = $this->cleanMoney($data['Invoice Total']);
                $amountPaid   = $this->cleanMoney($data['Payment Total']);
                $balance      = $this->cleanMoney($data['Balance']);
                $status       = $balance <= 0.50 ? 'completed' : 'active';

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
                    'staff_list'     => $staffList,
                    'start_date'     => $this->parseDate($data['Date Sold']),
                    'last_paid_date' => $this->parseDate($data['Last Date Paid'] ?? null),
                    'due_date'       => $this->parseDate($data['Date Required'] ?? null),
                    'notes'          => "Imported from Onswim. Job #{$jobNo}.",
                ];

                if (!$isDryRun) {
                    $depositNo = 'DEP-' . $jobNo;
                    $exists = DepositSale::where('deposit_no', $depositNo)->exists();
                    
                    DepositSale::withoutEvents(function() use ($depositNo, $payload) {
                        DepositSale::updateOrCreate(['deposit_no' => $depositNo], $payload);
                    });
                    
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
                $this->info("✅ DRY RUN COMPLETE.");
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
            ['Unlinked', $this->skipped],
            ['Created', $this->created], 
            ['Updated', $this->updated]
        ]);

        return Command::SUCCESS;
    }

    private function findCustomer($data)
    {
        $fullName = trim($data['Customer'] ?? '');
        if (empty($fullName)) return null;

        // Split "Gloria Villas" into ["Gloria", "Villas"]
        $parts = explode(' ', $fullName);
        $firstName = trim($parts[0]);
        $lastName  = trim(implode(' ', array_slice($parts, 1)));

        // 🚀 THE "NO-DUPLICATE" SEARCH LOGIC
        $found = Customer::withoutGlobalScopes()
            ->where(function($query) use ($firstName, $lastName, $fullName) {
                $query->where(function($q) use ($firstName, $lastName) {
                    $q->where('name', 'LIKE', $firstName)
                    ->where('last_name', 'LIKE', $lastName);
                })
                // Also check if the full string matches the concatenated name in DB
                ->orWhere(DB::raw("CONCAT(name, ' ', COALESCE(last_name, ''))"), 'LIKE', "%{$fullName}%");
            })
            ->first();

        if ($found) return $found;

        // Only create if we absolutely cannot find a match
        return Customer::withoutEvents(fn() => 
            Customer::create([
                'customer_no' => 'DEP-CUST-' . strtoupper(Str::random(8)),
                'name'        => $firstName,
                'last_name'   => $lastName ?: '.',
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