<!DOCTYPE html>
<html lang="en">
<head>
    @php
        use Illuminate\Support\Facades\DB;
        $settings = DB::table('site_settings')->pluck('value', 'key');

        // Colors — teal/emerald theme to distinguish from blue sale receipt
        $color      = '#0d7060';
        $colorDark  = '#065f50';
        $colorLight = '#ecfdf5';
        $colorAccent= '#10b981';

        $grandTotal  = floatval($sale->final_total);
        $customer    = $sale->customer;
        $custName    = $customer ? trim($customer->name . ' ' . ($customer->last_name ?? '')) : 'Walk-in';
        $custPhone   = $customer?->phone ?? 'N/A';
        $custEmail   = $customer?->email ?? 'N/A';

        if ($source === 'sale_payments') {
            $paymentDate   = \Carbon\Carbon::parse($payment->payment_date);
            $paymentMethod = strtoupper($payment->payment_method ?? '—');
            $paymentAmount = floatval($payment->amount);
        } else {
            $paymentDate   = \Carbon\Carbon::parse($payment->paid_at);
            $paymentMethod = strtoupper($payment->method ?? '—');
            $paymentAmount = floatval($payment->amount);
        }

        // Running total: sum all payments up to and including this one
        $running1 = $sale->payments()
            ->where(function($q) use ($source, $payment, $paymentDate) {
                if ($source === 'payments') {
                    $q->where('paid_at', '<', $payment->paid_at)
                      ->orWhere(fn($q2) => $q2->where('paid_at', $payment->paid_at)->where('id', '<=', $payment->id));
                } else {
                    $q->whereDate('paid_at', '<=', $paymentDate->format('Y-m-d'));
                }
            })->sum('amount');

        $running2 = $sale->salePayments()
            ->where(function($q) use ($source, $payment, $paymentDate) {
                if ($source === 'sale_payments') {
                    $q->where('payment_date', '<', $payment->payment_date)
                      ->orWhere(fn($q2) => $q2->where('payment_date', $payment->payment_date)->where('id', '<=', $payment->id));
                } else {
                    $q->whereDate('payment_date', '<=', $paymentDate->format('Y-m-d'));
                }
            })->sum('amount');

        $runningTotal = floatval($running1) + floatval($running2);
        $balanceAfter = max(0, $grandTotal - $runningTotal);
        $isFullyPaid  = $balanceAfter <= 0.01;

        // Payment number (which payment is this?)
        $allPayments1 = $sale->payments()->orderBy('paid_at')->get()->map(fn($p) => ['id' => $p->id, 'source' => 'payments', 'date' => $p->paid_at]);
        $allPayments2 = $sale->salePayments()->orderBy('payment_date')->get()->map(fn($p) => ['id' => $p->id, 'source' => 'sale_payments', 'date' => $p->payment_date]);
        $allSorted = $allPayments1->concat($allPayments2)->sortBy('date')->values();
        $paymentIndex = $allSorted->search(fn($p) => $p['id'] == $payment->id && $p['source'] == $source);
        $paymentNum   = $paymentIndex !== false ? $paymentIndex + 1 : '?';
        $totalPayments = $allSorted->count();
    @endphp

    <meta charset="utf-8">
    <title>Payment #{{ $paymentNum }} — {{ $sale->invoice_number }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;900&display=swap');
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Inter',sans-serif; background:#f0fdf4; padding:20px; color:#1e293b; }
        .slip { max-width:600px; margin:0 auto; background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 8px 30px rgba(0,0,0,.12); }

        /* Dashed tear line */
        .tearline {
            border-top: 2px dashed #cbd5e1;
            margin: 0;
            position: relative;
        }
        .tearline::before, .tearline::after {
            content:'';
            position:absolute;
            top:-8px;
            width:16px; height:16px;
            background:#f0fdf4;
            border-radius:50%;
        }
        .tearline::before { left:-8px; }
        .tearline::after  { right:-8px; }

        @media print {
            body { background:white; padding:0; }
            .slip { box-shadow:none; max-width:100%; }
            .no-print { display:none !important; }
            @page { margin:.3in; size:letter; }
        }
    </style>
</head>
<body>

<div class="no-print" style="max-width:600px;margin:0 auto 15px;display:flex;justify-content:space-between;align-items:center;">
    <span style="font-size:12px;color:#64748b;">Payment Receipt #{{ $paymentNum }} of {{ $totalPayments }}</span>
    <button onclick="window.print()" style="background:{{ $color }};color:white;border:none;padding:10px 20px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:8px;">
        <i class="fas fa-print"></i> Print
    </button>
</div>

<div class="slip">

    {{-- ── TOP HEADER BAND ── --}}
    <div style="background:{{ $color }};padding:24px 28px;color:white;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;">
            <div>
                <div style="font-size:10px;text-transform:uppercase;letter-spacing:1.5px;opacity:.7;margin-bottom:4px;">Payment Receipt</div>
                <div style="font-size:22px;font-weight:900;letter-spacing:-0.5px;">
                    {{ optional($sale->store)->name ?? 'Diamond Square' }}
                </div>
                <div style="font-size:10px;opacity:.7;margin-top:3px;">
                    {{ optional($sale->store)->street }}, {{ optional($sale->store)->state }}
                    &nbsp;|&nbsp; {{ optional($sale->store)->phone ?? '505-810-7222' }}
                </div>
            </div>
            <div style="text-align:right;">
                <div style="background:rgba(255,255,255,.15);border-radius:8px;padding:10px 16px;display:inline-block;">
                    <div style="font-size:9px;text-transform:uppercase;letter-spacing:1px;opacity:.8;">Invoice</div>
                    <div style="font-size:16px;font-weight:800;">{{ $sale->invoice_number }}</div>
                    <div style="font-size:9px;opacity:.7;margin-top:2px;">Payment {{ $paymentNum }} of {{ $totalPayments }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── BIG AMOUNT SPOTLIGHT ── --}}
    <div style="background:{{ $colorLight }};padding:28px;text-align:center;border-bottom:1px solid #d1fae5;">
        <div style="font-size:11px;text-transform:uppercase;letter-spacing:1.5px;color:{{ $color }};font-weight:700;margin-bottom:8px;">
            Amount Received
        </div>
        <div style="font-size:52px;font-weight:900;color:{{ $color }};line-height:1;">
            ${{ number_format($paymentAmount, 2) }}
        </div>
        <div style="margin-top:10px;display:inline-flex;align-items:center;gap:8px;background:white;border:1px solid #d1fae5;border-radius:99px;padding:6px 16px;">
            <span style="background:{{ $color }};color:white;border-radius:99px;padding:2px 10px;font-size:11px;font-weight:700;">
                {{ $paymentMethod }}
            </span>
            <span style="font-size:11px;color:#64748b;">{{ $paymentDate->format('F j, Y') }} at {{ $paymentDate->format('g:i A') }}</span>
        </div>
    </div>

    {{-- ── CUSTOMER + BALANCE GRID ── --}}
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:0;border-bottom:1px solid #e2e8f0;">
        <div style="padding:20px 24px;border-right:1px solid #e2e8f0;">
            <div style="font-size:9px;text-transform:uppercase;letter-spacing:1px;color:#94a3b8;font-weight:700;margin-bottom:8px;">Customer</div>
            <div style="font-weight:700;font-size:14px;color:#1e293b;margin-bottom:4px;">{{ $custName }}</div>
            <div style="font-size:11px;color:#64748b;">{{ $custPhone }}</div>
            <div style="font-size:11px;color:#64748b;">{{ $custEmail }}</div>
        </div>
        <div style="padding:20px 24px;">
            <div style="font-size:9px;text-transform:uppercase;letter-spacing:1px;color:#94a3b8;font-weight:700;margin-bottom:8px;">Balance After This Payment</div>
            @if($isFullyPaid)
                <div style="display:inline-flex;align-items:center;gap:6px;background:#ecfdf5;border:2px solid #10b981;border-radius:8px;padding:8px 14px;">
                    <i class="fas fa-check-circle" style="color:#10b981;font-size:16px;"></i>
                    <div>
                        <div style="font-size:18px;font-weight:900;color:#065f50;">$0.00</div>
                        <div style="font-size:9px;font-weight:700;color:#10b981;text-transform:uppercase;">Fully Paid ✓</div>
                    </div>
                </div>
            @else
                <div style="display:inline-flex;align-items:center;gap:6px;background:#fef2f2;border:2px solid #fca5a5;border-radius:8px;padding:8px 14px;">
                    <i class="fas fa-clock" style="color:#ef4444;font-size:16px;"></i>
                    <div>
                        <div style="font-size:18px;font-weight:900;color:#dc2626;">${{ number_format($balanceAfter, 2) }}</div>
                        <div style="font-size:9px;font-weight:700;color:#ef4444;text-transform:uppercase;">Remaining Balance</div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- ── PAYMENT BREAKDOWN ── --}}
    <div style="padding:20px 24px;border-bottom:1px solid #e2e8f0;">
        <div style="font-size:9px;text-transform:uppercase;letter-spacing:1px;color:#94a3b8;font-weight:700;margin-bottom:12px;">Payment Breakdown</div>
        <table width="100%" cellpadding="0" cellspacing="0" style="font-size:12px;">
            <tr>
                <td style="padding:5px 0;color:#64748b;">Invoice Total:</td>
                <td align="right" style="font-weight:600;">${{ number_format($grandTotal, 2) }}</td>
            </tr>
            <tr>
                <td style="padding:5px 0;color:#64748b;">This Payment ({{ $paymentMethod }}):</td>
                <td align="right" style="font-weight:700;color:{{ $color }};">+${{ number_format($paymentAmount, 2) }}</td>
            </tr>
            <tr>
                <td style="padding:5px 0;color:#64748b;">Total Paid to Date:</td>
                <td align="right" style="font-weight:600;color:{{ $color }};">${{ number_format($runningTotal, 2) }}</td>
            </tr>
            <tr style="border-top:2px solid #e2e8f0;">
                <td style="padding:10px 0 5px;font-weight:700;font-size:13px;">Balance Remaining:</td>
                <td align="right" style="padding:10px 0 5px;font-weight:900;font-size:15px;color:{{ $isFullyPaid ? '#10b981' : '#ef4444' }};">
                    {{ $isFullyPaid ? '✅ $0.00' : '$'.number_format($balanceAfter, 2) }}
                </td>
            </tr>
        </table>
    </div>

    {{-- TEAR LINE --}}
    <div class="tearline"></div>

    {{-- ── ITEMS MINI SUMMARY ── --}}
    <div style="padding:20px 24px;border-bottom:1px solid #e2e8f0;">
        <div style="font-size:9px;text-transform:uppercase;letter-spacing:1px;color:#94a3b8;font-weight:700;margin-bottom:10px;">Items on This Order</div>
        @foreach($sale->items as $item)
        @php
            $lineTotal = $item->sale_price_override > 0
                ? floatval($item->sale_price_override)
                : (floatval($item->sold_price) * intval($item->qty)) - floatval($item->discount_amount ?? 0);
        @endphp
        <div style="display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px dashed #f1f5f9;font-size:11px;">
            <div style="display:flex;align-items:center;gap:8px;">
                <span style="background:{{ $colorLight }};color:{{ $color }};padding:2px 7px;border-radius:4px;font-family:monospace;font-size:9px;font-weight:700;border:1px dashed {{ $colorAccent }};">
                    @if($item->productItem) {{ $item->productItem->barcode }}
                    @elseif($item->customOrder) CUSTOM
                    @else NON-TAG
                    @endif
                </span>
                <span style="color:#374151;">{{ \Illuminate\Support\Str::limit($item->custom_description, 45) }}</span>
            </div>
            <span style="font-weight:700;color:{{ $color }};white-space:nowrap;margin-left:10px;">${{ number_format($lineTotal, 2) }}</span>
        </div>
        @endforeach
    </div>

    {{-- ── FOOTER ── --}}
    <div style="padding:20px 24px;text-align:center;background:#f8fafc;">
        <div style="font-size:9px;color:#94a3b8;text-transform:uppercase;letter-spacing:1px;font-weight:700;margin-bottom:4px;">
            Thank you for your payment!
        </div>
        <div style="font-size:10px;color:#64748b;">
            {{ optional($sale->store)->name ?? 'Diamond Square' }}
            &nbsp;·&nbsp; {{ str_replace(['http://','https://'], '', optional($sale->store)->domain_url ?? 'jeweltag.us') }}
        </div>
        <div style="margin-top:12px;font-size:8px;color:#cbd5e1;">
            {!! $settings['receipt_terms'] ?? 'All sales final.' !!}
        </div>
    </div>

</div>

<script>
    window.addEventListener('load', function() {
        if (new URLSearchParams(window.location.search).get('print') === '1') window.print();
    });
</script>
</body>
</html>