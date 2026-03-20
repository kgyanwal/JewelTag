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
                    ->description('Filter by Customer details, Invoice #, or Job types')
                    ->aside()
                    ->schema([
                        Grid::make(4)->schema([
                            TextInput::make('invoice_number')->label('Invoice / Job #')->live(),
                            TextInput::make('staff_name')
                                ->label('Sales Staff')
                                ->placeholder('e.g. Anthony')
                                ->live(),
                            // 🚀 SIMON'S REQUEST: Separate First and Last Name
                            TextInput::make('first_name')->label('First Name')->live(),
                            TextInput::make('last_name')->label('Last Name')->live(),

                            TextInput::make('phone')->label('Phone Number')->tel()->prefix('+1')->live(),

                            Select::make('payment_method')
                                ->options(['cash' => 'CASH', 'laybuy' => 'LAYBUY', 'visa' => 'VISA'])
                                ->placeholder('All Methods')->live(),

                            DatePicker::make('date_from')->label('Date From')->live(),

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
                    ->when($this->data['staff_name'] ?? null, function ($q, $v) {
                        $q->where('sales_person_list', 'like', "%{$v}%");
                    })
                    // 🚀 UPDATED LOGIC: Filter by separate First and Last name fields
                    ->when($this->data['first_name'] ?? null, function ($q, $v) {
                        $q->whereHas('customer', fn($sq) => $sq->where('name', 'like', "%{$v}%"));
                    })
                    ->when($this->data['last_name'] ?? null, function ($q, $v) {
                        $q->whereHas('customer', fn($sq) => $sq->where('last_name', 'like', "%{$v}%"));
                    })

                    ->when($this->data['job_type'] ?? null, fn($q, $v) => $q->where('job_type', $v))
                    ->when($this->data['date_from'] ?? null, fn($q, $v) => $q->whereDate('created_at', '>=', $v))
                    ->latest()
            )
            ->columns([
                TextColumn::make('created_at')->label('DATE')->dateTime('M d, Y')->sortable(),

                TextColumn::make('invoice_number')->label('JOB #')->weight('bold')->color('primary')->copyable(),

                TextColumn::make('customer.name')
                    ->label('CUSTOMER')
                    // 🚀 Ensure full name is always displayed in the table
                    ->formatStateUsing(fn($record) => $record->customer ? "{$record->customer->name} {$record->customer->last_name}" : 'Walk-in')
                    ->description(fn($record) => $record->customer?->phone),

                TextColumn::make('items_summary')
                    ->label('ITEMS / DESCRIPTION')
                    ->getStateUsing(function ($record) {
                        $lines = $record->items->map(fn($i) => ($i->productItem?->barcode ?? 'Item') . ': ' . Str::limit($i->custom_description, 30))->toArray();
                        if ($record->job_type) {
                            $lines[] = "🛠️ " . $record->job_type . ": " . Str::limit($record->notes, 40);
                        }
                        return $lines;
                    })
                    ->listWithLineBreaks()->bulleted()->color('gray')->size('xs'),

                TextColumn::make('sales_person_list')
                    ->label('SALES STAFF')
                    ->badge()
                    ->color('gray')
                    ->separator(',') // Handles multiple names like "Jeanette, Simon"
                    ->searchable()
                    ->toggleable(),
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

                TextColumn::make('payment_status_summary')
                    ->label('PAYMENT SUMMARY')
                    ->getStateUsing(function ($record) {
                        $total = $record->final_total;
                        $paid = $record->payments->sum('amount'); // Summing all payments for this sale
                        $balance = $total - $paid;

                        $html = "<div class='text-xs text-gray-500'>Bill Total: $" . number_format($total, 2) . "</div>";
                        $html .= "<div class='text-sm font-bold text-success-600'>Paid: $" . number_format($paid, 2) . "</div>";

                        if ($balance > 0) {
                            $html .= "<div class='text-sm font-bold text-danger-600'>Balance: $" . number_format($balance, 2) . "</div>";
                        } else {
                            $html .= "<div class='text-[10px] bg-success-100 text-success-700 px-1 rounded inline-block uppercase font-bold mt-1'>Fully Paid</div>";
                        }

                        return new HtmlString($html);
                    }),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'completed' => 'success',
                        'refunded' => 'danger',            // Bright red for fully refunded
                        'partially_refunded' => 'warning', // Orange for partial
                        'cancelled' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state) => strtoupper(str_replace('_', ' ', $state))),
            ])
            ->actions([
                \Filament\Tables\Actions\EditAction::make()
        ->label('Edit')
        ->url(fn (Sale $record): string => SaleResource::getUrl('edit', ['record' => $record])),
                Action::make('quick_view')
                    ->label('View Details')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->slideOver()
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    // 🚀 Pass the $record to the form closure
                    ->form(fn(Sale $record): array => [
                        Group::make()
                            ->schema([
                                // ── CUSTOMER & SALE HEADER ──────────────────────────────
                                Grid::make(3)
                                    ->schema([
                                        Section::make('Customer Info')
                                            ->columnSpan(2)
                                            ->columns(2)
                                            ->schema([
                                                Placeholder::make('c_name')->label('Name')
                                                    ->content($record->customer ? "{$record->customer->name} {$record->customer->last_name}" : 'Walk-in'),
                                                Placeholder::make('c_phone')->label('Phone')
                                                    ->content($record->customer?->phone ?? '—'),
                                                Placeholder::make('c_email')->label('Email')
                                                    ->content($record->customer?->email ?? '—'),
                                                Placeholder::make('c_address')->label('Address')
                                                    ->content($record->customer?->address_line_1 ?? '—'),
                                            ]),
                                        Section::make('Quick Status')
                                            ->columnSpan(1)
                                            ->schema([
                                                Placeholder::make('s_invoice')->label('Invoice #')
                                                    ->content(new HtmlString("<span class='font-mono font-bold text-lg text-primary-600'>{$record->invoice_number}</span>")),
                                                Placeholder::make('s_status')->label('Status')
                                                    ->content(fn() => new HtmlString(
                                                        "<span class='px-2 py-1 rounded text-xs font-bold uppercase " .
                                                            match ($record->status) {
                                                                'completed' => 'bg-success-100 text-success-700',
                                                                'refunded' => 'bg-danger-100 text-danger-700',
                                                                'partially_refunded' => 'bg-warning-100 text-warning-700',
                                                                default => 'bg-gray-100 text-gray-700'
                                                            } . "'>{$record->status}</span>"
                                                    )),
                                            ]),
                                    ]),

                                // ── ITEM TABLE ────────────────────────────────────
                                Section::make('Bill Items')
                                    ->schema([
                                        Placeholder::make('items_html')
                                            ->label('')
                                            ->content(function () use ($record) {
                                                $html = '<table class="w-full text-sm text-left border-collapse">';
                                                $html .= '<thead class="bg-gray-50 text-gray-600 uppercase text-[10px]"><tr>';
                                                $html .= '<th class="p-2">SKU/Barcode</th><th class="p-2">Description</th><th class="p-2 text-right">Price</th><th class="p-2 text-right">Disc</th><th class="p-2 text-right">Total</th>';
                                                $html .= '</tr></thead><tbody>';

                                                foreach ($record->items as $item) {
                                                    $price = floatval($item->sold_price);
                                                    $disc = floatval($item->discount_amount);
                                                    $rowTotal = ($price * ($item->qty ?? 1)) - $disc;

                                                    $html .= "<tr class='border-b border-gray-100'>";
                                                    $html .= "<td class='p-2 font-mono text-primary-600'>" . ($item->productItem?->barcode ?? 'MANUAL') . "</td>";
                                                    $html .= "<td class='p-2 text-gray-600'>{$item->custom_description}</td>";
                                                    $html .= "<td class='p-2 text-right'>$" . number_format($price, 2) . "</td>";
                                                    $html .= "<td class='p-2 text-right text-danger-600'>-$" . number_format($disc, 2) . "</td>";
                                                    $html .= "<td class='p-2 text-right font-bold'>$" . number_format($rowTotal, 2) . "</td>";
                                                    $html .= "</tr>";
                                                }
                                                $html .= '</tbody></table>';
                                                return new HtmlString($html);
                                            }),
                                    ]),

                                // ── FINANCIALS ────────────────────────────────────
                                Grid::make(2)
                                    ->schema([
                                        Section::make('Workshop Details')
                                            ->visible(fn() => !empty($record->job_type))
                                            ->columnSpan(1)
                                            ->schema([
                                                Placeholder::make('j_type')->label('Type')->content($record->job_type),
                                                Placeholder::make('j_metal')->label('Metal')->content($record->metal_type),
                                                Placeholder::make('j_size')->label('Sizing')->content("{$record->current_size} ➔ {$record->target_size}"),
                                                Placeholder::make('j_notes')->label('Instructions')->content($record->job_instructions ?? '—'),
                                            ]),

                                        Section::make('Totals')
                                            ->columnSpan(fn() => !empty($record->job_type) ? 1 : 2)
                                            ->schema([
                                                Grid::make(2)->schema([
                                                    Placeholder::make('f_sub')->label('Subtotal')->content("$" . number_format($record->subtotal, 2)),
                                                    Placeholder::make('f_tax')->label('Tax')->content("$" . number_format($record->tax_amount, 2)),
                                                    Placeholder::make('f_trade')->label('Trade-In')->content("-$" . number_format($record->trade_in_value, 2)),
                                                    Placeholder::make('f_total')->label('Grand Total')
                                                        ->content(new HtmlString("<span class='text-xl font-black text-gray-900'>$" . number_format($record->final_total, 2) . "</span>")),
                                                ]),
                                                Placeholder::make('f_paid')->label('Total Payments Received')
                                                    ->content(new HtmlString("<div class='p-2 bg-success-50 border border-success-200 rounded text-success-700 font-bold'>$" . number_format($record->payments->sum('amount'), 2) . "</div>")),
                                            ]),
                                    ]),

                                Section::make('Internal Notes')
                                    ->collapsed()
                                    ->schema([
                                        Placeholder::make('f_notes')->label('')->content($record->notes ?? 'No internal notes recorded.'),
                                    ]),
                            ])
                    ]),

                \Filament\Tables\Actions\ActionGroup::make([
                    \Filament\Tables\Actions\Action::make('printStandard')
                        ->label('Standard Receipt')
                        ->icon('heroicon-o-printer')
                        ->url(fn(Sale $record) => route('sales.receipt', ['record' => $record, 'type' => 'standard']))
                        ->openUrlInNewTab()
                        // 🚀 THE MAGIC: This triggers the print dialog as soon as the new tab opens
                        ->extraAttributes([
                            'onclick' => "let win = window.open(this.href, '_blank'); win.onload = function() { win.print(); }; return false;"
                        ]),

                    \Filament\Tables\Actions\Action::make('printGift')
                        ->label('Gift Receipt')
                        ->icon('heroicon-o-gift')
                        ->color('success')
                        ->url(fn(Sale $record) => route('sales.receipt', ['record' => $record, 'type' => 'gift']))
                        ->openUrlInNewTab(),

                    \Filament\Tables\Actions\Action::make('printJob')
                        ->label('Workshop / Job Card')
                        ->icon('heroicon-o-wrench')
                        ->color('warning')
                        ->url(fn(Sale $record) => route('sales.receipt', ['record' => $record, 'type' => 'job']))
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
