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

        $directoryPath = storage_path("/data");
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
            while (($row = fgetcsv($file)) !== FALSE) {
                $totalRows++;
                $data = array_combine($headers, $row);

                $email = trim($data['Email'] ?? '');
                $phone = $this->formatPhone($data['Mobile'] ?? $data['Home Phone'] ?? '');
                $rawCustNo = trim($data['Cust No.'] ?? '');

                // 🚀 REASON FIX: If ID is empty, generate one instead of skipping!
                if (empty($rawCustNo)) {
                    $fullCustNo = 'CUST-TEMP-' . strtoupper(Str::random(6));
                    $tempIdsCreated++;
                } else {
                    $fullCustNo = 'CUST-' . $rawCustNo;
                }

                Customer::withoutEvents(function () use ($fullCustNo, $data, $email, $phone, &$created, &$updated) {
                    // Check for existing by customer_no
                    $customer = Customer::where('customer_no', $fullCustNo)->first();

                    if (!$customer) {
                        $customer = new Customer();
                        $customer->customer_no = $fullCustNo;
                        $created++;
                    } else {
                        $updated++;
                    }

                    // Prevent phone conflict with OTHER records
                    if (!empty($phone) && $phone !== $customer->phone) {
                        $phoneExists = Customer::where('phone', $phone)->where('id', '!=', $customer->id)->exists();
                        if ($phoneExists) {
                            $phone = $customer->phone; // Keep old phone if new one belongs to someone else
                        }
                    }

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
            }

            DB::connection('tenant')->commit();
            fclose($file);
            
            $this->info("Import finished!");
            $this->table(
                ['Processed Rows', 'Created', 'Updated', 'Generated Temp IDs'],
                [[$totalRows, $created, $updated, $tempIdsCreated]]
            );

        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            if (isset($file)) fclose($file);
            $this->error("Critical Error: " . $e->getMessage());
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