<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProductItem;
use App\Models\Supplier;
use Carbon\Carbon;

class ImportOnSwimInactiveStock extends Command
{
    /**
     * Usage: php artisan import:onswim-inactive lxdiamond
     */
    protected $signature = 'import:onswim-inactive {tenant}';
    protected $description = 'Import inactive/archived stock for a specific tenant';

    public function handle()
    {
        $tenantId = $this->argument('tenant');
        
        try {
            $tenant = \App\Models\Tenant::findOrFail($tenantId);
            tenancy()->initialize($tenant);
            $this->info("Tenant '{$tenantId}' initialized.");
        } catch (\Exception $e) {
            $this->error("Tenant not found.");
            return;
        }

        $filePath = getenv('HOME') . '/Downloads/report_STR003.csv';

        if (!file_exists($filePath)) {
            $this->error("File not found at: {$filePath}");
            return;
        }

        $file = fopen($filePath, 'r');
        $headers = fgetcsv($file);
        $headerCount = count($headers);
        
        $this->info("Importing Inactive Items...");

        while (($row = fgetcsv($file)) !== FALSE) {
            // 🚨 FIX: Align row elements with header count to prevent array_combine error
            $rowCount = count($row);
            if ($rowCount > $headerCount) {
                $row = array_slice($row, 0, $headerCount);
            } elseif ($rowCount < $headerCount) {
                $row = array_pad($row, $headerCount, null);
            }

            $data = array_combine($headers, $row);

            $barcode = trim($data['Stock No.'] ?? '');
            if (empty($barcode)) continue;

            // 1. Get or Create Supplier
            $supplierName = $data['Supplier'] ?? 'Legacy Supplier';
            $supplier = Supplier::firstOrCreate(['company_name' => $supplierName]);

            // 2. Map Jewelry and Inactivation Details
            ProductItem::updateOrCreate(
                ['barcode' => $barcode],
                [
                    'barcode' => $barcode,
                    'custom_description' => $data['Description'] ?? null,
                    'cost_price' => floatval($data['Cost Price'] ?? 0),
                    'retail_price' => floatval($data['Sale Price'] ?? 0),
                    'qty' => intval($data['Qty'] ?? 0),
                    'status' => 'inactive', // Explicitly archive these
                    
                    // Jewelry Specifications
                    'metal_type' => $data['Metal Type'] ?? null,
                    'diamond_weight' => $data['Carat Weight'] ?? null,
                    'shape' => $data['Shape'] ?? null,
                    'color' => $data['Colour'] ?? null,
                    'clarity' => $data['Clarity'] ?? null,
                    'cut' => $data['Cut'] ?? null,
                    'polish' => $data['Polish'] ?? null,
                    'symmetry' => $data['Symmetry'] ?? null,
                    'fluorescence' => $data['Fluorescence'] ?? null,
                    'measurements' => $data['Measurements'] ?? null,
                    
                    // Metadata Tracking
                    'date_in' => !empty($data['Date In']) ? $this->parseDate($data['Date In']) : null,
                    'inactivated_at' => !empty($data['Date Inactivated']) ? $this->parseDate($data['Date Inactivated']) : null,
                    'inactivated_by' => $data['Inactivated By'] ?? null,
                    'inactivated_reason' => $data['Inactivated Reason'] ?? null,
                    
                    'supplier_id' => $supplier->id,
                    'store_id' => 1,
                ]
            );
        }

        fclose($file);
        $this->info("Inactive stock import completed for {$tenantId}.");
    }

    private function parseDate($date)
    {
        try {
            return Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}