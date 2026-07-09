<?php

namespace App\Filament\Pages;

use App\Models\Sale;
use App\Models\DailyClosing;
use App\Models\EodAmendmentRequest;
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
    public ?float $monthlyTarget = null;
    public ?float $dailyTarget   = null;
    public string $debugTarget = '';
    public ?float $monthlyActual = null;
    public ?float $lastYearDaySales = null;
    public function mount(): void
    {
        $tz         = Store::first()?->timezone ?? config('app.timezone', 'UTC');
        $this->date = now()->setTimezone($tz)->format('Y-m-d');
        $this->loadTargetFromSettings();
        $this->loadData();
    }

    public function updatedDate(): void
    {
        $this->loadTargetFromSettings();
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

           Action::make('request_amendment')
    ->label('Request EOD Amendment')
    ->icon('heroicon-o-pencil-square')
    ->color('warning')
    ->visible(fn() => $this->isClosed)
    ->form([
        \Filament\Forms\Components\Placeholder::make('eod_info')
            ->hiddenLabel()
            ->content(new \Illuminate\Support\HtmlString(
                "<div style='padding:10px 14px;background:#fffbeb;border:1px solid #fde68a;border-radius:8px;font-size:13px;color:#78350f;'>
                    <strong>⚠️ EOD is locked for " . $this->date . "</strong><br>
                    Submit this request to a Superadmin who can approve and unlock the register so you can add the missing payment.
                </div>"
            )),

        \Filament\Forms\Components\TextInput::make('invoice_number')
            ->label('Invoice Number')
            ->placeholder('e.g. D5251')
            ->helperText('Enter the invoice number — amount will auto-fill')
            ->live(debounce: 600)
            ->afterStateUpdated(function ($state, \Filament\Forms\Set $set) {
                if (!$state) {
                    $set('amount', null);
                    $set('sale_info', null);
                    return;
                }
                $sale = \App\Models\Sale::where('invoice_number', trim($state))->first();
                if ($sale) {
                    $set('amount', floatval($sale->final_total));
                    $set('sale_info',
                        "✅ Found: " . ($sale->customer?->name ?? 'Walk-in') .
                        " — $" . number_format($sale->final_total, 2) .
                        " — " . ($sale->completed_at ? \Carbon\Carbon::parse($sale->completed_at)->format('M d, Y h:i A') : 'No date')
                    );
                } else {
                    $set('amount', null);
                    $set('sale_info', '⚠️ No sale found with that invoice number — you can still enter the amount manually.');
                }
            }),

        \Filament\Forms\Components\Placeholder::make('sale_info')
            ->hiddenLabel()
            ->content(fn(\Filament\Forms\Get $get) => $get('sale_info')
                ? new \Illuminate\Support\HtmlString(
                    str_starts_with($get('sale_info'), '✅')
                        ? "<div style='padding:8px 12px;background:#f0fdf4;border:1px solid #86efac;border-radius:8px;font-size:12px;font-weight:600;color:#065f46;'>" . e($get('sale_info')) . "</div>"
                        : "<div style='padding:8px 12px;background:#fef2f2;border:1px solid #fca5a5;border-radius:8px;font-size:12px;color:#991b1b;'>" . e($get('sale_info')) . "</div>"
                )
                : ''
            )
            ->visible(fn(\Filament\Forms\Get $get) => filled($get('sale_info'))),

        \Filament\Forms\Components\TextInput::make('amount')
            ->label('Amount ($)')
            ->numeric()
            ->prefix('$')
            ->placeholder('Auto-filled from invoice, or enter manually')
            ->helperText('Auto-filled when invoice is found — edit if needed'),

        \Filament\Forms\Components\Textarea::make('reason')
            ->label('Reason for Amendment')
            ->placeholder('e.g. Customer came in after EOD was posted. Sale L5251 worth $1,000 needs to be added.')
            ->required()
            ->rows(3),
    ])
    ->modalHeading('Request EOD Amendment')
    ->modalDescription('This will send a request to a Superadmin for approval. You will be notified once reviewed.')
    ->modalSubmitActionLabel('Submit Request')
    ->action(function (array $data) {
        $existing = EodAmendmentRequest::where('requested_by', auth()->id())
            ->where('eod_date', $this->date)
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            Notification::make()
                ->title('Request Already Pending')
                ->body('You already have a pending amendment request for ' . $this->date . '. Please wait for it to be reviewed.')
                ->warning()
                ->send();
            return;
        }

        EodAmendmentRequest::create([
            'requested_by'   => auth()->id(),
            'eod_date'       => $this->date,
            'invoice_number' => $data['invoice_number'] ?? null,
            'amount'         => $data['amount'] ?? null,
            'reason'         => $data['reason'],
            'status'         => 'pending',
        ]);

        $superadmins = \App\Models\User::role('Superadmin')->get();
        foreach ($superadmins as $admin) {
            Notification::make()
                ->title('EOD Amendment Request 📋')
                ->body(
                    auth()->user()->name . ' has requested to amend EOD for ' . $this->date .
                    ($data['invoice_number'] ? ' (Invoice: ' . $data['invoice_number'] . ')' : '') .
                    ($data['amount'] ? ' — $' . number_format($data['amount'], 2) : '') .
                    '. Go to Admin → EOD Amendment Requests to review.'
                )
                ->warning()
                ->sendToDatabase($admin);
        }

        Notification::make()
            ->title('Amendment Request Submitted ✅')
            ->body('Your request has been sent to a Superadmin for approval. You will be notified once reviewed.')
            ->success()
            ->send();
    }),
        ];
    }
    protected function loadTargetFromSettings(): void
    {
        $yearMonth = Carbon::parse($this->date)->format('Y-m');

        $override = DB::table('site_settings')
            ->where('key', 'monthly_target_override_' . $yearMonth)
            ->value('value');

        $default = DB::table('site_settings')
            ->where('key', 'monthly_sales_target')
            ->value('value');

        $active = ($override !== null && $override !== '' && (float) $override > 0)
            ? $override
            : $default;

        $this->monthlyTarget = ($active !== null && $active !== '' && (float) $active > 0)
            ? (float) $active
            : null;

        $this->dailyTarget = $this->monthlyTarget
            ? round($this->monthlyTarget / Carbon::parse($this->date)->daysInMonth, 2)
            : null;

        // Debug — remove after testing
        $this->debugTarget = "month={$yearMonth} | override={$override} | default={$default} | active={$active} | monthly={$this->monthlyTarget} | daily={$this->dailyTarget}";
        $tz = \App\Models\Store::first()?->timezone ?? config('app.timezone', 'UTC');
        $startOfMonth = \Carbon\Carbon::parse($this->date, $tz)->startOfMonth()->setTimezone('UTC');
        $endOfDay     = \Carbon\Carbon::parse($this->date, $tz)->endOfDay()->setTimezone('UTC');

        $monthlyFromPayments = \App\Models\Payment::whereBetween('paid_at', [$startOfMonth, $endOfDay])
            ->sum('amount');

        $monthlyFromSalePayments = \Illuminate\Support\Facades\DB::table('sale_payments')
            ->whereBetween('payment_date', [$startOfMonth, $endOfDay])
            ->sum('amount');

        $this->monthlyActual = $monthlyFromPayments + $monthlyFromSalePayments;

        // Last year same day sales
        $lastYearDate     = \Carbon\Carbon::parse($this->date)->subYear();
        $lastYearStartUtc = \Carbon\Carbon::parse($lastYearDate->format('Y-m-d'), $tz)->startOfDay()->setTimezone('UTC');
        $lastYearEndUtc   = \Carbon\Carbon::parse($lastYearDate->format('Y-m-d'), $tz)->endOfDay()->setTimezone('UTC');

        // Query both payments and sale_payments tables
        $lastYearFromPayments = \App\Models\Payment::whereBetween('paid_at', [$lastYearStartUtc, $lastYearEndUtc])
            ->sum('amount');

        $lastYearFromSalePayments = \Illuminate\Support\Facades\DB::table('sale_payments')
            ->whereBetween('payment_date', [$lastYearStartUtc, $lastYearEndUtc])
            ->sum('amount');

        $this->lastYearDaySales = $lastYearFromPayments + $lastYearFromSalePayments;
    }
}
