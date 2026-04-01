<?php

namespace App\Filament\Pages;

use App\Filament\Resources\CustomerResource;
use App\Models\Customer;
use App\Models\User;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class FindCustomer extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';
    protected static ?string $navigationGroup = 'Customer';
    protected static ?string $title = 'Find Customer';
    protected static string $view = 'filament.pages.find-customer';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Search Customers')
                    ->schema([
                        Grid::make(4)->schema([
                            TextInput::make('customer_no')
                                ->label('Customer ID')
                                ->live()
                                ->afterStateUpdated(fn() => $this->resetTable()),
                            TextInput::make('name')
                                ->label('First Name')
                                ->live()
                                ->afterStateUpdated(fn() => $this->resetTable()),
                            TextInput::make('last_name')
                                ->label('Last Name')
                                ->live()
                                ->afterStateUpdated(fn() => $this->resetTable()),
                            TextInput::make('company')
                                ->label('Company')
                                ->live()
                                ->afterStateUpdated(fn() => $this->resetTable()),
                        ]),
                        Grid::make(4)->schema([
                            TextInput::make('phone')
                                ->label('Mobile')
                                ->prefix('+1')
                                ->live()
                                ->afterStateUpdated(fn() => $this->resetTable()),
                            TextInput::make('email')
                                ->label('Email')
                                ->live()
                                ->afterStateUpdated(fn() => $this->resetTable()),
                            TextInput::make('city')
                                ->label('City')
                                ->live()
                                ->afterStateUpdated(fn() => $this->resetTable()),
                            Select::make('sales_person')
                                ->label('Sales Person')
                                ->options(User::pluck('name', 'name'))
                                ->placeholder('Any')
                                ->live()
                                ->afterStateUpdated(fn() => $this->resetTable()),
                        ]),
                    ])->compact(),
            ])
            ->statePath('data');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Customer::query())
            ->modifyQueryUsing(function (Builder $query) {
                $f = $this->data;

                return $query
                    ->when($f['customer_no'] ?? null, fn ($q, $v) => $q->where('customer_no', 'like', "%{$v}%"))
                    ->when($f['name'] ?? null, fn ($q, $v) => $q->where('name', 'like', "%{$v}%"))
                    ->when($f['last_name'] ?? null, fn ($q, $v) => $q->where('last_name', 'like', "%{$v}%"))
                    ->when($f['company'] ?? null, fn ($q, $v) => $q->where('company', 'like', "%{$v}%"))
                    ->when($f['phone'] ?? null, fn ($q, $v) => $q->where('phone', 'like', "%{$v}%"))
                    ->when($f['email'] ?? null, fn ($q, $v) => $q->where('email', 'like', "%{$v}%"))
                    ->when($f['city'] ?? null, fn ($q, $v) => $q->where('city', 'like', "%{$v}%"))
                    ->when($f['sales_person'] ?? null, fn ($q, $v) => $q->where('sales_person', $v))
                    ->latest();
            })
            ->columns([
                TextColumn::make('customer_no')
                    ->label('ID')
                    ->sortable()
                    ->copyable(),

                TextColumn::make('full_name')
                    ->label('FULL NAME')
                    ->weight('bold')
                    ->getStateUsing(fn ($record) => "{$record->name} {$record->last_name}"),

                TextColumn::make('phone')
                    ->label('MOBILE')
                    ->copyable(),

                TextColumn::make('email')
                    ->label('EMAIL')
                    ->copyable()
                    ->toggleable(),

                TextColumn::make('address')
                    ->label('ADDRESS')
                    ->getStateUsing(fn ($record) => trim("{$record->street} {$record->city}, {$record->state} {$record->postcode}"))
                    ->wrap(),
            ])
            ->actions([
                // 🚀 VIEW DETAILS POPUP (Slide-over)
                Action::make('view')
    ->label('Details')
    ->icon('heroicon-o-eye')
    ->color('info')
    ->slideOver()
    ->modalSubmitAction(false)
    ->modalCancelActionLabel('Close')
    ->form(fn (Customer $record): array => [

        // ── CUSTOMER HEADER ───────────────────────────────────────────
        Section::make('Customer Profile')
            ->schema([
                Grid::make(2)->schema([
                    Placeholder::make('id')->label('Customer ID')->content($record->customer_no),
                    Placeholder::make('created')->label('Member Since')->content($record->created_at->format('M d, Y')),
                    Placeholder::make('name')->label('Name')->content("{$record->name} {$record->last_name}"),
                    Placeholder::make('phone')->label('Phone')->content($record->phone),
                    Placeholder::make('email')->label('Email')->content($record->email ?? 'N/A'),
                    Placeholder::make('tier')->label('Loyalty Tier')->content(strtoupper($record->loyalty_tier ?? 'Standard')),
                ]),
            ]),

        Section::make('Mailing Address')
            ->schema([
                Placeholder::make('full_address')
                    ->label('')
                    ->content(new HtmlString("
                        <div class='text-sm text-gray-600'>
                            {$record->street}<br>
                            {$record->city}, {$record->state} {$record->postcode}<br>
                            <strong>Country:</strong> {$record->country}
                        </div>
                    ")),
            ]),

        // ── SALES HISTORY ─────────────────────────────────────────────
        Section::make('Purchase History')
            ->schema([
                Placeholder::make('sales_summary')
                    ->label('')
                    ->content(function () use ($record) {
                        $sales = $record->sales()
                            ->with(['items.productItem', 'payments'])
                            ->whereNotIn('status', ['void', 'cancelled'])
                            ->latest()
                            ->get();

                        if ($sales->isEmpty()) {
                            return new HtmlString("
                                <p class='text-sm text-gray-400 italic'>No purchase history found.</p>
                            ");
                        }

                        // ── SUMMARY STATS ──────────────────────────────
                        $totalSpent   = $sales->where('status', 'completed')->sum('final_total');
                        $visitCount   = $sales->count();
                        $lastVisit    = $sales->first()?->created_at?->format('M d, Y') ?? '—';

                        $statsHtml = "
                            <div style='display:flex;gap:12px;margin-bottom:16px;flex-wrap:wrap'>
                                <div style='background:var(--color-background-secondary);border-radius:8px;padding:8px 16px;text-align:center;min-width:90px'>
                                    <p style='font-size:11px;color:var(--color-text-secondary);margin:0;text-transform:uppercase;letter-spacing:.04em'>Total spent</p>
                                    <p style='font-size:16px;font-weight:500;margin:0;color:var(--color-text-primary)'>\$" . number_format($totalSpent, 2) . "</p>
                                </div>
                                <div style='background:var(--color-background-secondary);border-radius:8px;padding:8px 16px;text-align:center;min-width:60px'>
                                    <p style='font-size:11px;color:var(--color-text-secondary);margin:0;text-transform:uppercase;letter-spacing:.04em'>Visits</p>
                                    <p style='font-size:16px;font-weight:500;margin:0;color:var(--color-text-primary)'>{$visitCount}</p>
                                </div>
                                <div style='background:var(--color-background-secondary);border-radius:8px;padding:8px 16px;text-align:center;min-width:90px'>
                                    <p style='font-size:11px;color:var(--color-text-secondary);margin:0;text-transform:uppercase;letter-spacing:.04em'>Last visit</p>
                                    <p style='font-size:16px;font-weight:500;margin:0;color:var(--color-text-primary)'>{$lastVisit}</p>
                                </div>
                            </div>
                        ";

                        // ── SALE ROWS ──────────────────────────────────
                        $rowsHtml = '';
                        foreach ($sales as $sale) {
                            $total      = floatval($sale->final_total);
                            $paid       = floatval($sale->payments->sum('amount'));
                            if ($paid == 0 && floatval($sale->amount_paid) > 0) {
                                $paid = floatval($sale->amount_paid);
                            }
                            $balance    = max(0, $total - $paid);
                            $isOwing    = $balance > 0.01;

                            // Status badge
                            $statusColor = match ($sale->status) {
                                'completed'          => 'background:var(--color-background-success);color:var(--color-text-success)',
                                'refunded'           => 'background:var(--color-background-danger);color:var(--color-text-danger)',
                                'partially_refunded' => 'background:var(--color-background-warning);color:var(--color-text-warning)',
                                default              => 'background:var(--color-background-secondary);color:var(--color-text-secondary)',
                            };
                            $statusLabel = ucfirst(str_replace('_', ' ', $sale->status));

                            // Items pills
                            $itemPills = '';
                            foreach ($sale->items->take(3) as $item) {
                                $label = $item->productItem
                                    ? $item->productItem->barcode . ' — ' . \Illuminate\Support\Str::limit($item->custom_description, 28)
                                    : \Illuminate\Support\Str::limit($item->custom_description ?? 'Service', 32);
                                $itemPills .= "<span style='font-size:11px;background:var(--color-background-secondary);color:var(--color-text-secondary);padding:2px 8px;border-radius:99px;border:0.5px solid var(--color-border-tertiary);display:inline-block;margin:2px 2px 0 0'>{$label}</span>";
                            }
                            $extra = $sale->items->count() - 3;
                            if ($extra > 0) {
                                $itemPills .= "<span style='font-size:11px;color:var(--color-text-tertiary);padding:2px 4px;display:inline-block;margin-top:2px'>+{$extra} more</span>";
                            }

                            // Sales staff
                            $staff = is_array($sale->sales_person_list)
                                ? implode(', ', $sale->sales_person_list)
                                : ($sale->sales_person_list ?? '—');

                            // Border accent for owing
                            $borderStyle = $isOwing
                                ? 'border:0.5px solid var(--color-border-warning)'
                                : 'border:0.5px solid var(--color-border-tertiary)';

                            // Price color
                            $priceColor = $isOwing ? 'color:var(--color-text-warning)' : 'color:var(--color-text-primary)';

                            $balanceHtml = $isOwing
                                ? "<p style='font-size:11px;color:var(--color-text-warning);margin:2px 0 0'>Balance: \$" . number_format($balance, 2) . "</p>"
                                : '';

                            // Edit link
                            $editUrl = \App\Filament\Resources\SaleResource::getUrl('edit', ['record' => $sale->id]);

                            $rowsHtml .= "
                                <div style='{$borderStyle};border-radius:8px;padding:10px 12px;margin-bottom:8px'>
                                    <div style='display:flex;justify-content:space-between;align-items:flex-start;gap:12px'>
                                        <div style='flex:1;min-width:0'>
                                            <div style='display:flex;align-items:center;gap:8px;margin-bottom:2px'>
                                                <a href='{$editUrl}' style='font-size:13px;font-weight:500;color:var(--color-text-info);text-decoration:none'>Invoice #{$sale->invoice_number}</a>
                                            </div>
                                            <p style='font-size:12px;color:var(--color-text-secondary);margin:0 0 6px'>{$sale->created_at->format('M d, Y')} · {$staff}</p>
                                            <div>{$itemPills}</div>
                                        </div>
                                        <div style='text-align:right;flex-shrink:0'>
                                            <p style='font-size:14px;font-weight:500;margin:0;{$priceColor}'>\$" . number_format($total, 2) . "</p>
                                            <span style='font-size:11px;{$statusColor};padding:2px 8px;border-radius:99px;display:inline-block;margin-top:4px'>{$statusLabel}</span>
                                            {$balanceHtml}
                                        </div>
                                    </div>
                                </div>
                            ";
                        }

                        return new HtmlString($statsHtml . $rowsHtml);
                    }),
            ]),
    ]),

                \Filament\Tables\Actions\Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-m-pencil-square')
                    ->url(fn ($record) => CustomerResource::getUrl('edit', ['record' => $record])),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                \Filament\Tables\Actions\Action::make('reset')
                    ->label('Clear Filters')
                    ->color('gray')
                    ->action(fn() => $this->resetFilters()),
            ]);
    }

    public function resetFilters(): void
    {
        $this->form->fill();
        $this->resetTable();
    }
}