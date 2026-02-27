<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ImportOnSwimCustomers extends Command
{
    protected $signature = 'import:onswim-customers {tenant} {--rollback}';
    protected $description = 'Import customers and overwrite existing records based on Customer Number';

    public function handle()
    {
        $tenantId = $this->argument('tenant');
        
        try {
            $tenant = \App\Models\Tenant::findOrFail($tenantId);
            tenancy()->initialize($tenant);
        } catch (\Exception $e) {
            $this->error("Could not find or initialize tenant: {$tenantId}");
            return;
        }

        // 1. ROLLBACK LOGIC
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

        // 2. FILE PROCESSING
        $directoryPath = storage_path("app/data/{$tenantId}");
        $filePath = "{$directoryPath}/customer_dsqdata_feb25_26.csv";

        if (!file_exists($filePath)) {
            $this->error("File not found at: {$filePath}");
            return;
        }

        $file = fopen($filePath, 'r');
        $headers = array_map('trim', fgetcsv($file));

        $this->info("Starting Full Customer Import for {$tenantId}...");
        DB::connection('tenant')->beginTransaction();

        try {
            while (($row = fgetcsv($file)) !== FALSE) {
                $data = array_combine($headers, $row);

                $email = trim($data['Email'] ?? '');
                $phone = $this->formatPhone($data['Mobile'] ?? $data['Home Phone'] ?? '');
                
                // 🚀 THE FIX: Identify the unique Customer Number from the CSV
                $rawCustNo = trim($data['Cust No.'] ?? '');
                if (empty($rawCustNo)) continue; // Skip if no number
                $fullCustNo = 'CUST-' . $rawCustNo;

                // City logic
                $cityValue = !empty(trim($data['City/Province'] ?? '')) 
                                ? $data['City/Province'] 
                                : ($data['Suburb/City'] ?? null);

                // 🚀 UPDATED LOGIC: 
                // We now use 'customer_no' as the unique key to find the record.
                // If CUST-5532 exists, it will UPDATE. If not, it will INSERT.
                Customer::updateOrCreate(
                    ['customer_no' => $fullCustNo], 
                    [
                        'name' => $data['First Name'] ?? 'Walk-in',
                        'last_name' => $data['Last Name'] ?? null,
                        'email' => $email ?: null,
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
                    ]
                );
            }

            DB::connection('tenant')->commit();
            fclose($file);
            $this->info("Customer import completed successfully.");

        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            if (isset($file)) fclose($file);
            $this->error("Error during import: " . $e->getMessage());
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
        try { 
            return Carbon::parse($date)->format('Y-m-d'); 
        } catch (\Exception $e) { 
            return null; 
        }
    }
}