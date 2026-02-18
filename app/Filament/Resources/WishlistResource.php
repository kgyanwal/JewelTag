<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WishlistResource\Pages;
use App\Models\Wishlist;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class WishlistResource extends Resource
{
    protected static ?string $model = Wishlist::class;
    protected static ?string $navigationIcon = 'heroicon-o-heart';
    protected static ?string $navigationGroup = 'Customer';
    protected static ?string $navigationLabel = 'Customer Wishlists';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('customer_id')
                ->relationship('customer', 'name')
                ->searchable()
                ->required(),
            Forms\Components\Select::make('product_item_id')
                ->relationship('productItem', 'barcode')
                ->searchable()
                ->required(),
            Forms\Components\Textarea::make('notes'),
            Forms\Components\DatePicker::make('follow_up_date'),
            Forms\Components\Select::make('status')
                ->options([
                    'active' => 'Active',
                    'contacted' => 'Contacted',
                    'purchased' => 'Purchased',
                    'cancelled' => 'Cancelled'
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->date()->label('Date Added')->sortable(),
                
                // Customer Info
                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->description(fn (Wishlist $record) => $record->customer->phone),
                    
                // Product Info
                ImageColumn::make('productItem.image_path')->label('Image')->circular(),
                TextColumn::make('productItem.barcode')
                    ->label('Item')
                    ->weight('bold')
                    ->description(fn (Wishlist $record) => $record->productItem->custom_description ?? ''),
                TextColumn::make('productItem.retail_price')
                    ->label('Price')
                    ->money('USD'),

                // Wishlist Details
                TextColumn::make('notes')->limit(30)->tooltip(fn ($record) => $record->notes),
                TextColumn::make('follow_up_date')->date()->sortable()->color(fn ($state) => $state < now() ? 'danger' : 'gray'),
                TextColumn::make('salesPerson.name')->label('Staff'),
                
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'primary',
                        'contacted' => 'warning',
                        'purchased' => 'success',
                        'cancelled' => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'contacted' => 'Contacted',
                        'purchased' => 'Purchased',
                    ]),
                SelectFilter::make('sales_person_id')->relationship('salesPerson', 'name')->label('Sales Person'),
                Filter::make('follow_up_today')
                    ->label('Follow Up Due')
                    ->query(fn (Builder $query) => $query->where('follow_up_date', '<=', now())),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                
                // QUICK ACTION: Notify Customer
                Tables\Actions\Action::make('notify')
                    ->label('Send SMS')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->form([
                        Forms\Components\Textarea::make('message')
                            ->default(fn ($record) => "Hi {$record->customer->name}, the {$record->productItem->custom_description} you liked is still available! Come take a look.")
                    ])
                    ->action(function (Wishlist $record, array $data) {
                         // Insert your existing SMS logic here (using App\Services\SnsService or similar)
                         \Filament\Notifications\Notification::make()->title('Message Sent')->success()->send();
                         $record->update(['status' => 'contacted']);
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWishlists::route('/'),
            'create' => Pages\CreateWishlist::route('/create'),
            'edit' => Pages\EditWishlist::route('/{record}/edit'),
        ];
    }
}