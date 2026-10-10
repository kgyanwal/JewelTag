<?php

namespace App\Filament\Pages;

use App\Filament\Resources\SaleResource;
use App\Models\Payment;
use App\Models\Sale;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CardPayments extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'Sales';
    protected static ?string $navigationLabel = 'Card Payments';
    protected static ?string $title           = 'Card Terminal Payments';
    protected static ?string $slug            = 'card-payments';
    protected static ?int    $navigationSort  = 30;
    protected static string  $view            = 'filament.pages.card-payments';

    // ── Filters (all live) ───────────────────────────────────────────────
    public string $dateFrom = '';
    public string $dateTo   = '';
    public string $brand    = '';
    public string $search   = '';
    public string $tab      = 'customers';   // customers | transactions

    public static function canAccess(): bool
    {
        return SaleResource::valorEnabled();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return SaleResource::valorEnabled();
    }

    public function mount(): void
    {
        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->dateTo   = now()->toDateString();
    }

    public function setRange(string $range): void
    {
        [$from, $to] = match ($range) {
            'today' => [now(), now()],
            'week'  => [now()->startOfWeek(), now()],
            'month' => [now()->startOfMonth(), now()],
            'year'  => [now()->startOfYear(), now()],
            default => [now()->subYears(5), now()],   // 'all'
        };
        $this->dateFrom = $from->toDateString();
        $this->dateTo   = $to->toDateString();
    }

    // ── Query ────────────────────────────────────────────────────────────
    protected function baseQuery(): Builder
    {
        $q = Payment::query()->where('gateway', 'valor');

        if ($this->dateFrom) $q->whereDate('paid_at', '>=', $this->dateFrom);
        if ($this->dateTo)   $q->whereDate('paid_at', '<=', $this->dateTo);
        if ($this->brand)    $q->where('card_brand', $this->brand);

        if ($term = trim($this->search)) {
            $like = "%{$term}%";
            $q->where(function (Builder $w) use ($like) {
                $w->where('card_last4', 'like', $like)
                    ->orWhere('gateway_txn_id', 'like', $like)
                    ->orWhere('auth_code', 'like', $like)
                    ->orWhereHas('sale', function (Builder $s) use ($like) {
                        $s->where('invoice_number', 'like', $like)
                            ->orWhereHas('customer', function (Builder $c) use ($like) {
                                $c->whereRaw("CONCAT(name, ' ', COALESCE(last_name, '')) LIKE ?", [$like])
                                    ->orWhere('phone', 'like', $like);
                            });
                    });
            });
        }

        return $q;
    }

    protected static function salePaid(Sale $sale): float
    {
        return round((float) $sale->payments->sum('amount') + (float) $sale->salePayments->sum('amount'), 2);
    }

    protected function loadRows(): Collection
    {
        return $this->baseQuery()
            ->with(['sale.customer', 'sale.payments', 'sale.salePayments'])
            ->orderByDesc('paid_at')
            ->limit(3000)
            ->get();
    }

    protected function buildData(): array
    {
        $rows = $this->loadRows();

        // KPI
        $total = round($rows->sum('amount'), 2);
        $kpi = [
            'total'     => $total,
            'count'     => $rows->count(),
            'avg'       => $rows->count() ? round($total / $rows->count(), 2) : 0,
            'customers' => $rows->pluck('sale.customer_id')->filter()->unique()->count(),
            'sales'     => $rows->pluck('sale_id')->filter()->unique()->count(),
        ];

        // By card brand
        $byBrand = $rows->groupBy(fn($p) => strtoupper($p->card_brand ?: $p->method ?: 'CARD'))
            ->map(fn($g, $brand) => [
                'brand' => $brand,
                'total' => round($g->sum('amount'), 2),
                'count' => $g->count(),
                'pct'   => $total > 0 ? round($g->sum('amount') / $total * 100) : 0,
            ])->sortByDesc('total')->values();

        // By day (for the mini chart)
        $byDay = $rows->groupBy(fn($p) => \Carbon\Carbon::parse($p->paid_at)->format('Y-m-d'))
            ->map(fn($g, $d) => ['day' => $d, 'total' => round($g->sum('amount'), 2)])
            ->sortKeys()->values()->take(-31);

        // Customers: card totals in range + overall open balance
        $customerIds = $rows->pluck('sale.customer_id')->filter()->unique()->values();
        $allSales = Sale::whereIn('customer_id', $customerIds)
            ->whereNotIn('status', ['cancelled', 'void'])
            ->with(['payments', 'salePayments'])
            ->get()
            ->groupBy('customer_id');

        $customers = $rows->groupBy(fn($p) => $p->sale?->customer_id ?? 0)->map(function ($g, $cid) use ($allSales) {
            $customer = $g->first()->sale?->customer;
            $custSales = $allSales->get($cid, collect());

            $openBalance = 0;
            $saleRows = $g->groupBy('sale_id')->map(function ($pays) use (&$openBalance) {
                $sale = $pays->first()->sale;
                if (!$sale) return null;
                $paid    = self::salePaid($sale);
                $balance = max(0, round((float) $sale->final_total - $paid, 2));
                return [
                    'id'       => $sale->id,
                    'invoice'  => $sale->invoice_number,
                    'date'     => $sale->created_at ? \Carbon\Carbon::parse($sale->created_at)->format('M d, Y') : '—',
                    'status'   => $sale->status,
                    'total'    => (float) $sale->final_total,
                    'card'     => round($pays->sum('amount'), 2),
                    'paid'     => $paid,
                    'balance'  => $balance,
                    'brands'   => $pays->map(fn($p) => strtoupper($p->card_brand ?: 'CARD') . ' ••' . $p->card_last4)->unique()->values()->all(),
                ];
            })->filter()->values();

            foreach ($custSales as $s) {
                $openBalance += max(0, round((float) $s->final_total - self::salePaid($s), 2));
            }

            return [
                'id'      => $cid,
                'name'    => $customer ? trim($customer->name . ' ' . ($customer->last_name ?? '')) : 'Walk-in / Unknown',
                'phone'   => $customer?->phone,
                'charged' => round($g->sum('amount'), 2),
                'txns'    => $g->count(),
                'brands'  => $g->groupBy(fn($p) => strtoupper($p->card_brand ?: 'CARD'))->map(fn($b) => round($b->sum('amount'), 2))->sortDesc()->all(),
                'sales'   => $saleRows,
                'open'    => round($openBalance, 2),
                'last'    => $g->first()->paid_at ? \Carbon\Carbon::parse($g->first()->paid_at)->format('M d, Y h:i A') : '—',
            ];
        })->sortByDesc('charged')->values();

        // Transactions
        $txns = $rows->map(function ($p) {
            $sale = $p->sale;
            $bal  = $sale ? max(0, round((float) $sale->final_total - self::salePaid($sale), 2)) : null;
            return [
                'when'    => $p->paid_at ? \Carbon\Carbon::parse($p->paid_at)->format('M d, Y h:i A') : '—',
                'sale_id' => $sale?->id,
                'invoice' => $sale?->invoice_number,
                'cust'    => $sale?->customer ? trim($sale->customer->name . ' ' . ($sale->customer->last_name ?? '')) : '—',
                'brand'   => strtoupper($p->card_brand ?: 'CARD'),
                'last4'   => $p->card_last4,
                'auth'    => $p->auth_code,
                'txn'     => $p->gateway_txn_id,
                'amount'  => (float) $p->amount,
                'balance' => $bal,
            ];
        });

        $brandOptions = Payment::where('gateway', 'valor')->whereNotNull('card_brand')
            ->distinct()->orderBy('card_brand')->pluck('card_brand')->all();

        return compact('kpi', 'byBrand', 'byDay', 'customers', 'txns', 'brandOptions');
    }

    protected function getViewData(): array
    {
        return $this->buildData() + ['saleUrl' => fn($id) => SaleResource::getUrl('edit', ['record' => $id])];
    }

    // ── CSV export (respects current filters) ─────────────────────────────
    public function exportCsv()
    {
        $data = $this->buildData();
        $name = 'card-payments-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Invoice', 'Customer', 'Card', 'Last4', 'Auth', 'Txn ID', 'Amount', 'Sale balance due']);
            foreach ($data['txns'] as $t) {
                fputcsv($out, [$t['when'], $t['invoice'], $t['cust'], $t['brand'], $t['last4'], $t['auth'], $t['txn'], number_format($t['amount'], 2, '.', ''), $t['balance'] === null ? '' : number_format($t['balance'], 2, '.', '')]);
            }
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv']);
    }
}