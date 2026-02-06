<?php

namespace App\Filament\Resources\ProductItemResource\Pages;

use App\Filament\Resources\ProductItemResource;
use App\Services\InvoiceOcrService;
use App\Models\ProductItem;
use App\Models\Store;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class CreateProductItem extends CreateRecord
{
    protected static string $resource = ProductItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('scanInvoice')
                ->label('Scan Physical Invoice')
                ->icon('heroicon-o-camera')
                ->color('info')
                ->form([
                    FileUpload::make('invoice_image')
                        ->label('Upload or Take Photo of Supplier Invoice')
                        ->image()
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg', 'image/webp'])
                        ->disk('public')
                        ->directory('invoice-scans')
                        ->visibility('public')
                        ->required(),
                ])
                ->action(function (array $data, InvoiceOcrService $ocrService) {
                    $path = storage_path('app/public/' . $data['invoice_image']);
                    try {
                        $extracted = $ocrService->extractDataFromImage($path);
                        $this->form->fill([
                            'supplier_code' => $extracted['supplier_code'] ?? null,
                            'cost_price' => $extracted['cost_price'] ?? 0,
                            'custom_description' => $extracted['custom_description'] ?? '',
                            'qty' => 1,
                        ]);
                        Notification::make()->title('Invoice Data Retrieved!')->success()->send();
                    } catch (\Exception $e) {
                        Notification::make()->title('OCR Error')->body($e->getMessage())->danger()->send();
                    }
                }),
        ];
    }

protected function handleRecordCreation(array $data): Model
{
    $qty = (int) ($data['qty'] ?? 1);
    $storeId = $data['store_id'] ?? Store::first()?->id;

    // 🔹 1. Safely check for trade-in status
    $tradeInNo = $data['original_trade_in_no'] ?? null;
    $isTradeIn = ($tradeInNo !== null);

    // 🔹 2. Clean up non-database fields
    unset($data['print_options'], $data['creation_mode']);

    $firstRecord = null;
    
    // 🔹 3. UPDATED: Generate the next sequence starting with "D"
    $lastBarcode = ProductItem::where('barcode', 'LIKE', 'D%')
        ->orderByRaw('CAST(SUBSTRING(barcode, 2) AS UNSIGNED) DESC')
        ->value('barcode');
        
    // Start at 1000 if no "D" barcodes exist yet
    $lastNumber = $lastBarcode ? (int) substr($lastBarcode, 1) : 1000;

    for ($i = 0; $i < $qty; $i++) {
        $itemData = $data;
        $itemData['store_id'] = $storeId;
        
        // 🔹 4. UPDATED: Assign the new "D" prefix barcode
        $itemData['barcode'] = 'D' . ($lastNumber + $i + 1);
        
        $itemData['is_trade_in'] = $isTradeIn;
        $itemData['original_trade_in_no'] = $tradeInNo;

        if ($data['enable_rfid_tracking'] ?? false) {
            $itemData['rfid_code'] = strtoupper(bin2hex(random_bytes(12)));
        }

        // Create the record
        $record = static::getModel()::create($itemData);

        if ($i === 0) $firstRecord = $record;
    }

    return $firstRecord;
}
}