<?php

namespace App\Filament\Widgets;

use App\Models\ProductItem;
use Filament\Widgets\ChartWidget;

class DepartmentChart extends ChartWidget
{
    protected static ?string $heading = 'Stock by Department';
    protected static ?int $sort = 2;
    protected int|string|array $columnSpan = 'full';
    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $data = ProductItem::where('status', 'in_stock')
            ->selectRaw('department, SUM(retail_price) as total_value, COUNT(*) as item_count')
            ->groupBy('department')
            ->orderByDesc('total_value')
            ->take(8)
            ->get();

        $colors = $this->generateColors($data->count());

        return [
            'datasets' => [[
                'label' => 'Retail Value ($)',
                'data' => $data->pluck('total_value')->toArray(),
                'backgroundColor' => $colors,
                'borderColor' => '#fff',
                'borderWidth' => 2,
                'hoverOffset' => 15,
                'cutout' => '65%',
            ]],
            'labels' => $data->map(fn($item) => "{$item->department} (\${number_format($item->total_value)})")->toArray(),
        ];
    }

    protected function generateColors(int $count): array
    {
        $palette = ['#0d9488','#2dd4bf','#0e7490','#10b981','#14b8a6','#06b6d4','#0891b2','#0f766e','#134e4a','#115e59'];
        return array_slice(array_merge($palette, $palette), 0, $count);
    }

    protected function getType(): string { return 'doughnut'; }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => true,
            'plugins' => [
                'legend' => ['position'=>'right','labels'=>['padding'=>15,'usePointStyle'=>true,'pointStyle'=>'circle','font'=>['family'=>'Inter','size'=>12,'weight'=>500],'color'=>'#334155']],
                'tooltip' => ['backgroundColor'=>'rgba(13,148,136,0.9)','titleFont'=>['family'=>'Inter','size'=>13,'weight'=>600],'bodyFont'=>['family'=>'Inter','size'=>12,'weight'=>400],'padding'=>10,'cornerRadius'=>8,'displayColors'=>true],
            ],
            'cutoutPercentage' => 65,
            'animation' => ['animateScale'=>true,'animateRotate'=>true,'duration'=>1000,'easing'=>'easeOutQuart'],
        ];
    }

    public function getDescription(): ?string
    {
        $total = number_format(ProductItem::where('status','in_stock')->sum('retail_price'));
        $count = ProductItem::where('status','in_stock')->count();
        return "Total: \${$total} • {$count} items";
    }
}
