<?php

namespace App\Filament\Pages;

use App\Models\Laybuy;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Illuminate\Support\Carbon;

class LaybuyHealthReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon  = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Analytics & Reports';
    protected static ?string $navigationLabel = 'Laybuy Health';
    protected static ?string $title           = 'Laybuy Health Report';
    protected static string  $view            = 'filament.pages.laybuy-health-report';

    public string $riskFilter = 'all'; // all | healthy | watch | at_risk | overdue

    // 🚀 NEW — optional date filter on the laybuy's start_date/created_at.
    // Both null by default = show all active laybuys regardless of when
    // they were created, matching prior behavior exactly.
    public ?string $fromDate = null;
    public ?string $toDate   = null;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(3)->schema([
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
                ]),
            ]);
    }

    protected function activeLaybuys()
    {
        $query = Laybuy::query()
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->with('customer');

        // 🚀 FIX — start_date is nullable and isn't always populated (confirmed:
        // existing laybuy records can have a null start_date even though
        // created_at is always set). Filter against COALESCE(start_date,
        // created_at) so records without an explicit start_date still show up
        // correctly under the date they were actually created.
        if ($this->fromDate) {
            $query->whereRaw('COALESCE(DATE(start_date), DATE(created_at)) >= ?', [Carbon::parse($this->fromDate)->toDateString()]);
        }
        if ($this->toDate) {
            $query->whereRaw('COALESCE(DATE(start_date), DATE(created_at)) <= ?', [Carbon::parse($this->toDate)->toDateString()]);
        }

        return $query->get();
    }

    /**
     * Risk tiering logic:
     * - overdue: past due_date with balance still owed
     * - at_risk: due within 7 days AND less than 50% paid
     * - watch: less than 50% paid (regardless of due date), or no payment in 30+ days
     * - healthy: everything else (on track, majority paid, not yet due soon)
     */
    protected function classifyRisk(Laybuy $laybuy): string
    {
        $total   = floatval($laybuy->total_amount ?? 0);
        $paid    = floatval($laybuy->amount_paid ?? 0);
        $balance = floatval($laybuy->balance_due ?? max(0, $total - $paid));
        $pctPaid = $total > 0 ? ($paid / $total) * 100 : 0;
        $dueDate = $laybuy->due_date ? Carbon::parse($laybuy->due_date) : null;

        if ($balance <= 0.01) {
            return 'healthy';
        }

        if ($dueDate && $dueDate->isPast()) {
            return 'overdue';
        }

        if ($dueDate && $dueDate->diffInDays(now()) <= 7 && $pctPaid < 50) {
            return 'at_risk';
        }

        if ($pctPaid < 50) {
            return 'watch';
        }

        $lastPaymentAge = $laybuy->updated_at ? Carbon::parse($laybuy->updated_at)->diffInDays(now()) : 0;
        if ($lastPaymentAge >= 30) {
            return 'watch';
        }

        return 'healthy';
    }

    public function getClassifiedLaybuys(): array
    {
        $laybuys = $this->activeLaybuys();

        $buckets = [
            'overdue' => collect(),
            'at_risk' => collect(),
            'watch'   => collect(),
            'healthy' => collect(),
        ];

        foreach ($laybuys as $laybuy) {
            $risk = $this->classifyRisk($laybuy);
            $laybuy->risk_tier = $risk;
            $buckets[$risk]->push($laybuy);
        }

        // sort each bucket: most urgent first
        $buckets['overdue'] = $buckets['overdue']->sortBy('due_date')->values();
        $buckets['at_risk'] = $buckets['at_risk']->sortBy('due_date')->values();
        $buckets['watch']   = $buckets['watch']->sortByDesc(fn($l) => floatval($l->balance_due ?? 0))->values();
        $buckets['healthy'] = $buckets['healthy']->sortBy('due_date')->values();

        return $buckets;
    }

    // 🚀 NEW — separate from getStats()'s always-on risk metrics: this is
    // specifically "how many laybuy sales fall in the selected date range",
    // regardless of active/completed/cancelled status, so staff can answer
    // "how many laybuys did we write today" even after some have since
    // been paid off or cancelled.
    public function getPeriodStats(): array
    {
        $query = Laybuy::query();

        // 🚀 FIX — same COALESCE fallback as activeLaybuys(), so period stats
        // reconcile with the risk-tier list below rather than silently
        // excluding records with a null start_date.
        if ($this->fromDate) {
            $query->whereRaw('COALESCE(DATE(start_date), DATE(created_at)) >= ?', [Carbon::parse($this->fromDate)->toDateString()]);
        }
        if ($this->toDate) {
            $query->whereRaw('COALESCE(DATE(start_date), DATE(created_at)) <= ?', [Carbon::parse($this->toDate)->toDateString()]);
        }

        $periodLaybuys = $query->get();

        return [
            'count'       => $periodLaybuys->count(),
            'total_value' => $periodLaybuys->sum('total_amount'),
            'total_paid'  => $periodLaybuys->sum('amount_paid'),
        ];
    }

    public function getStats(): array
    {
        $all = $this->activeLaybuys();
        $buckets = $this->getClassifiedLaybuys();

        $totalOutstanding = $all->sum(fn($l) => floatval($l->balance_due ?? max(0, floatval($l->total_amount) - floatval($l->amount_paid))));
        $totalCollected    = $all->sum('amount_paid');
        $totalValue         = $all->sum('total_amount');

        $atRiskValue = $buckets['overdue']->concat($buckets['at_risk'])
            ->sum(fn($l) => floatval($l->balance_due ?? max(0, floatval($l->total_amount) - floatval($l->amount_paid))));

        return [
            'total_active'      => $all->count(),
            'total_outstanding' => $totalOutstanding,
            'total_collected'   => $totalCollected,
            'total_value'       => $totalValue,
            'collection_rate'   => $totalValue > 0 ? round(($totalCollected / $totalValue) * 100, 1) : 0,
            'overdue_count'     => $buckets['overdue']->count(),
            'at_risk_count'     => $buckets['at_risk']->count(),
            'watch_count'       => $buckets['watch']->count(),
            'healthy_count'     => $buckets['healthy']->count(),
            'at_risk_value'     => $atRiskValue,
        ];
    }
}
