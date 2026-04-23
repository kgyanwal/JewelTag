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

    public $actualTotals   = [];
    public $expectedTotals = [];
    public $salesSummary   = []; // Per-staff breakdown
    public $isClosed       = false;
    public $paymentMethods = [];

    public function mount(): void
    {
        $tz         = Store::first()?->timezone ?? config('app.timezone', 'UTC');
        $this->date = now()->setTimezone($tz)->format('Y-m-d');
        $this->loadData();
    }

    public function updatedDate(): void
    {
        $this->loadData();
    }

    // ── READ-ONLY CHECK ───────────────────────────────────────────────────────
    // Fixed: Pages don't have $this->record — check DailyClosing directly
    public function isReadOnly(): bool
    {
        if (auth()->user()->hasRole('Superadmin')) {
            return false;
        }
        return DailyClosing::whereDate('closing_date', $this->date)->exists();
    }

    public function loadData(): void
    {
        /*
        |----------------------------------------------------------------------
        | 1. Payment Methods
        |----------------------------------------------------------------------
        */
        $json           = DB::table('site_settings')->where('key', 'payment_methods')->value('value');
        $rawMethods     = $json ? json_decode($json, true) : ['CASH', 'VISA', 'MASTERCARD', 'AMEX', 'LAYBUY'];

        $this->paymentMethods = [];
        foreach ($rawMethods as $m) {
            $this->paymentMethods[strtolower(trim($m))] = strtoupper($m);
        }

        /*
        |----------------------------------------------------------------------
        | 2. Already Closed?
        |----------------------------------------------------------------------
        */
        $record = DailyClosing::whereDate('closing_date', $this->date)->first();

        if ($record) {
            $this->expectedTotals = $record->expected_data;
            $this->actualTotals   = $record->actual_data;
            $this->salesSummary   = $record->sales_summary ?? [];
            $this->isClosed       = true;
            return;
        }

        $this->isClosed       = false;
        $this->expectedTotals = [];
        $this->actualTotals   = [];
        $this->salesSummary   = [];

        /*
        |----------------------------------------------------------------------
        | 3. UTC window for the local day
        |----------------------------------------------------------------------
        */
        $tz       = Store::first()?->timezone ?? config('app.timezone', 'UTC');
        $startUtc = Carbon::parse($this->date, $tz)->startOfDay()->setTimezone('UTC');
        $endUtc   = Carbon::parse($this->date, $tz)->endOfDay()->setTimezone('UTC');

        /*
        |----------------------------------------------------------------------
        | 4. All payments in this window — keyed by payments.paid_at
        |    This correctly captures:
        |      • Regular sale payments (paid on sale day)
        |      • Laybuy installments (paid on installment day, NOT sale creation day)
        |----------------------------------------------------------------------
        */
        $payments = \App\Models\Payment::with('sale:id,sales_person_list')
            ->whereBetween('paid_at', [$startUtc, $endUtc])
            ->get();

        /*
        |----------------------------------------------------------------------
        | 5. System totals by payment method
        |----------------------------------------------------------------------
        */
        $systemTotals = array_fill_keys(array_keys($this->paymentMethods), 0.0);

        foreach ($payments as $payment) {
            $key = strtolower(trim($payment->method));
            if (array_key_exists($key, $systemTotals)) {
                $systemTotals[$key] += (float) $payment->amount;
            }
        }

        // Also pick up laybuy sales that have no Payment row
        // (old laybuy style: total stored on sale, no payments table entry)
        $laybuyTotal = Sale::whereBetween('created_at', [$startUtc, $endUtc])
            ->where('payment_method', 'laybuy')
            ->whereNotIn('id', function ($q) use ($startUtc, $endUtc) {
                // Exclude laybuy sales that already have payment rows today
                $q->select('sale_id')
                  ->from('payments')
                  ->whereBetween('paid_at', [$startUtc, $endUtc])
                  ->whereNotNull('sale_id');
            })
            ->sum('amount_paid');

        if ($laybuyTotal > 0) {
            $systemTotals['laybuy'] = ($systemTotals['laybuy'] ?? 0) + $laybuyTotal;
        }

        /*
        |----------------------------------------------------------------------
        | 6. Per-staff sales summary (keyed by payments.paid_at — NOT sales.created_at)
        |    This fixes the laybuy attribution: a laybuy payment made today by
        |    staff X shows under X TODAY, not on the original sale creation date.
        |----------------------------------------------------------------------
        */
        $staffTotals = [];

        foreach ($payments as $payment) {
            $sale      = $payment->sale;
            $staffList = $sale?->sales_person_list ?? [];

            // sales_person_list can be JSON array or comma-separated string
            if (is_string($staffList)) {
                $staffList = array_map('trim', explode(',', $staffList));
            }
            if (empty($staffList)) {
                $staffList = ['Unknown'];
            }

            foreach ($staffList as $staffName) {
                $staffName = trim($staffName);
                if (!$staffName) continue;

                if (!isset($staffTotals[$staffName])) {
                    $staffTotals[$staffName] = [
                        'name'    => $staffName,
                        'total'   => 0.0,
                        'methods' => [],
                    ];
                }

                $staffTotals[$staffName]['total'] += (float) $payment->amount;

                $methodKey = strtoupper(trim($payment->method));
                $staffTotals[$staffName]['methods'][$methodKey] =
                    ($staffTotals[$staffName]['methods'][$methodKey] ?? 0) + (float) $payment->amount;
            }
        }

        $this->salesSummary = array_values($staffTotals);

        /*
        |----------------------------------------------------------------------
        | 7. Populate expected/actual totals
        |----------------------------------------------------------------------
        */
        foreach ($this->paymentMethods as $key => $label) {
            $val = $systemTotals[$key] ?? 0.0;

            if ($val <= 0) continue;

            $this->expectedTotals[$key] = $val;
            $this->actualTotals[$key]   = auth()->user()->hasRole('Superadmin') ? $val : 0.0;
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('post_closing')
                ->label('POST CLOSING & LOCK DAY')
                ->color('success')
                ->requiresConfirmation()
                ->hidden(fn() => $this->isClosed)
                ->action(function () {
                    $this->loadData();

                    if ($this->isClosed) {
                        Notification::make()
                            ->title('Already Closed')
                            ->body('This day has already been locked.')
                            ->warning()
                            ->send();
                        return;
                    }

                    DailyClosing::create([
                        'closing_date'   => $this->date,
                        'expected_data'  => $this->expectedTotals,
                        'actual_data'    => $this->actualTotals,
                        'sales_summary'  => $this->salesSummary,
                        'total_expected' => array_sum($this->expectedTotals),
                        'total_actual'   => array_sum($this->actualTotals),
                        'user_id'        => auth()->id(),
                    ]);

                    Notification::make()->title('Day Closed Successfully')->success()->send();
                    $this->loadData();
                }),
        ];
    }
}