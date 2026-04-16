<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sale;
use App\Models\Laybuy;
use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ImportOnSwimLaybys extends Command
{
    protected $signature = 'import:onswim-laybys {tenant} {--date= : The date of the CSV file} {--rollback} {--dry-run}';
    protected $description = 'Import OnSwim Laybys from Python-extracted CSV.';

    private int $matched = 0;
    private int $skipped = 0;
    private int $created = 0;
    private int $updated = 0;

    public function handle()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1G');

        $tenantId = $this->argument('tenant');
        $isDryRun = $this->option('dry-run');

        try {
            $tenant = Tenant::findOrFail($tenantId);
            tenancy()->initialize($tenant);

            $adminUser = User::first();
            if ($adminUser) { auth()->login($adminUser); }

            // ── DYNAMIC FILE PATH LOGIC ──────────
            $directoryPath = storage_path("/{$tenantId}/data");
            $filePath = null;
            $fileName = null;

            if ($this->option('date')) {
                $manualDate = $this->option('date');
                $possible = [
                    "{$directoryPath}/layby_2026_{$manualDate}.csv",  // 🚀 Added 2026_
                    "{$directoryPath}/laybys_2026_{$manualDate}.csv", // 🚀 Added 2026_
                    "{$directoryPath}/laybuy_2026_{$manualDate}.csv", // 🚀 Added 2026_
                    "{$directoryPath}/layby_{$manualDate}.csv"        // Keeping fallback just in case
                ];
                foreach ($possible as $path) {
                    if (File::exists($path)) {
                        $filePath = $path;
                        $fileName = basename($path);
                        break;
                    }
                }
            } else {
                $allFiles = File::glob("{$directoryPath}/*.csv");
                $validFiles = [];
                foreach ($allFiles as $f) {
                    $base = strtolower(basename($f));
                    if (str_starts_with($base, 'layby') || str_starts_with($base, 'laybuy')) {
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
                $this->line("Search patterns: 'layby_*.csv' or 'laybys_*.csv'");
                return Command::FAILURE;
            }

            $this->info("📥 Using file: {$fileName}");

        } catch (\Exception $e) {
            $this->error("Initialization Error: " . $e->getMessage());
            return Command::FAILURE;
        }

       // Handle Rollback
        if ($this->option('rollback')) {
            $this->warn("⚠️  WARNING: You are about to delete ALL Laybys for tenant: {$tenantId}");
            if ($this->confirm('Are you sure? This will empty the laybuys table completely.', false)) {
                Schema::disableForeignKeyConstraints();
                $count = Laybuy::count();
                Laybuy::truncate();
                Schema::enableForeignKeyConstraints();
                $this->info("✅ SUCCESS: {$count} Layby records have been removed.");
            }
            return Command::SUCCESS;
        }

        $walkInCustomer = Customer::withoutEvents(fn() => 
            Customer::firstOrCreate(['customer_no' => 'WALKIN'], ['name' => 'Walk-in', 'last_name' => 'Customer', 'is_active' => true])
        );

        if ($isDryRun) {
            $this->warn("🛠️  DRY RUN ENABLED: No changes will be saved.");
        }

        // ── Read CSV (Matches Python Headers) ─────────────────────
        $file   = fopen($filePath, 'r');
        $header = array_map('trim', fgetcsv($file));

        $required = ['Date Sold', 'Job No.', 'Invoice Total', 'Payment Total', 'Balance', 'Customer', 'Phone'];
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
        $this->info("Processing {$total} Layby records...");
        $bar = $this->output->createProgressBar($total);

        DB::connection('tenant')->beginTransaction();

        try {
            foreach ($rows as $data) {
                $jobNo = trim($data['Job No.']);
                $cleanJobNo = preg_replace('/[^0-9]/', '', $jobNo);
                
                $invoiceTotal = $this->cleanMoney($data['Invoice Total']);
                $amountPaid   = $this->cleanMoney($data['Payment Total']);
                $balance      = $this->cleanMoney($data['Balance']);

                // Find Customer (Now supercharged with the phone number!)
                $customer = $this->findCustomer($data);
                $customerId = $customer ? $customer->id : $walkInCustomer->id;

                // PLAN A: Link by Job Number
                $sale = Sale::where('invoice_number', 'LIKE', "%{$cleanJobNo}%")->first();

                // PLAN B: The "Financial Fingerprint" Match
                if (!$sale) {
                    $sale = Sale::where('customer_id', $customerId)
                        ->whereBetween('final_total', [$invoiceTotal - 0.05, $invoiceTotal + 0.05])
                        ->latest()
                        ->first();
                }

                if ($sale) {
                    $customerId = $sale->customer_id; // Sync to the sale's exact customer
                    $this->matched++;
                } else {
                    $this->skipped++;
                }

                // Filament specifically expects 'completed' or 'in_progress'
                $status = $balance <= 0.50 ? 'completed' : 'in_progress';

                $payload = [
                    'customer_id'    => $customerId,
                    'sale_id'        => $sale ? $sale->id : null,
                    'total_amount'   => $invoiceTotal,
                    'amount_paid'    => $amountPaid,
                    'balance_due'    => max(0, $balance),
                    'status'         => $status,
                    'sales_person'   => 'System', // Often not on the PDF, default to System
                    'created_at'     => $this->parseDate($data['Date Sold']),
                    'notes'          => "Imported Lay-by from OnSwim PDF. Job #{$jobNo}.",
                ];

                if (!$isDryRun) {
                    $laybuyNo = 'LB-' . $jobNo;
                    $exists = Laybuy::where('laybuy_no', $laybuyNo)->exists();
                    
                    Laybuy::withoutEvents(function() use ($laybuyNo, $payload) {
                        Laybuy::updateOrCreate(['laybuy_no' => $laybuyNo], $payload);
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
                $this->info("✅ SUCCESS: Laybys synchronized.");
            }

        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            $this->error("\nDatabase Error: " . $e->getMessage());
            return Command::FAILURE;
        }

        $this->table(['Metric', 'Count'], [
            ['Total Rows', $total], 
            ['Matched to Sales', $this->matched], 
            ['Unlinked to Sale', $this->skipped],
            ['Created', $this->created], 
            ['Updated', $this->updated]
        ]);

        return Command::SUCCESS;
    }

    private function findCustomer($data)
    {
        $fullName = trim($data['Customer'] ?? '');
        $rawPhone = trim($data['Phone'] ?? '');
        
        // 1. Try mapping by Phone Number first (Highest Accuracy)
        $cleanPhone = preg_replace('/[^0-9]/', '', $rawPhone);
        if (strlen($cleanPhone) >= 7) {
            $found = Customer::withoutGlobalScopes()->where('phone', 'like', "%{$cleanPhone}%")->first();
            if ($found) return $found;
        }

        // 2. Try mapping by Name (Fallback)
        if (!empty($fullName)) {
            $parts = explode(' ', $fullName);
            $firstName = trim($parts[0]);
            $lastName  = trim(implode(' ', array_slice($parts, 1)));

            $found = Customer::withoutGlobalScopes()
                ->where(function($query) use ($firstName, $lastName, $fullName) {
                    $query->where(function($q) use ($firstName, $lastName) {
                        $q->where('name', 'LIKE', $firstName)
                        ->where('last_name', 'LIKE', $lastName);
                    })
                    ->orWhere(DB::raw("CONCAT(name, ' ', COALESCE(last_name, ''))"), 'LIKE', "%{$fullName}%");
                })
                ->first();

            if ($found) return $found;

            // Only create if we absolutely cannot find a match
            return Customer::withoutEvents(fn() => 
                Customer::create([
                    'customer_no' => 'LAY-CUST-' . strtoupper(Str::random(8)),
                    'name'        => $firstName,
                    'last_name'   => $lastName ?: '.',
                    'phone'       => !empty($cleanPhone) ? '+1' . substr($cleanPhone, -10) : null,
                    'is_active'   => true,
                ])
            );
        }
        
        return null;
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