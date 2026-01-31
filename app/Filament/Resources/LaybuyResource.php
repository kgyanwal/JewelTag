<?php

namespace App\Filament\Resources;

use App\Models\Laybuy;
use App\Models\Sale;
use App\Models\User;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\{TextInput, Select, Section, Grid, Placeholder, Textarea, DatePicker, Tabs, Tabs\Tab};
use App\Filament\Resources\LaybuyResource\Pages;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class LaybuyResource extends Resource
{
    protected static ?string $model = Laybuy::class;
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'Sales';
    protected static ?string $slug = 'laybuys';
    
    protected static ?string $navigationLabel = 'Laybuy Plans';
    protected static ?string $modelLabel = 'Laybuy Plan';
    protected static ?string $pluralModelLabel = 'Laybuy Plans';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Laybuy Details')
                    ->tabs([
                        // --- TAB 1: CORE PLAN INFO ---
                        Tab::make('Plan Information')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('laybuy_no')
                                        ->label('Laybuy #')
                                        ->default('LB-' . date('Ymd-His'))
                                        ->required()
                                        ->unique(ignoreRecord: true)
                                        ->disabledOn('edit'),
                                    
                                    Select::make('status')
                                        ->options([
                                            'in_progress' => 'In Progress',
                                            'completed' => 'Paid & Closed',
                                            'cancelled' => 'Cancelled',
                                            'defaulted' => 'Defaulted',
                                        ])
                                        ->default('in_progress')
                                        ->required()
                                        ->live(),
                                ]),
                                
                                Grid::make(2)->schema([
                                    Select::make('customer_id')
                                        ->relationship('customer', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->disabledOn('edit'),
                                    
                                    TextInput::make('sales_person')
                                        ->label('Assigned Salesperson')
                                        ->default(fn($record) => $record?->sales_person ?? auth()->user()->name)
                                        ->readOnly(),
                                ]),
                                
                                Grid::make(3)->schema([
                                    TextInput::make('total_amount')
                                        ->label('Total Plan Amount')
                                        ->numeric()
                                        ->required()
                                        ->prefix('$')
                                        ->readOnly(),
                                    
                                    TextInput::make('amount_paid')
                                        ->label('Total Paid To Date')
                                        ->numeric()
                                        ->prefix('$')
                                        ->readOnly(),
                                    
                                    TextInput::make('balance_due')
                                        ->label('Remaining Balance')
                                        ->numeric()
                                        ->prefix('$')
                                        ->readOnly()
                                        ->extraAttributes(['class' => 'text-red-600 font-bold']),
                                ]),
                                
                                Grid::make(2)->schema([
                                    DatePicker::make('start_date')
                                        ->label('Start Date')
                                        ->default(now())
                                        ->required(),
                                    
                                    DatePicker::make('due_date')
                                        ->label('Payment Deadline')
                                        ->default(now()->addDays(30))
                                        ->required(),
                                ]),
                                
                                Textarea::make('notes')->rows(2)->columnSpanFull(),
                            ]),
                        
                        // --- TAB 2: PRODUCTS ON HOLD ---
                        Tab::make('Items on Hold')
                            ->icon('heroicon-o-shopping-bag')
                            ->schema([
                                Placeholder::make('items_info')
                                    ->label('Products Currently Reserved')
                                    ->content(function ($record) {
                                        if (!$record || !$record->sale) {
                                            return 'No products linked to this plan.';
                                        }
                                        
                                        $html = '<div class="space-y-3">';
                                        foreach ($record->sale->items as $item) {
                                            $html .= "<div class='border p-4 rounded-lg bg-white shadow-sm border-l-4 border-l-orange-500'>";
                                            $html .= "<div class='font-bold text-gray-800'>{$item->custom_description}</div>";
                                            $html .= "<div class='text-sm text-gray-500 mt-1'>";
                                            $html .= "Stock No: <span class='text-black font-medium'>" . ($item->productItem->barcode ?? 'N/A') . "</span> | ";
                                            $html .= "Retail: $" . number_format($item->sold_price, 2);
                                            $html .= "</div>";
                                            $html .= "<div class='text-xs font-bold text-orange-600 mt-2 uppercase tracking-wider'>Status: ON HOLD (RESERVED)</div>";
                                            $html .= "</div>";
                                        }
                                        return new HtmlString($html . '</div>');
                                    })
                                    ->columnSpanFull(),
                            ]),
                        
                        // --- TAB 3: PAYMENT HISTORY ---
                        Tab::make('Installment History')
                            ->icon('heroicon-o-currency-dollar')
                            ->schema([
                                Placeholder::make('payment_schedule')
                                    ->label('Detailed Ledger')
                                    ->content(function ($record) {
                                        if (!$record || $record->payments->isEmpty()) {
                                            return 'No payments recorded yet.';
                                        }
                                        
                                        $html = '<table class="w-full text-sm text-left border-collapse">';
                                        $html .= '<thead class="bg-gray-100 uppercase text-xs"><tr><th class="p-2">Date</th><th class="p-2">Method</th><th class="p-2">Amount</th></tr></thead>';
                                        $html .= '<tbody>';
                                        foreach ($record->payments->sortByDesc('created_at') as $payment) {
                                            $html .= "<tr class='border-b'>
                                                <td class='p-2'>{$payment->created_at->format('M d, Y')}</td>
                                                <td class='p-2'>" . strtoupper($payment->payment_method) . "</td>
                                                <td class='p-2 font-bold text-green-700'>$" . number_format($payment->amount, 2) . "</td>
                                            </tr>";
                                        }
                                        $html .= '</tbody></table>';
                                        return new HtmlString($html);
                                    })
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('laybuy_no')->label('Laybuy #')->searchable()->sortable(),
                TextColumn::make('customer.name')->label('Customer')->searchable(),
                TextColumn::make('sales_person')->label('Salesperson')->sortable(),
                TextColumn::make('balance_due')
                    ->label('Balance')
                    ->money('USD')
                    ->color(fn ($record) => $record->balance_due > 0 ? 'danger' : 'success')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'in_progress' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('due_date')->date()->sortable(),
            ])
            ->actions([
                // 🔹 ACTION: Record Cash Installment
                Tables\Actions\Action::make('addPayment')
                    ->label('Add Payment')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->form([
                        TextInput::make('amount')->numeric()->required()->prefix('$')->label('Amount Received'),
                        Select::make('payment_method')
                            ->options(['cash' => 'CASH', 'visa' => 'VISA', 'debit' => 'DEBIT'])
                            ->default('cash')
                            ->required(),
                    ])
                    ->action(function (Laybuy $record, array $data) {
                        DB::transaction(function () use ($record, $data) {
                            $record->payments()->create($data);
                            $record->increment('amount_paid', $data['amount']);
                            $record->decrement('balance_due', $data['amount']);
                            
                            if ($record->balance_due <= 0) {
                                $record->update(['status' => 'completed']);
                                // Release Stock
                                if ($record->sale) {
                                    foreach ($record->sale->items as $item) {
                                        $item->productItem->update(['status' => 'sold', 'hold_reason' => null]);
                                    }
                                }
                            }
                        });
                        Notification::make()->title('Payment Recorded Successfully')->success()->send();
                    })
                    ->visible(fn ($record) => $record->balance_due > 0),

                // 🔹 ACTION: Final Receipt (Only when paid)
                Tables\Actions\Action::make('printFinalReceipt')
                    ->label('Receipt')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->url(fn ($record) => route('sales.receipt', $record->sale_id))
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => $record->balance_due <= 0),

                Tables\Actions\EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLaybuys::route('/'),
            'create' => Pages\CreateLaybuy::route('/create'),
            'edit' => Pages\EditLaybuy::route('/{record}/edit'),
        ];
    }
}