<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProductItem;
use App\Models\Supplier;
use App\Models\Store;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;

class ImportOnSwimStock extends Command
{
    protected $signature = 'import:onswim-stock {tenant} {--rollback}';
    protected $description = 'Import stock items from OnSwim CSV with dynamic filename and safe file-check';

    public function handle()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1G');

        $tenantId = $this->argument('tenant');
        
        try {
            $tenant = Tenant::findOrFail($tenantId);
            tenancy()->initialize($tenant);

            // 🚀 DYNAMIC PATH BUILDER
            $dbName = config('database.connections.tenant.database');
            $directoryPath = storage_path("{$dbName}/{$tenantId}/data");

            // 🚀 ENSURE DIRECTORY EXISTS
            if (!File::exists($directoryPath)) {
                File::makeDirectory($directoryPath, 0755, true);
            }

            // 🚀 DYNAMIC FILENAME (e.g., stock_mar30_26.csv)
            $yesterday = Carbon::yesterday()->format('M d, y');
            $formattedDate = str_replace([' ', ','], '', strtolower($yesterday)); 
            $fileName = "stock_" . $formattedDate . ".csv";
            $filePath = "{$directoryPath}/{$fileName}";

            // 🛡️ GRACEFUL CHECK: Prevent Crash if File is Missing
            if (!File::exists($filePath)) {
                $this->newLine();
                $this->error("FAILED: File not found.");
                $this->warn("Expected Location: {$filePath}");
                $this->line("Action Required: Please upload the CSV for yesterday's stock and try again.");
                $this->newLine();
                
                return Command::SUCCESS; // Exit safely without throwing an exception
            }

        } catch (\Exception $e) {
            $this->error("System Initialization Error: " . $e->getMessage());
            return Command::FAILURE;
        }

        // --- Start Processing if file exists ---

        $storeId = Store::first()?->id ?? 1;
        $file = fopen($filePath, 'r');
        $headers = array_map('trim', fgetcsv($file));

        $this->info("File Detected. Importing from [{$fileName}]...");
        $bar = $this->output->createProgressBar();

        DB::connection('tenant')->beginTransaction();

        try {
            while (($row = fgetcsv($file)) !== FALSE) {
                if (count($headers) !== count($row)) continue;

                $data = array_combine($headers, $row);
                $barcode = trim($data['Stock No.'] ?? '');
                if (empty($barcode)) continue;

                $supplierName = $data['Supplier'] ?? 'Legacy Supplier';
                $supplier = Supplier::withoutEvents(fn() => 
                    Supplier::firstOrCreate(['company_name' => $supplierName])
                );

                ProductItem::withoutEvents(function () use ($barcode, $supplier, $data, $storeId) {
                    ProductItem::updateOrCreate(
                        ['barcode' => $barcode],
                        [
                            'barcode'            => $barcode,
                            'supplier_id'        => $supplier->id,
                            'supplier_code'      => $data['Supplier Code'] ?? null,
                            'category'           => $data['Category'] ?? 'General Stock',
                            'custom_description' => $data['Description'] ?? null,
                            'cost_price'         => $this->cleanMoney($data['Cost Price'] ?? 0),
                            'retail_price'       => $this->cleanMoney($data['Sale Price'] ?? 0),
                            'web_price'          => $this->cleanMoney($data['Web Price'] ?? 0),
                            'markup'             => $this->cleanMoney($data['Markup'] ?? 0),
                            'qty'                => intval($data['Qty'] ?? 1),
                            'status'             => $this->mapStatus($data['Location'] ?? ''),
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
                });
                $bar->advance();
            }

            DB::connection('tenant')->commit();
            $bar->finish();
            fclose($file);
            $this->newLine();
            $this->info("✅ Stock Import Completed.");

        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            if (isset($file)) fclose($file);
            $this->error("\nDatabase Error: " . $e->getMessage());
        }
    }

    private function cleanMoney($value) {
        $clean = str_replace(['$', ',', ' ', '-'], '', trim($value));
        return is_numeric($clean) ? (float)$clean : 0.00;
    }

    private function mapStatus($location) {
        $location = strtolower(trim($location));
        return match ($location) {
            'sold' => 'sold',
            'inactive' => 'inactive',
            'returned to supplier' => 'returned',
            default => 'in_stock',
        };
    }

    private function parseDate($date) {
        if (empty($date) || $date == '-') return null;
        try { return Carbon::parse($date)->format('Y-m-d'); } 
        catch (\Exception $e) { return null; }
    }
}