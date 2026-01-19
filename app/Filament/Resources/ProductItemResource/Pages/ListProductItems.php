<?php

namespace App\Filament\Resources\ProductItemResource\Pages;

use App\Filament\Resources\ProductItemResource;
use App\Services\GeminiInvoiceService;
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
                ->action(function (array $data, GeminiInvoiceService $service) {
                    $filePath = storage_path('app/public/' . $data['invoice_file']);
                    
                    Log::info("Starting Bulk Scan for file: " . $filePath);

                    $extracted = $service->process($filePath);

                    if (!$extracted || empty($extracted['items'])) {
                        Notification::make()->title('Scanning failed or no items found')->danger()->send();
                        return;
                    }

                    // 1. Get the Supplier first to check against their specific codes
                    $supplier = Supplier::firstOrCreate(['company_name' => $extracted['vendor_name']]);

                    // 2. DUPLICATE CHECK: Check if the FIRST item's style code already exists for this supplier
                    // This is a "No Migration" way to see if the invoice was already scanned
                    $firstItemCode = $extracted['items'][0]['style_code'] ?? null;
                    
                    if ($firstItemCode) {
                        $duplicateExists = ProductItem::where('supplier_id', $supplier->id)
                            ->where('supplier_code', $firstItemCode)
                            ->exists();

                        if ($duplicateExists) {
                            Notification::make()
                                ->title('Potential Duplicate Detected')
                                ->body("An item with style code '{$firstItemCode}' already exists for {$supplier->company_name}. Scan aborted to prevent duplicates.")
                                ->danger()
                                ->send();
                            return;
                        }
                    }

                    // 3. DATABASE TRANSACTION
                    DB::transaction(function () use ($extracted, $supplier) {
                        // Logic for Unique sequential Stock Numbers
                        $lastItem = ProductItem::where('barcode', 'LIKE', 'G%')
                                    ->orderByRaw('CAST(SUBSTRING(barcode, 2) AS UNSIGNED) DESC')
                                    ->first();
                                    
                        $nextNumber = $lastItem ? ((int) preg_replace('/[^0-9]/', '', $lastItem->barcode)) + 1 : 1001;

                        foreach ($extracted['items'] as $item) {
                            ProductItem::create([
                                'store_id' => auth()->user()->store_id ?? 1,
                                'supplier_id' => $supplier->id,
                                'barcode' => 'G' . $nextNumber,
                                'custom_description' => $item['description'] ?? 'Scanned Item',
                                'qty' => $item['quantity'] ?? 1,
                                'cost_price' => $item['cost_price'] ?? 0,
                                'retail_price' => ($item['cost_price'] ?? 0) * 2.5,
                                'metal_type' => $item['metal_type'] ?? 'General',
                                'metal_weight' => $item['metal_weight'] ?? 0,
                                'supplier_code' => $item['style_code'] ?? null, // Uses existing column
                                'status' => 'in_stock',
                            ]);
                            $nextNumber++;
                        }
                    });

                    Notification::make()
                        ->title('Bulk Scan Success')
                        ->body("Imported " . count($extracted['items']) . " items.")
                        ->success()
                        ->send();
                })
        ];
    }
}