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
               // Inside ListProductItems.php -> bulk_scan action
->action(function (array $data, GeminiInvoiceService $service) {
    // 🔹 Ensure we get the correct path from the 'public' disk
    $filePath = storage_path('app/public/' . $data['invoice_file']);
    
    \Illuminate\Support\Facades\Log::info("Starting Bulk Scan for file: " . $filePath);

    $extracted = $service->process($filePath);

    if (!$extracted) {
        Notification::make()
            ->title('Scanning failed')
            ->body('Check storage/logs/laravel.log for details.')
            ->danger()
            ->send();
        return;
    }

    if (!isset($extracted['items']) || count($extracted['items']) === 0) {
        Notification::make()
            ->title('No items found')
            ->body('The AI could not find any jewelry items in this image.')
            ->warning()
            ->send();
        return;
    }

    $supplier = \App\Models\Supplier::firstOrCreate(['company_name' => $extracted['vendor_name']]);
    
    // Logic for Unique sequential Stock Numbers (G1001, G1002...)
    $lastItem = \App\Models\ProductItem::where('barcode', 'LIKE', 'G%')
                ->orderByRaw('CAST(SUBSTRING(barcode, 2) AS UNSIGNED) DESC')
                ->first();
                
    $nextNumber = $lastItem ? ((int) preg_replace('/[^0-9]/', '', $lastItem->barcode)) + 1 : 1001;

    foreach ($extracted['items'] as $item) {
        \App\Models\ProductItem::create([
            'store_id' => auth()->user()->store_id ?? 1,
            'supplier_id' => $supplier->id,
            'barcode' => 'G' . $nextNumber,
            'custom_description' => $item['description'] ?? 'Scanned Item',
            'qty' => $item['quantity'] ?? 1,
            'cost_price' => $item['cost_price'] ?? 0,
            'retail_price' => ($item['cost_price'] ?? 0) * 2.5,
            'metal_type' => $item['metal_type'] ?? 'General',
            'metal_weight' => $item['metal_weight'] ?? 0,
            'supplier_code' => $item['style_code'] ?? null,
            'status' => 'in_stock',
        ]);
        $nextNumber++;
    }

    Notification::make()
        ->title('Bulk Scan Success')
        ->body("Imported " . count($extracted['items']) . " items.")
        ->success()
        ->send();
})
        ];
    }
}