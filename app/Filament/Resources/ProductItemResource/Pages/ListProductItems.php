<?php

namespace App\Filament\Resources\ProductItemResource\Pages;

use App\Filament\Resources\ProductItemResource;
use App\Services\TextractInvoiceService;
use App\Models\ProductItem;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Wizard\Step;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\DB;

class ListProductItems extends ListRecords
{
    protected static string $resource = ProductItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            Actions\Action::make('bulkUploadInvoice')
                ->label('Scan Jewelry Invoice')
                ->icon('heroicon-o-document-arrow-up')
                ->color('warning')
                ->modalHeading('Bulk Import Vendor Invoice')
                ->modalWidth('7xl')
                ->modalSubmitActionLabel('✅ Verify & Save')

                ->steps([

                    // ── STEP 1: Upload ────────────────────────────────────────
                    Step::make('Upload Invoice')
                        ->description('Upload a photo or scan of the vendor packing slip.')
                        ->schema([
                            Select::make('supplier_id')
                                ->label('Select Vendor')
                                ->options(\App\Models\Supplier::pluck('company_name', 'id'))
                                ->searchable()
                                ->required()
                                ->helperText('Who did you buy these items from?'),

                            FileUpload::make('invoice_image')
                                ->image()
                                ->disk('public')
                                ->directory('invoice-scans')
                                ->required()
                                ->live(),
                        ]),

                    // ── STEP 2: Review ────────────────────────────────────────
                    Step::make('Review & Edit Items')
                        ->description('AI-extracted items shown below. Fix typos, remove bad rows, then approve.')
                        ->schema([

                            Placeholder::make('scan_trigger')
                                ->hiddenLabel()
                                ->content(function (\Filament\Forms\Get $get, \Filament\Forms\Set $set) {
                                    $rawFile        = $get('invoice_image');
                                    $alreadyScanned = $get('has_been_scanned') ?? false;

                                    if (!$rawFile || $alreadyScanned) return '';

                                    // Resolve path from TemporaryUploadedFile or string
                                    $fullPath = null;

                                    if (is_array($rawFile)) {
                                        $file = reset($rawFile);
                                        if ($file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                                            $fullPath = $file->getRealPath();
                                        } elseif (is_string($file)) {
                                            $fullPath = str_starts_with($file, '/') ? $file : storage_path('app/public/' . $file);
                                        }
                                    } elseif ($rawFile instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                                        $fullPath = $rawFile->getRealPath();
                                    } elseif (is_string($rawFile)) {
                                        $fullPath = str_starts_with($rawFile, '/') ? $rawFile : storage_path('app/public/' . $rawFile);
                                    }

                                    if (!$fullPath || !file_exists($fullPath)) {
                                        Notification::make()->title('File not found')->body('Please try uploading again.')->danger()->send();
                                        return '';
                                    }

                                    // Duplicate detection
                                    $invoiceHash   = 'INVOICE_HASH:' . md5_file($fullPath);
                                    $alreadyExists = ProductItem::where('serial_number', $invoiceHash)->first();

                                    if ($alreadyExists) {
                                        $set('duplicate_warning',
                                            "⛔ This invoice was already scanned on " .
                                            $alreadyExists->created_at->format('M d, Y \a\t h:i A') .
                                            ". You may still proceed but duplicates will be created."
                                        );
                                    }

                                    $set('invoice_hash', $invoiceHash);

                                    try {
                                        $service       = new TextractInvoiceService();
                                        $extractedData = $service->extractBulkDataFromImage($fullPath);

                                        $stagingItems = [];
                                        foreach ($extractedData['items'] as $item) {
                                            $stagingItems[] = [
                                                'supplier_code'      => $item['supplier_code'],
                                                'custom_description' => $item['custom_description'],
                                                'cost_price'         => $item['cost_price'],
                                                'qty'                => ($item['qty'] ?? 1) > 0 ? $item['qty'] : 1,
                                                'metal_type'         => $item['metal_type'] ?? '14k',
                                            ];
                                        }

                                        $set('items_to_import', $stagingItems);
                                        $set('has_been_scanned', true);

                                    } catch (\Exception $e) {
                                        Notification::make()->title('Scan Failed')->body($e->getMessage())->danger()->send();
                                    }

                                    return '';
                                }),

                            Placeholder::make('duplicate_warning')
                                ->hiddenLabel()
                                ->content(fn(\Filament\Forms\Get $get) => $get('duplicate_warning')
                                    ? new \Illuminate\Support\HtmlString(
                                        "<div class='p-3 bg-red-50 border border-red-300 rounded text-red-700 font-medium'>" .
                                        $get('duplicate_warning') . "</div>"
                                    ) : ''
                                )
                                ->visible(fn(\Filament\Forms\Get $get) => filled($get('duplicate_warning'))),

                            Hidden::make('has_been_scanned')->default(false),
                            Hidden::make('invoice_hash')->default(null),

                            Repeater::make('items_to_import')
                                ->hiddenLabel()
                                ->schema([
                                    Grid::make(6)->schema([
                                        TextInput::make('supplier_code')
                                            ->label('Style Code')
                                            ->required()
                                            ->columnSpan(1),
                                        TextInput::make('custom_description')
                                            ->label('Description')
                                            ->required()
                                            ->columnSpan(2),
                                        Select::make('metal_type')
                                            ->label('Metal')
                                            ->options(['10k' => '10k', '14k' => '14k', '18k' => '18k', '925 Silver' => '925 Silver', 'Platinum' => 'Platinum'])
                                            ->columnSpan(1),
                                        TextInput::make('qty')
                                            ->label('Qty')
                                            ->numeric()
                                            ->default(1)
                                            ->required()
                                            ->columnSpan(1),
                                        TextInput::make('cost_price')
                                            ->label('Unit Cost ($)')
                                            ->numeric()
                                            ->required()
                                            ->columnSpan(1),
                                    ]),
                                ])
                                ->defaultItems(0)
                                ->reorderable(false),
                        ]),
                ])

                // ── ACTION ────────────────────────────────────────────────────
                // Filament wizard merges ALL step fields into ONE flat $data array.
                // Just access $data['supplier_id'], $data['items_to_import'] directly.
                ->action(function (array $data) {
                    $supplierId  = $data['supplier_id']     ?? null;
                    $itemsToSave = $data['items_to_import'] ?? [];
                    $invoiceHash = $data['invoice_hash']    ?? null;

                    if (empty($itemsToSave) || !$supplierId) {
                        Notification::make()
                            ->title('Cannot Save')
                            ->body('No items found or no vendor selected. Please go back and check Step 1.')
                            ->warning()
                            ->send();
                        return;
                    }

                    $savedCount = 0;
                    $storeId    = auth()->user()->store_id ?? \App\Models\Store::first()?->id ?? 1;
                    $isFirst    = true;

                    DB::transaction(function () use ($itemsToSave, $storeId, $supplierId, $invoiceHash, &$savedCount, &$isFirst) {
                        foreach ($itemsToSave as $item) {
                            $qty    = max(1, (int) ($item['qty'] ?? 1));
                            $prefix = ProductItemResource::getPrefixForSubDepartment($item['metal_type'] ?? null);

                            for ($i = 0; $i < $qty; $i++) {
                                ProductItem::create([
                                    'store_id'           => $storeId,
                                    'supplier_id'        => $supplierId,
                                    'supplier_code'      => $item['supplier_code'],
                                    'custom_description' => $item['custom_description'],
                                    'cost_price'         => floatval($item['cost_price']),
                                    'retail_price'       => floatval($item['cost_price']) * 2.5,
                                    'metal_type'         => $item['metal_type'] ?? null,
                                    'qty'                => 1,
                                    'barcode'            => ProductItemResource::generatePersistentBarcode($prefix),
                                    'rfid_code'          => strtoupper(bin2hex(random_bytes(4))),
                                    'status'             => 'staged',
                                    // Hash stamped on first item only — blocks re-scanning same invoice
                                    'serial_number'      => ($isFirst && $invoiceHash) ? $invoiceHash : null,
                                ]);

                                $isFirst = false;
                                $savedCount++;
                            }
                        }
                    });

                    Notification::make()
                        ->title('Bulk Import Successful')
                        ->body("Created {$savedCount} inventory tag(s) in stock.")
                        ->success()
                        ->send();
                }),
        ];
    }
}