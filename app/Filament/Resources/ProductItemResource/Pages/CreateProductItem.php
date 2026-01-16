<?php

namespace App\Filament\Resources\ProductItemResource\Pages;

use App\Filament\Resources\ProductItemResource;
use App\Services\InvoiceOcrService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

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
            ->image() // 🔹 This validates it is an actual image
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg', 'image/webp']) // 🔹 Adds extra safety
            ->disk('public')
            ->directory('invoice-scans')
            ->visibility('public') // 🔹 Ensures the OCR service can read the file
            ->required(),
    ])
                ->action(function (array $data, InvoiceOcrService $ocrService) {
                    $path = storage_path('app/public/' . $data['invoice_image']);
                    
                    try {
                        $extracted = $ocrService->extractDataFromImage($path);

                        // Fills the form fields automatically
                        $this->form->fill([
                            'supplier_code' => $extracted['supplier_code'],
                            'cost_price' => $extracted['cost_price'],
                            'custom_description' => $extracted['custom_description'],
                            'qty' => 1, // Defaulting to 1 for a single invoice item
                        ]);

                        Notification::make()->title('Invoice Data Retrieved!')->success()->send();
                    } catch (\Exception $e) {
                        Notification::make()->title('OCR Error')->body($e->getMessage())->danger()->send();
                    }
                }),
        ];
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
{
    $qty = (int) ($data['qty'] ?? 1);
    unset($data['qty'], $data['options'], $data['creation_mode']);

    $firstRecord = null;

    // 1. Find the highest existing "G" number in the database
    $lastBarcode = \App\Models\ProductItem::where('barcode', 'LIKE', 'G%')
        ->orderByRaw('CAST(SUBSTRING(barcode, 2) AS UNSIGNED) DESC')
        ->value('barcode');

    // 2. Extract the number part (e.g., "1008" from "G1008") or start at 1000
    $lastNumber = $lastBarcode ? (int) substr($lastBarcode, 1) : 1000;

    for ($i = 0; $i < $qty; $i++) {
        $itemData = $data;
        $itemData['store_id'] = 1; 

        // 3. Increment correctly for each item in the loop
        $nextNumber = $lastNumber + $i + 1;
        $itemData['barcode'] = 'G' . $nextNumber;
        
        $record = static::getModel()::create($itemData);
        
        if ($i === 0) $firstRecord = $record;
    }

    return $firstRecord;
}
}