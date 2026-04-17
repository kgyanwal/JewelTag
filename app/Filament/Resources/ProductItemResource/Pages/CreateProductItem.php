<?php

namespace App\Filament\Resources\ProductItemResource\Pages;

use App\Filament\Resources\ProductItemResource;
use App\Services\TextractInvoiceService;
use App\Services\ZebraPrinterService; // 👈 Import the printer service
use App\Models\ProductItem;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action as NotificationAction; // 👈 Import Notification Action
use App\Helpers\Staff;
use Filament\Forms\Components\TextInput;

class CreateProductItem extends CreateRecord
{
    protected static string $resource = ProductItemResource::class;

    /**
     * 🚀 Redirect back to the create page instead of the list
     * This allows the "Create New Tag" requirement by staying on the same page.
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('create');
    }

    /**
     * 🚀 THE CORE FIX: This triggers AFTER the stock is saved in the DB.
     */
    protected function afterCreate(): void
    {
        $record = $this->record;

        Notification::make()
            ->success()
            ->title('Stock Assembled Successfully')
            ->body("Stock No: **{$record->barcode}** has been added.")
            ->persistent() // Keeps it on screen until they click
            ->actions([
                // 1. PRINT OPTION
                NotificationAction::make('print_tag')
                    ->label('Print Tag Now')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->button()
                    ->emit('zebra-print', [
                        'zpl' => app(ZebraPrinterService::class)->getZplCode($record, false)
                    ]),

                // 2. CREATE NEW OPTION
                NotificationAction::make('new_item')
                    ->label('Add Another')
                    ->icon('heroicon-o-plus-circle')
                    ->color('gray')
                    ->url($this->getResource()::getUrl('create')),
            ])
            ->send();
    }

 protected function getHeaderActions(): array
    {
        return [
            Action::make('scanInvoice')
                ->label('Scan Physical Invoice')
                ->icon('heroicon-o-camera')
                ->color('info')
                ->form([
                    FileUpload::make('invoice_image')
                        ->image()
                        ->disk('public')
                        ->directory('invoice-scans')
                        ->required()
                ])
                // 🚀 2. INJECT THE NEW TEXTRACT SERVICE HERE
               ->action(function (array $data, TextractInvoiceService $ocrService, \Filament\Forms\Contracts\HasForms $livewire) {
                    $path = storage_path('app/public/' . $data['invoice_image']);
                    
                    try {
                        // 1. Get the data from AWS
                        $extracted = $ocrService->extractDataFromImage($path);
                        
                        // 2. Safely merge the new data with the existing form data
                        $currentState = $livewire->form->getRawState();
                        
                        // 3. Fill the form with the AWS data + Smart Parsing data
                        $livewire->form->fill([
                            ...$currentState,
                            'supplier_code'      => $extracted['supplier_code'] ?? $currentState['supplier_code'] ?? null, 
                            'cost_price'         => $extracted['cost_price'] ?? $currentState['cost_price'] ?? 0, 
                            'custom_description' => $extracted['custom_description'] ?? $currentState['custom_description'] ?? '', 
                            'qty'                => $extracted['qty'] ?? $currentState['qty'] ?? 1,
                            
                            // 🚀 NEW FIELDS ADDED HERE:
                            'metal_type'         => $extracted['metal_type'] ?? $currentState['metal_type'] ?? null,
                            'diamond_weight'     => $extracted['diamond_weight'] ?? $currentState['diamond_weight'] ?? null,
                            'size'               => $extracted['size'] ?? $currentState['size'] ?? null,
                        ]);
                        
                        Notification::make()
                            ->title('AWS Textract Success!')
                            ->body('Invoice data, Metal Karat, and CTW successfully extracted.')
                            ->success()
                            ->send();
                            
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('AWS Error')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('create')
                ->label('Assemble Stock & Print') // 👈 Changed label for clarity
                ->color('primary')
                ->icon('heroicon-o-plus')
                ->size(\Filament\Support\Enums\ActionSize::Large)
                ->extraAttributes([
                    'class' => 'w-full md:w-1/2 mx-auto shadow-xl font-black tracking-wider ring-1 ring-green-600/20 transform hover:scale-105 transition-all duration-300',
                    'style' => 'height: 3.5rem; font-size: 1.1rem;',
                ])
                ->requiresConfirmation()
                ->modalHeading('Staff Verification')
                ->modalDescription('Enter your PIN to authorize adding this stock.')
                ->modalSubmitActionLabel('Verify & Save')
                ->form([
                    TextInput::make('verification_pin')
                        ->label('Enter PIN')
                        ->password()
                        ->revealable()
                        ->required()
                        ->numeric()
                        ->autofocus(),
                ])
                ->action(function (array $data) {
                    $staff = Staff::user() ?? auth()->user();
                    if (!$staff) {
                        Notification::make()->title('Error')->body('No active staff session found.')->danger()->send();
                        return;
                    }

                    if ($staff->pin_code !== $data['verification_pin']) {
                        Notification::make()
                            ->title('Invalid PIN')
                            ->body('Access Denied. Inventory was not added.')
                            ->danger()
                            ->send();
                        return; 
                    }

                    $this->create();
                }),

            parent::getCancelFormAction(),
        ];
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $qty = (int) ($data['qty'] ?? 1);
        $storeId = $data['store_id'] ?? \App\Models\Store::first()?->id;
        $tradeInNo = $data['original_trade_in_no'] ?? null;
        $department = $data['department'] ?? '';
        $subDepartment = $data['sub_department'] ?? ''; 

        $isRepair = str_contains(strtolower($department), 'repair');
        
        unset($data['print_options'], $data['creation_mode'], $data['qty'], $data['enable_rfid_tracking']);

        $dynamicPrefix = ProductItemResource::getPrefixForSubDepartment($subDepartment);
        $firstRecord = null;

        for ($i = 0; $i < $qty; $i++) {
            $itemData = $data;
            $itemData['store_id'] = $storeId;
            $itemData['is_trade_in'] = ($tradeInNo !== null);
            $itemData['original_trade_in_no'] = $tradeInNo;
            $itemData['web_item'] = $itemData['web_item'] ?? false;
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