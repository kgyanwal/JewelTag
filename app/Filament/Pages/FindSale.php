<?php

namespace App\Filament\Pages;

use App\Filament\Resources\SaleResource;
use App\Models\Sale;
use Filament\Forms\Components\{DatePicker, Grid, Section, Select, TextInput, Placeholder, Toggle, Group};
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Tables\Columns\{TextColumn, IconColumn};
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class FindSale extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';
    protected static ?string $navigationGroup = 'Sales';
    protected static ?string $title = 'Find Sale';
    protected static string $view = 'filament.pages.find-sale';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function updated($property): void
    {
        if (str_starts_with($property, 'data.')) {
            $this->resetTable();
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Section::make('Advanced Sale Search')
                    ->description('Filter by Customer, Date, or Pending Jobs')
                    ->aside()
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('invoice_number')->label('Invoice / Job #')->live(),
                            TextInput::make('customer_name')->label('Customer Name')->live(),
                            TextInput::make('phone')->label('Phone Number')->tel()->prefix('+1')->live(),

                            Select::make('payment_method')
                                ->options(['cash' => 'CASH', 'laybuy' => 'LAYBUY', 'visa' => 'VISA'])
                                ->placeholder('All Methods')->live(),

                            DatePicker::make('date_from')->label('Date From')->live(),

                            // 🚀 FIXED: The key must match the database column 'job_type'
                            Select::make('job_type')
                                ->label('Job Type')
                                ->options([
                                    'Resize' => 'Resize',
                                    'Solder' => 'Solder / Weld',
                                    'Bail Change' => 'Bail Change',
                                    'Shorten' => 'Shortening',
                                    'Stone Setting' => 'Stone Setting',
                                ])
                                ->placeholder('All Service Types')
                                ->live(),
                        ]),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Sale::query())
            ->modifyQueryUsing(
                fn(Builder $query) => $query
                    ->with(['customer', 'items.productItem'])
                    ->when($this->data['invoice_number'] ?? null, fn($q, $v) => $q->where('invoice_number', 'like', "%{$v}%"))
                    ->when($this->data['customer_name'] ?? null, function ($q, $v) {
                        $q->whereHas('customer', fn($sq) => $sq->where('name', 'like', "%{$v}%")->orWhere('last_name', 'like', "%{$v}%"));
                    })
                    // 🚀 FIXED: Querying the new job_type column
                    ->when($this->data['job_type'] ?? null, fn($q, $v) => $q->where('job_type', $v))
                    ->when($this->data['date_from'] ?? null, fn($q, $v) => $q->whereDate('created_at', '>=', $v))
                    ->latest()
            )
            ->columns([
                TextColumn::make('created_at')->label('DATE')->dateTime('M d, Y')->sortable(),

                TextColumn::make('invoice_number')->label('JOB #')->weight('bold')->color('primary')->copyable(),

                TextColumn::make('customer.name')
                    ->label('CUSTOMER')
                    ->formatStateUsing(fn($record) => $record->customer ? "{$record->customer->name} {$record->customer->last_name}" : 'Walk-in')
                    ->description(fn($record) => $record->customer?->phone),

                TextColumn::make('items_summary')
                    ->label('ITEMS / DESCRIPTION')
                    ->getStateUsing(function ($record) {
                        $lines = $record->items->map(fn($i) => ($i->productItem?->barcode ?? 'Item') . ': ' . Str::limit($i->custom_description, 30))->toArray();
                        
                        // 🚀 FIXED: Check job_type instead of is_resize
                        if ($record->job_type) {
                            $lines[] = "🛠️ " . $record->job_type . ": " . Str::limit($record->notes, 40);
                        }
                        return $lines;
                    })
                    ->listWithLineBreaks()->bulleted()->color('gray')->size('xs'),

                TextColumn::make('resize_info')
                    ->label('JOB STATUS')
                    ->getStateUsing(function ($record) {
                        if (!$record->job_type) return null;
                        if ($record->job_type === 'Resize') {
                             return "Size: {$record->current_size} ➔ {$record->target_size}";
                        }
                        return $record->job_type;
                    })
                    ->description(fn($record) => $record->date_required ? "Due: " . \Carbon\Carbon::parse($record->date_required)->format('M d') : null)
                    ->badge()->color('warning')
                    ->visible(fn() => Sale::whereNotNull('job_type')->exists()),

                TextColumn::make('final_total')->label('TOTAL')->money('USD')->alignment('right')->weight('bold'),

                TextColumn::make('status')->badge()
                    ->color(fn(string $state) => match ($state) {
                        'completed' => 'success',
                        'pending' => 'warning',
                        default => 'gray'
                    }),
            ])
            ->actions([
                Action::make('quick_view')
                    ->label('View Details')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->slideOver()
                    ->modalSubmitAction(false)
                    ->form(fn(Sale $record) => [
                        Section::make('Sale Summary')
                            ->schema([
                                Grid::make(3)->schema([
                                    Placeholder::make('inv')->label('Invoice')->content($record->invoice_number),
                                    Placeholder::make('cust')->label('Customer')->content($record->customer?->name ?? 'Walk-in'),
                                    Placeholder::make('total')->label('Amount')->content('$' . number_format($record->final_total, 2)),
                                ]),
                            ]),
                        
                        // 🚀 FIXED: Detailed Workshop view in popup
                        Section::make('🛠️ Workshop Job Details')
                            ->visible(fn($record) => $record->job_type !== null)
                            ->schema([
                                Grid::make(3)->schema([
                                    Placeholder::make('jt')->label('Job Type')->content(fn($record) => $record->job_type),
                                    Placeholder::make('mt')->label('Metal')->content(fn($record) => $record->metal_type ?? 'N/A'),
                                    Placeholder::make('due')->label('Due Date')->content(fn($record) => $record->date_required ?? 'ASAP'),
                                ]),
                                Grid::make(2)
                                    ->visible(fn($record) => $record->job_type === 'Resize')
                                    ->schema([
                                        Placeholder::make('cs')->label('From')->content(fn($record) => $record->current_size),
                                        Placeholder::make('ts')->label('To')->content(fn($record) => $record->target_size),
                                    ]),
                                Placeholder::make('inst')->label('Instructions')->content(fn($record) => $record->job_instructions),
                            ])->compact(),

                        Section::make('Bill Items')
                            ->schema([
                                Placeholder::make('items_list')
                                    ->label('')
                                    ->content(new HtmlString(
                                        '<ul class="list-disc pl-5 text-sm">' .
                                            $record->items->map(fn($i) => "<li><strong>{$i->qty}x</strong> {$i->custom_description} ($" . number_format($i->sold_price, 2) . ")</li>")->implode('') .
                                            '</ul>'
                                    )),
                            ]),
                    ]),

                \Filament\Tables\Actions\ActionGroup::make([
                    \Filament\Tables\Actions\Action::make('printStandard')
                        ->label('Standard Receipt')
                        ->icon('heroicon-o-printer')
                        ->url(fn (Sale $record) => route('sales.receipt', ['record' => $record, 'type' => 'standard']))
                        ->openUrlInNewTab(),

                    \Filament\Tables\Actions\Action::make('printGift')
                        ->label('Gift Receipt')
                        ->icon('heroicon-o-gift')
                        ->color('success')
                        ->url(fn (Sale $record) => route('sales.receipt', ['record' => $record, 'type' => 'gift']))
                        ->openUrlInNewTab(),

                    \Filament\Tables\Actions\Action::make('printJob')
                        ->label('Workshop / Job Card')
                        ->icon('heroicon-o-wrench')
                        ->color('warning')
                        ->url(fn (Sale $record) => route('sales.receipt', ['record' => $record, 'type' => 'job']))
                        ->openUrlInNewTab(),
                ])
                ->label('Print')
                ->icon('heroicon-m-printer')
                ->color('gray')
                ->button()
                ->outlined(),
            ]);
    }

    public function resetFilters(): void
    {
        $this->form->fill();
        $this->resetTable();
    }
}