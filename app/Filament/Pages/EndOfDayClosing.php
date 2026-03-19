<?php

namespace App\Filament\Pages;

use App\Models\Sale;
use App\Models\DailyClosing;
use App\Models\Store;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Illuminate\Support\Carbon;

class EndOfDayClosing extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calculator';
    protected static ?string $navigationGroup = 'Sales';
    protected static ?string $navigationLabel = 'End of Day';
    protected static string $view = 'filament.pages.end-of-day-closing';

    #[Url]
    public $date;

    public $actualTotals = [];
    public $expectedTotals = [];
    public $isClosed = false;
    public $paymentMethods = [];

    public function mount()
    {
        // 🚀 Fetch dynamic timezone from store settings
        $tz = Store::first()?->timezone ?? config('app.timezone', 'UTC');

        $this->date = now()->setTimezone($tz)->format('Y-m-d');

        $this->loadData();
    }

    public function updatedDate()
    {
        $this->loadData();
    }

    public function loadData()
    {
        /*
        |--------------------------------------------------------------------------
        | 1. Load Payment Methods (STANDARDIZED KEYS)
        |--------------------------------------------------------------------------
        */
        $json = DB::table('site_settings')->where('key', 'payment_methods')->value('value');
        $defaultMethods = ['CASH', 'VISA', 'MASTERCARD', 'AMEX', 'LAYBUY'];
        $rawMethods = $json ? json_decode($json, true) : $defaultMethods;

        $this->paymentMethods = [];
        foreach ($rawMethods as $methodName) {
            $key = strtolower(trim($methodName)); 
            $this->paymentMethods[$key] = strtoupper($methodName);
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Check If Day Already Closed
        |--------------------------------------------------------------------------
        */
        $record = DailyClosing::whereDate('closing_date', $this->date)->first();

        if ($record) {
            $this->expectedTotals = $record->expected_data;
            $this->actualTotals   = $record->actual_data;
            $this->isClosed       = true;
            return;
        }

        $this->isClosed = false;

        /*
        |--------------------------------------------------------------------------
        | 3. Calculate Live System Totals (TIMEZONE FIX)
        |--------------------------------------------------------------------------
        */
        $tz = Store::first()?->timezone ?? config('app.timezone', 'UTC');

        // 🚀 THE FIX: Calculate the exact UTC start and end for the local day
        $startUtc = Carbon::parse($this->date, $tz)->startOfDay()->setTimezone('UTC');
        $endUtc = Carbon::parse($this->date, $tz)->endOfDay()->setTimezone('UTC');

        // Use whereBetween to capture everything in that 24-hour UTC window
    $payments = \App\Models\Payment::whereBetween('paid_at', [$startUtc, $endUtc])
    ->get();

        $systemTotals = array_fill_keys(array_keys($this->paymentMethods), 0);

        $systemTotals = array_fill_keys(array_keys($this->paymentMethods), 0);

foreach ($payments as $payment) {
    $key = strtolower(trim($payment->method));
    if (isset($systemTotals[$key])) {
        $systemTotals[$key] += (float) $payment->amount;
    }
}

        /*
        |--------------------------------------------------------------------------
        | 4. Populate Totals Based on Role
        |--------------------------------------------------------------------------
        */
       foreach ($this->paymentMethods as $key => $label) {

    $systemVal = $systemTotals[$key] ?? 0;

    // ❌ Skip methods that have no payments
    if ($systemVal <= 0) {
        continue;
    }

    if (auth()->user()->hasRole('Superadmin')) {
        $this->expectedTotals[$key] = $systemVal;
        $this->actualTotals[$key]   = $systemVal;
    } else {
        $this->expectedTotals[$key] = 0;
        $this->actualTotals[$key]   = 0;
    }
}
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('post_closing')
                ->label('POST CLOSING & LOCK DAY')
                ->color('success')
                ->requiresConfirmation()
                ->hidden(fn () => $this->isClosed)
                ->action(function () {
                    $this->loadData(); // Fresh calculation using the UTC window

                    DailyClosing::create([
                        'closing_date'  => $this->date,
                        'expected_data' => $this->expectedTotals,
                        'actual_data'   => $this->actualTotals,
                        'total_expected'=> array_sum($this->expectedTotals),
                        'total_actual'  => array_sum($this->actualTotals),
                        'user_id'       => auth()->id(),
                    ]);

                    Notification::make()->title('Day Closed Successfully')->success()->send();
                    $this->loadData();
                }),
        ];
    }
}