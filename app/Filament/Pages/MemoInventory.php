<?php

namespace App\Filament\Pages;

use App\Models\ProductItem;
use App\Models\Memo;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class MemoInventory extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Memo Inventory';
    protected static ?string $navigationGroup = 'Inventory';
    protected static string $view = 'filament.pages.memo-inventory';

    public function table(Table $table): Table
    {
        return $table
            // 🔹 Only show items currently held on memo
            ->query(ProductItem::query()->where('is_memo', true)->where('memo_status', 'on_memo'))
            ->columns([
                Tables\Columns\TextColumn::make('barcode')->label('Stock #')->weight('bold')->searchable(),
                Tables\Columns\TextColumn::make('memoVendor.company_name')
                    ->label('Memo Vendor')
                    ->badge()
                    ->color('warning'),
                Tables\Columns\TextColumn::make('custom_description')->label('Description')->wrap(),
                Tables\Columns\TextColumn::make('cost_price')->money('USD'),
                Tables\Columns\TextColumn::make('created_at')->label('Received Date')->date(),
            ])
         ->actions([
    // 🔁 Return to Memo Vendor
    Tables\Actions\Action::make('return_to_vendor')
        ->label('Return to Vendor')
        // ... icon and color ...
        ->action(function (ProductItem $record) {
            // Determine the correct vendor ID
            $vendorId = $record->memo_vendor_id ?? $record->supplier_id;

            if (!$vendorId) {
                Notification::make()->title('Error: No Vendor linked to this item')->danger()->send();
                return;
            }

            $record->update(['memo_status' => 'returned', 'status' => 'returned']);
            
            Memo::create([
                'product_item_id' => $record->id,
                'supplier_id' => $vendorId, // 🔹 Using the verified ID
                'action' => 'returned',
                'action_date' => now(),
            ]);
            Notification::make()->title('Item Returned to Vendor')->success()->send();
        }),

    // 💰 Mark as Sold
    Tables\Actions\Action::make('mark_sold')
        // ... label and icon ...
        ->action(function (ProductItem $record) {
            $vendorId = $record->memo_vendor_id ?? $record->supplier_id;

            if (!$vendorId) {
                Notification::make()->title('Error: No Vendor linked to this item')->danger()->send();
                return;
            }

            $record->update(['memo_status' => 'sold', 'status' => 'sold']);
            
            Memo::create([
                'product_item_id' => $record->id,
                'supplier_id' => $vendorId, // 🔹 Using the verified ID
                'action' => 'sold',
                'action_date' => now(),
            ]);
            Notification::make()->title('Marked as Sold')->success()->send();
        }),
]);
    }
}