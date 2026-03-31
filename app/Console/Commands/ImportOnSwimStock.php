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
    /**
     * Added {--dry-run} to the signature
     */
    protected $signature = 'import:onswim-stock {tenant} {--rollback} {--dry-run}';
    protected $description = 'Import stock from OnSwim CSV with dynamic filename and optional Dry Run mode';

    public function handle()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1G');

        $tenantId = $this->argument('tenant');
        $isDryRun = $this->option('dry-run');
        
        try {
            $tenant = Tenant::findOrFail($tenantId);
            tenancy()->initialize($tenant);

            // 🚀 MATCHING YOUR 'LS' STRUCTURE: storage/tenantlxd/lxd/data/
            $directoryPath = storage_path("/{$tenantId}/data");

            // Ensure directory exists
            if (!File::exists($directoryPath)) {
                File::makeDirectory($directoryPath, 0755, true);
            }

            // 🚀 MATCHING YOUR FILENAME: stock_2026_03_30.csv
            // 'Y' = 2026, 'm' = 03, 'd' = 30
            $yesterday = Carbon::yesterday()->format('Y_m_d'); 
            
            $fileName = "stock_{$yesterday}.csv";
            $filePath = "{$directoryPath}/{$fileName}";

            if (!File::exists($filePath)) {
                $this->newLine();
                $this->error("❌ FILE NOT FOUND");
                $this->warn("Path: {$filePath}");
                $this->line("Please upload the file and try again.");
                return Command::SUCCESS; 
            }

        } catch (\Exception $e) {
            $this->error("Initialization Error: " . $e->getMessage());
            return Command::FAILURE;
        }

        if ($isDryRun) {
            $this->newLine();
            $this->warn("🛠️  DRY RUN ENABLED: No changes will be saved to the database.");
            $this->newLine();
        }

        // --- Start Processing ---

        $storeId = Store::first()?->id ?? 1;
        $file = fopen($filePath, 'r');
        $headers = array_map('trim', fgetcsv($file));

        $this->info("Processing [{$fileName}]...");
        $bar = $this->output->createProgressBar();

        // Start transaction but we will rollback if it's a dry run
        DB::connection('tenant')->beginTransaction();

        try {
            while (($row = fgetcsv($file)) !== FALSE) {
                if (count($headers) !== count($row)) continue;

                $data = array_combine($headers, $row);
                $barcode = trim($data['Stock No.'] ?? '');
                if (empty($barcode)) continue;

                $supplierName = $data['Supplier'] ?? 'Legacy Supplier';

                if (!$isDryRun) {
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
                } else {
                    // Log the simulated action if in dry run
                    // $this->info("Simulating update for barcode: {$barcode}");
                }
                
                $bar->advance();
            }

            if ($isDryRun) {
                DB::connection('tenant')->rollBack(); // 🚀 Rollback changes anyway
                $bar->finish();
                $this->newLine(2);
                $this->info("✅ DRY RUN COMPLETE: Data verified. No database entries were created.");
            } else {
                DB::connection('tenant')->commit(); // 🚀 Save changes
                $bar->finish();
                $this->newLine(2);
                $this->info("✅ SUCCESS: Stock synchronized from {$fileName}");
            }

            fclose($file);

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