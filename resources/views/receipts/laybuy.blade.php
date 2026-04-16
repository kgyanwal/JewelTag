<!DOCTYPE html>
<html lang="en">
<head>
    @php
        $receiptTitle = 'LAYBY INSTALLMENT RECEIPT';
        $receiptColor = '#0ea5e9'; /* Vibrant Sky Blue */
        $receiptDarkColor = '#0369a1';
        $receiptAccent = '#38bdf8';
        $receiptBgLight = '#f0f9ff';
        
        $isFullyPaid = $balanceAfter <= 0.01;

        if ($isFullyPaid) {
            $statusLabel = 'PAID IN FULL';
            $statusColor = '#10b981';
            $statusBg = '#ecfdf5';
            $statusIcon = 'fa-check-circle';
        } else {
            $statusLabel = 'ACTIVE LAYBY';
            $statusColor = '#f59e0b';
            $statusBg = '#fffbeb';
            $statusIcon = 'fa-clock';
        }
    @endphp

    <meta charset="utf-8">
    <title>{{ $receiptTitle }}: {{ $laybuy->laybuy_no }}</title>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap');

        * { margin: 0; padding: 0; box-sizing: border-box; font-size: 11px; }

        body {
            font-family: 'Inter', Helvetica, Arial, sans-serif;
            color: #2c3e50; line-height: 1.4; background: #f0f9ff; padding: 20px;
        }

        .print-button-container {
            max-width: 850px; margin: 0 auto 15px; display: flex; justify-content: flex-end;
        }

        .print-button {
            background: {{ $receiptColor }}; color: white; border: none; padding: 10px 20px;
            border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer;
            display: flex; align-items: center; gap: 8px; box-shadow: 0 2px 8px rgba(0,0,0,.1);
        }

        @media print {
            body { background: white; padding: 0; }
            .invoice-container {
                box-shadow: none !important; padding: 20px !important;
                max-width: 100% !important; border-top: none !important;
            }
            .no-print { display: none !important; }
            .invoice-header-card, .totals-card, .items-table thead th {
                -webkit-print-color-adjust: exact; print-color-adjust: exact;
            }
            @page { margin: .3in; size: auto; }
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

        <div style="margin:10px 0 15px;padding:8px;border-radius:4px;font-weight:700;text-transform:uppercase;text-align:center;letter-spacing:1px;background:{{ $receiptBgLight }};color:{{ $receiptColor }};border:1px solid {{ $receiptColor }};font-size:11px;">
            <i class="fas fa-credit-card"></i> {{ $receiptTitle }}
        </div>

        {{-- ── STATUS BANNER ── --}}
        <div style="margin-bottom:15px;padding:10px 15px;border-radius:6px;background:{{ $statusBg }};border:1px solid {{ $statusColor }};border-left:4px solid {{ $statusColor }};display:flex;align-items:center;gap:10px;">
            <i class="fas {{ $statusIcon }}" style="color:{{ $statusColor }};font-size:16px;"></i>
            <div>
                <div style="font-weight:800;font-size:11px;color:{{ $statusColor }};text-transform:uppercase;letter-spacing:0.5px;">{{ $statusLabel }}</div>
                @if(!$isFullyPaid)
                <div style="font-size:10px;color:#666;margin-top:2px;">
                    Next Payment Due: <strong>{{ \Carbon\Carbon::parse($laybuy->due_date)->format('M d, Y') }}</strong>
                </div>
                @endif
            </div>
        </div>

        {{-- ── HEADER ── --}}
        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:25px;border-bottom:2px solid #e0e7ee;padding-bottom:20px;">
            <tr>
                <td width="60%" valign="top">
                    <h1 style="font-family:'Playfair Display',serif;font-size:24px;color:{{ $receiptColor }};margin-bottom:5px;">
                        {{ optional($store)->name ?? 'Diamond Square' }}
                    </h1>
                    <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:{{ $receiptAccent }};letter-spacing:1px;margin-bottom:10px;">
                        Premium Jewelry & Luxury Timepieces
                    </div>
                    <div style="font-size:10px;background:#f8fafc;padding:10px;border-radius:6px;border-left:3px solid {{ $receiptAccent }};color:#546e7a;line-height:1.5;">
                        <div style="margin-bottom:3px;"><i class="fas fa-map-marker-alt"></i> {{ optional($store)->street }}, {{ optional($store)->state }} {{ optional($store)->postcode }}</div>
                        <div style="margin-bottom:3px;"><i class="fas fa-phone"></i> {{ optional($store)->phone ?? '505-810-7222' }} &nbsp;|&nbsp; <i class="fas fa-envelope"></i> {{ optional($store)->email ?? 'info@example.com' }}</div>
                    </div>
                </td>
                <td width="40%" valign="top" align="right">
                    <div class="invoice-header-card" style="background:{{ $receiptColor }};color:white;padding:15px 20px;border-radius:8px;text-align:right;">
                        <span style="display:block;font-size:10px;text-transform:uppercase;letter-spacing:1px;font-weight:600;">RECEIPT NO.</span>
                        <span style="display:block;font-size:20px;font-weight:700;margin:5px 0;">
                            {{ $laybuy->laybuy_no }}
                        </span>
                        <div style="font-size:10px;line-height:1.4;margin-top:8px;">
                            <div><i class="fas fa-calendar-day"></i> <b>Date:</b> {{ $paymentDate->format('m/d/Y') }}</div>
                            <div><i class="fas fa-clock"></i> <b>Time:</b> {{ $paymentDate->format('h:i A') }}</div>
                            <div><i class="fas fa-user-tie"></i> <b>Associate:</b> {{ $laybuy->sales_person ?? 'Staff' }}</div>
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        {{-- ── CUSTOMER INFO ── --}}
        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;">
            <tr>
                <td width="100%" valign="top" style="background:#f9fbfc;border-radius:6px;padding:15px;border:1px solid #e0e7ee;border-left:3px solid {{ $receiptColor }};">
                    <div style="font-size:9px;color:{{ $receiptColor }};text-transform:uppercase;font-weight:700;margin-bottom:8px;letter-spacing:.5px;">
                        <i class="fas fa-user-circle"></i> Customer Information
                    </div>
                    <div style="font-size:15px;font-weight:700;color:{{ $receiptColor }};margin-bottom:5px;">
                        {{ $laybuy->customer->name ?? 'Valued' }} {{ $laybuy->customer->last_name ?? 'Customer' }}
                    </div>
                    <div style="color:#546e7a;line-height:1.5;font-size:10px;">
                        <div><i class="fas fa-phone"></i> {{ $laybuy->customer->phone ?? 'N/A' }}</div>
                        <div><i class="fas fa-envelope"></i> {{ $laybuy->customer->email ?? 'N/A' }}</div>
                    </div>
                </td>
            </tr>
        </table>

        {{-- ── ITEMS TABLE ── --}}
        <div style="font-size:14px;font-weight:700;color:{{ $receiptColor }};margin-bottom:10px;">
            <i class="fas fa-box-open"></i> Reserved Items
        </div>

        <table class="items-table" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;border:1px solid #e0e7ee;border-radius:6px;border-collapse:collapse;font-size:10px;">
            <thead>
                <tr>
                    <th style="background:{{ $receiptColor }};color:white;padding:10px 8px;text-align:left;font-size:9px;text-transform:uppercase;border:1px solid #e0e7ee;">Reference #</th>
                    <th style="background:{{ $receiptColor }};color:white;padding:10px 8px;text-align:left;font-size:9px;text-transform:uppercase;border:1px solid #e0e7ee;">Item Description</th>
                    <th style="background:{{ $receiptColor }};color:white;padding:10px 8px;text-align:center;width:10%;font-size:9px;text-transform:uppercase;border:1px solid #e0e7ee;">Qty</th>
                    <th style="background:{{ $receiptColor }};color:white;padding:10px 8px;text-align:right;width:20%;font-size:9px;text-transform:uppercase;border:1px solid #e0e7ee;">Price</th>
                </tr>
            </thead>
            <tbody>
                @if($laybuy->sale && $laybuy->sale->items)
                    @foreach($laybuy->sale->items as $item)
                    <tr>
                        <td style="padding:10px 8px;border-bottom:1px solid #eee;background:#fff;">
                            <span style="background:#f0f7fa;color:{{ $receiptColor }};padding:3px 6px;border-radius:3px;font-family:monospace;font-size:9px;border:1px dashed #c2e0ee;font-weight:700;">
                                {{ $item->productItem?->barcode ?? 'CUSTOM' }}
                            </span>
                        </td>
                        <td style="padding:10px 8px;border-bottom:1px solid #eee;background:#fff;">
                            <div style="font-weight:700;color:#2c3e50;font-size:12px;">{{ strtoupper($item->custom_description ?? 'Item') }}</div>
                        </td>
                        <td style="text-align:center;font-weight:700;padding:10px 8px;border-bottom:1px solid #eee;background:#fff;">{{ $item->qty }}</td>
                        <td style="text-align:right;font-weight:700;color:{{ $receiptColor }};padding:10px 8px;border-bottom:1px solid #eee;background:#fff;">
                            ${{ number_format($item->sold_price, 2) }}
                        </td>
                    </tr>
                    @endforeach
                @endif
            </tbody>
        </table>

        {{-- ── BOTTOM: NOTES + TOTALS ── --}}
        <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td width="55%" valign="top" style="padding-right:20px;">
                    <div style="background:#f8fafc;padding:12px;border-radius:6px;border:1px solid #e0e7ee;margin-bottom:15px;">
                        <div style="color:{{ $receiptColor }};font-size:9px;text-transform:uppercase;font-weight:700;margin-bottom:5px;letter-spacing:.5px;">
                            <i class="fas fa-file-contract"></i> LAYBY TERMS & CONDITIONS
                        </div>
                        <p style="font-size:8px;color:#546e7a;line-height:1.4;margin:0;">
                            Layby items remain the property of {{ optional($store)->legal_name ?? 'Diamond Square' }} until paid in full. Payments are non-refundable. If the balance is not paid by the due date, items will be returned to stock and deposits forfeited.
                        </p>
                    </div>

                    <div style="margin-top:15px;border-top:1px solid #2c3e50;width:200px;padding-top:5px;font-weight:600;font-size:8px;text-transform:uppercase;color:{{ $receiptColor }};">
                        Customer Signature
                    </div>
                </td>

                <td width="45%" valign="top">
                    {{-- TOTALS CARD --}}
                    <div style="background:{{ $receiptColor }};border-radius:6px;padding:15px;color:white;">
                        <table width="100%" cellpadding="0" cellspacing="0" style="color:white;font-size:11px;">
                            <tr>
                                <td style="padding:5px 0;border-bottom:1px dashed rgba(255,255,255,.2);">Total Agreement</td>
                                <td align="right" style="padding:5px 0;border-bottom:1px dashed rgba(255,255,255,.2);">${{ number_format($laybuy->total_amount, 2) }}</td>
                            </tr>
                            <tr>
                                <td style="padding:5px 0;border-bottom:1px dashed rgba(255,255,255,.2);">Previous Balance</td>
                                <td align="right" style="padding:5px 0;border-bottom:1px dashed rgba(255,255,255,.2);">${{ number_format($balanceBefore, 2) }}</td>
                            </tr>
                            <tr>
                                <td style="padding:5px 0;border-bottom:1px dashed rgba(255,255,255,.2);color:#4ade80;font-weight:600;">Payment Today ({{ $paymentMethod }})</td>
                                <td align="right" style="padding:5px 0;border-bottom:1px dashed rgba(255,255,255,.2);color:#4ade80;font-weight:600;">-${{ number_format($currentPaymentAmount, 2) }}</td>
                            </tr>
                            <tr>
                                <td style="padding-top:10px;font-size:16px;font-weight:700;">NEW BALANCE</td>
                                <td align="right" style="padding-top:10px;font-size:16px;font-weight:700;">${{ number_format($balanceAfter, 2) }}</td>
                            </tr>
                        </table>
                    </div>
                    
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
            if (new URLSearchParams(window.location.search).get('print') === '1') {
                window.print();
            }
        });
    </script>
    @endif
</body>
</html>