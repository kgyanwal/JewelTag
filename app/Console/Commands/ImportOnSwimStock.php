<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProductItem;
use App\Models\Supplier;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ImportOnSwimStock extends Command
{
    /**
     * Usage: php artisan import:onswim-stock lxdiamond
     * Rollback: php artisan import:onswim-stock lxdiamond --rollback
     */
    protected $signature = 'import:onswim-stock {tenant} {--rollback}';
    protected $description = 'Import stock items from OnSwim CSV (STR003 format) with defensive mapping';

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

        // 1. SAFE ROLLBACK
        if ($this->option('rollback')) {
            $this->warn("Rolling back (deleting) stock for tenant: {$tenantId}...");
            if ($this->confirm('This will delete ALL stock items. Are you sure?', false)) {
                Schema::disableForeignKeyConstraints();
                ProductItem::truncate();
                Schema::enableForeignKeyConstraints();
                $this->info("Stock table truncated successfully.");
            }
            return;
        }

        // 2. FILE PROCESSING
        $directoryPath = storage_path("app/data/{$tenantId}");
        $filePath = "{$directoryPath}/stock_dsqdata_feb27_26.csv";

        if (!file_exists($filePath)) {
            $this->error("File not found at: {$filePath}");
            return;
        }

        $storeId = Store::first()?->id ?? 1;
        $file = fopen($filePath, 'r');
        // Trim headers to avoid hidden spaces
        $headers = array_map('trim', fgetcsv($file));

        $this->info("Starting Stock Import for {$tenantId}...");
        DB::connection('tenant')->beginTransaction();

        try {
            while (($row = fgetcsv($file)) !== FALSE) {
                $data = array_combine($headers, $row);

                // Mapping 'Stock No.' to 'barcode'
                $barcode = trim($data['Stock No.'] ?? '');
                if (empty($barcode)) continue;

                // Handle Supplier
                $supplierName = $data['Supplier'] ?? 'Legacy Supplier';
                $supplier = Supplier::firstOrCreate(['company_name' => $supplierName]);

                // Create or Update Product Item
                ProductItem::updateOrCreate(
                    ['barcode' => $barcode],
                    [
                        'barcode'            => $barcode,
                        'supplier_id'        => $supplier->id,
                        'supplier_code'      => $data['Supplier Code'] ?? null,
                        'category'           => $data['Category'] ?? 'General Stock', // Fallback if header missing
                        'custom_description' => $data['Description'] ?? null,
                        'cost_price'         => floatval($data['Cost Price'] ?? 0),
                        'retail_price'       => floatval($data['Sale Price'] ?? 0),
                        'web_price'          => floatval($data['Web Price'] ?? 0),
                        'markup'             => floatval($data['Markup'] ?? 0),
                        'qty'                => intval($data['Qty'] ?? 1),
                        'status'             => $this->mapStatus($data['Location'] ?? ''),
                        
                        // Physical Attributes
                        'metal_type'         => $data['Metal Type'] ?? null,
                        'diamond_weight'     => $data['Carat Weight'] ?? null,
                        'shape'              => $data['Shape'] ?? null,
                        'color'              => $data['Colour'] ?? null,
                        'clarity'            => $data['Clarity'] ?? null,
                        'cut'                => $data['Cut'] ?? null,
                        'polish'             => $data['Polish'] ?? null,
                        'symmetry'           => $data['Symmetry'] ?? null,
                        'fluorescence'       => $data['Fluorescence'] ?? null,
                        'measurements'       => $data['Measurements'] ?? null,
                        
                        // Certification & Metadata
                        'certificate_number' => $data['Cert. Number'] ?? null,
                        'certificate_agency' => $data['Cert. Agency'] ?? null,
                        'date_in'            => $this->parseDate($data['Date In'] ?? null),
                        'inactivated_at'     => $this->parseDate($data['Date Inactivated'] ?? null),
                        'inactivated_by'     => $data['Inactivated By'] ?? null,
                        'inactivated_reason' => $data['Inactivated Reason'] ?? null,
                        
                        'store_id'           => $storeId,
                        'web_item'           => (isset($data['Web Item']) && strtolower($data['Web Item']) === 'yes'),
                    ]
                );
            }

            DB::connection('tenant')->commit();
            fclose($file);
            $this->info("Stock import completed successfully.");

        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            $this->error("Error: " . $e->getMessage());
        }
    }

    private function mapStatus($location)
    {
        $location = strtolower(trim($location));
        return match ($location) {
            'sold' => 'sold',
            'inactive' => 'inactive',
            'returned to supplier' => 'returned',
            default => 'in_stock',
        };
    }

    private function parseDate($date)
    {
        if (empty($date)) return null;
        try { return Carbon::parse($date)->format('Y-m-d'); } 
        catch (\Exception $e) { return null; }
    }
}