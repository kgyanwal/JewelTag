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
                            
                            // 🚀 NEW: Filter specifically for Resizes
                            Select::make('is_resize')
                                ->label('Job Type')
                                ->options([
                                    1 => 'Only Resizes/Jobs',
                                    0 => 'Standard Sales',
                                ])
                                ->placeholder('All Types')
                                ->live(),
                        ]),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Sale::query())
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with(['customer', 'items.productItem'])
                ->when($this->data['invoice_number'] ?? null, fn ($q, $v) => $q->where('invoice_number', 'like', "%{$v}%"))
                ->when($this->data['customer_name'] ?? null, function ($q, $v) {
                    $q->whereHas('customer', fn($sq) => $sq->where('name', 'like', "%{$v}%")->orWhere('last_name', 'like', "%{$v}%"));
                })
                ->when($this->data['is_resize'] ?? null, fn($q, $v) => $q->where('is_resize', $v))
                ->when($this->data['date_from'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
                ->latest()
            )
            ->columns([
                TextColumn::make('created_at')->label('DATE')->dateTime('M d, Y')->sortable(),
                
                TextColumn::make('invoice_number')->label('JOB #')->weight('bold')->color('primary')->copyable(),

                TextColumn::make('customer.name')
                    ->label('CUSTOMER')
                    ->formatStateUsing(fn ($record) => $record->customer ? "{$record->customer->name} {$record->customer->last_name}" : 'Walk-in')
                    ->description(fn($record) => $record->customer?->phone),

                // 🚀 IMPROVED: Summarize items and highlight jobs
                TextColumn::make('items_summary')
                    ->label('ITEMS / DESCRIPTION')
                    ->getStateUsing(function ($record) {
                        $lines = $record->items->map(fn($i) => ($i->productItem?->barcode ?? 'Item') . ': ' . Str::limit($i->custom_description, 30))->toArray();
                        if ($record->is_resize) $lines[] = "🛠️ Job: " . Str::limit($record->notes, 40);
                        return $lines;
                    })
                    ->listWithLineBreaks()->bulleted()->color('gray')->size('xs'),

                // 🚀 NEW: Dedicated Resize Column
                TextColumn::make('resize_info')
                    ->label('RESIZE STATUS')
                    ->getStateUsing(function ($record) {
                        if (!$record->is_resize) return null;
                        return "{$record->current_size} ➔ {$record->target_size}";
                    })
                    ->description(fn($record) => $record->date_required ? "Due: " . \Carbon\Carbon::parse($record->date_required)->format('M d') : null)
                    ->badge()->color('warning')
                    ->visible(fn($livewire) => Sale::where('is_resize', true)->exists()),

                TextColumn::make('final_total')->label('TOTAL')->money('USD')->alignment('right')->weight('bold'),

                TextColumn::make('status')->badge()
                    ->color(fn (string $state) => match ($state) { 'completed' => 'success', 'pending' => 'warning', default => 'gray' }),
            ])
            ->actions([
                // 🚀 POPUP VIEW (The Onswim Style Slide-over)
                Action::make('quick_view')
                    ->label('View Details')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->slideOver()
                    ->modalSubmitAction(false)
                    ->form(fn (Sale $record) => [
                        Section::make('Sale Summary')
                            ->schema([
                                Grid::make(3)->schema([
                                    Placeholder::make('inv')->label('Invoice')->content($record->invoice_number),
                                    Placeholder::make('cust')->label('Customer')->content($record->customer?->name ?? 'Walk-in'),
                                    Placeholder::make('total')->label('Amount')->content('$'.number_format($record->final_total, 2)),
                                ]),
                            ]),
                        Section::make('🛠️ Resize Instructions')
                            ->visible($record->is_resize)
                            ->schema([
                                Grid::make(2)->schema([
                                    Placeholder::make('cs')->label('From')->content($record->current_size),
                                    Placeholder::make('ts')->label('To')->content($record->target_size),
                                    Placeholder::make('due')->label('Required By')->content($record->date_required ?? 'N/A'),
                                ]),
                                Placeholder::make('nts')->label('Bench Notes')->content($record->notes),
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

                Action::make('print')->label('Receipt')->icon('heroicon-o-printer')->color('gray')
                    ->url(fn (Sale $record) => route('sales.receipt', $record))->openUrlInNewTab(),
            ]);
    }

    public function resetFilters(): void
    {
        $this->form->fill();
        $this->resetTable();
    }
}