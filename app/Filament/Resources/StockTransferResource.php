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
                    ->default('TRF-' . date('Ymd') . '-' . strtoupper(Str::random(6)))
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
                // ── ACCEPT — only for INCOMING pending ───────────────────
                Tables\Actions\Action::make('accept')
                    ->label('✅ Accept')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->button()
                    ->requiresConfirmation()
                    ->modalHeading('Accept Incoming Stock')
                    ->modalDescription(fn(StockTransfer $record) =>
                        "Review the incoming items and map the vendors to your local database before adding them to inventory. Transfer #: {$record->transfer_number}"
                    )
                    ->form(function (StockTransfer $record) {
                        // 1. Find all unique vendor names coming from the snapshot
                        $incomingVendors = collect($record->item_snapshot)
                            ->pluck('supplier_company_name')
                            ->filter()
                            ->unique()
                            ->values();

                        $schema = [];

                        // 2. Build a mapping dropdown for EVERY incoming vendor
                        foreach ($incomingVendors as $index => $vendorName) {
                            $schema[] = \Filament\Forms\Components\Fieldset::make("Vendor: {$vendorName}")
                                ->schema([
                                    \Filament\Forms\Components\Radio::make("vendor_action_{$index}")
                                        ->label('How should we handle this vendor?')
                                        ->options([
                                            'match'  => 'Match to an existing local vendor',
                                            'create' => "Create new vendor: '{$vendorName}'",
                                        ])
                                        ->default('match')
                                        ->live(),

                                    \Filament\Forms\Components\Select::make("mapped_vendor_id_{$index}")
                                        ->label('Select Local Vendor')
                                        ->options(\App\Models\Supplier::pluck('company_name', 'id'))
                                        ->searchable()
                                        ->required(fn(\Filament\Forms\Get $get) => $get("vendor_action_{$index}") === 'match')
                                        ->visible(fn(\Filament\Forms\Get $get) => $get("vendor_action_{$index}") === 'match')
                                        ->hint('Search your existing vendors to avoid duplicates.'),
                                    
                                    // Hidden field just to store the original name for the action loop
                                    \Filament\Forms\Components\Hidden::make("original_vendor_name_{$index}")
                                        ->default($vendorName)
                                ]);
                        }

                        if (empty($schema)) {
                            $schema[] = \Filament\Forms\Components\Placeholder::make('no_vendors')
                                ->label('')
                                ->content(new HtmlString(
                                    "<div class='p-3 bg-warning-50 border border-warning-200 rounded-lg text-warning-700 text-sm font-medium'>
                                        ⚠️ No vendor info found in transfer snapshot. Items will be imported without vendor links.
                                    </div>"
                                ));
                        }

                        return $schema;
                    })
                    ->visible(function (StockTransfer $record): bool {
                        return $record->to_tenant === tenant('id')
                            && in_array($record->status, ['pending', 'in_transit']);
                    })
                    ->action(function (StockTransfer $record, array $data): void {
                        $currentTenant      = tenant('id');
                        $snapshot           = (array) ($record->item_snapshot ?? []);
                        $skipped            = [];
                        $created            = [];

                        // 1. Build a lookup array of [OriginalName => LocalSupplierID]
                        $vendorMap = [];
                        $index = 0;
                        while (isset($data["original_vendor_name_{$index}"])) {
                            $origName = $data["original_vendor_name_{$index}"];
                            $action   = $data["vendor_action_{$index}"];
                            
                            if ($action === 'match') {
                                $vendorMap[$origName] = $data["mapped_vendor_id_{$index}"];
                            } else {
                                // Create the new vendor right now
                                $newVendor = \App\Models\Supplier::withoutEvents(function () use ($origName) {
                                    return \App\Models\Supplier::firstOrCreate(
                                        ['company_name' => $origName],
                                        [
                                            'supplier_code' => 'SUP-' . strtoupper(Str::random(6)),
                                            'is_active'     => true,
                                        ]
                                    );
                                });
                                $vendorMap[$origName] = $newVendor->id;
                            }
                            $index++;
                        }

                        // 2. Import the items
                        foreach ($snapshot as $itemData) {
                            if (empty($itemData['barcode'])) continue;

                            // ── Prefix barcode with T for destination tenant ────────
                            $originalBarcode    = $itemData['barcode'];
                            $barcode            = Str::startsWith($originalBarcode, 'T')
                                ? $originalBarcode
                                : 'T' . $originalBarcode;
                            $itemData['barcode'] = $barcode;

                            // Apply the mapped vendor ID!
                            $origVendor = $itemData['supplier_company_name'] ?? null;
                            $itemData['supplier_id'] = $origVendor && isset($vendorMap[$origVendor]) 
                                ? $vendorMap[$origVendor] 
                                : null;

                            // Clean up source database keys
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

                            $existing = ProductItem::withTrashed()->where('barcode', $barcode)->first();

                            if ($existing) {
                                if ($existing->trashed()) $existing->restore();
                                $existing->update([
                                    'status'      => 'in_stock',
                                    'store_id'    => 1,
                                    'supplier_id' => $itemData['supplier_id'],
                                ]);
                                $skipped[] = $barcode;
                            } else {
                                try {
                                    ProductItem::withoutEvents(fn() => ProductItem::create($itemData));
                                    $created[] = $barcode;
                                } catch (\Exception $e) {
                                    ProductItem::where('barcode', $barcode)
                                        ->update(['status' => 'in_stock', 'store_id' => 1]);
                                    $skipped[] = $barcode;
                                }
                            }
                        }

                        $record->update([
                            'status'      => 'accepted',
                            'actioned_by' => auth()->user()->name,
                            'actioned_at' => now(),
                        ]);

                        // 3. Inform the sending tenant
                        $fromTenantId = $record->from_tenant;
                        if ($fromTenantId && $fromTenantId !== $currentTenant) {
                            $srcTenant = Tenant::find($fromTenantId);
                            if ($srcTenant) {
                                tenancy()->initialize($srcTenant);

                                StockTransfer::where('transfer_number', $record->transfer_number)
                                    ->update([
                                        'status'      => 'accepted',
                                        'actioned_by' => auth()->user()->name,
                                        'actioned_at' => now(),
                                    ]);

                                foreach ($snapshot as $snap) {
                                    if (empty($snap['barcode'])) continue;
                                    ProductItem::withoutEvents(function () use ($snap) {
                                        ProductItem::where('barcode', $snap['barcode'])
                                            ->where('status', 'on_hold')
                                            ->delete();
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
                            ->title('✅ Transfer Accepted & Vendors Mapped')
                            ->body(trim($msg) ?: 'Items processed.')
                            ->success()
                            ->send();
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
                                    ->update([
                                        'status'      => 'denied',
                                        'actioned_by' => auth()->user()->name,
                                        'actioned_at' => now(),
                                    ]);

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
                            ->warning()
                            ->send();
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
                                    ->update([
                                        'status'      => 'denied',
                                        'actioned_by' => auth()->user()->name,
                                        'actioned_at' => now(),
                                    ]);
                                tenancy()->initialize(Tenant::find($currentTenant));
                            }
                        }

                        Notification::make()
                            ->title('Transfer Cancelled')
                            ->body('Items restored to In Stock.')
                            ->warning()
                            ->send();
                    }),

                // ── EDIT — only for OUTGOING pending ─────────────────────
                Tables\Actions\EditAction::make()
                    ->visible(function (StockTransfer $record): bool {
                        return $record->status === 'pending'
                            && $record->from_tenant === tenant('id');
                    }),

                // ── QUICK VIEW MODAL ──────────────────────────────────────
                Tables\Actions\Action::make('quick_view')
                    ->label('View Items')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->slideOver()
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->form(fn(StockTransfer $record): array => [
                        \Filament\Forms\Components\Group::make()->schema([
                            \Filament\Forms\Components\Section::make('Transfer Overview')
                                ->columns(2)
                                ->schema([
                                    \Filament\Forms\Components\Placeholder::make('v_number')
                                        ->label('Transfer #')
                                        ->content(new HtmlString("<span class='font-mono font-bold text-lg text-primary-600'>{$record->transfer_number}</span>")),
                                    \Filament\Forms\Components\Placeholder::make('v_status')
                                        ->label('Status')
                                        ->content(new HtmlString(
                                            "<span class='px-2 py-1 rounded text-xs font-bold uppercase " .
                                                match ($record->status) {
                                                    'accepted', 'completed' => 'bg-success-100 text-success-700',
                                                    'denied'                => 'bg-danger-100 text-danger-700',
                                                    'in_transit'            => 'bg-blue-100 text-blue-700',
                                                    default                 => 'bg-warning-100 text-warning-700',
                                                } . "'>{$record->status}</span>"
                                        )),
                                    \Filament\Forms\Components\Placeholder::make('v_from')
                                        ->label('From Tenant')
                                        ->content($record->from_tenant),
                                    \Filament\Forms\Components\Placeholder::make('v_to')
                                        ->label('To Tenant')
                                        ->content($record->to_tenant),
                                ]),

                            \Filament\Forms\Components\Section::make('Items in Transfer')->schema([
                                \Filament\Forms\Components\Placeholder::make('v_items')
                                    ->label('')
                                    ->content(function () use ($record) {
                                        $snapshot = is_array($record->item_snapshot) ? $record->item_snapshot : [];

                                        if (empty($snapshot)) {
                                            return new HtmlString("<div class='text-gray-400 italic text-sm py-4'>No items found in this transfer payload.</div>");
                                        }

                                        $html  = '<table class="w-full text-sm text-left border-collapse">';
                                        $html .= '<thead class="bg-gray-50 text-gray-600 uppercase text-[10px]"><tr>';
                                        $html .= '<th class="p-2">Barcode</th><th class="p-2">Description</th>';
                                        $html .= '<th class="p-2">Metal</th><th class="p-2 text-right">Retail Price</th>';
                                        $html .= '</tr></thead><tbody>';

                                        foreach ($snapshot as $item) {
                                            $barcode = $item['barcode'] ?? 'N/A';
                                            $desc    = $item['custom_description'] ?? 'No Description';
                                            $metal   = $item['metal_type'] ?? '—';
                                            $price   = isset($item['retail_price'])
                                                ? '$' . number_format((float) $item['retail_price'], 2)
                                                : '—';

                                            $html .= "<tr class='border-b border-gray-100 hover:bg-gray-50 transition'>";
                                            $html .= "<td class='p-2 font-mono font-bold text-primary-600'>{$barcode}</td>";
                                            $html .= "<td class='p-2 text-gray-700'>" . e($desc) . "</td>";
                                            $html .= "<td class='p-2 text-gray-500'>{$metal}</td>";
                                            $html .= "<td class='p-2 text-right font-semibold text-success-600'>{$price}</td>";
                                            $html .= "</tr>";
                                        }

                                        $html .= '</tbody></table>';
                                        return new HtmlString($html);
                                    }),
                            ]),

                            \Filament\Forms\Components\Section::make('Notes')->collapsed()->schema([
                                \Filament\Forms\Components\Placeholder::make('v_notes')
                                    ->label('')
                                    ->content($record->notes ?? 'No notes recorded.'),
                            ]),
                        ]),
                    ]),
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