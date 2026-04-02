<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SalePaymentResource\Pages;
use App\Models\SalePayment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class SalePaymentResource extends Resource
{
    protected static ?string $model = SalePayment::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Sales';
    protected static ?string $navigationLabel = 'Payments & Deposits';

    public static function canCreate(): bool
    {
        return false; // Prevent manual creation here, it should happen via POS
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            // Form is empty as this is view-only for imports right now
        ]);
    }

   public static function table(Table $table): Table
{
    return $table
        ->columns([
            TextColumn::make('payment_date')
                ->label('Date')
                ->date()
                ->sortable(),

            TextColumn::make('sale.invoice_number')
                ->label('Job / Inv #')
                ->searchable()
                ->sortable()
                ->color('primary')
                ->weight('bold'),

            TextColumn::make('customer.name')
                ->label('Customer')
                ->formatStateUsing(fn($record) => $record->customer
                    ? "{$record->customer->name} {$record->customer->last_name}"
                    : 'N/A')
                ->searchable(['name', 'last_name']),

            TextColumn::make('amount')
                ->money('USD')
                ->weight('bold')
                ->sortable(),

            TextColumn::make('original_payment_type')
                ->label('Method')
                ->badge()
                ->color('info')
                ->searchable(),

            TextColumn::make('is_layby')
                ->label('Type')
                ->formatStateUsing(fn($state) => $state ? 'Layby/Deposit' : 'Full Payment')
                ->badge()
                ->color(fn($state) => $state ? 'warning' : 'success'),

            // ↓ Sales associate from the parent sale
            TextColumn::make('sale.sales_person_list')
                ->label('Sales Associate')
                ->formatStateUsing(function ($record) {
                    $list = $record->sale?->sales_person_list;
                    if (!$list) return 'N/A';
                    if (is_array($list)) return implode(', ', $list);
                    $decoded = json_decode($list, true);
                    return is_array($decoded) ? implode(', ', $decoded) : $list;
                })
                ->badge()
                ->color('gray')
                ->searchable(),

            // ↓ Sale balance — is this job fully paid?
            TextColumn::make('sale.balance_due')
                ->label('Balance Due')
                ->formatStateUsing(function ($record) {
                    $due = $record->sale?->balance_due ?? 0;
                    return '$' . number_format($due, 2);
                })
                ->color(fn($record) => ($record->sale?->balance_due ?? 0) > 0 ? 'danger' : 'success')
                ->weight('bold'),
        ])
        ->filters([
            Tables\Filters\TernaryFilter::make('is_layby')
                ->label('Layby / Deposits Only'),

            // ↓ Filter by fully paid vs outstanding
            Tables\Filters\SelectFilter::make('balance_status')
                ->label('Balance Status')
                ->options([
                    'paid'    => 'Fully Paid',
                    'pending' => 'Outstanding Balance',
                ])
                ->query(function ($query, array $data) {
                    if ($data['value'] === 'paid') {
                        $query->whereHas('sale', fn($q) => $q->where('balance_due', 0));
                    } elseif ($data['value'] === 'pending') {
                        $query->whereHas('sale', fn($q) => $q->where('balance_due', '>', 0));
                    }
                }),
        ])
        ->actions([
            Tables\Actions\Action::make('view_sale')
                ->label('View Sale')
                ->icon('heroicon-o-shopping-cart')
                ->url(fn(SalePayment $record) => SaleResource::getUrl('edit', ['record' => $record->sale_id])),
        ])
        ->defaultSort('payment_date', 'desc');
}

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSalePayments::route('/'),
        ];
    }
}