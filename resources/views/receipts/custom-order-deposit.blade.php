<!DOCTYPE html>
<html lang="en">
<head>
    @php
        use Illuminate\Support\Facades\DB;
        use Carbon\Carbon;

        $settings   = DB::table('site_settings')->pluck('value', 'key');
        $color      = '#BB8ED0';
        $accent     = '#d4b3e6';
        $bgLight    = '#f9f5fc';

        // ── STORE INFO ─────────────────────────────────────────────────
        $store     = DB::table('stores')->first();
        $storeName = $store?->name ?? 'Diamond Square';
        $storePhone= $store?->phone ?? ($settings['store_phone'] ?? '505-810-7222');
        $storeEmail= $store?->email ?? ($settings['store_email'] ?? 'info@example.com');
        $storeAddr = trim(($store?->street ?? '') . ', ' . ($store?->state ?? '') . ' ' . ($store?->postcode ?? ''));

        // ── ORDER NUMBER fallback ──────────────────────────────────────
        // order_no may be null on older records — fallback to ID
        $orderNo = $order->order_no ?? ('CO-' . str_pad($order->id, 5, '0', STR_PAD_LEFT));

        // ── PAYMENT DATA ───────────────────────────────────────────────
        // Merge payments table rows + amount_paid fallback
        $paymentRows = $order->payments->sortBy('paid_at');

        // If no payment records in payments table, synthesize one from amount_paid
        if ($paymentRows->isEmpty() && floatval($order->amount_paid) > 0) {
            $paymentRows = collect([[
                'amount'     => floatval($order->amount_paid),
                'method'     => 'CASH',
                'paid_at'    => $order->updated_at ?? $order->created_at,
                'synthesized'=> true,
            ]])->map(fn($p) => (object)$p);
        }

        $totalPaid = $paymentRows->sum('amount');

        // ── TOTALS ─────────────────────────────────────────────────────
        $isTaxFree  = (bool)($order->is_tax_free ?? false);
        $dbTax      = floatval($settings['tax_rate'] ?? 7.63);
        $taxRate    = $isTaxFree ? 0 : $dbTax / 100;
      $discountPct  = floatval($order->discount_percent ?? 0);
$discountAmt  = floatval($order->discount_amount ?? ($order->quoted_price * $discountPct / 100));
$afterDiscount = floatval($order->quoted_price) - $discountAmt;
$tax          = $afterDiscount * $taxRate;
$grandTotal   = $afterDiscount + $tax;
        $balance    = max(0, $grandTotal - $totalPaid);
        $isFullyPaid= $balance <= 0.01;

        // ── ASSOCIATE ──────────────────────────────────────────────────
        $associateName = $order->staff?->name ?? 'Staff';
    @endphp

    <meta charset="utf-8">
    <title>Deposit Receipt — Custom Order #{{ $orderNo }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-size:11px; }
        body { font-family: Helvetica, Arial, sans-serif; color:#2c3e50; background:#f0f9ff; padding:20px; line-height:1.4; }
        @media print {
            body { background:white; padding:0; }
            .no-print { display:none !important; }
            .card { box-shadow:none !important; }
            * { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
            @page { margin:.3in; size:auto; }
        }
    </style>
</head>
<body>

{{-- PRINT BUTTON --}}
@if(!isset($is_pdf))
<div class="no-print" style="max-width:850px;margin:0 auto 15px;text-align:right;">
    <button onclick="window.print()" style="background:{{ $color }};color:white;border:none;padding:10px 20px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:8px;">
        <i class="fas fa-print"></i> Print Receipt
    </button>
</div>
@endif

<div class="card" style="max-width:850px;margin:0 auto;padding:30px;background:#fff;border-radius:10px;box-shadow:0 5px 20px rgba(0,0,0,.1);border-top:6px solid {{ $color }};">

    {{-- TYPE BADGE --}}
    <div style="margin:0 0 15px;padding:8px;border-radius:4px;font-weight:700;text-transform:uppercase;text-align:center;letter-spacing:1px;background:{{ $bgLight }};color:{{ $color }};border:1px solid {{ $color }};font-size:11px;">
        <i class="fas fa-palette"></i> CUSTOM ORDER — DEPOSIT RECEIPT
    </div>

    {{-- STATUS BANNER --}}
    @if($isFullyPaid)
    <div style="margin-bottom:15px;padding:10px 15px;border-radius:6px;background:#ecfdf5;border:1px solid #10b981;border-left:4px solid #10b981;display:flex;align-items:center;gap:10px;">
        <i class="fas fa-check-circle" style="color:#10b981;font-size:16px;"></i>
        <div>
            <div style="font-weight:800;font-size:11px;color:#10b981;text-transform:uppercase;letter-spacing:.5px;">PAID IN FULL — Ready for Pickup</div>
            <div style="font-size:10px;color:#666;margin-top:2px;">Total Paid: <strong>${{ number_format($totalPaid, 2) }}</strong></div>
        </div>
    </div>
    @else
    <div style="margin-bottom:15px;padding:10px 15px;border-radius:6px;background:#fffbeb;border:1px solid #f59e0b;border-left:4px solid #f59e0b;display:flex;align-items:center;gap:10px;">
        <i class="fas fa-hourglass-half" style="color:#f59e0b;font-size:16px;"></i>
        <div>
            <div style="font-weight:800;font-size:11px;color:#f59e0b;text-transform:uppercase;letter-spacing:.5px;">
                @if($totalPaid > 0) DEPOSIT RECEIVED — BALANCE DUE @else PENDING PAYMENT @endif
            </div>
            <div style="font-size:10px;color:#666;margin-top:2px;">
                @if($totalPaid > 0)
                    Deposit Paid: <strong>${{ number_format($totalPaid, 2) }}</strong> &nbsp;|&nbsp;
                    Outstanding: <strong style="color:#f59e0b;">${{ number_format($balance, 2) }}</strong>
                @else
                    Amount Due: <strong style="color:#f59e0b;">${{ number_format($grandTotal, 2) }}</strong>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- HEADER --}}
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:25px;border-bottom:2px solid #e0e7ee;padding-bottom:20px;">
        <tr>
            <td width="60%" valign="top">
                <h1 style="font-size:24px;color:{{ $color }};margin-bottom:5px;">{{ $storeName }}</h1>
                <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:{{ $accent }};letter-spacing:1px;margin-bottom:10px;">
                    Premium Jewelry & Luxury Timepieces
                </div>
                <div style="font-size:10px;background:#f8fafc;padding:10px;border-radius:6px;border-left:3px solid {{ $accent }};color:#546e7a;line-height:1.6;">
                    @if($storeAddr !== ', ')
                    <div><i class="fas fa-map-marker-alt"></i> {{ $storeAddr }}</div>
                    @endif
                    <div><i class="fas fa-phone"></i> {{ $storePhone }}</div>
                    <div><i class="fas fa-envelope"></i> {{ $storeEmail }}</div>
                </div>
            </td>
            <td width="40%" valign="top" align="right">
                <div style="background:{{ $color }};color:white;padding:15px 20px;border-radius:8px;text-align:right;">
                    <span style="display:block;font-size:10px;text-transform:uppercase;letter-spacing:1px;font-weight:600;">DEPOSIT RECEIPT</span>
                    <span style="display:block;font-size:20px;font-weight:700;margin:5px 0;">{{ $orderNo }}</span>
                    <div style="font-size:10px;line-height:1.6;margin-top:8px;">
                        <div><i class="fas fa-calendar-day"></i> <b>Date:</b> {{ $order->created_at?->format('m/d/Y') ?? now()->format('m/d/Y') }}</div>
                        <div><i class="fas fa-clock"></i> <b>Time:</b> {{ $order->created_at?->format('h:i A') ?? now()->format('h:i A') }}</div>
                        <div><i class="fas fa-user-tie"></i> <b>Associate:</b> {{ $associateName }}</div>
                        <div><i class="fas fa-tag"></i> <b>Status:</b> {{ ucfirst(str_replace('_', ' ', $order->status ?? 'draft')) }}</div>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    {{-- CUSTOMER + PAYMENT SUMMARY --}}
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;">
        <tr>
            {{-- Customer Info --}}
            <td width="48%" valign="top" style="background:#f9fbfc;border-radius:6px;padding:15px;border:1px solid #e0e7ee;border-left:3px solid {{ $color }};">
                <div style="font-size:9px;color:{{ $color }};text-transform:uppercase;font-weight:700;margin-bottom:8px;letter-spacing:.5px;">
                    <i class="fas fa-user-circle"></i> Customer Information
                </div>
                <div style="font-size:15px;font-weight:700;color:{{ $color }};margin-bottom:6px;">
                    {{ $order->customer->name ?? 'Valued' }} {{ $order->customer->last_name ?? 'Customer' }}
                </div>
                <div style="color:#546e7a;line-height:1.6;font-size:10px;">
                    @if($order->customer?->phone)
                    <div><i class="fas fa-phone"></i> {{ $order->customer->phone }}</div>
                    @endif
                    @if($order->customer?->email)
                    <div><i class="fas fa-envelope"></i> {{ $order->customer->email }}</div>
                    @endif
                    @if($order->customer?->customer_no)
                    <div style="margin-top:4px;font-size:9px;color:#94a3b8;">Customer #: {{ $order->customer->customer_no }}</div>
                    @endif
                </div>
            </td>

            <td width="4%"></td>

            {{-- Payment Summary --}}
            <td width="48%" valign="top" style="background:#f9fbfc;border-radius:6px;padding:15px;border:1px solid #e0e7ee;border-left:3px solid {{ $color }};">
                <div style="font-size:9px;color:{{ $color }};text-transform:uppercase;font-weight:700;margin-bottom:8px;letter-spacing:.5px;">
                    <i class="fas fa-credit-card"></i> Payment Summary
                </div>
                <table width="100%" cellpadding="0" cellspacing="0" style="font-size:10px;line-height:1.7;">
                    {{-- Payment breakdown by method --}}
                    @foreach($paymentRows->groupBy('method') as $method => $methodPayments)
                    <tr>
                        <td style="color:#546e7a;">{{ strtoupper($method) }}:</td>
                        <td align="right"><strong style="color:{{ $color }};">${{ number_format(collect($methodPayments)->sum('amount'), 2) }}</strong></td>
                    </tr>
                    @endforeach
                    <tr><td colspan="2" style="border-top:1px dashed #e0e7ee;padding-top:4px;"></td></tr>
                    <tr>
                        <td style="color:#2c3e50;font-weight:600;">Total Paid:</td>
                        <td align="right"><strong style="color:#10b981;">${{ number_format($totalPaid, 2) }}</strong></td>
                    </tr>
                    @if(!$isFullyPaid)
                    <tr>
                        <td style="color:#2c3e50;font-weight:600;">Balance Due:</td>
                        <td align="right"><strong style="color:#ef4444;">${{ number_format($balance, 2) }}</strong></td>
                    </tr>
                    @endif
                    <tr><td colspan="2" style="border-top:1px dashed #e0e7ee;padding-top:4px;"></td></tr>
                    <tr>
                        <td style="color:#2c3e50;padding-top:4px;">Status:</td>
                        <td align="right" style="padding-top:4px;">
                            <span style="display:inline-block;padding:3px 8px;border-radius:20px;font-size:9px;font-weight:700;background:{{ $isFullyPaid ? '#ecfdf5' : '#fffbeb' }};color:{{ $isFullyPaid ? '#10b981' : '#f59e0b' }};border:1px solid {{ $isFullyPaid ? '#10b981' : '#f59e0b' }};">
                                {{ $isFullyPaid ? '✓ PAID IN FULL' : ($totalPaid > 0 ? 'DEPOSIT RECEIVED' : 'PENDING') }}
                            </span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- ORDER ITEMS --}}
    <div style="font-size:13px;font-weight:700;color:{{ $color }};margin-bottom:10px;">
        <i class="fas fa-palette"></i> Custom Order Items
    </div>
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;border:1px solid #e0e7ee;border-radius:6px;border-collapse:collapse;font-size:10px;">
        <thead>
            <tr>
                <th style="background:{{ $color }};color:white;padding:10px 8px;text-align:left;font-size:9px;text-transform:uppercase;border:1px solid #d4b3e6;width:15%;">Order #</th>
                <th style="background:{{ $color }};color:white;padding:10px 8px;text-align:left;font-size:9px;text-transform:uppercase;border:1px solid #d4b3e6;">Item Description</th>
                <th style="background:{{ $color }};color:white;padding:10px 8px;text-align:right;font-size:9px;text-transform:uppercase;border:1px solid #d4b3e6;width:18%;">Price</th>
            </tr>
        </thead>
        <tbody>
            @php $items = is_array($order->items) ? $order->items : []; @endphp
            @if(!empty($items))
                @foreach($items as $idx => $item)
                <tr style="background:{{ $idx % 2 === 0 ? '#fff' : '#fafafa' }};">
                    <td style="padding:10px 8px;border-bottom:1px solid #f0e8f8;vertical-align:top;">
                        <span style="background:#f0e8f8;color:{{ $color }};padding:3px 6px;border-radius:3px;font-family:monospace;font-size:9px;border:1px dashed #d4b3e6;font-weight:700;display:inline-block;">
                            {{ $orderNo }}
                        </span>
                        @if(count($items) > 1)
                        <div style="font-size:8px;color:#94a3b8;margin-top:2px;">Item {{ $idx + 1 }} of {{ count($items) }}</div>
                        @endif
                    </td>
                    <td style="padding:10px 8px;border-bottom:1px solid #f0e8f8;vertical-align:top;">
                        <div style="font-size:8px;text-transform:uppercase;padding:2px 4px;border-radius:2px;margin-bottom:4px;display:inline-block;font-weight:600;color:white;background:{{ $color }};">Custom Design</div>
                        <div style="font-weight:700;color:#2c3e50;font-size:12px;">{{ $item['product_name'] ?? 'Custom Item' }}</div>
                        <div style="color:#546e7a;font-size:10px;margin-top:3px;line-height:1.5;">
                            @if(!empty($item['metal_type']))
                            <span><i class="fas fa-ring" style="font-size:8px;"></i> Metal: {{ $item['metal_type'] }}</span>
                            @endif
                            @if(!empty($item['metal_weight']))
                            &nbsp;|&nbsp; <span>Weight: {{ $item['metal_weight'] }}g</span>
                            @endif
                            @if(!empty($item['diamond_weight']))
                            &nbsp;|&nbsp; <span>Diamond: {{ $item['diamond_weight'] }}ctw</span>
                            @endif
                            @if(!empty($item['size']))
                            &nbsp;|&nbsp; <span>Size: {{ $item['size'] }}</span>
                            @endif
                        </div>
                        @if(!empty($item['design_notes']))
                        <div style="color:#78350f;font-size:10px;font-style:italic;margin-top:4px;background:#fffbeb;padding:4px 6px;border-radius:3px;border-left:2px solid #f59e0b;">
                            {{ $item['design_notes'] }}
                        </div>
                        @endif
                    </td>
                    <td style="text-align:right;font-weight:700;color:{{ $color }};padding:10px 8px;border-bottom:1px solid #f0e8f8;vertical-align:top;font-size:12px;">
                        ${{ number_format(floatval($item['quoted_price'] ?? 0), 2) }}
                    </td>
                </tr>
                @endforeach
            @else
            {{-- Fallback to legacy single-item fields --}}
            <tr>
                <td style="padding:10px 8px;border-bottom:1px solid #f0e8f8;vertical-align:top;">
                    <span style="background:#f0e8f8;color:{{ $color }};padding:3px 6px;border-radius:3px;font-family:monospace;font-size:9px;border:1px dashed #d4b3e6;font-weight:700;display:inline-block;">
                        {{ $orderNo }}
                    </span>
                </td>
                <td style="padding:10px 8px;border-bottom:1px solid #f0e8f8;">
                    <div style="font-size:8px;text-transform:uppercase;padding:2px 4px;border-radius:2px;margin-bottom:4px;display:inline-block;font-weight:600;color:white;background:{{ $color }};">Custom Design</div>
                    <div style="font-weight:700;color:#2c3e50;font-size:12px;">{{ $order->product_name ?? 'Custom Item' }}</div>
                    @if($order->metal_type)
                    <div style="color:#546e7a;font-size:10px;margin-top:2px;">Metal: {{ $order->metal_type }}</div>
                    @endif
                    @if($order->design_notes)
                    <div style="color:#78350f;font-size:10px;font-style:italic;margin-top:4px;background:#fffbeb;padding:4px 6px;border-radius:3px;border-left:2px solid #f59e0b;">
                        {{ $order->design_notes }}
                    </div>
                    @endif
                </td>
                <td style="text-align:right;font-weight:700;color:{{ $color }};padding:10px 8px;border-bottom:1px solid #f0e8f8;font-size:12px;">
                    ${{ number_format(floatval($order->quoted_price ?? 0), 2) }}
                </td>
            </tr>
            @endif
        </tbody>
    </table>

    {{-- PAYMENT HISTORY LOG ─────────────────────────────────────────── --}}
    {{-- Shows every payment with date, method, amount, running balance --}}
    @if($paymentRows->count() > 0)
    <div style="font-size:13px;font-weight:700;color:{{ $color }};margin-bottom:10px;">
        <i class="fas fa-history"></i> Payment History
    </div>
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;border:1px solid #e0e7ee;border-radius:6px;border-collapse:collapse;font-size:10px;">
        <thead>
            <tr>
                <th style="background:{{ $color }};color:white;padding:8px;text-align:left;font-size:9px;text-transform:uppercase;border:1px solid #d4b3e6;">#</th>
                <th style="background:{{ $color }};color:white;padding:8px;text-align:left;font-size:9px;text-transform:uppercase;border:1px solid #d4b3e6;">Date & Time</th>
                <th style="background:{{ $color }};color:white;padding:8px;text-align:left;font-size:9px;text-transform:uppercase;border:1px solid #d4b3e6;">Method</th>
                <th style="background:{{ $color }};color:white;padding:8px;text-align:right;font-size:9px;text-transform:uppercase;border:1px solid #d4b3e6;">Amount Paid</th>
                <th style="background:{{ $color }};color:white;padding:8px;text-align:right;font-size:9px;text-transform:uppercase;border:1px solid #d4b3e6;">Running Balance</th>
            </tr>
        </thead>
        <tbody>
            @php $runningPaid = 0; @endphp
            @foreach($paymentRows->values() as $idx => $payment)
            @php
                $runningPaid   += floatval($payment->amount ?? $payment['amount'] ?? 0);
                $runningBalance = max(0, $grandTotal - $runningPaid);
                $paidAt         = $payment->paid_at ?? $payment['paid_at'] ?? null;
                $paidDate       = $paidAt ? \Carbon\Carbon::parse($paidAt)->format('M d, Y') : '—';
                $paidTime       = $paidAt ? \Carbon\Carbon::parse($paidAt)->format('h:i A')  : '—';
                $method         = strtoupper($payment->method ?? $payment['method'] ?? 'N/A');
                $amount         = floatval($payment->amount ?? $payment['amount'] ?? 0);
                $isSynthesized  = $payment->synthesized ?? $payment['synthesized'] ?? false;
            @endphp
            <tr style="background:{{ $idx % 2 === 0 ? '#fff' : '#fafafa' }};">
                <td style="padding:8px;border-bottom:1px solid #f0e8f8;color:#94a3b8;font-weight:700;">{{ $idx + 1 }}</td>
                <td style="padding:8px;border-bottom:1px solid #f0e8f8;">
                    <div style="font-weight:600;color:#2c3e50;">{{ $paidDate }}</div>
                    <div style="font-size:9px;color:#94a3b8;">{{ $paidTime }}</div>
                    @if($isSynthesized)
                    <div style="font-size:8px;color:#94a3b8;font-style:italic;">* Deposit recorded at time of order</div>
                    @endif
                </td>
                <td style="padding:8px;border-bottom:1px solid #f0e8f8;">
                    <span style="display:inline-block;padding:2px 8px;border-radius:20px;font-size:9px;font-weight:700;background:#f0e8f8;color:{{ $color }};border:1px solid {{ $accent }};">
                        {{ $method }}
                    </span>
                </td>
                <td style="padding:8px;border-bottom:1px solid #f0e8f8;text-align:right;font-weight:700;color:#10b981;font-size:11px;">
                    +${{ number_format($amount, 2) }}
                </td>
                <td style="padding:8px;border-bottom:1px solid #f0e8f8;text-align:right;font-weight:700;font-size:11px;color:{{ $runningBalance <= 0 ? '#10b981' : '#ef4444' }};">
                    ${{ number_format($runningBalance, 2) }}
                    @if($runningBalance <= 0)
                    <div style="font-size:8px;color:#10b981;">✓ Paid in Full</div>
                    @endif
                </td>
            </tr>
            @endforeach
            {{-- SUMMARY ROW --}}
            <tr style="background:#f9f5fc;">
                <td colspan="3" style="padding:8px;font-weight:700;font-size:10px;color:{{ $color }};text-transform:uppercase;letter-spacing:.5px;">
                    Total Payments Made
                </td>
                <td style="padding:8px;text-align:right;font-weight:700;color:#10b981;font-size:12px;">
                    ${{ number_format($totalPaid, 2) }}
                </td>
                <td style="padding:8px;text-align:right;font-weight:700;font-size:12px;color:{{ $isFullyPaid ? '#10b981' : '#ef4444' }};">
                    ${{ number_format($balance, 2) }}
                </td>
            </tr>
        </tbody>
    </table>
    @endif

    {{-- TOTALS + TERMS --}}
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td width="52%" valign="top" style="padding-right:20px;">

                {{-- Delivery info --}}
                @if($order->due_date || $order->expected_delivery_date)
                <div style="margin-bottom:15px;background:#fffbeb;border:1px solid #fef3c7;border-radius:6px;padding:12px;border-left:5px solid #f59e0b;">
                    <div style="color:#92400e;font-size:10px;font-weight:800;text-transform:uppercase;margin-bottom:6px;">
                        <i class="fas fa-calendar-check"></i> DELIVERY INFORMATION
                    </div>
                    @if($order->expected_delivery_date)
                    <div style="font-size:11px;color:#78350f;margin-bottom:3px;">
                        <strong>Expected Delivery:</strong> {{ \Carbon\Carbon::parse($order->expected_delivery_date)->format('M d, Y') }}
                    </div>
                    @endif
                    @if($order->due_date)
                    <div style="font-size:11px;color:#78350f;">
                        <strong>Vendor Due Date:</strong> {{ \Carbon\Carbon::parse($order->due_date)->format('M d, Y') }}
                    </div>
                    @endif
                </div>
                @endif

                {{-- Terms --}}
                <div style="background:#f8fafc;padding:12px;border-radius:6px;border:1px solid #e0e7ee;margin-bottom:15px;">
                    <div style="color:{{ $color }};font-size:9px;text-transform:uppercase;font-weight:700;margin-bottom:5px;letter-spacing:.5px;">
                        <i class="fas fa-file-contract"></i> TERMS & CONDITIONS
                    </div>
                    <p style="font-size:8px;color:#546e7a;line-height:1.5;">
                        Custom orders are final sale — no returns or exchanges. Deposit is non-refundable once production has begun. Balance due upon pickup.
                    </p>
                </div>

                {{-- Signature line --}}
                <div style="margin-top:30px;">
                    <div style="border-top:1px solid #2c3e50;width:200px;padding-top:5px;font-size:8px;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;">
                        Customer Signature
                    </div>
                    <div style="margin-top:15px;border-top:1px solid #2c3e50;width:200px;padding-top:5px;font-size:8px;color:{{ $color }};text-transform:uppercase;font-weight:600;letter-spacing:.5px;">
                        Master Custom Jewelry Designers
                    </div>
                </div>
            </td>

            <td width="48%" valign="top">
                {{-- TOTALS BOX --}}
                <div style="background:{{ $color }};border-radius:6px;padding:15px;color:white;">
                    <table width="100%" cellpadding="0" cellspacing="0" style="color:white;font-size:11px;">
                      <tr>
    <td style="padding:5px 0;border-bottom:1px dashed rgba(255,255,255,.2);">Quoted Price</td>
    <td align="right" style="padding:5px 0;border-bottom:1px dashed rgba(255,255,255,.2);">${{ number_format($order->quoted_price, 2) }}</td>
</tr>
@if($discountAmt > 0)
<tr>
    <td style="padding:5px 0;border-bottom:1px dashed rgba(255,255,255,.2);color:#fde68a;">
        Discount ({{ number_format($discountPct, 2) }}%)
    </td>
    <td align="right" style="padding:5px 0;border-bottom:1px dashed rgba(255,255,255,.2);color:#fde68a;">
        -${{ number_format($discountAmt, 2) }}
    </td>
</tr>
<tr>
    <td style="padding:5px 0;border-bottom:1px dashed rgba(255,255,255,.2);">After Discount</td>
    <td align="right" style="padding:5px 0;border-bottom:1px dashed rgba(255,255,255,.2);">${{ number_format($afterDiscount, 2) }}</td>
</tr>
@endif
<tr>
    <td style="padding:5px 0;border-bottom:1px dashed rgba(255,255,255,.2);">
        Tax ({{ $isTaxFree ? 'Exempt' : number_format($dbTax, 2) . '%' }})
    </td>
    <td align="right" style="padding:5px 0;border-bottom:1px dashed rgba(255,255,255,.2);">${{ number_format($tax, 2) }}</td>
</tr>
                        <tr>
                            <td style="padding:10px 0 5px;font-size:15px;font-weight:700;border-bottom:1px dashed rgba(255,255,255,.2);">TOTAL</td>
                            <td align="right" style="padding:10px 0 5px;font-size:15px;font-weight:700;border-bottom:1px dashed rgba(255,255,255,.2);">${{ number_format($grandTotal, 2) }}</td>
                        </tr>
                    </table>

                    {{-- Deposit summary --}}
                    <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:8px;background:rgba(0,0,0,.2);border-radius:4px;">
                        <tr>
                            <td style="padding:8px;font-weight:600;font-size:11px;">DEPOSIT PAID</td>
                            <td align="right" style="padding:8px;font-size:13px;font-weight:700;color:#4ade80;">${{ number_format($totalPaid, 2) }}</td>
                        </tr>
                        <tr>
                            <td style="padding:0 8px 8px;font-weight:600;font-size:11px;">BALANCE DUE</td>
                            <td align="right" style="padding:0 8px 8px;font-size:13px;font-weight:700;color:{{ $isFullyPaid ? '#4ade80' : '#fbbf24' }};">${{ number_format($balance, 2) }}</td>
                        </tr>
                    </table>
                </div>

                <div style="text-align:center;margin-top:15px;color:{{ $accent }};font-weight:700;letter-spacing:.5px;font-size:9px;">
                    THANK YOU FOR YOUR BUSINESS!
                </div>
            </td>
        </tr>
    </table>

</div>{{-- end .card --}}

@if(!isset($is_pdf))
<script>
    window.addEventListener('load', function() {
        if (new URLSearchParams(window.location.search).get('print') === '1') {
            window.print();
        }
    });
</script>
@endif
</body>
</html>