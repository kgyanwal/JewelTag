<?php

namespace App\Filament\Widgets;

use App\Models\ProductItem;
use Filament\Widgets\ChartWidget;

class DepartmentChart extends ChartWidget
{
    protected static ?string $heading = '📊 Stock by Department';
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
                'borderColor' => '#ffffff',
                'borderWidth' => 3,
                'hoverOffset' => 20,
                'cutout' => '70%',
                'borderRadius' => 8,
                'spacing' => 3,
            ]],
            'labels' => $data->map(fn($item) => "{$item->department}")->toArray(),
        ];
    }

    protected function generateColors(int $count): array
    {
        $palette = ['#0d9488','#2dd4bf','#0e7490','#10b981','#14b8a6','#06b6d4','#0891b2','#0f766e','#134e4a','#115e59'];
        return array_slice(array_merge($palette, $palette), 0, $count);
    }

    protected function getType(): string { 
        return 'doughnut'; 
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => true,
            'plugins' => [
                'legend' => [
                    'position' => 'right',
                    'labels' => [
                        'padding' => 15,
                        'usePointStyle' => true,
                        'pointStyle' => 'circle',
                        'font' => [
                            'family' => 'Inter, sans-serif',
                            'size' => 12,
                            'weight' => 600,
                        ],
                        'color' => '#1f2937',
                    ],
                ],
                'tooltip' => [
                    'backgroundColor' => 'rgba(13, 148, 136, 0.95)',
                    'titleFont' => [
                        'family' => 'Inter, sans-serif',
                        'size' => 13,
                        'weight' => 600,
                    ],
                    'bodyFont' => [
                        'family' => 'Inter, sans-serif',
                        'size' => 12,
                        'weight' => 400,
                    ],
                    'padding' => 12,
                    'cornerRadius' => 8,
                    'displayColors' => true,
                    'callbacks' => [
                        'label' => function($context) {
                            $value = $context->raw;
                            return 'Value: $' . number_format($value);
                        }
                    ],
                ],
            ],
            'cutout' => '70%',
            'animation' => [
                'animateScale' => true,
                'animateRotate' => true,
                'duration' => 1000,
                'easing' => 'easeOutQuart',
            ],
        ];
    }

    public function getDescription(): ?string
    {
        $total = number_format(ProductItem::where('status','in_stock')->sum('retail_price'));
        $count = ProductItem::where('status','in_stock')->count();
        return "Total: \${$total} • {$count} items";
    }

    // Add custom CSS for background
    public static function getAssets(): array
    {
        return [
            'style' => <<<'HTML'
<style>
    /* Department Chart Widget Styling */
    .fi-wi-department-chart {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%) !important;
        border-radius: 16px !important;
        border: 1px solid #e5e7eb !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05) !important;
        overflow: hidden !important;
    }

    .fi-wi-department-chart .fi-wi-header {
        background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        padding: 1.25rem 1.5rem !important;
    }

    .fi-wi-department-chart .fi-wi-header-heading {
        color: white !important;
        font-weight: 700 !important;
        font-size: 1.25rem !important;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
    }

    .fi-wi-department-chart .fi-wi-header-description {
        color: rgba(255, 255, 255, 0.9) !important;
        font-weight: 500 !important;
        margin-top: 0.25rem !important;
    }

    .fi-wi-department-chart .fi-wi-stats {
        background: rgba(13, 148, 136, 0.05);
        padding: 1rem 1.5rem !important;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        gap: 2rem;
        align-items: center;
    }

    .fi-wi-department-chart .fi-wi-stat {
        display: flex;
        flex-direction: column;
    }

    .fi-wi-department-chart .fi-wi-stat-value {
        font-weight: 800;
        font-size: 1.5rem;
        color: #0d9488;
    }

    .fi-wi-department-chart .fi-wi-stat-label {
        font-weight: 500;
        font-size: 0.875rem;
        color: #6b7280;
    }

    /* Chart container */
    .fi-wi-department-chart .fi-wi-chart-ctn {
        padding: 1.5rem !important;
        background: white !important;
        border-radius: 0 0 16px 16px !important;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .fi-wi-department-chart .fi-wi-stats {
            flex-direction: column;
            gap: 1rem;
            align-items: flex-start;
        }
        
        .fi-wi-department-chart .plugins.legend {
            position: relative !important;
            margin-top: 1rem !important;
        }
    }
</style>
HTML,
        ];
    }
}