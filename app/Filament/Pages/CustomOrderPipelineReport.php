<?php

namespace App\Filament\Pages;

use App\Models\CustomOrder;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CustomOrderPipelineReport extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-paint-brush';
    protected static ?string $navigationGroup = 'Analytics & Reports';
    protected static ?string $navigationLabel = 'Custom Order Pipeline';
    protected static ?string $title           = 'Custom Order Pipeline';
    protected static string  $view            = 'filament.pages.custom-order-pipeline-report';

    public string $rangeFilter = 'all'; // all | 30 | 90

    protected array $stages = [
        'draft'         => ['label' => 'Draft',          'icon' => 'heroicon-o-document',          'color' => '#5C5346'],
        'quoted'        => ['label' => 'Quoted',          'icon' => 'heroicon-o-document-text',     'color' => '#8A6428'],
        'approved'      => ['label' => 'Approved',        'icon' => 'heroicon-o-check-circle',      'color' => '#B8863B'],
        'in_production' => ['label' => 'In Production',   'icon' => 'heroicon-o-wrench',             'color' => '#E0AE5C'],
        'received'      => ['label' => 'Ready for Pickup','icon' => 'heroicon-o-archive-box',       'color' => '#6FCF97'],
        'completed'     => ['label' => 'Completed',       'icon' => 'heroicon-o-check-badge',       'color' => '#3E7C5A'],
    ];

    public function getStages(): array
    {
        return $this->stages;
    }

    public function updatedRangeFilter(): void
    {
        // triggers re-render via Livewire reactivity, getPipelineData() recalculates
    }

    protected function baseQuery()
    {
        $query = CustomOrder::query();

        if ($this->rangeFilter === '30') {
            $query->where('created_at', '>=', now()->subDays(30));
        } elseif ($this->rangeFilter === '90') {
            $query->where('created_at', '>=', now()->subDays(90));
        }

        return $query;
    }

    public function getPipelineData(): array
    {
        $orders = $this->baseQuery()
            ->get(['id', 'order_no', 'customer_id', 'product_name', 'status', 'quoted_price', 'discount_amount', 'amount_paid', 'balance_due', 'due_date', 'created_at']);

        $byStage = [];
        foreach (array_keys($this->stages) as $stageKey) {
            $byStage[$stageKey] = collect();
        }

        foreach ($orders as $order) {
            $stage = $order->status ?? 'draft';
            if (!isset($byStage[$stage])) {
                $byStage[$stage] = collect();
            }
            $byStage[$stage]->push($order);
        }

        $stageMetrics = [];
        foreach ($this->stages as $key => $meta) {
            $items = $byStage[$key] ?? collect();
            $value = $items->sum(fn($o) => floatval($o->quoted_price) - floatval($o->discount_amount ?? 0));

            $stageMetrics[$key] = [
                'count'    => $items->count(),
                'value'    => $value,
                'items'    => $items->sortBy('due_date')->values(),
            ];
        }

        return $stageMetrics;
    }

    public function getOverdueOrders()
    {
        return $this->baseQuery()
            ->whereNotIn('status', ['completed'])
            ->whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->with('customer')
            ->orderBy('due_date')
            ->get();
    }

    public function getAvgDaysInStage(): array
    {
        // Average age (days since created_at) of orders CURRENTLY sitting in each stage —
        // a proxy for "how long things tend to linger here" without needing a stage-history table.
        $rows = $this->baseQuery()
            ->whereNotIn('status', ['completed'])
            ->get(['status', 'created_at']);

        $grouped = $rows->groupBy('status');
        $result  = [];

        foreach (array_keys($this->stages) as $stage) {
            $group = $grouped->get($stage, collect());
            $result[$stage] = $group->isEmpty()
                ? 0
                : round($group->avg(fn($r) => Carbon::parse($r->created_at)->diffInDays(now())), 1);
        }

        return $result;
    }

    public function getStats(): array
    {
        $all = $this->baseQuery()->get(['status', 'quoted_price', 'discount_amount', 'amount_paid', 'balance_due', 'due_date']);

        $active     = $all->whereNotIn('status', ['completed']);
        $completed  = $all->where('status', 'completed');
        $overdue    = $active->filter(fn($o) => $o->due_date && Carbon::parse($o->due_date)->isPast());

        $pipelineValue = $active->sum(fn($o) => floatval($o->quoted_price) - floatval($o->discount_amount ?? 0));
        $depositsHeld  = $active->sum('amount_paid');
        $outstanding   = $active->sum('balance_due');

        return [
            'total_active'     => $active->count(),
            'total_completed'  => $completed->count(),
            'overdue_count'    => $overdue->count(),
            'pipeline_value'   => $pipelineValue,
            'deposits_held'    => $depositsHeld,
            'outstanding'      => $outstanding,
            'completion_rate'  => $all->count() > 0 ? round(($completed->count() / $all->count()) * 100, 1) : 0,
        ];
    }
}