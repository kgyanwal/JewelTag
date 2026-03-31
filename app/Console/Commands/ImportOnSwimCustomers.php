<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ImportOnSwimCustomers extends Command
{
    // 🚀 Added --dry-run and kept --rollback
    protected $signature = 'import:onswim-customers {tenant} {--rollback} {--dry-run}';
    protected $description = 'Import customers with dynamic pathing and database-level conflict resolution';

    public function handle()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1G');

        $tenantId = $this->argument('tenant');
        $isDryRun = $this->option('dry-run');
        
        try {
            $tenant = Tenant::findOrFail($tenantId);
            tenancy()->initialize($tenant);

            // 🚀 WORKING PATH PATTERN
            $directoryPath = storage_path("/{$tenantId}/data");
            
            // 🚀 WORKING DATE PATTERN: customers_2026_03_30.csv
            $yesterday = Carbon::yesterday()->format('Y_m_d'); 
            $fileName = "customers_{$yesterday}.csv";
            $filePath = "{$directoryPath}/{$fileName}";

            if (!File::exists($filePath)) {
                $this->error("❌ FILE NOT FOUND: {$filePath}");
                return Command::SUCCESS; 
            }

        } catch (\Exception $e) {
            $this->error("Initialization Error: " . $e->getMessage());
            return Command::FAILURE;
        }

        // Login first user as admin for auditing
        $adminUser = User::first();
        if ($adminUser) auth()->login($adminUser); 

        if ($this->option('rollback')) {
            $this->warn("Rolling back (deleting) customers for tenant: {$tenantId}...");
            if ($this->confirm('This will delete ALL customers. Are you sure?', false)) {
                Schema::disableForeignKeyConstraints();
                Customer::truncate();
                Schema::enableForeignKeyConstraints();
                $this->info("Customer table truncated.");
            }
            return;
        }

        if ($isDryRun) {
            $this->warn("🛠️ DRY RUN ENABLED: No changes will be saved.");
        }

        $file = fopen($filePath, 'r');
        $headers = array_map('trim', fgetcsv($file));

        $this->info("Processing [{$fileName}]...");
        
        $totalRows = 0; $created = 0; $updated = 0; $tempIdsCreated = 0;
        $bar = $this->output->createProgressBar(); 

        DB::connection('tenant')->beginTransaction();

        try {
            while (($row = fgetcsv($file)) !== FALSE) {
                if (count($headers) !== count($row)) continue;

                $totalRows++;
                $data = array_combine($headers, $row);

                $email = strtolower(trim($data['Email'] ?? ''));
                $phone = $this->formatPhone($data['Mobile'] ?? $data['Home Phone'] ?? '');
                $rawCustNo = trim($data['Cust No.'] ?? '');

                if (!$isDryRun) {
                    Customer::withoutEvents(function () use ($rawCustNo, $data, $email, $phone, &$created, &$updated, &$tempIdsCreated) {
                        $customer = null;

                        // 🚀 IMPROVED MATCHING: Try ID first, then Email
                        if (!empty($rawCustNo)) {
                            $customer = Customer::withoutGlobalScopes()->where('customer_no', 'CUST-' . $rawCustNo)->first();
                        }
                        
                        if (!$customer && !empty($email)) {
                            $customer = Customer::withoutGlobalScopes()->where('email', $email)->first();
                        }

                        if (!$customer) {
                            $customer = new Customer();
                            if (empty($rawCustNo)) {
                                $customer->customer_no = 'CUST-TEMP-' . strtoupper(Str::random(6));
                                $tempIdsCreated++;
                            } else {
                                $customer->customer_no = 'CUST-' . $rawCustNo;
                            }
                            $created++;
                        } else {
                            $updated++;
                        }

                        $cityValue = !empty(trim($data['City/Province'] ?? '')) 
                                        ? $data['City/Province'] 
                                        : ($data['Suburb/City'] ?? null);

                        $customer->fill([
                            'name' => $data['First Name'] ?? 'Walk-in',
                            'last_name' => $data['Last Name'] ?? null,
                            'email' => $email ?: $customer->email,
                            'phone' => $phone ?: $customer->phone,
                            'home_phone' => $data['Home Phone'] ?? null,
                            'street'   => $data['Street'] ?? null,
                            'suburb'   => $data['Suburb/City'] ?? null,   
                            'city'     => $cityValue,
                            'state'    => $data['State'] ?? null,
                            'postcode' => $data['Postcode'] ?? null,
                            'country'  => $data['Country'] ?? 'USA',
                            'dob' => $this->parseDate($data['Birthdate'] ?? null),
                            'wedding_anniversary' => $this->parseDate($data['Wedding Date'] ?? null),
                            'gender' => $data['Gender'] ?? null,
                            'gold_preference' => $data['Gold Preference'] ?? null,
                            'lh_ring' => $data['Finger Size'] ?? null,
                            'sales_person' => $data['Staff Assigned'] ?? null,
                            'how_found_store' => $data['Referred By'] ?? null,
                            'spouse_name' => trim(($data['Spouse First Name'] ?? '') . ' ' . ($data['Spouse Last Name'] ?? '')),
                            'spouse_email' => $data['Spouse Email'] ?? null,
                            'comments' => $data['Comments'] ?? null,
                            'customer_alerts' => $data['Issues'] ?? null,
                            'exclude_from_mailing' => (strtolower($data['On Mailing List'] ?? '') === 'no'),
                            'is_active' => true,
                            'company' => $data['Company'] ?? null,
                        ]);

                        try {
                            $customer->save();
                        } catch (\Illuminate\Database\QueryException $e) {
                            if ($e->getCode() == 23000 || (isset($e->errorInfo[1]) && $e->errorInfo[1] == 1062)) {
                                $errorMessage = $e->getMessage();
                                // Revert conflicting unique fields
                                if (str_contains($errorMessage, 'phone_unique')) $customer->phone = $customer->exists ? $customer->getOriginal('phone') : null;
                                if (str_contains($errorMessage, 'email_unique')) $customer->email = $customer->exists ? $customer->getOriginal('email') : null;
                                $customer->save();
                            } else {
                                throw $e;
                            }
                        }
                    });
                }
                $bar->advance();
            }

            if ($isDryRun) {
                DB::connection('tenant')->rollBack();
                $bar->finish();
                $this->newLine(2);
                $this->info("✅ DRY RUN COMPLETE: No data was saved.");
            } else {
                DB::connection('tenant')->commit();
                $bar->finish();
                $this->newLine(2);
                $this->info("✅ SUCCESS: Customers imported.");
                $this->table(
                    ['Processed', 'Created', 'Updated', 'Temp IDs'],
                    [[$totalRows, $created, $updated, $tempIdsCreated]]
                );
            }
            fclose($file);

        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            if (isset($file)) fclose($file);
            $this->error("\nCritical Error: " . $e->getMessage());
        }
    }

    private function formatPhone($phone)
    {
        $digits = preg_replace('/[^0-9]/', '', $phone);
        if (empty($digits)) return null;
        return '+1' . substr($digits, -10);
    }

    private function parseDate($date)
    {
        if (empty($date) || trim($date) == '-' || trim($date) == '') return null;
        try { return Carbon::parse($date)->format('Y-m-d'); } 
        catch (\Exception $e) { return null; }
    }
}