<!DOCTYPE html>
<html lang="en">

<head>
    @php
    $totalSavings = 0;
    $hasRepair = false;
    $hasCustom = false;
   // Build special jobs array from new JSON field, fallback to legacy single fields
$specialJobs = $sale->special_jobs ?? [];
if (empty($specialJobs) && !empty($sale->job_type)) {
    $specialJobs = [[
        'job_type'         => $sale->job_type,
        'metal_type'       => $sale->metal_type,
        'date_required'    => $sale->date_required,
        'current_size'     => $sale->current_size,
        'target_size'      => $sale->target_size,
        'job_instructions' => $sale->job_instructions,
    ]];
}
$isSpecialJob = !empty($specialJobs);

    foreach($sale->items as $item) {
    if($item->discount_percent > 0) {
    $originalPrice = $item->sold_price > 0 ? $item->sold_price / (1 - ($item->discount_percent / 100)) : 0;
    $totalSavings += ($originalPrice - $item->sold_price) * $item->qty;
    }
    if($item->repair_id || $item->repair) $hasRepair = true;
    if($item->custom_order_id || $item->customOrder) $hasCustom = true;
    }

    if ($hasRepair) {
    $receiptType = 'repair';
    $receiptTitle = 'REPAIR RECEIPT';
    $receiptColor = '#249E94';
    $receiptDarkColor = '#1a7a72';
    $receiptAccent = '#4fc1b7';
    $receiptBgLight = '#f0f9f8';
    } elseif ($hasCustom) {
    $receiptType = 'custom';
    $receiptTitle = 'CUSTOM ORDER RECEIPT';
    $receiptColor = '#BB8ED0';
    $receiptDarkColor = '#9a6fb3';
    $receiptAccent = '#d4b3e6';
    $receiptBgLight = '#f9f5fc';
    } else {
    $receiptType = 'normal';
    $receiptTitle = 'SALES RECEIPT';
    $receiptColor = '#1a6b8c';
    $receiptDarkColor = '#12506b';
    $receiptAccent = '#d4af37';
    $receiptBgLight = '#eff6ff';
    }

    $settings = DB::table('site_settings')->pluck('value', 'key');
    $standardTerms = $settings['receipt_terms'] ?? 'All sales final. Returns accepted within 14 days for exchange/store credit only, item unworn with original receipt.';
    $repairTerms = $settings['repair_terms'] ?? 'All repair services guaranteed for 90 days. Returns not accepted for completed repairs.';

    // ── PAYMENT CALCULATIONS ──────────────────────────────────────────
    $isCustomDeposit = $sale->has_trade_in && str_contains($sale->trade_in_description ?? '', 'Prior Deposit');

    $displayTotal = floatval($sale->final_total);
    if ($isCustomDeposit) {
    $displayTotal += floatval($sale->trade_in_value);
    }

    $allPayments = collect();
    if ($sale->payments) {
    $allPayments = $allPayments->merge($sale->payments);
    }
    if ($isCustomDeposit) {
    foreach($sale->items as $item) {
    if ($item->custom_order_id && $item->customOrder && $item->customOrder->payments) {
    $allPayments = $allPayments->merge($item->customOrder->payments);
    }
    }
    }

    $totalPaid = $allPayments->sum('amount');
    $balance = max(0, $displayTotal - $totalPaid);
    $isFullyPaid = $balance <= 0.01;
        $isLaybuy=$sale->payment_method === 'laybuy';
        $isPending = in_array($sale->status, ['pending', 'inprogress']);
        $hasDeposit = $totalPaid > 0 && !$isFullyPaid;

        // Status badge config
        if ($isLaybuy) {
        $statusLabel = 'LAYBY ACTIVE';
        $statusColor = '#f59e0b';
        $statusBg = '#fffbeb';
        $statusIcon = 'fa-clock';
        } elseif ($isPending && $hasDeposit) {
        $statusLabel = 'DEPOSIT RECEIVED — BALANCE DUE';
        $statusColor = '#f59e0b';
        $statusBg = '#fffbeb';
        $statusIcon = 'fa-hourglass-half';
        } elseif ($isPending && !$hasDeposit) {
        $statusLabel = 'PENDING — PAYMENT REQUIRED';
        $statusColor = '#ef4444';
        $statusBg = '#fef2f2';
        $statusIcon = 'fa-exclamation-circle';
        } elseif ($isFullyPaid) {
        $statusLabel = 'PAID IN FULL';
        $statusColor = '#10b981';
        $statusBg = '#ecfdf5';
        $statusIcon = 'fa-check-circle';
        } else {
        $statusLabel = 'PARTIAL PAYMENT';
        $statusColor = '#f59e0b';
        $statusBg = '#fffbeb';
        $statusIcon = 'fa-exclamation-triangle';
        }
        @endphp

        <meta charset="utf-8">
        <title>{{ $receiptTitle }}: {{ $sale->invoice_number }}</title>

        <style>
            @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap');

            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
                font-size: 11px;
            }

            body {
                font-family: 'Inter', Helvetica, Arial, sans-serif;
                color: #2c3e50;
                line-height: 1.4;
                background: #f0f9ff;
                padding: 20px;
            }

            .print-button-container {
                max-width: 850px;
                margin: 0 auto 15px;
                display: flex;
                justify-content: flex-end;
            }

            .print-button {
                background: {
                        {
                        $receiptColor
                    }
                }

                ;
                color:white;
                border:none;
                padding:10px 20px;
                border-radius:8px;
                font-size:13px;
                font-weight:600;
                cursor:pointer;
                display:flex;
                align-items:center;
                gap:8px;
                box-shadow:0 2px 8px rgba(0, 0, 0, .1);
            }

            @media print {
                body {
                    background: white;
                    padding: 0;
                }

                .invoice-container {
                    box-shadow: none !important;
                    padding: 20px !important;
                    max-width: 100% !important;
                    border-top: none !important;
                }

                .no-print {
                    display: none !important;
                }

                .invoice-header-card,
                .totals-card,
                .items-table thead th {
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }

                @page {
                    margin: .3in;
                    size: auto;
                }

                a[href]:after {
                    content: none !important;
                }
            }
        </style>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    @if(!isset($is_pdf) && !isset($is_email))
    <div class="print-button-container no-print">
        <button class="print-button" onclick="window.print()">
            <i class="fas fa-print"></i> Print Receipt
        </button>
    </div>
    @endif

    <div class="invoice-container" id="receipt-content" style="max-width:850px;margin:0 auto;padding:30px;background:#fff;border-radius:10px;box-shadow:0 5px 20px rgba(0,0,0,.1);border-top:6px solid {{ $receiptColor }};">

        @if($receiptType !== 'normal')
        <div style="margin:10px 0 15px;padding:8px;border-radius:4px;font-weight:700;text-transform:uppercase;text-align:center;letter-spacing:1px;background:{{ $receiptBgLight }};color:{{ $receiptColor }};border:1px solid {{ $receiptColor }};font-size:11px;">
            <i class="fas {{ $receiptType == 'repair' ? 'fa-wrench' : 'fa-palette' }}"></i> {{ $receiptTitle }}
        </div>
        @endif

        {{-- ── STATUS BANNER (shows for non-completed sales) ── --}}
        @if(!$isFullyPaid || $isPending)
        <div style="margin-bottom:15px;padding:10px 15px;border-radius:6px;background:{{ $statusBg }};border:1px solid {{ $statusColor }};border-left:4px solid {{ $statusColor }};display:flex;align-items:center;gap:10px;">
            <i class="fas {{ $statusIcon }}" style="color:{{ $statusColor }};font-size:16px;"></i>
            <div>
                <div style="font-weight:800;font-size:11px;color:{{ $statusColor }};text-transform:uppercase;letter-spacing:0.5px;">{{ $statusLabel }}</div>
                @if($hasDeposit && !$isLaybuy)
                <div style="font-size:10px;color:#666;margin-top:2px;">
                    Deposit Received: <strong>${{ number_format($totalPaid, 2) }}</strong> &nbsp;|&nbsp;
                    Outstanding Balance: <strong style="color:{{ $statusColor }};">${{ number_format($balance, 2) }}</strong>
                </div>
                @elseif($isPending && !$hasDeposit)
                <div style="font-size:10px;color:#666;margin-top:2px;">
                    Amount Due: <strong style="color:{{ $statusColor }};">${{ number_format($displayTotal, 2) }}</strong>
                </div>
                @endif
            </div>
        </div>
        @endif

        {{-- ── HEADER ── --}}
        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:25px;border-bottom:2px solid #e0e7ee;padding-bottom:20px;">
            <tr>
                <td width="60%" valign="top">
                    <h1 style="font-family:'Playfair Display',serif;font-size:24px;color:{{ $receiptColor }};margin-bottom:5px;">
                        {{ optional($sale->store)->name ?? 'Diamond Square' }}
                    </h1>
                    <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:{{ $receiptAccent }};letter-spacing:1px;margin-bottom:10px;">
                        Premium Jewelry & Luxury Timepieces
                    </div>
                    <div style="font-size:10px;background:#f8fafc;padding:10px;border-radius:6px;border-left:3px solid {{ $receiptAccent }};color:#546e7a;line-height:1.5;">
                        <div style="margin-bottom:3px;"><i class="fas fa-map-marker-alt"></i> {{ optional($sale->store)->street }}, {{ optional($sale->store)->state }} {{ optional($sale->store)->postcode }}</div>
                        <div style="margin-bottom:3px;"><i class="fas fa-phone"></i> {{ optional($sale->store)->phone ?? '505-810-7222' }} &nbsp;|&nbsp; <i class="fas fa-envelope"></i> {{ optional($sale->store)->email ?? 'info@example.com' }}</div>
                        <div><i class="fas fa-globe"></i> {{ str_replace(['http://','https://'], '', optional($sale->store)->domain_url ?? 'jeweltag.us') }}</div>
                    </div>
                </td>
                <td width="40%" valign="top" align="right">
                    <div class="invoice-header-card" style="background:{{ $receiptColor }};color:white;padding:15px 20px;border-radius:8px;text-align:right;">
                        <span style="display:block;font-size:10px;text-transform:uppercase;letter-spacing:1px;font-weight:600;">{{ $receiptTitle }}</span>
                        <span style="display:block;font-size:20px;font-weight:700;margin:5px 0;">
                            {{ $receiptType == 'normal' ? $sale->invoice_number : strtoupper($receiptType).'-'.$sale->invoice_number }}
                        </span>
                        <div style="font-size:10px;line-height:1.4;margin-top:8px;">
                            <div><i class="fas fa-calendar-day"></i> <b>Date:</b> {{ $sale->created_at->format('m/d/Y') }}</div>
                            <div><i class="fas fa-clock"></i> <b>Time:</b> {{ $sale->created_at->format('h:i A') }}</div>
                            <div><i class="fas fa-user-tie"></i> <b>Associate:</b> {{ is_array($sale->sales_person_list) ? implode(', ', $sale->sales_person_list) : $sale->sales_person_list }}</div>
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        {{-- ── CUSTOMER + PAYMENT SUMMARY ── --}}
        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;">
            <tr>
                <td width="48%" valign="top" style="background:#f9fbfc;border-radius:6px;padding:15px;border:1px solid #e0e7ee;border-left:3px solid {{ $receiptColor }};">
                    <div style="font-size:9px;color:{{ $receiptColor }};text-transform:uppercase;font-weight:700;margin-bottom:8px;letter-spacing:.5px;">
                        <i class="fas fa-user-circle"></i> Customer Information
                    </div>
                    <div style="font-size:15px;font-weight:700;color:{{ $receiptColor }};margin-bottom:5px;">
                        {{ $sale->customer->name ?? 'Valued' }} {{ $sale->customer->last_name ?? 'Customer' }}
                    </div>
                    <div style="color:#546e7a;line-height:1.5;font-size:10px;">
                        <div><i class="fas fa-phone"></i> {{ $sale->customer->phone ?? 'N/A' }}</div>
                        <div><i class="fas fa-envelope"></i> {{ $sale->customer->email ?? 'N/A' }}</div>
                    </div>
                </td>
                <td width="4%"></td>
                <td width="48%" valign="top" style="background:#f9fbfc;border-radius:6px;padding:15px;border:1px solid #e0e7ee;border-left:3px solid {{ $receiptColor }};">
                    <div style="font-size:9px;color:{{ $receiptColor }};text-transform:uppercase;font-weight:700;margin-bottom:8px;letter-spacing:.5px;">
                        <i class="fas fa-credit-card"></i> Payment Summary
                    </div>

                    <table width="100%" cellpadding="0" cellspacing="0" style="line-height:1.6;font-size:10px;">
                        {{-- Payment method(s) --}}
                        <tr>
                            <td valign="top" style="color:#2c3e50;font-weight:600;">Payment Method:</td>
                            <td valign="top" align="right">
                                @if($displayTotal <= 0 && ($hasRepair || $isSpecialJob) && !$isCustomDeposit)
                                    <strong style="color:#10b981;">COVERED BY WARRANTY</strong>
                                    @elseif($allPayments->where('amount', '>', 0)->count() > 0)
                                    @foreach($allPayments->where('amount', '>', 0)->groupBy('method') as $method => $payments)
                                    <div style="margin-bottom:2px;">
                                        <span style="font-size:9px;color:#666;">{{ strtoupper(str_replace('_',' ',$method)) }}:</span>
                                        <strong style="color:{{ $receiptColor }};">${{ number_format($payments->sum('amount'), 2) }}</strong>
                                    </div>
                                    @endforeach
                                    @elseif($sale->amount_paid > 0)
                                    <div style="margin-bottom:2px;">
                                        <span style="font-size:9px;color:#666;">{{ strtoupper(str_replace('_',' ',$sale->payment_method ?? 'SINGLE PAYMENT')) }}:</span>
                                        <strong style="color:{{ $receiptColor }};">${{ number_format($sale->amount_paid, 2) }}</strong>
                                    </div>
                                    @else
                                    <strong style="color:#ef4444;">UNPAID</strong>
                                    @endif
                            </td>
                        </tr>

                        {{-- Deposit row (only if partial) --}}
                        @if($hasDeposit && !$isLaybuy)
                        <tr>
                            <td colspan="2" style="border-top:1px dashed #eee;height:5px;"></td>
                        </tr>
                        <tr>
                            <td valign="top" style="color:#2c3e50;">Deposit Paid:</td>
                            <td valign="top" align="right">
                                <strong style="color:#10b981;">${{ number_format($totalPaid, 2) }}</strong>
                            </td>
                        </tr>
                        <tr>
                            <td valign="top" style="color:#2c3e50;">Balance Due:</td>
                            <td valign="top" align="right">
                                <strong style="color:#ef4444;">${{ number_format($balance, 2) }}</strong>
                            </td>
                        </tr>
                        @endif

                        <tr>
                            <td colspan="2" style="border-top:1px dashed #eee;height:5px;"></td>
                        </tr>

                        {{-- Status badge --}}
                        <tr>
                            <td valign="top" style="padding-top:5px;color:#2c3e50;">Status:</td>
                            <td valign="top" align="right" style="padding-top:5px;">
                                <span style="display:inline-block;padding:3px 8px;border-radius:20px;font-size:9px;font-weight:700;background:{{ $statusBg }};color:{{ $statusColor }};border:1px solid {{ $statusColor }};">
                                    <i class="fas {{ $statusIcon }}"></i> {{ $statusLabel }}
                                </span>
                            </td>
                        </tr>

                        {{-- Layby specific --}}
                        @if($isLaybuy && $sale->laybuy)
                        <tr>
                            <td colspan="2" style="border-top:1px dashed #eee;height:5px;"></td>
                        </tr>
                        <tr>
                            <td style="color:#2c3e50;">Amount Paid:</td>
                            <td align="right"><strong style="color:#10b981;">${{ number_format($sale->laybuy->amount_paid, 2) }}</strong></td>
                        </tr>
                        <tr>
                            <td style="color:#2c3e50;">Layby Balance:</td>
                            <td align="right"><strong style="color:#f59e0b;">${{ number_format($sale->laybuy->balance_due, 2) }}</strong></td>
                        </tr>
                        <tr>
                            <td style="color:#2c3e50;">Due Date:</td>
                            <td align="right"><strong>{{ optional($sale->laybuy->due_date)->format('M d, Y') }}</strong></td>
                        </tr>
                        @endif
                    </table>
                </td>
            </tr>
        </table>

        {{-- ── ITEMS TABLE ── --}}
        <div style="font-size:14px;font-weight:700;color:{{ $receiptColor }};margin-bottom:10px;">
            <i class="fas {{ $receiptType == 'repair' ? 'fa-wrench' : ($receiptType == 'custom' ? 'fa-palette' : 'fa-gem') }}"></i>
            {{ $receiptType == 'repair' ? 'Repair Services' : ($receiptType == 'custom' ? 'Custom Order Items' : 'Purchased Items') }}
        </div>

        <table class="items-table" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;border:1px solid #e0e7ee;border-radius:6px;border-collapse:collapse;font-size:10px;">
            <thead>
                <tr>
                    <th style="background:{{ $receiptColor }};color:white;padding:10px 8px;text-align:left;font-size:9px;text-transform:uppercase;border:1px solid #e0e7ee;">Reference #</th>
                    <th style="background:{{ $receiptColor }};color:white;padding:10px 8px;text-align:left;font-size:9px;text-transform:uppercase;border:1px solid #e0e7ee;">Item Description</th>
                    <th style="background:{{ $receiptColor }};color:white;padding:10px 8px;text-align:center;width:10%;font-size:9px;text-transform:uppercase;border:1px solid #e0e7ee;">Qty</th>
                    <th style="background:{{ $receiptColor }};color:white;padding:10px 8px;text-align:center;width:10%;font-size:9px;text-transform:uppercase;border:1px solid #e0e7ee;">Disc %</th>
                    <th style="background:{{ $receiptColor }};color:white;padding:10px 8px;text-align:right;width:20%;font-size:9px;text-transform:uppercase;border:1px solid #e0e7ee;">Price</th>
                </tr>
            </thead>
           <tbody>
@php $processedRepairIds = []; @endphp
@foreach($sale->items as $item)
    @if($item->repair_id && $item->repair && !empty($item->repair->items))
        @if(!in_array($item->repair_id, $processedRepairIds))
            @php $processedRepairIds[] = $item->repair_id; @endphp
            {{-- Expand each sub-item from the repair's JSON items array --}}
            @foreach($item->repair->items as $index => $repairSubItem)
            <tr>
                <td style="padding:10px 8px;border-bottom:1px solid #eee;background:#fff;">
                    <span style="background:#f0f7fa;color:{{ $receiptColor }};padding:3px 6px;border-radius:3px;font-family:monospace;font-size:9px;border:1px dashed #c2e0ee;font-weight:700;">
                        REPAIR #{{ $item->repair->repair_no }}-{{ $index + 1 }}
                    </span>
                </td>
                <td style="padding:10px 8px;border-bottom:1px solid #eee;background:#fff;">
                    <div style="font-size:8px;text-transform:uppercase;padding:2px 4px;border-radius:2px;margin-bottom:3px;display:inline-block;font-weight:600;color:white;background:{{ $receiptColor }};">Service/Repair</div>
                    <div style="font-weight:700;color:#2c3e50;font-size:12px;">{{ $repairSubItem['item_description'] ?? $item->custom_description }}</div>
                    @if(!empty($repairSubItem['reported_issue']))
                    <div style="color:#546e7a;font-size:10px;margin-top:2px;">{{ $repairSubItem['reported_issue'] }}</div>
                    @endif
                </td>
                <td style="text-align:center;font-weight:700;padding:10px 8px;border-bottom:1px solid #eee;background:#fff;">1</td>
                <td style="text-align:center;padding:10px 8px;border-bottom:1px solid #eee;background:#fff;">
                    <span style="color:#546e7a;">-</span>
                </td>
                <td style="text-align:right;font-weight:700;color:{{ $receiptColor }};padding:10px 8px;border-bottom:1px solid #eee;background:#fff;">
                    ${{ number_format(floatval($repairSubItem['final_cost'] ?? $repairSubItem['estimated_cost'] ?? 0), 2) }}
                </td>
            </tr>
            @endforeach
        @endif
    @else
        {{-- Normal sale item (non-repair) --}}
        <tr>
           <td style="padding:10px 8px;border-bottom:1px solid #eee;background:#fff;">
    <span style="background:#f0f7fa;color:{{ $receiptColor }};padding:3px 6px;border-radius:3px;font-family:monospace;font-size:9px;border:1px dashed #c2e0ee;font-weight:700;">
        @if($item->productItem)
            {{ $item->productItem->barcode }}
        @elseif($item->customOrder)
            CUSTOM #{{ $item->customOrder->order_no }}
        @elseif($item->stock_no_display && $item->stock_no_display !== 'NON-TAG')
            {{ $item->stock_no_display }}
        @else
            <span style="background:#f1f5f9;color:#94a3b8;padding:2px 5px;border-radius:3px;font-size:8px;font-weight:600;border:1px solid #e2e8f0;">NON-TAG</span>
        @endif
    </span>
</td>
           <td style="padding:10px 8px;border-bottom:1px solid #eee;background:#fff;">
    @if($item->custom_order_id || $item->customOrder)
        <div style="font-size:8px;text-transform:uppercase;padding:2px 4px;border-radius:2px;margin-bottom:3px;display:inline-block;font-weight:600;color:white;background:{{ $receiptColor }};">Custom Design</div>
    @elseif(!$item->product_item_id && !$item->repair_id && !$item->custom_order_id)
        <div style="font-size:8px;text-transform:uppercase;padding:2px 4px;border-radius:2px;margin-bottom:3px;display:inline-block;font-weight:600;color:#64748b;background:#f1f5f9;border:1px solid #e2e8f0;">Service / Non-Tag Item</div>
    @endif
    <div style="font-weight:700;color:#2c3e50;font-size:12px;">{{ $item->custom_description }}</div>
</td>
            <td style="text-align:center;font-weight:700;padding:10px 8px;border-bottom:1px solid #eee;background:#fff;">{{ $item->qty }}</td>
            <td style="text-align:center;padding:10px 8px;border-bottom:1px solid #eee;background:#fff;">
                @if($item->discount_percent > 0)
                <span style="color:#10b981;font-weight:700;">{{ number_format($item->discount_percent, 0) }}%</span>
                @else
                <span style="color:#546e7a;">-</span>
                @endif
            </td>
            <td style="text-align:right;font-weight:700;color:{{ $receiptColor }};padding:10px 8px;border-bottom:1px solid #eee;background:#fff;">
                ${{ number_format($item->sold_price * $item->qty, 2) }}
            </td>
        </tr>
    @endif
@endforeach
</tbody>
        </table>

        {{-- ── BOTTOM: NOTES + TOTALS ── --}}
        <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td width="55%" valign="top" style="padding-right:20px;">

                    @if($sale->notes)
                    <div style="margin-bottom:15px;background:#fffbeb;border:1px solid #fef3c7;border-radius:6px;padding:12px;border-left:5px solid #f59e0b;">
                        <div style="color:#92400e;font-size:10px;font-weight:800;text-transform:uppercase;margin-bottom:6px;">
                            <i class="fas fa-info-circle"></i> SPECIAL INSTRUCTIONS / JOB NOTES
                        </div>
                        <div style="font-size:11px;color:#78350f;line-height:1.6;white-space:pre-wrap;">{{ $sale->notes }}</div>
                    </div>
                    @endif

                   @if($isSpecialJob)
<div style="margin-bottom:15px;background:#fffbeb;border:1px solid #fef3c7;border-radius:6px;padding:12px;border-left:5px solid #f59e0b;">
    <div style="color:#92400e;font-size:10px;font-weight:800;text-transform:uppercase;margin-bottom:6px;">
        <i class="fas fa-wrench"></i> WORKSHOP SERVICE DETAILS
    </div>
    @foreach($specialJobs as $jobIndex => $job)
    <div style="{{ $jobIndex > 0 ? 'margin-top:10px;padding-top:10px;border-top:1px dashed #fef3c7;' : '' }}">
        <table width="100%" cellpadding="0" cellspacing="0" style="font-size:11px;color:#78350f;margin-bottom:4px;">
            <tr>
                <td width="50%">
                    <strong>{{ count($specialJobs) > 1 ? 'Job ' . ($jobIndex + 1) . ':' : 'Service:' }}</strong>
                    {{ $job['job_type'] ?? 'General Service' }}
                </td>
                <td width="50%">
                    <strong>Metal:</strong>
                    {{ $job['metal_type'] ?? 'N/A' }}
                </td>
            </tr>
            @if(($job['job_type'] ?? '') === 'Resize')
            <tr>
                <td><strong>From:</strong> {{ $job['current_size'] ?? '—' }}</td>
                <td><strong>To:</strong> {{ $job['target_size'] ?? '—' }}</td>
            </tr>
            @endif
            @if(!empty($job['date_required']))
            <tr>
                <td colspan="2">
                    <strong>Completion:</strong>
                    {{ \Carbon\Carbon::parse($job['date_required'])->format('M d, Y') }}
                </td>
            </tr>
            @endif
        </table>
        @if(!empty($job['job_instructions']))
        <div style="font-size:11px;color:#78350f;line-height:1.6;border-top:1px dashed #fef3c7;padding-top:5px;">
            <strong>Instructions:</strong> {{ $job['job_instructions'] }}
        </div>
        @endif
    </div>
    @endforeach
</div>
@endif

                    <div style="background:#f8fafc;padding:12px;border-radius:6px;border:1px solid #e0e7ee;margin-bottom:15px;">
                        <div style="color:{{ $receiptColor }};font-size:9px;text-transform:uppercase;font-weight:700;margin-bottom:5px;letter-spacing:.5px;">
                            <i class="fas fa-file-contract"></i> TERMS & CONDITIONS
                        </div>
                        <p style="font-size:8px;color:#546e7a;line-height:1.4;margin:0;">
                            @if($receiptType == 'repair') {!! $repairTerms !!}
                            @elseif($receiptType == 'custom') Custom orders are final sale — no returns/exchanges.
                            @else {!! $standardTerms !!}
                            @endif
                        </p>
                    </div>

                    <div style="margin-top:15px;border-top:1px solid #2c3e50;width:200px;padding-top:5px;font-weight:600;font-size:8px;text-transform:uppercase;color:{{ $receiptColor }};">
                        @if($receiptType == 'repair') Your Trusted Jewelry Repair Experts
                        @elseif($receiptType == 'custom') Master Custom Jewelry Designers
                        @else Your Trusted Jewelry Store In Town
                        @endif
                    </div>
                </td>

                <td width="45%" valign="top">
                    {{-- TOTALS CARD --}}
                    <div style="background:{{ $receiptColor }};border-radius:6px;padding:15px;color:white;">
                        <table width="100%" cellpadding="0" cellspacing="0" style="color:white;font-size:11px;">
                            <tr>
                                <td style="padding:5px 0;border-bottom:1px dashed rgba(255,255,255,.2);">Subtotal</td>
                                <td align="right" style="padding:5px 0;border-bottom:1px dashed rgba(255,255,255,.2);">${{ number_format($sale->subtotal, 2) }}</td>
                            </tr>
                            @if($totalSavings > 0)
                            <tr>
                                <td style="padding:5px 0;border-bottom:1px dashed rgba(255,255,255,.2);color:#4ade80;font-weight:600;">Total Savings</td>
                                <td align="right" style="padding:5px 0;border-bottom:1px dashed rgba(255,255,255,.2);color:#4ade80;font-weight:600;">-${{ number_format($totalSavings, 2) }}</td>
                            </tr>
                            @endif
                            <tr>
                                <td style="padding:5px 0;border-bottom:1px dashed rgba(255,255,255,.2);">Sales Tax</td>
                                <td align="right" style="padding:5px 0;border-bottom:1px dashed rgba(255,255,255,.2);">${{ number_format($sale->tax_amount, 2) }}</td>
                            </tr>
                            @if($sale->has_trade_in && !$isCustomDeposit)
                            <tr>
                                <td style="padding:5px 0;border-bottom:1px dashed rgba(255,255,255,.2);color:#ffeb3b;">Trade-In Credit</td>
                                <td align="right" style="padding:5px 0;border-bottom:1px dashed rgba(255,255,255,.2);color:#ffeb3b;">-${{ number_format($sale->trade_in_value, 2) }}</td>
                            </tr>
                            @endif
                            @if($sale->shipping_charges > 0)
                            <tr>
                                <td style="padding:5px 0;border-bottom:1px dashed rgba(255,255,255,.2);">Shipping</td>
                                <td align="right" style="padding:5px 0;border-bottom:1px dashed rgba(255,255,255,.2);">${{ number_format($sale->shipping_charges, 2) }}</td>
                            </tr>
                            @endif
                            @if($sale->has_warranty && ($sale->warranty_charge ?? 0) > 0)
                            <tr>
                                <td style="padding:5px 0;border-bottom:1px dashed rgba(255,255,255,.2);">
                                    <i class="fas fa-shield-alt"></i> Warranty ({{ $sale->warranty_period }})
                                </td>
                                <td align="right" style="padding:5px 0;border-bottom:1px dashed rgba(255,255,255,.2);">${{ number_format($sale->warranty_charge, 2) }}</td>
                            </tr>
                            @endif
                            <tr>
                                <td style="padding-top:10px;font-size:16px;font-weight:700;">TOTAL</td>
                                <td align="right" style="padding-top:10px;font-size:16px;font-weight:700;">${{ number_format($displayTotal, 2) }}</td>
                            </tr>
                        </table>

                        {{-- Deposit / Balance rows --}}
                        @if($isLaybuy && $sale->laybuy)
                        <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:8px;background:rgba(0,0,0,.2);border-radius:4px;">
                            <tr>
                                <td style="padding:8px;font-weight:600;font-size:11px;">LAYBY BALANCE</td>
                                <td align="right" style="padding:8px;font-size:14px;font-weight:700;color:{{ $receiptAccent }};">${{ number_format($sale->laybuy->balance_due ?? $sale->final_total, 2) }}</td>
                            </tr>
                            <tr>
                                <td style="padding:0 8px 8px;font-size:9px;color:rgba(255,255,255,.7);">Due by: {{ optional($sale->laybuy->due_date)->format('M d, Y') }}</td>
                                <td></td>
                            </tr>
                        </table>
                        @endif
                    </div>

                    @if($sale->has_warranty)
                    <div style="background:#f9fbfc;border-radius:6px;padding:15px;border:1px solid #e0e7ee;border-left:3px solid {{ $receiptColor }};margin-top:15px;">
                        <div style="font-size:9px;color:{{ $receiptColor }};text-transform:uppercase;font-weight:700;margin-bottom:8px;letter-spacing:.5px;">
                            <i class="fas fa-shield-alt"></i> Warranty Coverage
                        </div>
                        <table width="100%" cellpadding="0" cellspacing="0" style="line-height:1.5;font-size:9px;">
                            <tr>
                                <td style="color:#2c3e50;">Coverage:</td>
                                <td align="right"><strong style="color:#10b981;"><i class="fas fa-check-circle"></i> INCLUDED</strong></td>
                            </tr>
                            <tr>
                                <td colspan="2" style="border-top:1px dashed #eee;height:4px;padding-top:4px;"></td>
                            </tr>
                            <tr>
                                <td style="color:#2c3e50;">Duration:</td>
                                <td align="right"><strong style="color:{{ $receiptColor }};">{{ $sale->warranty_period }}</strong></td>
                            </tr>
                        </table>
                        <div style="font-size:8px;color:#999;margin-top:5px;">*Covers manufacturing defects. See store policy.</div>
                    </div>
                    @endif

                    <div style="text-align:center;margin-top:15px;color:{{ $receiptAccent }};font-weight:700;letter-spacing:.5px;font-size:9px;">
                        THANK YOU FOR YOUR BUSINESS!
                    </div>
                </td>
            </tr>
        </table>
    </div>

    @if(!isset($is_pdf) && !isset($is_email))
    <script>
        window.addEventListener('load', function() {
            // Auto-print if ?print=1 is in URL
            if (new URLSearchParams(window.location.search).get('print') === '1') {
                window.print();
            }
        });
    </script>
    @endif
</body>

</html>