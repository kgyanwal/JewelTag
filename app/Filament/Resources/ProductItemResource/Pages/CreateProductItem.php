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
                ->label('Scan Physical Invoice')->icon('heroicon-o-camera')->color('info')
                ->form([FileUpload::make('invoice_image')->image()->disk('public')->directory('invoice-scans')->required()])
                ->action(function (array $data, InvoiceOcrService $ocrService) {
                    $path = storage_path('app/public/' . $data['invoice_image']);
                    try {
                        $extracted = $ocrService->extractDataFromImage($path);
                        $this->form->fill(['supplier_code' => $extracted['supplier_code'] ?? null, 'cost_price' => $extracted['cost_price'] ?? 0, 'custom_description' => $extracted['custom_description'] ?? '', 'qty' => 1]);
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
        $tradeInNo = $data['original_trade_in_no'] ?? null;
        $department = $data['department'] ?? '';

        // 🔹 LOGIC: Should we generate RFID? (Always yes, unless it's a Repair)
        $isRepair = str_contains(strtolower($department), 'repair');
        
        unset($data['print_options'], $data['creation_mode'], $data['qty'], $data['enable_rfid_tracking']);

        $firstRecord = null;

        for ($i = 0; $i < $qty; $i++) {
            $itemData = $data;
            $itemData['store_id'] = $storeId;
            $itemData['is_trade_in'] = ($tradeInNo !== null);
            $itemData['original_trade_in_no'] = $tradeInNo;

            // 🔹 COLLISION FIX: Re-query inside loop
            $itemData['barcode'] = ProductItemResource::generatePersistentBarcode('D');

            // 🔹 AUTOMATIC 8-CHARACTER RFID GENERATION
            if (!$isRepair) {
                $itemData['rfid_code'] = strtoupper(bin2hex(random_bytes(4)));
            } else {
                $itemData['rfid_code'] = null;
            }

            $record = static::getModel()::create($itemData);
            if ($i === 0) $firstRecord = $record;
        }

        return $firstRecord;
    }
}