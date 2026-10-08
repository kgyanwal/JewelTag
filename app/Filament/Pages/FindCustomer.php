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
                    ->when($f['customer_no'] ?? null, fn($q, $v) => $q->where('customer_no', 'like', "%{$v}%"))
                    ->when($f['name'] ?? null, fn($q, $v) => $q->where('name', 'like', "%{$v}%"))
                    ->when($f['last_name'] ?? null, fn($q, $v) => $q->where('last_name', 'like', "%{$v}%"))
                    ->when($f['company'] ?? null, fn($q, $v) => $q->where('company', 'like', "%{$v}%"))
                    ->when($f['phone'] ?? null, fn($q, $v) => $q->where('phone', 'like', "%{$v}%"))
                    ->when($f['email'] ?? null, fn($q, $v) => $q->where('email', 'like', "%{$v}%"))
                    ->when($f['city'] ?? null, fn($q, $v) => $q->where('city', 'like', "%{$v}%"))
                    ->when($f['sales_person'] ?? null, fn($q, $v) => $q->where('sales_person', $v))
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
                    ->getStateUsing(fn($record) => "{$record->name} {$record->last_name}"),

                TextColumn::make('phone')
                    ->label('MOBILE')
                    ->copyable(),

                TextColumn::make('email')
                    ->label('EMAIL')
                    ->copyable()
                    ->toggleable(),

                TextColumn::make('address')
                    ->label('ADDRESS')
                    ->getStateUsing(fn($record) => trim("{$record->street} {$record->city}, {$record->state} {$record->postcode}"))
                    ->wrap(),

                // 🚀 NEW — only renders when the customer actually carries a
                // balance, same treatment as CustomerResource's own table.
                TextColumn::make('credit_balance')
                    ->label('STORE CREDIT')
                    ->getStateUsing(fn($record) => floatval($record->credit_balance ?? 0))
                    ->formatStateUsing(function ($state) {
                        if ($state <= 0) return '';
                        return new HtmlString(
                            "<span style='background:#f5f3ff;color:#6d28d9;border:1px solid #c4b5fd;border-radius:99px;padding:3px 10px;font-size:11px;font-weight:800;white-space:nowrap;'>💳 \$" . number_format($state, 2) . "</span>"
                        );
                    })
                    ->html()
                    ->sortable(),

                TextColumn::make('loyalty_tier')
                    ->label('STORE CREDIT')
                    ->visible(fn() => \App\Services\StoreFeatures::storeCredit())
                    ->sortable()
                    ->getStateUsing(fn($record) => $record->loyalty_tier ?: 'standard')
                    ->formatStateUsing(function ($state, $record) {
                        [$fg, $bg, $icon] = match ($state) {
                            'gold'   => ['#92400e', '#fde68a', '🥇'],
                            'silver' => ['#334155', '#e2e8f0', '🥈'],
                            default  => ['#1e3a8a', '#dbeafe', '⭐'],
                        };
                        $pts = number_format((int) $record->loyalty_points);
                        return new HtmlString(
                            "<span style='background:{$bg};color:{$fg};border-radius:99px;padding:3px 10px;font-size:11px;font-weight:800;white-space:nowrap;text-transform:uppercase;'>{$icon} " . e($state) . "</span>"
                                . "<div style='font-size:11px;color:#6b7280;margin-top:3px;font-weight:600;'>{$pts} pts</div>"
                        );
                    })
                    ->html(),
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
                    ->form(fn(Customer $record): array => [

                        // ── CUSTOMER HEADER ───────────────────────────────────────────
                        Section::make('')
                            ->schema([
                                Placeholder::make('cinematic_header')
                                    ->hiddenLabel()
                                    ->content(function () use ($record) {
                                        $fullName = trim("{$record->name} {$record->last_name}");
                                        $tierColors = match ($record->loyalty_tier) {
                                            'gold'   => ['#fbbf24', '#92400e', '🥇'],
                                            'silver' => ['#cbd5e1', '#334155', '🥈'],
                                            default  => ['#93c5fd', '#1e3a8a', '⭐'],
                                        };
                                        [$tierBg, $tierText, $tierIcon] = $tierColors;
                                        $memberSince = $record->created_at->format('M d, Y');
                                        $avatar = $record->image
                                            ? "background-image:url('" . asset('storage/' . $record->image) . "');background-size:cover;background-position:center;"
                                            : "background:linear-gradient(135deg,#0B3D3C,#134e4a);display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:900;color:#F8F6F1;";
                                        $initial = strtoupper(substr($fullName, 0, 1));
                                        $avatarInner = $record->image ? '' : $initial;

                                        return new HtmlString("
                            <div style='background:linear-gradient(135deg,#0B3D3C,#0f4c46);border-radius:16px;padding:20px 24px;box-shadow:0 6px 20px rgba(11,61,60,0.25);'>
                                <div style='display:flex;align-items:center;gap:16px;'>
                                    <div style='width:64px;height:64px;border-radius:50%;{$avatar}flex-shrink:0;box-shadow:0 4px 12px rgba(0,0,0,0.2);border:2px solid #C9A24B;'>{$avatarInner}</div>
                                    <div style='flex:1;'>
                                        <div style='font-size:19px;font-weight:900;color:#F8F6F1;'>{$fullName}</div>
                                        <div style='font-size:12px;color:#a7d4c9;margin-top:2px;'>#{$record->customer_no} &nbsp;·&nbsp; Member since {$memberSince}</div>
                                    </div>
                                    <span style='background:{$tierBg};color:{$tierText};padding:5px 14px;border-radius:99px;font-size:11px;font-weight:900;text-transform:uppercase;letter-spacing:0.04em;white-space:nowrap;'>{$tierIcon} " . ucfirst($record->loyalty_tier ?? 'Standard') . "</span>
                                </div>
                                <div style='display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:16px;padding-top:16px;border-top:1px solid rgba(255,255,255,0.12);'>
                                    <div>
                                        <div style='font-size:9px;font-weight:700;color:#a7d4c9;text-transform:uppercase;letter-spacing:0.08em;'>Phone</div>
                                        <div style='font-size:13px;font-weight:700;color:#F8F6F1;margin-top:2px;'>" . ($record->phone ?? '—') . "</div>
                                    </div>
                                    <div>
                                        <div style='font-size:9px;font-weight:700;color:#a7d4c9;text-transform:uppercase;letter-spacing:0.08em;'>Email</div>
                                        <div style='font-size:13px;font-weight:700;color:#F8F6F1;margin-top:2px;'>" . ($record->email ?? '—') . "</div>
                                    </div>
                                </div>
                            </div>
                        ");
                                    }),
                            ]),

                        // 🚀 NEW — only shown when the customer actually has a balance,
                        // styled as a standalone notification card so it's unmissable
                        // during a checkout lookup.
                        ...(floatval($record->credit_balance ?? 0) > 0 ? [
                            Section::make('Store Credit')
                                ->schema([
                                    Placeholder::make('credit_balance_display')
                                        ->hiddenLabel()
                                        ->content(new HtmlString("
                            <div style='background:linear-gradient(135deg,#f5f3ff,#ede9fe);border:1.5px solid #c4b5fd;border-radius:12px;padding:14px 16px;'>
                                <div style='display:flex;align-items:center;gap:10px;'>
                                    <div style='background:#6d28d9;border-radius:50%;width:34px;height:34px;display:flex;align-items:center;justify-content:center;flex-shrink:0;'>
                                        <span style='font-size:16px;'>💳</span>
                                    </div>
                                    <div>
                                        <div style='font-size:10px;font-weight:800;color:#6d28d9;text-transform:uppercase;letter-spacing:0.06em;'>Available Store Credit</div>
                                        <div style='font-size:22px;font-weight:900;color:#4c1d95;'>\$" . number_format($record->credit_balance, 2) . "</div>
                                    </div>
                                </div>
                                <div style='font-size:11px;color:#6b21a8;margin-top:8px;'>This customer can apply this balance toward any future purchase.</div>
                            </div>
                        ")),
                                ]),
                        ] : []),

                        // ── LOYALTY ───────────────────────────────────────────────────
                        Section::make('')
                            ->visible(fn() => \App\Services\StoreFeatures::loyalty())
                            ->schema([
                                Placeholder::make('loyalty_display')
                                    ->hiddenLabel()
                                    ->content(function () use ($record) {
                                        $L      = \App\Filament\Pages\LoyaltyCenter::class;
                                        $slug   = $record->loyalty_tier ?: 'standard';
                                        $label  = $L::TIERS[$slug][0] ?? ucfirst($slug);
                                        $spend12 = $L::spendSince($record->id, now()->subMonths(12));
                                        $life    = $L::spendSince($record->id);
                                        $pts     = (int) $record->loyalty_points;
                                        $ptsVal  = $pts / $L::POINTS_PER_DOLLAR;
                                        [$nextLabel, $pct, $left] = $L::progress($spend12);
                                        $credit  = (float) ($record->credit_balance ?? 0);
                                        $birthday = $record->dob ? \Carbon\Carbon::parse($record->dob)->format('M j') : '—';
                                        $url = \App\Filament\Pages\LoyaltyCenter::getUrl(['q' => trim($record->name . ' ' . $record->last_name)]);

                                        [$accent, $icon] = match ($slug) {
                                            'gold'   => ['#E4CD8E', '🥇'],
                                            'silver' => ['#d7dee8', '🥈'],
                                            default  => ['#93c5fd', '⭐'],
                                        };
                                        $nextText = $nextLabel
                                            ? '$' . number_format($left, 0) . ' more in 12 months to reach <b>' . e($nextLabel) . '</b>'
                                            : 'Top tier reached ✦';
                                        $tile = fn($l, $v, $c = '#F8F6F1') =>
                                        "<div style='background:rgba(255,255,255,.07);border:1px solid rgba(228,205,142,.18);border-radius:10px;padding:10px 12px;'>
                                <div style='font-size:9px;font-weight:800;color:#a7d4c9;text-transform:uppercase;letter-spacing:.08em;'>{$l}</div>
                                <div style='font-size:17px;font-weight:900;color:{$c};margin-top:3px;'>{$v}</div>
                            </div>";

                                        return new HtmlString("
                            <div style='background:linear-gradient(150deg,#07292A,#0B3D3C 60%,#0e4a47);border-radius:16px;padding:20px 22px;box-shadow:0 6px 20px rgba(11,61,60,.25);border:1px solid rgba(201,162,75,.35);'>
                                <div style='display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:14px;'>
                                    <div>
                                        <div style='font-size:10px;font-weight:800;color:#C9A24B;text-transform:uppercase;letter-spacing:.14em;'>Loyalty Status</div>
                                        <div style='font-size:22px;font-weight:900;color:{$accent};margin-top:2px;'>{$icon} {$label}</div>
                                    </div>
                                    <a href='{$url}' style='background:#C9A24B;color:#07292A;padding:7px 14px;border-radius:8px;font-size:11px;font-weight:800;text-decoration:none;white-space:nowrap;'>Open Loyalty Center →</a>
                                </div>
                                <div style='display:grid;grid-template-columns:1fr 1fr;gap:10px;'>
                                    " . $tile('Points', number_format($pts), '#E4CD8E') . "
                                    " . $tile('Points Value', '$' . number_format($ptsVal, 2), '#34d399') . "
                                    " . $tile('Spend · 12 Months', '$' . number_format($spend12, 2)) . "
                                    " . $tile('Lifetime Spend', '$' . number_format($life, 2)) . "
                                    " . $tile('Store Credit', '$' . number_format($credit, 2), $credit > 0 ? '#c4b5fd' : '#F8F6F1') . "
                                    " . $tile('Birthday', $birthday) . "
                                </div>
                                <div style='margin-top:14px;'>
                                    <div style='height:7px;background:rgba(255,255,255,.1);border-radius:99px;overflow:hidden;'>
                                        <div style='height:100%;width:{$pct}%;background:linear-gradient(90deg,#3D6B63,#E4CD8E);border-radius:99px;'></div>
                                    </div>
                                    <div style='font-size:11px;color:#a7d4c9;margin-top:6px;'>{$nextText}</div>
                                </div>
                            </div>
                        ");
                                    }),
                            ]),

                        // 🚀 NEW — cinematic, only rendered when a spouse is on file. Uses a
                        // dark romantic gradient card with a connecting "&" motif between the
                        // two names, rather than a plain field list, since this is meant to
                        // read as a relationship snapshot at a glance during a checkout call.
                        ...(filled($record->spouse_name) ? [
                            Section::make('')
                                ->schema([
                                    Placeholder::make('spouse_cinematic_display')
                                        ->hiddenLabel()
                                        ->content(function () use ($record) {
                                            $customerName = trim("{$record->name} {$record->last_name}");
                                            $spouseName   = e($record->spouse_name);
                                            $spouseEmail  = $record->spouse_email;

                                            $anniversary = $record->wedding_anniversary
                                                ? \Carbon\Carbon::parse($record->wedding_anniversary)->format('F j, Y')
                                                : null;
                                            $yearsMarried = $record->wedding_anniversary
                                                ? \Carbon\Carbon::parse($record->wedding_anniversary)->diffInYears(now())
                                                : null;

                                            $anniversaryHtml = $anniversary
                                                ? "<div style='margin-top:14px;padding-top:14px;border-top:1px solid rgba(255,255,255,0.15);text-align:center;'>
                                        <div style='font-size:10px;font-weight:700;color:#d4a5c9;text-transform:uppercase;letter-spacing:0.1em;'>Anniversary</div>
                                        <div style='font-size:15px;font-weight:700;color:#fdf2f8;margin-top:2px;'>{$anniversary}" .
                                                ($yearsMarried !== null ? " <span style='color:#f0abfc;font-weight:500;'>({$yearsMarried} yrs)</span>" : '') . "</div>
                                   </div>"
                                                : '';

                                            $emailHtml = $spouseEmail
                                                ? "<div style='font-size:11px;color:#e9d5ff;margin-top:4px;'>✉ " . e($spouseEmail) . "</div>"
                                                : '';

                                            return new HtmlString("
                                <div style='
                                    background:radial-gradient(circle at top left,#4c1d95,#1e1b3a 65%);
                                    border-radius:16px;
                                    padding:24px 28px;
                                    box-shadow:0 8px 30px rgba(76,29,149,0.35);
                                    position:relative;
                                    overflow:hidden;
                                '>
                                    <div style='position:absolute;top:-30px;right:-20px;font-size:120px;opacity:0.06;line-height:1;'>💍</div>
                                    <div style='font-size:10px;font-weight:800;color:#d8b4fe;text-transform:uppercase;letter-spacing:0.15em;margin-bottom:14px;'>Family / Partner Snapshot</div>
                                    <div style='display:flex;align-items:center;justify-content:center;gap:18px;'>
                                        <div style='text-align:center;flex:1;'>
                                            <div style='width:52px;height:52px;border-radius:50%;background:linear-gradient(135deg,#a855f7,#7c3aed);display:flex;align-items:center;justify-content:center;margin:0 auto 8px;font-size:20px;font-weight:900;color:#fff;box-shadow:0 4px 12px rgba(168,85,247,0.4);'>" . strtoupper(substr($customerName, 0, 1)) . "</div>
                                            <div style='font-size:14px;font-weight:800;color:#f5f3ff;'>{$customerName}</div>
                                        </div>
                                        <div style='font-size:22px;color:#f0abfc;font-weight:300;'>&amp;</div>
                                        <div style='text-align:center;flex:1;'>
                                            <div style='width:52px;height:52px;border-radius:50%;background:linear-gradient(135deg,#ec4899,#db2777);display:flex;align-items:center;justify-content:center;margin:0 auto 8px;font-size:20px;font-weight:900;color:#fff;box-shadow:0 4px 12px rgba(219,39,119,0.4);'>" . strtoupper(substr($spouseName, 0, 1)) . "</div>
                                            <div style='font-size:14px;font-weight:800;color:#fdf2f8;'>{$spouseName}</div>
                                            {$emailHtml}
                                        </div>
                                    </div>
                                    {$anniversaryHtml}
                                </div>
                            ");
                                        }),
                                ]),
                        ] : []),

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
                    ->url(fn($record) => CustomerResource::getUrl('edit', ['record' => $record])),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                \Filament\Tables\Actions\Action::make('loyalty_center')
                    ->label('Loyalty Center')
                    ->icon('heroicon-o-gift')
                    ->color('warning')
                    ->url(fn() => \App\Filament\Pages\LoyaltyCenter::getUrl()),
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
