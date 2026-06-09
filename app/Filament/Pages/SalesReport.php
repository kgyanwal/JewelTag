<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use App\Models\Payment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SalesReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static ?string $navigationGroup = 'Analytics & Reports';
    protected static ?string $navigationLabel = 'Store Sales Reports';
    protected static ?string $title = '';
    protected static string $view = 'filament.pages.sales-report';

    public ?string $fromDate = null;
    public ?string $toDate = null;

    public function mount()
    {
        $this->fromDate = now()->startOfMonth()->toDateString();
        $this->toDate = now()->endOfMonth()->toDateString();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(4)->schema([
                    DatePicker::make('fromDate')
                        ->label('From Date')
                        ->native(false)
                        ->displayFormat('m/d/Y')
                        ->live()
                        ->columnSpan(1),
                    DatePicker::make('toDate')
                        ->label('To Date')
                        ->native(false)
                        ->displayFormat('m/d/Y')
                        ->live()
                        ->columnSpan(1),
                ])
            ]);
    }

   public function getReportDataProperty(): array
{
    $tz    = \App\Models\Store::first()?->timezone ?? config('app.timezone', 'UTC');
    $start = Carbon::parse($this->fromDate ?? now(), $tz)->startOfDay()->setTimezone('UTC');
    $end   = Carbon::parse($this->toDate ?? now(), $tz)->endOfDay()->setTimezone('UTC');

    // ── 1. payments table (same as EOD) ──────────────────────────
    $payments = Payment::whereBetween('paid_at', [$start, $end])
        ->select(
            DB::raw('UPPER(TRIM(method)) as method'),
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(amount) as total')
        )
        ->groupBy('method')
        ->orderBy('method')
        ->get();

    // ── 2. sale_payments table (EOD also includes this) ──────────
    $salePayments = DB::table('sale_payments')
        ->whereBetween('payment_date', [$start, $end])
        ->select(
            DB::raw('UPPER(TRIM(payment_method)) as method'),
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(amount) as total')
        )
        ->groupBy('payment_method')
        ->orderBy('payment_method')
        ->get();

    // ── 3. Laybuy sales with no payment rows (same EOD fallback) ──
    $laybuyTotal = \App\Models\Sale::whereBetween('created_at', [$start, $end])
        ->where('payment_method', 'laybuy')
        ->whereNotIn('id', function ($q) use ($start, $end) {
            $q->select('sale_id')
                ->from('payments')
                ->whereBetween('paid_at', [$start, $end])
                ->whereNotNull('sale_id');
        })
        ->sum('amount_paid');

    // ── 4. Merge all three sources into one collection ────────────
    $merged = collect();

    foreach ($payments as $p) {
        $merged->push(['method' => $p->method, 'count' => $p->count, 'total' => floatval($p->total)]);
    }

    foreach ($salePayments as $p) {
        $merged->push(['method' => $p->method, 'count' => $p->count, 'total' => floatval($p->total)]);
    }

    if ($laybuyTotal > 0) {
        $merged->push(['method' => 'LAYBUY', 'count' => 0, 'total' => floatval($laybuyTotal)]);
    }

    // ── 5. Group merged by method ─────────────────────────────────
    $grouped = $merged->groupBy('method')->map(function ($rows, $method) {
        return (object)[
            'method' => $method,
            'count'  => $rows->sum('count'),
            'total'  => $rows->sum('total'),
        ];
    })->values();

    // ── 6. Categorize (same logic as before) ─────────────────────
    $categorized = [
        'Amex'                    => [],
        'Credit Card and Finance' => [],
        'Cash Entry'              => [],
        'Laybys'                  => [],
    ];

    $grandTotal = 0;

    foreach ($grouped as $payment) {
        $method      = strtoupper(trim($payment->method));
        $grandTotal += floatval($payment->total);

        if (in_array($method, ['AMEX', 'AMERICAN EXPRESS'])) {
            $categorized['Amex'][] = $payment;
        } elseif (in_array($method, ['CASH', 'CHECK'])) {
            $categorized['Cash Entry'][] = $payment;
        } elseif (in_array($method, ['KATAPULT', 'LAYBUY', 'LAYBUY_DEPOSIT', 'TRADE_IN', 'STORE_CREDIT'])) {
            $categorized['Laybys'][] = $payment;
        } else {
            $categorized['Credit Card and Finance'][] = $payment;
        }
    }

    $groupTotals = [];
    foreach ($categorized as $groupName => $items) {
        $groupTotals[$groupName] = collect($items)->sum('total');
    }

    return [
        'categories'  => $categorized,
        'groupTotals' => $groupTotals,
        'grandTotal'  => $grandTotal,
    ];
}
}