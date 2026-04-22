<?php

namespace App\Filament\Pages;

use App\Models\DailyClosing;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class DailyClosingRegister extends Page
{
    protected static string $view = 'filament.pages.daily-closing-register';
    
    protected static ?string $navigationIcon = 'heroicon-o-calculator';
    protected static ?string $navigationLabel = 'EOD Register';
    protected static ?string $title = 'Daily Closing';

    public ?string $date = null;
    public bool $isClosed = false;
    
    public array $expectedTotals = [];
    public array $actualTotals = [];

    // CRITICAL FIX: No type hinting (e.g., ?float). 
    // This allows Livewire to accept empty strings ("") when you clear the input box without crashing.
    public $totalExpected; 

    public function mount(): void
    {
        $this->date = Carbon::today()->format('Y-m-d');
        $this->loadDayData();
    }

    public function updatedDate(): void
    {
        $this->loadDayData();
    }

    protected function loadDayData(): void
    {
        $closingRecord = DailyClosing::whereDate('closing_date', $this->date)->first();

        if ($closingRecord) {
            $this->isClosed       = true;
            $this->expectedTotals = $closingRecord->expected_data ?? [];
            $this->actualTotals   = $closingRecord->actual_data ?? [];
            $this->totalExpected  = $closingRecord->sales_summary['total_expected'] ?? array_sum($this->expectedTotals);
        } else {
            $this->isClosed = false;
            
            // Expected system totals
            $this->expectedTotals = [
                'cash'       => 100.00,
                'visa'       => 1076.30,
                'mastercard' => 0.00,
                'katapult'   => 538.15,
            ];

            // Counted actuals
            $this->actualTotals = [
                'cash'       => 100.00,
                'visa'       => 1076.30,
                'mastercard' => 0.00,
                'katapult'   => 538.15,
            ];

            // CRITICAL FIX: Set the property immediately so the blade view never has to guess.
            $this->totalExpected = array_sum($this->expectedTotals);
        }
    }

    protected function getActions(): array
    {
        return [
            Action::make('post_closing')
                ->label('Post Closing & Lock Day')
                ->requiresConfirmation()
                ->modalHeading('Lock Daily Register')
                ->modalDescription('Are you sure you want to close the register for this day? This action cannot be undone.')
                ->modalSubmitActionLabel('Yes, Lock Register')
                ->color('primary')
                ->action(fn () => $this->saveClosingData()),
        ];
    }

    public function saveClosingData(): void
    {
        if ($this->isClosed) {
            Notification::make()->title('This day is already locked.')->danger()->send();
            return;
        }

        // Safely parse the user's input for saving
        $finalExpected = is_numeric($this->totalExpected) ? (float) $this->totalExpected : 0.00;
        $finalActual = array_sum($this->actualTotals);

        DailyClosing::create([
            'closing_date'  => $this->date,
            'expected_data' => $this->expectedTotals,
            'actual_data'   => $this->actualTotals,
            'sales_summary' => [
                'total_expected' => $finalExpected,
                'total_actual'   => $finalActual,
                'variance'       => $finalActual - $finalExpected,
            ],
        ]);

        Notification::make()
            ->title('Register successfully closed for the day.')
            ->success()
            ->send();

        $this->loadDayData();
    }
}