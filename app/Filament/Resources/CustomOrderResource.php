<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomOrderResource\Pages;
use App\Models\CustomOrder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\{Section, Grid, Select, TextInput, Textarea, FileUpload, ViewField};
use Filament\Notifications\Notification;

class CustomOrderResource extends Resource
{
    protected static ?string $model = CustomOrder::class;
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationGroup = 'Sales';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Group::make()->schema([
                Section::make('Customer & Design Notes')
                    ->schema([
                       Select::make('customer_id')
    ->label('Select Customer')
    ->relationship('customer', 'name') // 🔹 Matches the method name in your model
    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->name}") // 🔹 Ensures name displays
    ->searchable(['name', 'phone']) // 🔹 Allows searching by both name and phone
    ->preload() // 🔹 Loads the list immediately when clicked
    ->required(),
                        Textarea::make('design_notes')
                            ->rows(5)
                            ->placeholder('Describe the custom piece details...'),
                        FileUpload::make('reference_image')
                            ->image()
                            ->directory('custom-orders'),
                    ]),
            ])->columnSpan(8),

            Forms\Components\Group::make()->schema([
                Section::make('Technical Specifications')
                    ->schema([
                        Select::make('metal_type')
                            ->options([
                                '10k' => '10k Gold',
                                '14k' => '14k Gold',
                                '18k' => '18k Gold',
                                'platinum' => 'Platinum',
                                'silver' => 'Silver',
                            ])->required(),
                        TextInput::make('metal_weight')->numeric()->suffix('grams'),
                        TextInput::make('diamond_weight')->numeric()->suffix('ctw'),
                        TextInput::make('size')->placeholder('e.g., 7.5'),
                    ]),
                Section::make('Pricing & Status')
                    ->schema([
                        TextInput::make('budget')->numeric()->prefix('$'),
                        TextInput::make('quoted_price')->numeric()->prefix('$')->required(),
                        Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'quoted' => 'Quoted',
                                'approved' => 'Approved',
                                'in_production' => 'In Production',
                                'completed' => 'Completed',
                            ])->default('draft'),
                    ]),
            ])->columnSpan(4),
        ])->columns(12);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_no')->label('Order #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('customer.name')->label('Customer'),
                Tables\Columns\TextColumn::make('metal_type')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('quoted_price')->money('USD')->weight('bold'),
                Tables\Columns\SelectColumn::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'quoted' => 'Quoted',
                        'approved' => 'Approved',
                        'in_production' => 'Production',
                        'completed' => 'Completed',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                // 🔹 THE MAGIC LINK: This button pushes the custom order to the POS
                Tables\Actions\Action::make('convertToSale')
                    ->label('Send to POS')
                    ->icon('heroicon-o-shopping-cart')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Convert Custom Order to Sale?')
                    ->action(function (CustomOrder $record) {
                        return redirect()->route('filament.admin.resources.sales.create', [
                            'customer_id' => $record->customer_id,
                            'custom_order_id' => $record->id,
                            'price' => $record->quoted_price,
                            'desc' => "Custom Order: {$record->order_no} ({$record->metal_type})",
                        ]);
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomOrders::route('/'),
            'create' => Pages\CreateCustomOrder::route('/create'),
            'edit' => Pages\EditCustomOrder::route('/{record}/edit'),
        ];
    }
}