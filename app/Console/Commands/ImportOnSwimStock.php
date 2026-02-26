<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProductItem;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ImportOnSwimStock extends Command
{
    protected $signature = 'import:onswim-stock {tenant}';
    protected $description = 'Import stock items for a specific tenant from OnSwim CSV';

    public function handle()
    {
        $tenantId = $this->argument('tenant');
        
        try {
            $tenant = \App\Models\Tenant::findOrFail($tenantId);
            tenancy()->initialize($tenant);
            $this->info("Tenant '{$tenantId}' initialized. Starting import...");
        } catch (\Exception $e) {
            $this->error("Could not find or initialize tenant: {$tenantId}");
            return;
        }

        // Fixed Path Logic: getenv('HOME') provides /Users/nischalsapkota
        $filePath = getenv('HOME') . '/Downloads/report_STR002.csv';

        if (!file_exists($filePath)) {
            $this->error("File not found at: {$filePath}");
            return;
        }

        $file = fopen($filePath, 'r');
        $headers = fgetcsv($file);
        
        $this->info("Importing Stock Items...");

        while (($row = fgetcsv($file)) !== FALSE) {
            $data = array_combine($headers, $row);

            // Mapping 'Stock No.' from your CSV to 'barcode'
            $barcode = trim($data['Stock No.'] ?? '');
            
            if (empty($barcode) || str_contains(strtoupper($data['Category'] ?? ''), 'NON STOCK')) {
                continue;
            }

            // Supplier mapping using 'company_name'
            $supplierName = $data['Supplier'] ?? 'Legacy Supplier';
            $supplier = Supplier::firstOrCreate(['company_name' => $supplierName]);

            // Create or Update Product Item in the tenant DB
            ProductItem::updateOrCreate(
                ['barcode' => $barcode],
                [
                    'barcode' => $barcode,
                    'custom_description' => $data['Description'] ?? null,
                    'cost_price' => floatval($data['Cost Price'] ?? 0),
                    'retail_price' => floatval($data['Sale Price'] ?? 0),
                    'markup' => floatval($data['Markup'] ?? 0),
                    'qty' => intval($data['Qty'] ?? 1),
                    'status' => (isset($data['Location']) && strtolower($data['Location']) === 'sold') ? 'sold' : 'in_stock',
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
                    'certificate_number' => $data['Cert. Number'] ?? null,
                    'certificate_agency' => $data['Cert. Agency'] ?? null,
                    'rfid_code' => $data['RFID Code'] ?? null,
                    'web_item' => (isset($data['Web Item']) && strtolower($data['Web Item']) === 'yes'),
                    'date_in' => !empty($data['Date In']) ? Carbon::parse($data['Date In'])->format('Y-m-d') : null,
                    'store_id' => 1, // Verified via Tinker
                    'supplier_id' => $supplier->id,
                ]
            );
        }

        fclose($file);
        $this->info("Stock import completed for tenant: {$tenantId}");
    }
}