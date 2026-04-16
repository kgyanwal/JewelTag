<?php

namespace App\Filament\Resources\ProductItemResource\Pages;

use App\Filament\Resources\ProductItemResource;
use App\Services\TextractInvoiceService; // 🚀 Use Textract
use App\Models\ProductItem;
use App\Models\Supplier;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ListProductItems extends ListRecords
{
    protected static string $resource = ProductItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            
            Actions\Action::make('bulk_scan')
                ->label('Scan Jewelry Invoice')
                ->icon('heroicon-o-camera')
                ->color('warning')
                ->form([
                    FileUpload::make('invoice_file')
                        ->disk('public')
                        ->directory('invoices')
                        ->required()
                ])
                ->action(function (array $data, TextractInvoiceService $service) {
                    $filePath = storage_path('app/public/' . $data['invoice_file']);
                    
                    try {
                        Log::info("Starting Bulk Scan for file: " . $filePath);

                        // 🚀 1. Call the new Bulk Method
                        $extracted = $service->extractBulkDataFromImage($filePath);

                        if (empty($extracted['items'])) {
                            Notification::make()->title('No items found on this invoice.')->danger()->send();
                            return;
                        }

                        // 2. Fetch or Create Vendor (Textract reads Vendor names!)
                        $vendorName = $extracted['vendor_name'] !== 'Unknown Vendor' ? $extracted['vendor_name'] : 'Scanned Vendor';
                        $supplier = Supplier::firstOrCreate(['company_name' => $vendorName]);

                        // 3. Duplicate Check
                        $firstItemCode = $extracted['items'][0]['supplier_code'] ?? null;
                        if ($firstItemCode) {
                            $duplicateExists = ProductItem::where('supplier_id', $supplier->id)
                                ->where('supplier_code', $firstItemCode)
                                ->exists();

                            if ($duplicateExists) {
                                Notification::make()
                                    ->title('Potential Duplicate Detected')
                                    ->body("An item with code '{$firstItemCode}' already exists. Scan aborted to prevent duplicates.")
                                    ->danger()
                                    ->send();
                                return;
                            }
                        }

                        // 4. Database Transaction to insert all rows
                        DB::transaction(function () use ($extracted, $supplier) {
                            // Fetch default prefix for generating barcodes
                            $prefix = ProductItemResource::getPrefixForSubDepartment(null);

                            foreach ($extracted['items'] as $item) {
                                
                                // Loop based on Qty (If Qty is 3, create 3 separate tags)
                                for ($i = 0; $i < $item['qty']; $i++) {
                                    ProductItem::create([
                                        'store_id'           => auth()->user()->store_id ?? 1,
                                        'supplier_id'        => $supplier->id,
                                        // 🚀 Use your custom persistent barcode generator!
                                        'barcode'            => ProductItemResource::generatePersistentBarcode($prefix),
                                        'custom_description' => $item['custom_description'] ?: 'Scanned Item',
                                        'qty'                => 1, // Store as 1 per physical tag
                                        'cost_price'         => $item['cost_price'],
                                        'retail_price'       => $item['cost_price'] * 2.5,
                                        'web_price'          => $item['cost_price'] * 2.5,
                                        'metal_type'         => $item['metal_type'],
                                        'diamond_weight'     => $item['diamond_weight'],
                                        'size'               => $item['size'],
                                        'supplier_code'      => $item['supplier_code'],
                                        'status'             => 'in_stock',
                                        // Generate an RFID if needed
                                        'rfid_code'          => strtoupper(bin2hex(random_bytes(4))),
                                    ]);
                                }
                            }
                        });

                        Notification::make()
                            ->title('Bulk Scan Success')
                            ->body("Successfully imported line items from {$vendorName}.")
                            ->success()
                            ->send();

                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Bulk Scan Error')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
        ];
    }
}