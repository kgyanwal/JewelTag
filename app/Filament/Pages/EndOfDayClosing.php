<?php

namespace App\Filament\Pages;

use App\Models\Sale;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class EndOfDayClosing extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calculator';
    protected static ?string $navigationGroup = 'Sales';
    protected static ?string $navigationLabel = 'End of Day';
    protected static string $view = 'filament.pages.end-of-day-closing';

    public $actualTotals = [];
    public $expectedTotals = [];

    public function mount()
    {
        $this->calculateTotals();
    }

    public function calculateTotals()
    {
        // Define all payment methods from your SaleResource
        $methods = [
            'visa' => 'Visa',
            'cash' => 'Cash',
            'debit_card' => 'Debit Card',
            'katapult' => 'Katapult',
            'kafene' => 'Kafene',
            'acima_finance' => 'Acima Finance',
            // Add others as needed...
        ];

        $todaySales = Sale::whereDate('created_at', today())
            ->where('status', 'completed')
            ->select('payment_method', DB::raw('SUM(final_total) as total'))
            ->groupBy('payment_method')
            ->pluck('total', 'payment_method');

        foreach ($methods as $key => $label) {
            $this->expectedTotals[$key] = $todaySales->get($key, 0);
            $this->actualTotals[$key] = $this->expectedTotals[$key]; // Default actual to expected
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('post_closing')
                ->label('POST')
                ->color('success')
                ->requiresConfirmation()
                ->action(function () {
                    // Logic to lock these sales or save a ClosingRecord model
                    Notification::make()
                        ->title('Daily Totals Posted')
                        ->success()
                        ->send();
                }),
        ];
    }
}