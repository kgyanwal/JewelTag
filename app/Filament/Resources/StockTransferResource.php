<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockTransferResource\Pages;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\ProductItem;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class StockTransferResource extends Resource
{
    protected static ?string $model = StockTransfer::class;
    protected static ?string $navigationIcon  = 'heroicon-o-truck';
    protected static ?string $navigationLabel = 'Stock Transfers';
    protected static ?string $navigationGroup = 'Inventory';

    public static function getNavigationBadge(): ?string
    {
        $pending = StockTransfer::where('to_tenant', tenant('id'))
            ->where('status', 'pending')
            ->count();
        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Transfer Details')->schema([
                TextInput::make('transfer_number')
                    ->default('TRF-' . date('Ymd') . '-' . rand(100, 999))
                    ->required()
                    ->readOnly(),

                Select::make('to_tenant')
                    ->label('Send To (Destination Store)')
                    ->options(
                        fn() => Tenant::where('id', '!=', tenant('id'))
                            ->pluck('id', 'id')
                            ->toArray()
                    )
                    ->required()
                    ->searchable(),

                Textarea::make('notes')
                    ->label('Transfer Notes / Reason')
                    ->rows(2)
                    ->columnSpanFull(),

            ])->columns(2),

            Section::make('Items to Transfer')->schema([
                Repeater::make('items')
                    ->relationship()
                    ->schema([
                        Select::make('product_item_id')
                            ->label('Select Stock Item')
                            ->searchable()
                            ->required()
                            ->options(
                                fn() =>
                                ProductItem::where('status', 'in_stock')
                                    ->limit(100)
                                    ->get()
                                    ->mapWithKeys(fn($item) => [
                                        $item->id => "{$item->barcode} — " .
                                            \Illuminate\Support\Str::limit($item->custom_description ?? '', 40)
                                    ])
                            )
                            ->getSearchResultsUsing(
                                fn(string $search) =>
                                ProductItem::where('status', 'in_stock')
                                    ->where(
                                        fn($q) => $q
                                            ->where('barcode', 'like', "%{$search}%")
                                            ->orWhere('custom_description', 'like', "%{$search}%")
                                    )
                                    ->limit(30)
                                    ->get()
                                    ->mapWithKeys(fn($item) => [
                                        $item->id => "{$item->barcode} — " .
                                            \Illuminate\Support\Str::limit($item->custom_description ?? '', 40)
                                    ])
                            )
                            ->columnSpanFull(),
                    ])
                    ->addActionLabel('+ Add Item')
                    ->columns(1)
                    ->defaultItems(1),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $currentTenant = tenant('id');

        return $table
            ->modifyQueryUsing(fn($query) => $query->with(['fromStore', 'toStore', 'items.productItem']))
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('DATE')
                    ->dateTime('M d, Y h:i A')
                    ->sortable()
                    ->grow(false),

                Tables\Columns\TextColumn::make('transfer_number')
                    ->label('TRANSFER #')
                    ->searchable()
                    ->weight('bold')
                    ->fontFamily('mono')
                    ->copyable()
                    ->grow(false),

                Tables\Columns\TextColumn::make('direction')
                    ->label('TYPE')
                    ->getStateUsing(function ($record) use ($currentTenant) {
                        return $record->to_tenant === $currentTenant ? 'incoming' : 'outgoing';
                    })
                    ->formatStateUsing(function ($state) {
                        if ($state === 'incoming') {
                            return new HtmlString(
                                "<span style='background:#eff6ff;color:#1d4ed8;border:1px solid #93c5fd;padding:3px 10px;border-radius:20px;font-size:10px;font-weight:800;'>📥 INCOMING</span>"
                            );
                        }
                        return new HtmlString(
                            "<span style='background:#fff7ed;color:#c2410c;border:1px solid #fdba74;padding:3px 10px;border-radius:20px;font-size:10px;font-weight:800;'>📤 OUTGOING</span>"
                        );
                    })
                    ->grow(false),

                Tables\Columns\TextColumn::make('from_tenant')
                    ->label('FROM')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('to_tenant')
                    ->label('TO')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('barcode')
                    ->label('STOCK #')
                    ->getStateUsing(
                        fn($record) =>
                        $record->barcode
                            ?? $record->items->pluck('productItem.barcode')->filter()->implode(', ')
                            ?? '—'
                    )
                    ->fontFamily('mono')
                    ->color('primary')
                    ->description(
                        fn($record) => ($first = $record->items->pluck('productItem.custom_description')->filter()->first())
                            ? \Illuminate\Support\Str::limit($first, 30)
                            : null
                    )
                    ->grow(false),

                Tables\Columns\TextColumn::make('items_count')
                    ->label('# ITEMS')
                    ->getStateUsing(
                        fn($record) =>
                        $record->items->count() ?: (is_array($record->item_snapshot) ? count($record->item_snapshot) : 1)
                    )
                    ->alignCenter()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('status')
                    ->label('STATUS')
                    ->formatStateUsing(fn($state) => match ($state) {
                        'pending'    => new HtmlString("<span style='background:#fef3c7;color:#b45309;border:1px solid #fcd34d;padding:3px 10px;border-radius:20px;font-size:10px;font-weight:800;'>⏳ PENDING</span>"),
                        'in_transit' => new HtmlString("<span style='background:#eff6ff;color:#1d4ed8;border:1px solid #93c5fd;padding:3px 10px;border-radius:20px;font-size:10px;font-weight:800;'>🚚 IN TRANSIT</span>"),
                        'accepted'   => new HtmlString("<span style='background:#f0fdf4;color:#15803d;border:1px solid #86efac;padding:3px 10px;border-radius:20px;font-size:10px;font-weight:800;'>✅ ACCEPTED</span>"),
                        'denied'     => new HtmlString("<span style='background:#fef2f2;color:#b91c1c;border:1px solid #fca5a5;padding:3px 10px;border-radius:20px;font-size:10px;font-weight:800;'>❌ DENIED</span>"),
                        'completed'  => new HtmlString("<span style='background:#f0fdf4;color:#15803d;border:1px solid #86efac;padding:3px 10px;border-radius:20px;font-size:10px;font-weight:800;'>✅ COMPLETED</span>"),
                        default      => $state,
                    })
                    ->grow(false),

                Tables\Columns\TextColumn::make('transferred_by')
                    ->label('SENT BY')
                    ->placeholder('—')
                    ->color('gray')
                    ->size('sm'),

                Tables\Columns\TextColumn::make('actioned_by')
                    ->label('ACTIONED BY')
                    ->placeholder('—')
                    ->color('gray')
                    ->size('sm')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('notes')
                    ->label('NOTES')
                    ->limit(30)
                    ->placeholder('—')
                    ->color('warning')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending'    => '⏳ Pending',
                        'in_transit' => '🚚 In Transit',
                        'accepted'   => '✅ Accepted',
                        'denied'     => '❌ Denied',
                        'completed'  => '✅ Completed',
                    ]),

                Tables\Filters\Filter::make('incoming')
                    ->label('Incoming Only')
                    ->query(fn($query) => $query->where('to_tenant', tenant('id'))),

                Tables\Filters\Filter::make('outgoing')
                    ->label('Outgoing Only')
                    ->query(fn($query) => $query->where('from_tenant', tenant('id'))),
            ])
            ->actions([
                // ── ACCEPT — only for INCOMING pending ───────────────────
                Tables\Actions\Action::make('accept')
                    ->label('✅ Accept')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->button()
                    ->requiresConfirmation()
                    ->modalHeading('Accept Incoming Stock?')
                    ->modalDescription(fn(StockTransfer $record) =>
                        "Add " . (is_array($record->item_snapshot) ? count($record->item_snapshot) : 1) .
                        " item(s) to your inventory. Transfer #: {$record->transfer_number}"
                    )
                    // 🚀 ADDED VENDOR FORM PROMPT
                    ->form([
                        Select::make('fallback_supplier_id')
                            ->label('Receiving Vendor (Fallback)')
                            ->options(fn() => Supplier::pluck('company_name', 'id'))
                            ->required()
                            ->searchable()
                            ->helperText('Please select a vendor to assign to these items in case the original vendor is missing.'),
                    ])
                    ->visible(function (StockTransfer $record): bool {
                        return $record->to_tenant === tenant('id')
                            && in_array($record->status, ['pending', 'in_transit']);
                    })
                    ->action(function (StockTransfer $record, array $data): void {
                        $currentTenant = tenant('id');
                        $snapshot      = (array) ($record->item_snapshot ?? []);
                        $skipped       = [];
                        $created       = [];
                        
                        $fallbackSupplierId = $data['fallback_supplier_id']; // Captured from the modal

                        foreach ($snapshot as $itemData) {
                            if (empty($itemData['barcode'])) continue;

                            // 🚀 INJECT 'T' PREFIX LOGIC FOR RECEIVER
                            $originalBarcode = $itemData['barcode'];
                            $barcode = Str::startsWith($originalBarcode, 'T') ? $originalBarcode : 'T' . $originalBarcode;
                            $itemData['barcode'] = $barcode;

                            $supplierId         = $fallbackSupplierId; // Default to fallback
                            $sourceSupplierName = $itemData['supplier_company_name'] ?? $itemData['supplier_name'] ?? null;

                            if ($sourceSupplierName) {
                                try {
                                    $supplier = Supplier::withoutEvents(function() use ($sourceSupplierName) {
                                        return Supplier::firstOrCreate(
                                            ['company_name' => $sourceSupplierName],
                                            ['supplier_code' => 'SUP-' . strtoupper(Str::random(6))]
                                        );
                                    });
                                    $supplierId = $supplier->id;
                                } catch (\Exception $e) {
                                    // Let it safely revert to $fallbackSupplierId
                                }
                            }

                            unset(
                                $itemData['id'],
                                $itemData['created_at'],
                                $itemData['updated_at'],
                                $itemData['deleted_at'],
                                $itemData['supplier_company_name'],
                                $itemData['supplier_name']
                            );

                            $itemData['status']      = 'in_stock';
                            $itemData['store_id']    = 1;
                            $itemData['supplier_id'] = $supplierId; // 🚀 GUARANTEED NO NULL ERRORS

                            $existing = ProductItem::withTrashed()->where('barcode', $barcode)->first();

                            if ($existing) {
                                if ($existing->trashed()) $existing->restore();
                                $existing->update(['status' => 'in_stock', 'store_id' => 1, 'supplier_id' => $supplierId]);
                                $skipped[] = $barcode;
                            } else {
                                try {
                                    ProductItem::withoutEvents(fn() => ProductItem::create($itemData));
                                    $created[] = $barcode;
                                } catch (\Exception $e) {
                                    ProductItem::where('barcode', $barcode)->update(['status' => 'in_stock', 'store_id' => 1]);
                                    $skipped[] = $barcode;
                                }
                            }
                        }

                        $record->update([
                            'status'      => 'accepted',
                            'actioned_by' => auth()->user()->name,
                            'actioned_at' => now(),
                        ]);

                        $fromTenantId = $record->from_tenant;
                        if ($fromTenantId && $fromTenantId !== $currentTenant) {
                            $srcTenant = Tenant::find($fromTenantId);
                            if ($srcTenant) {
                                tenancy()->initialize($srcTenant);

                                StockTransfer::where('transfer_number', $record->transfer_number)
                                    ->update(['status' => 'accepted', 'actioned_by' => auth()->user()->name, 'actioned_at' => now()]);

                                foreach ($snapshot as $snap) {
                                    if (empty($snap['barcode'])) continue;
                                    
                                    // 🚀 Ensure we delete from source without triggering foreign key LogsActivity errors
                                    ProductItem::withoutEvents(function () use ($snap) {
                                        // The snapshot contains the ORIGINAL barcode without the T, which is perfect for deleting the source
                                        ProductItem::where('barcode', $snap['barcode'])->where('status', 'on_hold')->delete();
                                    });
                                }

                                $body = "Store [{$currentTenant}] accepted transfer #{$record->transfer_number}.";
                                if (!empty($created)) $body .= " ✅ Added: " . implode(', ', $created) . ".";
                                if (!empty($skipped)) $body .= " ♻️ Already existed: " . implode(', ', $skipped) . ".";

                                User::all()->each(fn($u) =>
                                    \Filament\Notifications\Notification::make()
                                        ->title('✅ Transfer Accepted')
                                        ->body($body)
                                        ->success()
                                        ->sendToDatabase($u)
                                );

                                tenancy()->initialize(Tenant::find($currentTenant));
                            }
                        }

                        $msg = '';
                        if (!empty($created)) $msg .= count($created) . ' item(s) added. ';
                        if (!empty($skipped)) $msg .= count($skipped) . ' already existed (updated). ';

                        Notification::make()
                            ->title('✅ Transfer Accepted')
                            ->body(trim($msg) ?: 'Items processed.')
                            ->success()->send();
                    }),

                // ── DENY — only for INCOMING pending ─────────────────────
                Tables\Actions\Action::make('deny')
                    ->label('❌ Deny')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->button()
                    ->requiresConfirmation()
                    ->modalHeading('Deny This Transfer?')
                    ->modalDescription('Items will be restored to In Stock at the sender\'s store.')
                    ->visible(function (StockTransfer $record): bool {
                        return $record->to_tenant === tenant('id')
                            && in_array($record->status, ['pending', 'in_transit']);
                    })
                    ->action(function (StockTransfer $record): void {
                        $currentTenant = tenant('id');
                        $snapshot      = (array) ($record->item_snapshot ?? []);

                        $record->update([
                            'status'      => 'denied',
                            'actioned_by' => auth()->user()->name,
                            'actioned_at' => now(),
                        ]);

                        $fromTenantId = $record->from_tenant;
                        if ($fromTenantId && $fromTenantId !== $currentTenant) {
                            $srcTenant = Tenant::find($fromTenantId);
                            if ($srcTenant) {
                                tenancy()->initialize($srcTenant);

                                StockTransfer::where('transfer_number', $record->transfer_number)
                                    ->update(['status' => 'denied', 'actioned_by' => auth()->user()->name, 'actioned_at' => now()]);

                                foreach ($snapshot as $snap) {
                                    if (empty($snap['barcode'])) continue;
                                    ProductItem::where('barcode', $snap['barcode'])
                                        ->where('status', 'on_hold')
                                        ->update(['status' => 'in_stock']);
                                }

                                User::all()->each(fn($u) =>
                                    \Filament\Notifications\Notification::make()
                                        ->title('❌ Transfer Denied')
                                        ->body("Store [{$currentTenant}] denied transfer #{$record->transfer_number}. Items restored to In Stock.")
                                        ->danger()
                                        ->sendToDatabase($u)
                                );

                                tenancy()->initialize(Tenant::find($currentTenant));
                            }
                        }

                        Notification::make()
                            ->title('Transfer Denied')
                            ->body('Items returned to sender\'s inventory.')
                            ->warning()->send();
                    }),

                // ── CANCEL — only for OUTGOING pending (sender withdraws) ─
                Tables\Actions\Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-mark')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Cancel This Transfer?')
                    ->modalDescription('Items will be restored to In Stock in your inventory.')
                    ->visible(function (StockTransfer $record): bool {
                        return $record->from_tenant === tenant('id')
                            && $record->status === 'pending';
                    })
                    ->action(function (StockTransfer $record): void {
                        $currentTenant = tenant('id');
                        $snapshot      = (array) ($record->item_snapshot ?? []);

                        foreach ($snapshot as $snap) {
                            if (empty($snap['barcode'])) continue;
                            ProductItem::where('barcode', $snap['barcode'])
                                ->where('status', 'on_hold')
                                ->update(['status' => 'in_stock']);
                        }

                        $record->update([
                            'status'      => 'denied',
                            'actioned_by' => auth()->user()->name,
                            'actioned_at' => now(),
                        ]);

                        $toTenantId = $record->to_tenant;
                        if ($toTenantId) {
                            $destTenant = Tenant::find($toTenantId);
                            if ($destTenant) {
                                tenancy()->initialize($destTenant);
                                StockTransfer::where('transfer_number', $record->transfer_number)
                                    ->update(['status' => 'denied', 'actioned_by' => auth()->user()->name, 'actioned_at' => now()]);
                                tenancy()->initialize(Tenant::find($currentTenant));
                            }
                        }

                        Notification::make()
                            ->title('Transfer Cancelled')
                            ->body('Items restored to In Stock.')
                            ->warning()->send();
                    }),

                // ── EDIT — only for OUTGOING pending ─────────────────────
                Tables\Actions\EditAction::make()
                    ->visible(function (StockTransfer $record): bool {
                        return $record->status === 'pending'
                            && $record->from_tenant === tenant('id');
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListStockTransfers::route('/'),
            'create' => Pages\CreateStockTransfer::route('/create'),
            'edit'   => Pages\EditStockTransfer::route('/{record}/edit'),
        ];
    }
}