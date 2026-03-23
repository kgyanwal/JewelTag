<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sale;
use App\Models\Laybuy;
use App\Models\Tenant;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ImportOnSwimDeposits extends Command
{
    protected $signature = 'import:onswim-deposits {tenant}';
    protected $description = 'Import OnSwim Deposit Sales/Laybuys and link to existing sales';

    public function handle()
    {
        $tenantId = $this->argument('tenant');
        $tenant = Tenant::findOrFail($tenantId);
        tenancy()->initialize($tenant);

        $directoryPath = storage_path("app/data");
$filePath = "{$directoryPath}/dsq23mar_deposit_sales.csv";

        if (!file_exists($filePath)) {
            $this->error("File not found at: {$filePath}");
            return;
        }

        $file = fopen($filePath, 'r');
        $header = array_map('trim', fgetcsv($file)); // Clean headers

        $this->info("Importing Deposit Sales for {$tenantId}...");

        while (($row = fgetcsv($file)) !== FALSE) {
            $data = array_combine($header, $row);

            $jobNo = trim($data['Job No.']);
            if (empty($jobNo)) continue;

            // 1. Find the Sale record
            $sale = Sale::where('invoice_number', $jobNo)
                        ->orWhere('invoice_number', 'D' . $jobNo)
                        ->orWhere('invoice_number', 'like', "%-{$jobNo}")
                        ->first();

            if (!$sale) {
                $this->warn("Skipping Job #{$jobNo}: No matching Sale found.");
                continue;
            }

            // 🚀 PROCESS STAFF: Split "Jeanette/Anthony" into ["Jeanette", "Anthony"]
            $rawStaff = $data['Staff'] ?? 'System';
            $staffList = str_contains($rawStaff, '/') ? explode('/', $rawStaff) : [$rawStaff];

            // 2. Safely create or update the Laybuy record
            Laybuy::updateOrCreate(
                ['sale_id' => $sale->id], 
                [
                    'customer_id'  => $sale->customer_id,
                    'laybuy_no'    => 'LAY-' . $jobNo,
                    'total_amount' => $this->cleanMoney($data['Invoice Total']),
                    'amount_paid'  => $this->cleanMoney($data['Payment Total']),
                    'balance_due'  => $this->cleanMoney($data['Balance']),
                    'status'       => $this->cleanMoney($data['Balance']) <= 0.50 ? 'completed' : 'in_progress',
                    'sales_person' => $rawStaff,
                    'staff_list'   => array_map('trim', $staffList), // 🚀 Save as clean array
                    'start_date'   => $this->parseDate($data['Date Sold']),
                    'last_paid_date' => $this->parseDate($data['Last Date Paid']),
                    'due_date'     => $this->parseDate($data['Date Required']),
                    'notes'        => "Imported from OnSwim. Last Paid: " . ($data['Last Date Paid'] ?? 'N/A'),
                ]
            );
        }

        fclose($file);
        $this->info("Import complete!");
    }

    private function cleanMoney($value) {
        // 🚀 FIXED: Handles "-" safely by treating it as 0
        $clean = str_replace(['$', ',', ' '], '', trim($value ?: '0'));
        return ($clean === '-' || !is_numeric($clean)) ? 0.00 : (float)$clean;
    }

    private function parseDate($date) {
        if (empty($date) || $date === '-') return null;
        try {
            return Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}