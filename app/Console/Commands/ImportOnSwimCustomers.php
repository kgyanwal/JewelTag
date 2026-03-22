<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ImportOnSwimCustomers extends Command
{
    protected $signature = 'import:onswim-customers {tenant} {--rollback}';
    protected $description = 'Import customers with bulletproof database-level conflict resolution';

    public function handle()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $tenantId = $this->argument('tenant');
        
        try {
            $tenant = \App\Models\Tenant::findOrFail($tenantId);
            tenancy()->initialize($tenant);
        } catch (\Exception $e) {
            $this->error("Could not find or initialize tenant: {$tenantId}");
            return;
        }

        $adminUser = \App\Models\User::first();
        if ($adminUser) {
            auth()->login($adminUser); 
        }

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

    $directoryPath = storage_path("{$tenantId}/data");
        $filePath = "{$directoryPath}/customer_dsqdata_mar22_26.csv";

        if (!file_exists($filePath)) {
            $this->error("File not found at: {$filePath}");
            return;
        }

        $file = fopen($filePath, 'r');
        $headers = array_map('trim', fgetcsv($file));

        $this->info("Starting Full Customer Import for {$tenantId}...");
        
        $totalRows = 0;
        $created = 0;
        $updated = 0;
        $tempIdsCreated = 0;

        DB::connection('tenant')->beginTransaction();

        try {
            $bar = $this->output->createProgressBar(); 

            while (($row = fgetcsv($file)) !== FALSE) {
                $totalRows++;
                $data = array_combine($headers, $row);

                $email = trim($data['Email'] ?? '');
                $phone = $this->formatPhone($data['Mobile'] ?? $data['Home Phone'] ?? '');
                $rawCustNo = trim($data['Cust No.'] ?? '');

                Customer::withoutEvents(function () use ($rawCustNo, $data, $email, $phone, &$created, &$updated, &$tempIdsCreated) {
                    $customer = null;

                    // 1. Try to find existing customer
                    if (!empty($rawCustNo)) {
                        // Use withoutGlobalScopes to find trashed/hidden records if they exist
                        $customer = Customer::withoutGlobalScopes()->where('customer_no', 'CUST-' . $rawCustNo)->first();
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

                    // 🚀 THE BULLETPROOF FIX: Try to save, catch database rejections instantly
                    try {
                        $customer->save();
                    } catch (\Illuminate\Database\QueryException $e) {
                        // 23000 / 1062 = MySQL Unique Constraint Violation
                        if ($e->getCode() == 23000 || (isset($e->errorInfo[1]) && $e->errorInfo[1] == 1062)) {
                            
                            $errorMessage = $e->getMessage();
                            
                            // If Phone caused the crash, revert to old phone or set to null
                            if (str_contains($errorMessage, 'phone_unique')) {
                                $customer->phone = $customer->exists ? $customer->getOriginal('phone') : null;
                            }
                            
                            // If Email caused the crash, revert or set to null
                            if (str_contains($errorMessage, 'email_unique')) {
                                $customer->email = $customer->exists ? $customer->getOriginal('email') : null;
                            }

                            // If we aren't sure, wipe both just to force the save through safely
                            if (!str_contains($errorMessage, 'phone_unique') && !str_contains($errorMessage, 'email_unique')) {
                                $customer->phone = $customer->exists ? $customer->getOriginal('phone') : null;
                                $customer->email = $customer->exists ? $customer->getOriginal('email') : null;
                            }

                            // Save again with the conflicts removed
                            $customer->save();
                        } else {
                            // If it's a completely different database error, throw it so we know
                            throw $e;
                        }
                    }
                });
                
                $bar->advance();
            }

            DB::connection('tenant')->commit();
            fclose($file);
            $bar->finish();
            $this->newLine(2);
            
            $this->info("Import finished!");
            $this->table(
                ['Processed Rows', 'Created', 'Updated', 'Generated Temp IDs'],
                [[$totalRows, $created, $updated, $tempIdsCreated]]
            );

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
        if (str_starts_with($digits, '1') && strlen($digits) === 11) return '+' . $digits;
        return '+1' . $digits;
    }

    private function parseDate($date)
    {
        if (empty($date) || trim($date) == '') return null;
        try { return Carbon::parse($date)->format('Y-m-d'); } 
        catch (\Exception $e) { return null; }
    }
}