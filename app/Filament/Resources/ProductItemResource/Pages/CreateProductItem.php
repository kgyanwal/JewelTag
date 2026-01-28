<?php

namespace App\Filament\Resources\ProductItemResource\Pages;

use App\Filament\Resources\ProductItemResource;
use App\Services\InvoiceOcrService;
use App\Services\ZebraPrinterService;
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
                            'supplier_code' => $extracted['supplier_code'],
                            'cost_price' => $extracted['cost_price'],
                            'custom_description' => $extracted['custom_description'],
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
    $storeId = $data['store_id'] ?? \App\Models\Store::first()?->id;
    $trackingEnabled = $data['enable_rfid_tracking'] ?? false;

    // We keep 'qty' in $data so it SAVES to the database
    unset($data['print_options'], $data['creation_mode']);

    $firstRecord = null;
    $lastBarcode = \App\Models\ProductItem::where('barcode', 'LIKE', 'G%')
        ->orderByRaw('CAST(SUBSTRING(barcode, 2) AS UNSIGNED) DESC')
        ->value('barcode');
    $lastNumber = $lastBarcode ? (int) substr($lastBarcode, 1) : 1000;

    for ($i = 0; $i < $qty; $i++) {
        $itemData = $data;
        $itemData['store_id'] = $storeId;
        $itemData['barcode'] = 'G' . ($lastNumber + $i + 1);
        
        if ($trackingEnabled) {
            $itemData['rfid_code'] = strtoupper(substr(md5(uniqid() . $i), 0, 24));
        }

        // 🔹 This now saves the 'qty' you entered into the DB column
        $record = static::getModel()::create($itemData);

        // ... (Printing logic)
        if ($i === 0) $firstRecord = $record;
    }

    return $firstRecord;
}
}