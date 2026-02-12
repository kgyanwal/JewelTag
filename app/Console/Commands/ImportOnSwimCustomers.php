<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ImportOnSwimCustomers extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'import:onswim-customers';

    /**
     * The console command description.
     */
    protected $description = 'Import customers from OnSwim CSV in Downloads with duplicate phone protection';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Path to your Mac Downloads folder
        $home = getenv('HOME');
        $filePath = $home . '/Downloads/dsq_customers_-1.csv';

        if (!file_exists($filePath)) {
            $this->error("File not found at: {$filePath}");
            return;
        }

        // Count lines for the progress bar (subtracting header)
        $lines = file($filePath);
        $lineCount = count($lines) - 1;
        
        $file = fopen($filePath, 'r');
        $headers = fgetcsv($file);

        $this->info("Starting Customer Import ($lineCount records)...");
        $bar = $this->output->createProgressBar($lineCount);
        $bar->start();

        while (($row = fgetcsv($file)) !== FALSE) {
            $data = array_combine($headers, $row);

            // 1. Clean and Format Phone Number with persistent +1 logic
            $mobile = trim($data['Mobile'] ?? '');
            if (!empty($mobile)) {
                $digits = preg_replace('/[^0-9]/', '', $mobile);
                // Avoid +11 if the user already typed 1 followed by 10 digits
                if (str_starts_with($digits, '1') && strlen($digits) > 10) {
                    $digits = substr($digits, 1);
                }
                $mobile = '+1' . $digits;
            } else {
                // Since your DB column is now nullable, use null to avoid unique clash on empty string
                $mobile = null;
            }

            // 2. Format Birthdate
            $dob = null;
            if (!empty($data['Birthdate'])) {
                try {
                    $dob = Carbon::parse($data['Birthdate'])->format('Y-m-d');
                } catch (\Exception $e) {
                    $dob = null;
                }
            }

            // 3. Duplicate Protection Logic
            // First, find if this person exists by Email
            $existingCustomer = Customer::where('email', $data['Email'])->whereNotNull('email')->first();

            // If not found by email, find if the phone number is already taken
            if (!$existingCustomer && !empty($mobile)) {
                $existingCustomer = Customer::where('phone', $mobile)->first();
            }

            // 4. Update existing or create new
            Customer::updateOrCreate(
                ['id' => $existingCustomer?->id ?? 0], // If ID is 0, it creates a new record
                [
                    'email' => $data['Email'] ?: ($existingCustomer?->email ?? 'no-email-' . $data['Cust No.'] . '@example.com'),
                    'customer_no' => $existingCustomer?->customer_no ?? 'CUST-' . $data['Cust No.'],
                    'name' => $data['First Name'],
                    'last_name' => $data['Last Name'],
                    'phone' => $mobile,
                    'home_phone' => $data['Home Phone'] ?? null,
                    'street' => $data['Street'] ?? null,
                    'suburb' => $data['Suburb/City'] ?? null,
                    'city' => $data['City/Province'] ?? null,
                    'postcode' => $data['Postcode'] ?? null,
                    'dob' => $dob,
                    'is_active' => true,
                    'loyalty_tier' => 'standard',
                ]
            );

            $bar->advance();
        }

        fclose($file);
        $bar->finish();
        
        $this->newLine(2);
        $this->info("Import completed: Processed {$lineCount} rows.");
    }
}