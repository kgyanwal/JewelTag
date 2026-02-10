<!DOCTYPE html>
<html lang="en">

<head>
  @php
// Initialize variables
$totalSavings = 0;
$hasRepair = false;
$hasCustom = false;

// Check receipt type
foreach($sale->items as $item) {
    if($item->discount_percent > 0) {
        $originalPrice = $item->sold_price / (1 - ($item->discount_percent / 100));
        $totalSavings += ($originalPrice - $item->sold_price) * $item->qty;
    }
    
    // Debug: Check what's in the item
    // dd($item->toArray());
    
    // FIX: Check both ways
    if($item->repair_id || $item->repair) $hasRepair = true;
    if($item->custom_order_id || $item->customOrder) $hasCustom = true;
}

// Determine receipt type
if ($hasRepair) {
    $receiptType = 'repair';
    $receiptTitle = 'REPAIR RECEIPT';
    $receiptTypeLabel = 'Service/Repair';
    $receiptColor = '#b45309'; // Amber
    $receiptDarkColor = '#92400e';
    $receiptAccent = '#f59e0b';
    $receiptBadgeColor = 'bg-repair';
} elseif ($hasCustom) {
    $receiptType = 'custom';
    $receiptTitle = 'CUSTOM ORDER RECEIPT';
    $receiptTypeLabel = 'Custom Design';
    $receiptColor = '#6d28d9'; // Purple
    $receiptDarkColor = '#4c1d95';
    $receiptAccent = '#c084fc';
    $receiptBadgeColor = 'bg-custom';
} else {
    $receiptType = 'normal';
    $receiptTitle = 'SALES RECEIPT';
    $receiptTypeLabel = 'Product Sale';
    $receiptColor = '#1a6b8c'; // Blue
    $receiptDarkColor = '#12506b';
    $receiptAccent = '#d4af37';
    $receiptBadgeColor = 'bg-normal';
}
@endphp
    
    <meta charset="utf-8">
    <title>{{ $receiptTitle }}: {{ $sale->invoice_number }} | Diamond Square</title>

    <!-- PDF/Email Specific CSS -->
    @if(isset($is_pdf) || isset($is_email))
    <style>
        /* Force table layout for PDF compatibility */
        .header {
            display: table !important;
            width: 100% !important;
            border-bottom: 2px solid #e0e7ee !important;
            margin-bottom: 20px !important;
        }

        .store-info {
            display: table-cell !important;
            width: 60% !important;
            vertical-align: top !important;
            padding-right: 20px !important;
        }

        .invoice-header-card {
            display: table-cell !important;
            width: 40% !important;
            background-color: {{ $receiptColor }} !important;
            color: #ffffff !important;
            padding: 20px !important;
            text-align: right !important;
            border-radius: 8px !important;
            vertical-align: top !important;
        }

        .invoice-label {
            display: block !important;
            font-size: 12px !important;
            color: #ffffff !important;
            opacity: 1 !important;
            text-transform: uppercase !important;
            font-weight: bold !important;
            letter-spacing: 1px !important;
        }

        .invoice-number {
            display: block !important;
            font-size: 24px !important;
            font-weight: bold !important;
            color: #ffffff !important;
            margin: 5px 0 !important;
        }

        .invoice-meta {
            display: block !important;
            font-size: 11px !important;
            color: #ffffff !important;
            line-height: 1.4 !important;
            margin-top: 10px !important;
        }

        .info-grid {
            display: table !important;
            width: 100% !important;
            margin-bottom: 25px !important;
        }

        .info-card {
            display: table-cell !important;
            width: 50% !important;
            vertical-align: top !important;
            background: #f9fbfc !important;
            border-radius: 8px !important;
            padding: 20px !important;
            border: 1px solid #e0e7ee !important;
            border-left: 4px solid {{ $receiptColor }} !important;
        }

        /* Remove problematic elements for PDF */
        .invoice-container::before {
            display: none !important;
        }

        /* Force block display for better PDF rendering */
        .store-details div {
            display: block !important;
            margin-bottom: 4px !important;
        }

        /* Ensure proper table rendering */
        .items-table {
            width: 100% !important;
            border-collapse: collapse !important;
            margin-bottom: 25px !important;
        }

        .items-table th,
        .items-table td {
            padding: 12px 8px !important;
            border: 1px solid #e0e7ee !important;
        }

        /* Force solid backgrounds for PDF */
        .totals-card {
            background-color: {{ $receiptColor }} !important;
            border-radius: 8px !important;
            padding: 25px !important;
            color: white !important;
        }

        /* Hide QR/Social for email PDF */
        .social-qr-card {
            display: none !important;
        }

        .footer-layout {
            display: table !important;
            width: 100% !important;
        }

        .terms-area,
        .totals-area {
            display: table-cell !important;
            vertical-align: top !important;
        }

        .terms-area {
            width: 60% !important;
            padding-right: 20px !important;
        }

        .totals-area {
            width: 40% !important;
        }
        
        /* Receipt type banner */
        .receipt-type-banner {
            margin: 15px 0 !important;
            padding: 12px !important;
            border-radius: 6px !important;
            font-weight: 800 !important;
            text-transform: uppercase !important;
            text-align: center !important;
            letter-spacing: 2px !important;
            background: {{ $receiptType == 'repair' ? '#fff7ed' : ($receiptType == 'custom' ? '#f5f3ff' : '#eff6ff') }} !important;
            color: {{ $receiptColor }} !important;
            border: 2px solid {{ $receiptColor }} !important;
            display: block !important;
        }
        
        /* Stock badge colors */
        .stock-badge {
            background: #f0f7fa !important;
            color: {{ $receiptColor }} !important;
            padding: 4px 8px !important;
            border-radius: 4px !important;
            font-family: monospace !important;
            font-size: 12px !important;
            border: 1px dashed {{ $receiptColor }} !important;
            font-weight: 700 !important;
        }
        
        .type-indicator {
            font-size: 9px !important;
            text-transform: uppercase !important;
            padding: 2px 5px !important;
            border-radius: 3px !important;
            margin-bottom: 4px !important;
            display: inline-block !important;
            font-weight: bold !important;
            color: white !important;
            background-color: {{ $receiptColor }} !important;
        }
    </style>
    @endif

    <!-- Regular CSS for web view -->
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap');

        /* Dynamic color variables based on receipt type */
        :root {
            --primary: {{ $receiptColor }};
            --primary-dark: {{ $receiptDarkColor }};
            --primary-light: {{ $receiptColor }}dd;
            --accent: {{ $receiptAccent }};
            --text-dark: #2c3e50;
            --text-medium: #546e7a;
            --bg-light: #f8fafc;
            --border: #e0e7ee;
            --success: #10b981;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            color: var(--text-dark);
            line-height: 1.4;
            background: #f0f9ff;
            padding: 10px;
        }

        .invoice-container {
            max-width: 850px;
            margin: 0 auto;
            padding: 30px;
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .invoice-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 6px;
            background: linear-gradient(90deg, var(--primary), var(--accent), var(--primary-light));
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--border);
        }

        .store-info h1 {
            font-family: 'Playfair Display', serif;
            font-size: 32px;
            color: var(--primary);
            margin-bottom: 5px;
        }

        .store-tagline {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--accent);
            letter-spacing: 2px;
            margin-bottom: 12px;
        }

        .store-details {
            font-size: 12px;
            background: var(--bg-light);
            padding: 12px;
            border-radius: 8px;
            border-left: 4px solid var(--accent);
            color: var(--text-medium);
            line-height: 1.6;
        }

        .invoice-header-card {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 20px;
            border-radius: 8px;
            min-width: 300px;
            text-align: right;
        }

        .invoice-label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 2px;
            opacity: 0.9;
            font-weight: 600;
        }

        .invoice-number {
            font-size: 26px;
            font-weight: 700;
            display: block;
            margin: 5px 0;
        }

        .invoice-meta {
            font-size: 12px;
            opacity: 0.9;
            margin-top: 10px;
            line-height: 1.5;
        }

        /* Grid Layout */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }

        .info-card {
            background: #f9fbfc;
            border-radius: 8px;
            padding: 20px;
            border: 1px solid var(--border);
            border-left: 4px solid var(--primary-light);
        }

        .card-title {
            font-size: 11px;
            color: var(--primary);
            text-transform: uppercase;
            font-weight: 700;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            letter-spacing: 1px;
        }

        .customer-name {
            font-size: 18px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 8px;
        }

        /* Items Table */
        .section-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
            border: 1px solid var(--border);
            overflow: hidden;
            border-radius: 8px;
        }

        .items-table thead {
            background: var(--primary);
            color: white;
        }

        .items-table th {
            padding: 15px;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .items-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            background: #fff;
        }

        .stock-badge {
            background: #f0f7fa;
            color: var(--primary);
            padding: 4px 8px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 12px;
            border: 1px dashed #c2e0ee;
            font-weight: 700;
        }

        /* Receipt Type Banner */
        .receipt-type-banner {
            margin: 15px 0;
            padding: 12px;
            border-radius: 6px;
            font-weight: 800;
            text-transform: uppercase;
            text-align: center;
            letter-spacing: 2px;
            background: {{ $receiptType == 'repair' ? '#fff7ed' : ($receiptType == 'custom' ? '#f5f3ff' : '#eff6ff') }};
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        /* Type Indicator Badge */
        .type-indicator {
            font-size: 9px;
            text-transform: uppercase;
            padding: 2px 5px;
            border-radius: 3px;
            margin-bottom: 4px;
            display: inline-block;
            font-weight: bold;
            color: white;
            background-color: var(--primary);
        }

        /* Totals & Footer Grid */
        .footer-layout {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 30px;
            align-items: start;
        }

        .totals-card {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 8px;
            padding: 25px;
            color: white;
            box-shadow: 0 4px 15px rgba(26, 107, 140, 0.2);
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dashed rgba(255, 255, 255, 0.2);
            font-size: 14px;
        }

        .grand-total-row {
            display: flex;
            justify-content: space-between;
            padding-top: 15px;
            margin-top: 10px;
            border-top: 2px solid var(--accent);
            font-size: 22px;
            font-weight: 700;
            color: #fff;
        }

        .signature-box {
            margin-top: 30px;
            border-top: 2px solid var(--text-dark);
            width: 250px;
            padding-top: 10px;
            font-weight: 700;
            font-size: 11px;
            text-transform: uppercase;
            color: var(--primary);
        }

        .social-qr-card {
            background: var(--bg-light);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 20px;
            margin-top: 20px;
        }

        .qr-code {
            width: 90px;
            height: 90px;
            border: 4px solid white;
            border-radius: 4px;
        }

        .social-links {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .social-links a {
            text-decoration: none;
            color: var(--text-dark);
            font-weight: 600;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .social-links i {
            color: var(--primary);
            width: 16px;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .invoice-container {
                box-shadow: none;
                padding: 0;
                width: 100%;
            }

            @page {
                margin: 0.4in;
            }

            .invoice-header-card,
            .totals-card,
            .items-table thead {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>

    <div class="invoice-container">
        <!-- Receipt Type Banner -->
        @if($receiptType !== 'normal')
        <div class="receipt-type-banner">
            <i class="fas {{ $receiptType == 'repair' ? 'fa-wrench' : 'fa-palette' }}"></i>
            {{ $receiptTitle }}
        </div>
        @endif
        
        <div class="header">
            <div class="store-info">
                <h1>Diamond Square</h1>
                <div class="store-tagline">Premium Jewelry & Luxury Timepieces</div>
                <div class="store-details">
                    <div><i class="fas fa-map-marker-alt"></i> 6600 Menaul Blvd NE #6508, Albuquerque, NM 87110</div>
                    <div><i class="fas fa-phone"></i> 505-810-7222 &nbsp; | &nbsp; <i class="fas fa-envelope"></i> info@thedsq.com</div>
                    <div><i class="fas fa-globe"></i> www.thediamondsq.com</div>
                </div>
            </div>
            <div class="invoice-header-card">
                <span class="invoice-label">{{ $receiptTitle }}</span>
                <span class="invoice-number">{{ $receiptType == 'normal' ? $sale->invoice_number : strtoupper($receiptType).'-'.$sale->invoice_number }}</span>
                <div class="invoice-meta">
                    <div><i class="fas fa-calendar-day"></i> <b>Date:</b> {{ $sale->created_at->format('m/d/Y') }}</div>
                    <div><i class="fas fa-clock"></i> <b>Time:</b> {{ $sale->created_at->format('h:i A') }}</div>
                    <div><i class="fas fa-user-tie"></i> <b>Associate:</b> {{ $sale->sales_person_list }}</div>
                </div>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-card">
                <div class="card-title"><i class="fas fa-user-circle"></i> Customer Information</div>
                <div class="customer-name">{{ $sale->customer->name ?? 'Valued Customer' }}</div>
                <div style="color: var(--text-medium); line-height: 1.6;">
                    <div><i class="fas fa-phone"></i> {{ $sale->customer->phone ?? 'N/A' }}</div>
                    <div><i class="fas fa-envelope"></i> {{ $sale->customer->email ?? 'N/A' }}</div>
                </div>
            </div>
            <div class="info-card">
                <div class="card-title"><i class="fas fa-credit-card"></i> Payment Summary</div>
                <div style="line-height: 1.8;">
                    <div style="display: flex; justify-content: space-between;">
                        <span>Payment Method:</span>
                        <strong style="color: var(--primary);">{{ strtoupper(str_replace('_', ' ', $sale->payment_method)) }}</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span>Status:</span>
                        @if($sale->payment_method === 'laybuy' && ($sale->laybuy->balance_due ?? 1) > 0)
                        <strong style="color: #f59e0b;">LAYBY ACTIVE</strong>
                        @else
                        <strong style="color: var(--success);">PAID IN FULL</strong>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="section-title"><i class="fas {{ $receiptType == 'repair' ? 'fa-wrench' : ($receiptType == 'custom' ? 'fa-palette' : 'fa-gem') }}"></i> 
            {{ $receiptType == 'repair' ? 'Repair Services' : ($receiptType == 'custom' ? 'Custom Order Items' : 'Purchased Items') }}
        </div>
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 15%">Reference #</th>
                    <th>Item Description</th>
                    <th style="text-align: center; width: 10%">Qty</th>
                    <th style="text-align: center; width: 10%">Disc %</th>
                    <th style="text-align: right; width: 20%">Price</th>
                </tr>
            </thead>
            <tbody>
               @foreach($sale->items as $item)
<tr>
  <td>
    <span class="stock-badge">
        @if ($item->productItem)
            {{ $item->productItem->barcode }}
        @elseif ($item->repair)
            REPAIR #{{ $item->repair->repair_no }}
        @elseif ($item->customOrder)
            CUSTOM #{{ $item->customOrder->order_no }}
        @else
            N/A
        @endif
    </span>
</td>
<td>
    @if($item->repair_id || $item->repair)
        <span class="type-indicator">Service/Repair</span>
    @elseif($item->custom_order_id || $item->customOrder)
        <span class="type-indicator">Custom Design</span>
    @endif
    <div style="font-weight: 700; color: var(--text-dark); font-size: 14px;">
        {{ $item->custom_description }}
    </div>
</td>
    <td style="text-align: center; font-weight: 700;">{{ $item->qty }}</td>
    <td style="text-align: center;">
        @if($item->discount_percent > 0)
        <span style="color: var(--success); font-weight: 700;">{{ number_format($item->discount_percent, 0) }}%</span>
        @else
        <span style="color: var(--text-medium);">-</span>
        @endif
    </td>
    <td style="text-align: right; font-weight: 700; color: var(--primary);">
        ${{ number_format($item->sold_price * $item->qty, 2) }}
    </td>
</tr>
@endforeach
            </tbody>
        </table>

        <div class="footer-layout">
            <div class="terms-area">
                <div style="background: var(--bg-light); padding: 20px; border-radius: 8px; border: 1px solid var(--border);">
                    <h4 style="color: var(--primary); font-size: 12px; text-transform: uppercase; margin-bottom: 8px;">Terms & Conditions</h4>
                    <p style="font-size: 11px; color: var(--text-medium); line-height: 1.6;">
                        @if($receiptType == 'repair')
                        All repair services are guaranteed for 90 days from the date of completion. Returns are not accepted for completed repair work. Any additional issues must be reported within the warranty period.
                        @elseif($receiptType == 'custom')
                        Custom orders are final sale. Due to the personalized nature of custom jewelry, returns or exchanges are not accepted. All custom designs are created to customer specifications.
                        @else
                        All sales are final. Returns are accepted within 14 days of purchase for exchange or store credit only, provided the item is unworn and accompanied by the original receipt.
                        @endif
                    </p>
                </div>

                @if(!isset($is_pdf) && !isset($is_email))
                <div class="social-qr-card">
                    <img class="qr-code" src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=https://www.thediamondsq.com/" alt="QR">
                    <div class="social-links">
                        <div style="font-weight: 700; color: var(--primary); margin-bottom: 4px;">Follow Us Online</div>
                        <a href="https://www.thediamondsq.com/" target="_blank"><i class="fas fa-globe"></i> thediamondsq.com</a>
                        <a href="https://www.facebook.com/diamondsquareabq/" target="_blank"><i class="fab fa-facebook-square"></i> diamondsquareabq</a>
                        <a href="https://www.instagram.com/thediamondsq/" target="_blank"><i class="fab fa-instagram"></i> thediamondsq</a>
                    </div>
                </div>
                @endif

                <div class="signature-box">
                    @if($receiptType == 'repair')
                    Your Trusted Jewelry Repair Experts
                    @elseif($receiptType == 'custom')
                    Master Custom Jewelry Designers
                    @else
                    Your Trusted Jewelry Store In Town
                    @endif
                </div>
            </div>

            <div class="totals-area">
                <div class="totals-card">
                    <div class="total-row">
                        <span>Subtotal</span>
                        <span>${{ number_format($sale->subtotal, 2) }}</span>
                    </div>
                    @if($totalSavings > 0)
                    <div class="total-row" style="color: #4ade80; font-weight: 600;">
                        <span>Total Savings</span>
                        <span>-${{ number_format($totalSavings, 2) }}</span>
                    </div>
                    @endif
                    <div class="total-row">
                        <span>Sales Tax</span>
                        <span>${{ number_format($sale->tax_amount, 2) }}</span>
                    </div>
                    @if($sale->has_trade_in)
                    <div class="total-row" style="color: #ffeb3b;">
                        <span>Trade-In Credit</span>
                        <span>-${{ number_format($sale->trade_in_value, 2) }}</span>
                    </div>
                    @endif
                    <div class="grand-total-row">
                        <span>{{ $receiptType == 'repair' ? 'TOTAL SERVICE COST' : 'TOTAL AMOUNT' }}</span>
                        <span>${{ number_format($sale->final_total, 2) }}</span>
                    </div>

                    @if($sale->payment_method === 'laybuy')
                    <div class="total-row" style="margin-top: 15px; background: rgba(0,0,0,0.2); padding: 12px; border-radius: 6px; border-bottom: none;">
                        <span style="font-weight: 700;">BALANCE DUE</span>
                        <span style="font-size: 18px; font-weight: 700; color: var(--accent);">${{ number_format($sale->laybuy->balance_due ?? $sale->final_total, 2) }}</span>
                    </div>
                    @endif
                </div>
                <div style="text-align: center; margin-top: 20px; color: var(--accent); font-weight: 700; letter-spacing: 1px;">
                    THANK YOU FOR YOUR BUSINESS!
                </div>
            </div>
        </div>
    </div>

    @if(!isset($is_pdf) && !isset($is_email))
    <script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 1000);
        };
    </script>
    @endif
</body>

</html>