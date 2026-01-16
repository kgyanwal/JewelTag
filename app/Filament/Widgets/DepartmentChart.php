<?php

namespace App\Filament\Widgets;

use App\Models\ProductItem;
use Filament\Widgets\ChartWidget;

class DepartmentChart extends ChartWidget
{
    protected static ?string $heading = 'Stock Value by Department';

    protected function getData(): array
    {
        $data = ProductItem::where('status', 'in_stock')
            ->selectRaw('department, SUM(retail_price) as total_value')
            ->groupBy('department')
            ->pluck('total_value', 'department');

        return [
            'datasets' => [
                [
                    'label' => 'Retail Value ($)',
                    'data' => $data->values()->toArray(),
                    'backgroundColor' => ['#fbbf24', '#3b82f6', '#10b981', '#f43f5e', '#8b5cf6'],
                ],
            ],
            'labels' => $data->keys()->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut'; // Matches the professional look of Swim software
    }
}