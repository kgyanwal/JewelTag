<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProductItem;
use App\Models\Supplier;
use App\Models\Store;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ImportOnSwimStock extends Command
{
    // Ensure --rollback is NOT here, protecting against deletion
    protected $signature = 'import:onswim-stock {tenant} {--dry-run}';
    protected $description = 'Import stock from multiple OnSwim CSVs (stock & stock_entered) with zero data loss.';

    public function handle()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1G');

        $tenantId = $this->argument('tenant');
        $isDryRun = $this->option('dry-run');
        
        try {
            $tenant = Tenant::findOrFail($tenantId);
            tenancy()->initialize($tenant);

            $directoryPath = storage_path("/{$tenantId}/data");

            if (!File::exists($directoryPath)) {
                File::makeDirectory($directoryPath, 0755, true);
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

        // ── Define the strict dated files we want to process ──
        // Check a 5-day window to make this 100% bulletproof across ALL global timezones
        // ── Find recent files dynamically (Look back up to 5 days to ignore timezones) ──
        $possibleFiles = [];
        for ($i = 0; $i <= 5; $i++) {
            $dateStr = Carbon::now()->subDays($i)->format('Y_m_d');
            $possibleFiles[] = "stock_{$dateStr}.csv";
            $possibleFiles[] = "stock_entered_{$dateStr}.csv";
        }

        // Filter down to only files that actually exist in the folder right now
        $filesToProcess = [];
        foreach ($possibleFiles as $fileName) {
            if (File::exists("{$directoryPath}/{$fileName}")) {
                $filesToProcess[] = $fileName;
            }
        }

        if (empty($filesToProcess)) {
            $this->error("❌ NO FILES FOUND in {$directoryPath}");
            $this->line("The script searched for 'stock_YYYY_MM_DD.csv' and 'stock_entered_YYYY_MM_DD.csv' over the last 5 days.");
            $this->line("Please ensure your file is named correctly and uploaded.");
            return Command::SUCCESS; 
        }
        // Filter down to only files that actually exist in the folder right now
        $filesToProcess = [];
        foreach ($possibleFiles as $fileName) {
            if (File::exists("{$directoryPath}/{$fileName}")) {
                $filesToProcess[] = $fileName;
            }
        }

        if (empty($filesToProcess)) {
            $this->error("❌ NO FILES FOUND in {$directoryPath}");
            $this->line("Please upload 'stock_{$yesterday}.csv' or 'stock_entered_{$yesterday}.csv' and try again.");
            return Command::SUCCESS; 
        }

        $storeId = Store::first()?->id ?? 1;
        
        // Track totals across all files processed
        $totalRowsProcessed = 0;
        $itemsCreatedOrUpdated = 0;

        // Start a single transaction for all files
        DB::connection('tenant')->beginTransaction();

        try {
            foreach ($filesToProcess as $fileName) {
                $filePath = "{$directoryPath}/{$fileName}";
                $this->info("📥 Processing [{$fileName}]...");
                
                $file = fopen($filePath, 'r');
                $headers = array_map('trim', fgetcsv($file));
                
                // Build a quick array of rows to accurately feed the progress bar
                $rows = [];
                while (($row = fgetcsv($file)) !== FALSE) {
                    if (count($headers) === count($row)) {
                        $rows[] = array_combine($headers, $row);
                    }
                }
                fclose($file);

                $bar = $this->output->createProgressBar(count($rows));

                foreach ($rows as $data) {
                    $barcode = trim($data['Stock No.'] ?? '');
                    if (empty($barcode)) {
                        $bar->advance();
                        continue;
                    }

                    $totalRowsProcessed++;
                    $supplierName = $data['Supplier'] ?? 'Legacy Supplier';

                    if (!$isDryRun) {
                        $supplier = Supplier::withoutEvents(fn() => 
                            Supplier::firstOrCreate(['company_name' => $supplierName])
                        );

                        // updateOrCreate ensures NO duplicates and NO deletion of existing data.
                        ProductItem::withoutEvents(function () use ($barcode, $supplier, $data, $storeId) {
                            ProductItem::updateOrCreate(
                                ['barcode' => $barcode], // The unique identifier
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
                        $itemsCreatedOrUpdated++;
                    }
                    $bar->advance();
                }
                $bar->finish();
                $this->newLine(2);
            }

            if ($isDryRun) {
                DB::connection('tenant')->rollBack(); 
                $this->info("✅ DRY RUN COMPLETE: Processed {$totalRowsProcessed} valid rows across " . count($filesToProcess) . " file(s). No database entries were modified.");
            } else {
                DB::connection('tenant')->commit(); 
                $this->info("✅ SUCCESS: Synchronized {$itemsCreatedOrUpdated} items across " . count($filesToProcess) . " file(s).");
            }

        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            $this->error("\nDatabase Error: " . $e->getMessage());
        }

        return Command::SUCCESS;
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