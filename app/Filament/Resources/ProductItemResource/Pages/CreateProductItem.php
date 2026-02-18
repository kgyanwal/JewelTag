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
use App\Helpers\Staff; // 👈 Import Staff Helper
use Filament\Forms\Components\TextInput; // 👈 Import Input
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
protected function getFormActions(): array
    {
        return [
            Action::make('create')
                ->label('Add to Inventory')
                ->color('primary')
                ->icon('heroicon-o-plus')
                ->size(\Filament\Support\Enums\ActionSize::Large) 
                
                // 🎨 CUSTOM UI STYLING (Shadows, Width, Bold Text)
                ->extraAttributes([
                    'class' => 'w-full md:w-1/2 mx-auto shadow-xl font-black tracking-wider ring-1 ring-green-600/20 transform hover:scale-105 transition-all duration-300',
                    'style' => 'height: 3.5rem; font-size: 1.1rem;', 
                ])
                
                // Pop-up Config
                ->requiresConfirmation()
                ->modalHeading('Staff Verification')
                ->modalDescription('Enter your PIN to authorize adding this stock.')
                ->modalSubmitActionLabel('Verify & Save')
                
                // PIN Form
                ->form([
                    TextInput::make('verification_pin')
                        ->label('Enter PIN')
                        ->password()
                        ->revealable()
                        ->required()
                        ->numeric()
                        ->autofocus(),
                ])
                
                // Logic
                ->action(function (array $data) {
                    // Get Active Staff
                    $staff = Staff::user() ?? auth()->user();

                    if (!$staff) {
                        Notification::make()->title('Error')->body('No active staff session found.')->danger()->send();
                        return;
                    }

                    // Verify PIN
                    if ($staff->pin_code !== $data['verification_pin']) {
                        Notification::make()
                            ->title('Invalid PIN')
                            ->body('Access Denied. Inventory was not added.')
                            ->danger()
                            ->send();
                        return; // 🛑 Stop execution
                    }

                    // ✅ Valid PIN: Run the creation logic
                    $this->create();
                }),

            // Keep the cancel button
            parent::getCancelFormAction(),
        ];
    }
   protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
{
    $qty = (int) ($data['qty'] ?? 1);
    $storeId = $data['store_id'] ?? \App\Models\Store::first()?->id;
    $tradeInNo = $data['original_trade_in_no'] ?? null;
    $department = $data['department'] ?? '';

    $isRepair = str_contains(strtolower($department), 'repair');
    
    unset($data['print_options'], $data['creation_mode'], $data['qty'], $data['enable_rfid_tracking']);

    // 🔹 Fetch dynamic prefix from settings once before the loop
    $dynamicPrefix = \Illuminate\Support\Facades\DB::table('site_settings')
        ->where('key', 'barcode_prefix')
        ->value('value') ?? 'D';

    $firstRecord = null;

    for ($i = 0; $i < $qty; $i++) {
        $itemData = $data;
        $itemData['store_id'] = $storeId;
        $itemData['is_trade_in'] = ($tradeInNo !== null);
        $itemData['original_trade_in_no'] = $tradeInNo;

        // 🔹 Pass the dynamic prefix to the generator
        $itemData['barcode'] = ProductItemResource::generatePersistentBarcode($dynamicPrefix);

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