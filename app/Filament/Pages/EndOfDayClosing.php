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
        $tz = Store::first()?->timezone ?? config('app.timezone');

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
            $key = strtolower(trim($methodName)); // STANDARD KEY
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
        | 3. Calculate Live System Totals
        |--------------------------------------------------------------------------
        */
        $sales = Sale::whereDate('created_at', $this->date)
            ->where('status', 'completed')
            ->get();

        $systemTotals = [];

        foreach ($this->paymentMethods as $key => $label) {
            $systemTotals[$key] = 0;
        }

        foreach ($sales as $sale) {

            if ($sale->is_split_payment) {

                if ($sale->payment_method_1) {
                    $key = strtolower(trim($sale->payment_method_1));
                    if (isset($systemTotals[$key])) {
                        $systemTotals[$key] += (float) $sale->payment_amount_1;
                    }
                }

                if ($sale->payment_method_2) {
                    $key = strtolower(trim($sale->payment_method_2));
                    if (isset($systemTotals[$key])) {
                        $systemTotals[$key] += (float) $sale->payment_amount_2;
                    }
                }

                if ($sale->payment_method_3) {
                    $key = strtolower(trim($sale->payment_method_3));
                    if (isset($systemTotals[$key])) {
                        $systemTotals[$key] += (float) $sale->payment_amount_3;
                    }
                }

            } else {

                $key = strtolower(trim($sale->payment_method));

                if (isset($systemTotals[$key])) {
                    $systemTotals[$key] += (float) $sale->final_total;
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Populate Totals Based on Role
        |--------------------------------------------------------------------------
        */
        foreach ($this->paymentMethods as $key => $label) {

            $systemVal = $systemTotals[$key] ?? 0;

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

                    $this->loadData();

                    $sales = Sale::whereDate('created_at', $this->date)
                        ->where('status', 'completed')
                        ->get();

                    $finalExpected = [];

                    foreach ($this->paymentMethods as $key => $label) {
                        $finalExpected[$key] = 0;
                    }

                    foreach ($sales as $sale) {

                        if ($sale->is_split_payment) {

                            if ($sale->payment_method_1) {
                                $key = strtolower(trim($sale->payment_method_1));
                                if (isset($finalExpected[$key])) {
                                    $finalExpected[$key] += (float) $sale->payment_amount_1;
                                }
                            }

                            if ($sale->payment_method_2) {
                                $key = strtolower(trim($sale->payment_method_2));
                                if (isset($finalExpected[$key])) {
                                    $finalExpected[$key] += (float) $sale->payment_amount_2;
                                }
                            }

                            if ($sale->payment_method_3) {
                                $key = strtolower(trim($sale->payment_method_3));
                                if (isset($finalExpected[$key])) {
                                    $finalExpected[$key] += (float) $sale->payment_amount_3;
                                }
                            }

                        } else {

                            $key = strtolower(trim($sale->payment_method));

                            if (isset($finalExpected[$key])) {
                                $finalExpected[$key] += (float) $sale->final_total;
                            }
                        }
                    }

                    DailyClosing::create([
                        'closing_date'  => $this->date,
                        'expected_data' => $finalExpected,
                        'actual_data'   => $this->actualTotals,
                        'total_expected'=> array_sum($finalExpected),
                        'total_actual'  => array_sum($this->actualTotals),
                        'user_id'       => auth()->id(),
                    ]);

                    Notification::make()
                        ->title('Day Closed Successfully')
                        ->success()
                        ->send();

                    $this->loadData();
                }),
        ];
    }
}