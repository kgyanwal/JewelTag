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
    protected static ?string $title = 'Sales Report & Bank Deposits';
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
        $start = Carbon::parse($this->fromDate ?? now())->startOfDay();
        $end = Carbon::parse($this->toDate ?? now())->endOfDay();

        // 1. Fetch dynamic settings to ensure we account for everything
        $settings = DB::table('site_settings')->where('key', 'payment_methods')->value('value');
        $configuredMethods = $settings ? json_decode($settings, true) : [];

        // 2. Fetch actual payments in date range
        $payments = Payment::whereBetween('paid_at', [$start, $end])
            ->select(DB::raw('UPPER(method) as method'), DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total'))
            ->groupBy('method')
            ->orderBy('method')
            ->get();

        $categorized = [
            'Amex' => [],
            'Credit Card and Finance' => [],
            'Cash Entry' => [],
            'Laybys' => [],
        ];

        $grandTotal = 0;

        // 3. Intelligently group the dynamic payment methods
        foreach ($payments as $payment) {
            $method = trim($payment->method);
            $grandTotal += floatval($payment->total);

            if ($method === 'AMEX' || $method === 'AMERICAN EXPRESS') {
                $categorized['Amex'][] = $payment;
            } elseif (in_array($method, ['CASH', 'CHECK'])) {
                $categorized['Cash Entry'][] = $payment;
            } elseif (in_array($method, ['KATAPULT', 'LAYBUY', 'LAYBUY_DEPOSIT', 'TRADE_IN', 'STORE_CREDIT'])) {
                $categorized['Laybys'][] = $payment;
            } else {
                // VISA, MASTERCARD, ACIMA, SNAP, etc.
                $categorized['Credit Card and Finance'][] = $payment;
            }
        }

        $groupTotals = [];
        foreach ($categorized as $groupName => $items) {
            $groupTotals[$groupName] = collect($items)->sum('total');
        }

        return [
            'categories' => $categorized,
            'groupTotals' => $groupTotals,
            'grandTotal' => $grandTotal,
        ];
    }
}