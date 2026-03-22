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
    protected $description = 'Import customers with recursive conflict resolution to prevent 1062 unique key errors';

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
            $bar = $this->output->createProgressBar(); // Optional: Visual progress

            while (($row = fgetcsv($file)) !== FALSE) {
                $totalRows++;
                $data = array_combine($headers, $row);

                $email = trim($data['Email'] ?? '');
                $phone = $this->formatPhone($data['Mobile'] ?? $data['Home Phone'] ?? '');
                $rawCustNo = trim($data['Cust No.'] ?? '');

                // Pass variables into the closure
                Customer::withoutEvents(function () use ($rawCustNo, $data, $email, $phone, &$created, &$updated, &$tempIdsCreated) {
                    $customer = null;

                    // 1. Try to find existing customer
                    if (!empty($rawCustNo)) {
                        $customer = Customer::where('customer_no', 'CUST-' . $rawCustNo)->first();
                    }

                    // 2. Create new if missing
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

                    // 🚀 3. THE FIX: Correctly check for Phone conflicts
                    if (!empty($phone)) {
                        $phoneQuery = Customer::where('phone', $phone);
                        
                        // Only exclude 'self' if the customer already exists in the database
                        if ($customer->exists) {
                            $phoneQuery->where('id', '!=', $customer->id);
                        }

                        if ($phoneQuery->exists()) {
                            // The phone belongs to someone else! Prevent the 1062 crash.
                            // If it's a new record, set phone to null. If updating, keep their old phone.
                            $phone = $customer->exists ? $customer->phone : null;
                        }
                    }

                    // 4. Map the data
                    $cityValue = !empty(trim($data['City/Province'] ?? '')) 
                                    ? $data['City/Province'] 
                                    : ($data['Suburb/City'] ?? null);

                    $customer->fill([
                        'name' => $data['First Name'] ?? 'Walk-in',
                        'last_name' => $data['Last Name'] ?? null,
                        'email' => $email ?: $customer->email,
                        'phone' => $phone,
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

                    $customer->save();
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