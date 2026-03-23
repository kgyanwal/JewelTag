<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Support\Facades\DB;

class ImportOnSwimNonStock extends Command
{
    protected $signature = 'import:onswim-nonstock {tenant}';
    protected $description = 'Import Non-Stock items (Grills, Repairs, Custom Labor) from OnSwim';

    public function handle()
    {
        $tenantId = $this->argument('tenant');
        tenancy()->initialize(\App\Models\Tenant::findOrFail($tenantId));
        $directoryPath = storage_path("app/data");
        $filePath = "{$directoryPath}/non_stock_sales_dsq23mar.csv";

        $file = fopen($filePath, 'r');
        $headers = array_map('trim', fgetcsv($file));

        $this->info("Importing Non-Stock Detailed Items...");
        $bar = $this->output->createProgressBar();

        while (($row = fgetcsv($file)) !== FALSE) {
            $data = array_combine($headers, $row);
            $jobNo = trim($data['Job No.']);

            // Find the parent sale
            $sale = Sale::where('invoice_number', 'like', "%{$jobNo}%")->first();
            if (!$sale) continue;

            SaleItem::updateOrCreate(
                [
                    'sale_id' => $sale->id,
                    'custom_description' => $data['Description'] ?? 'Service',
                ],
                [
                    'is_non_stock' => true,
                    'qty' => (float)($data['Qty'] ?? 1),
                    'cost_price' => $this->cleanMoney($data['Cost Price']),
                    'sold_price' => $this->cleanMoney($data['Sold Price']),
                    'job_description' => $data['Description'] ?? '',
                ]
            );
            $bar->advance();
        }
        $bar->finish();
        $this->info("\nImport Complete.");
    }

    private function cleanMoney($val)
    {
        $clean = str_replace(['$', ',', ' ', '-'], '', trim($val));
        return is_numeric($clean) ? (float)$clean : 0.00;
    }
}
