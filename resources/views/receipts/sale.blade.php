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
        
        if($item->repair_id || $item->repair) $hasRepair = true;
        if($item->custom_order_id || $item->customOrder) $hasCustom = true;
    }

    // Determine receipt type with colors
    if ($hasRepair) {
        $receiptType = 'repair';
        $receiptTitle = 'REPAIR RECEIPT';
        $receiptTypeLabel = 'Service/Repair';
        $receiptColor = '#249E94'; // REPAIR COLOR
        $receiptDarkColor = '#1a7a72';
        $receiptAccent = '#4fc1b7';
        $receiptBgLight = '#f0f9f8';
    } elseif ($hasCustom) {
        $receiptType = 'custom';
        $receiptTitle = 'CUSTOM ORDER RECEIPT';
        $receiptTypeLabel = 'Custom Design';
        $receiptColor = '#BB8ED0'; // CUSTOM ORDER COLOR
        $receiptDarkColor = '#9a6fb3';
        $receiptAccent = '#d4b3e6';
        $receiptBgLight = '#f9f5fc';
    } else {
        $receiptType = 'normal';
        $receiptTitle = 'SALES RECEIPT';
        $receiptTypeLabel = 'Product Sale';
        $receiptColor = '#1a6b8c'; // Blue remains same
        $receiptDarkColor = '#12506b';
        $receiptAccent = '#d4af37';
        $receiptBgLight = '#eff6ff';
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

        /* Hide print button in PDF */
        .no-print {
            display: none !important;
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
            font-size: 11px;
        }

        body {
            font-family: 'Inter', sans-serif;
            color: var(--text-dark);
            line-height: 1.4;
            background: #f0f9ff;
            padding: 20px;
        }

        /* Print Button */
        .print-button-container {
            max-width: 850px;
            margin: 0 auto 15px;
            display: flex;
            justify-content: flex-end;
        }

        .print-button {
            background: var(--primary);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .print-button:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
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
            font-size: 24px;
            color: var(--primary);
            margin-bottom: 5px;
        }

        .store-tagline {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--accent);
            letter-spacing: 1px;
            margin-bottom: 10px;
        }

        .store-details {
            font-size: 10px;
            background: var(--bg-light);
            padding: 10px;
            border-radius: 6px;
            border-left: 3px solid var(--accent);
            color: var(--text-medium);
            line-height: 1.5;
        }

        .invoice-header-card {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            min-width: 280px;
            text-align: right;
        }

        .invoice-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.9;
            font-weight: 600;
        }

        .invoice-number {
            font-size: 20px;
            font-weight: 700;
            display: block;
            margin: 3px 0;
        }

        .invoice-meta {
            font-size: 10px;
            opacity: 0.9;
            margin-top: 8px;
            line-height: 1.4;
        }

        /* Grid Layout */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }

        .info-card {
            background: #f9fbfc;
            border-radius: 6px;
            padding: 15px;
            border: 1px solid var(--border);
            border-left: 3px solid var(--primary-light);
        }

        .card-title {
            font-size: 9px;
            color: var(--primary);
            text-transform: uppercase;
            font-weight: 700;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 5px;
            letter-spacing: 0.5px;
        }

        .customer-name {
            font-size: 15px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 5px;
        }

        /* Items Table */
        .section-title {
            font-size: 14px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            border: 1px solid var(--border);
            overflow: hidden;
            border-radius: 6px;
            font-size: 10px;
        }

        .items-table thead {
            background: var(--primary);
            color: white;
        }

        .items-table th {
            padding: 10px 8px;
            text-align: left;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .items-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #eee;
            background: #fff;
        }

        .stock-badge {
            background: #f0f7fa;
            color: var(--primary);
            padding: 3px 6px;
            border-radius: 3px;
            font-family: monospace;
            font-size: 9px;
            border: 1px dashed #c2e0ee;
            font-weight: 700;
        }

        /* Receipt Type Banner */
        .receipt-type-banner {
            margin: 10px 0 15px;
            padding: 8px;
            border-radius: 4px;
            font-weight: 700;
            text-transform: uppercase;
            text-align: center;
            letter-spacing: 1px;
            background: {{ $receiptBgLight }};
            color: var(--primary);
            border: 1px solid var(--primary);
            font-size: 11px;
        }

        /* Type Indicator Badge */
        .type-indicator {
            font-size: 8px;
            text-transform: uppercase;
            padding: 2px 4px;
            border-radius: 2px;
            margin-bottom: 3px;
            display: inline-block;
            font-weight: 600;
            color: white;
            background-color: var(--primary);
        }

        /* Totals & Footer Grid */
        .footer-layout {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 20px;
            align-items: start;
        }

        .terms-area {
            background: var(--bg-light);
            padding: 12px;
            border-radius: 6px;
            border: 1px solid var(--border);
        }

        .terms-title {
            color: var(--primary);
            font-size: 9px;
            text-transform: uppercase;
            font-weight: 700;
            margin-bottom: 5px;
            letter-spacing: 0.5px;
        }

        .terms-text {
            font-size: 8px;
            color: var(--text-medium);
            line-height: 1.4;
            margin: 0;
        }

        .totals-card {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 6px;
            padding: 15px;
            color: white;
            box-shadow: 0 2px 8px rgba(26, 107, 140, 0.15);
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px dashed rgba(255, 255, 255, 0.2);
            font-size: 11px;
        }

        .grand-total-row {
            display: flex;
            justify-content: space-between;
            padding-top: 10px;
            margin-top: 5px;
            border-top: 2px solid var(--accent);
            font-size: 16px;
            font-weight: 700;
            color: #fff;
        }

        .signature-box {
            margin-top: 15px;
            border-top: 1px solid var(--text-dark);
            width: 200px;
            padding-top: 5px;
            font-weight: 600;
            font-size: 8px;
            text-transform: uppercase;
            color: var(--primary);
        }

        .social-qr-card {
            background: var(--bg-light);
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 12px;
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 12px;
        }

        .qr-code {
            width: 70px;
            height: 70px;
            border: 2px solid white;
            border-radius: 4px;
        }

        .social-links {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .social-links a {
            text-decoration: none;
            color: var(--text-dark);
            font-weight: 500;
            font-size: 9px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .social-links i {
            color: var(--primary);
            width: 14px;
            font-size: 10px;
        }

        /* Thank you message */
        .thank-you {
            text-align: center;
            margin-top: 12px;
            color: var(--accent);
            font-weight: 700;
            letter-spacing: 0.5px;
            font-size: 9px;
        }

        /* Print styles */
        @media print {
            body {
                background: white;
                padding: 0;
            }

            .invoice-container {
                box-shadow: none;
                padding: 20px;
                max-width: 100%;
            }

            .no-print {
                display: none !important;
            }

            .invoice-header-card,
            .totals-card,
            .items-table thead {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            @page {
                margin: 0.3in;
                size: auto;
            }
            
            /* Hide URL from printing */
            a[href]:after {
                content: none !important;
            }
            
            /* Tiny terms for print */
            .terms-text {
                font-size: 7px !important;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <!-- Print Button - Only show in web view -->
    @if(!isset($is_pdf) && !isset($is_email))
    <div class="print-button-container no-print">
        <button class="print-button" onclick="printReceipt()">
            <i class="fas fa-print"></i>
            Print Receipt
        </button>
    </div>
    @endif

    <div class="invoice-container" id="receipt-content">
        <!-- Receipt Type Banner -->
        @if($receiptType !== 'normal')
        <div class="receipt-type-banner">
            <i class="fas {{ $receiptType == 'repair' ? 'fa-wrench' : 'fa-palette' }}"></i>
            {{ $receiptTitle }}
        </div>
        @endif
        
     <div class="header">
            <div class="store-info">
                <h1>{{ optional($sale->store)->name ?? 'Diamond Square' }}</h1>
                <div class="store-tagline">Premium Jewelry & Luxury Timepieces</div>
                <div class="store-details">
                    <div>
                        <i class="fas fa-map-marker-alt"></i> 
                        {{ optional($sale->store)->location ?? 'Your Location(insert from store)' }}
                    </div>
                    <div>
                        <i class="fas fa-phone"></i> 
                        {{ optional($sale->store)->phone ?? '505-810-7222' }} &nbsp; | &nbsp; 
                        <i class="fas fa-envelope"></i> 
                        {{ optional($sale->store)->email ?? 'info@example.com' }}
                    </div>
                    <div>
                        <i class="fas fa-globe"></i> 
                        {{ str_replace(['http://', 'https://'], '', optional($sale->store)->domain_url ?? 'thedsq.jeweltag.us') }}
                    </div>
                </div>
            </div>
            <div class="invoice-header-card">
                <span class="invoice-label">{{ $receiptTitle }}</span>
                <span class="invoice-number">{{ $receiptType == 'normal' ? $sale->invoice_number : strtoupper($receiptType).'-'.$sale->invoice_number }}</span>
                <div class="invoice-meta">
                    <div><i class="fas fa-calendar-day"></i> <b>Date:</b> {{ $sale->created_at->format('m/d/Y') }}</div>
                    <div><i class="fas fa-clock"></i> <b>Time:</b> {{ $sale->created_at->format('h:i A') }}</div>
                    <div><i class="fas fa-user-tie"></i> <b>Associate:</b> {{ is_array($sale->sales_person_list) ? implode(', ', $sale->sales_person_list) : $sale->sales_person_list }}</div>
                </div>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-card">
                <div class="card-title"><i class="fas fa-user-circle"></i> Customer Information</div>
                <div class="customer-name">{{ $sale->customer->name ?? 'Valued Customer' }}</div>
                <div style="color: var(--text-medium); line-height: 1.5; font-size: 10px;">
                    <div><i class="fas fa-phone"></i> {{ $sale->customer->phone ?? 'N/A' }}</div>
                    <div><i class="fas fa-envelope"></i> {{ $sale->customer->email ?? 'N/A' }}</div>
                </div>
            </div>
            <div class="info-card">
                <div class="card-title"><i class="fas fa-credit-card"></i> Payment Summary</div>
                <div style="line-height: 1.6; font-size: 10px;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <span>Payment Method:</span>
                        
                        @if($sale->is_split_payment)
                            <div style="text-align: right;">
                                @if($sale->payment_amount_1 > 0)
                                <div style="margin-bottom: 2px;">
                                    <span style="font-size: 9px; color: #666;">{{ strtoupper($sale->payment_method_1) }}:</span> 
                                    <strong style="color: var(--primary);">${{ number_format($sale->payment_amount_1, 2) }}</strong>
                                </div>
                                @endif

                                @if($sale->payment_amount_2 > 0)
                                <div style="margin-bottom: 2px;">
                                    <span style="font-size: 9px; color: #666;">{{ strtoupper($sale->payment_method_2) }}:</span> 
                                    <strong style="color: var(--primary);">${{ number_format($sale->payment_amount_2, 2) }}</strong>
                                </div>
                                @endif

                                @if($sale->payment_amount_3 > 0)
                                <div>
                                    <span style="font-size: 9px; color: #666;">{{ strtoupper($sale->payment_method_3) }}:</span> 
                                    <strong style="color: var(--primary);">${{ number_format($sale->payment_amount_3, 2) }}</strong>
                                </div>
                                @endif
                            </div>
                        @else
                            <strong style="color: var(--primary);">{{ strtoupper(str_replace('_', ' ', $sale->payment_method)) }}</strong>
                        @endif
                    </div>

                    <div style="display: flex; justify-content: space-between; border-top: 1px dashed #eee; margin-top: 5px; padding-top: 5px;">
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
                        <div style="font-weight: 700; color: var(--text-dark); font-size: 12px;">
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
                <div class="terms-title">
                    <i class="fas fa-file-contract"></i> TERMS & CONDITIONS
                </div>
                <p class="terms-text">
                    @if($receiptType == 'repair')
                    All repair services guaranteed for 90 days. Returns not accepted for completed repairs. Additional issues must be reported within warranty period. By signing, you acknowledge receipt of items and agree to terms.
                    @elseif($receiptType == 'custom')
                    Custom orders are final sale - no returns/exchanges. All designs created to customer specifications. By signing, you approve the final design and acknowledge the final sale nature.
                    @else
                    All sales final. Returns accepted within 14 days for exchange/store credit only, item unworn with original receipt. By signing, you agree to store policy.
                    @endif
                </p>

                @if(!isset($is_pdf) && !isset($is_email))
                <div class="social-qr-card no-print">
                    <img class="qr-code" src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=https://thedsq.jeweltag.us" alt="QR Code - Scan to visit our website">
                    
                    <div class="social-links">
                        <div style="font-weight: 600; color: var(--primary); margin-bottom: 2px; font-size: 9px;">Follow Us Online</div>
                        
                        <a href="{{ optional($sale->store)->domain_url ?? 'https://thedsq.jeweltag.us' }}" target="_blank">
                            <i class="fas fa-globe"></i> 
                            {{ str_replace(['http://', 'https://'], '', optional($sale->store)->domain_url ?? 'thedsq.jeweltag.us') }}
                        </a>

                        @if(!empty(optional($sale->store)->facebook_link))
                        <a href="{{ $sale->store->facebook_link }}" target="_blank">
                            <i class="fab fa-facebook-square"></i> Facebook
                        </a>
                        @endif

                        @if(!empty(optional($sale->store)->instagram_link))
                        <a href="{{ $sale->store->instagram_link }}" target="_blank">
                            <i class="fab fa-instagram"></i> Instagram
                        </a>
                        @endif
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
                        <span>{{ $receiptType == 'repair' ? 'TOTAL' : 'TOTAL' }}</span>
                        <span>${{ number_format($sale->final_total, 2) }}</span>
                    </div>

                    @if($sale->payment_method === 'laybuy')
                    <div class="total-row" style="margin-top: 10px; background: rgba(0,0,0,0.2); padding: 8px; border-radius: 4px; border-bottom: none;">
                        <span style="font-weight: 600;">BALANCE DUE</span>
                        <span style="font-size: 14px; font-weight: 700; color: var(--accent);">${{ number_format($sale->laybuy->balance_due ?? $sale->final_total, 2) }}</span>
                    </div>
                    @endif
                </div>

                @if($sale->has_warranty)
                <div class="info-card" style="margin-top: 15px;">
                    <div class="card-title">
                        <i class="fas fa-shield-alt"></i> Warranty Coverage
                    </div>
                    <div style="line-height: 1.5; font-size: 9px;">
                        <div style="display: flex; justify-content: space-between;">
                            <span>Coverage Status:</span>
                            <strong style="color: var(--success);"><i class="fas fa-check-circle"></i> INCLUDED</strong>
                        </div>

                        <div style="display: flex; justify-content: space-between; border-top: 1px dashed #eee; margin-top: 4px; padding-top: 4px;">
                            <span>Duration:</span>
                            <strong style="color: var(--primary);">{{ $sale->warranty_period }}</strong>
                        </div>
                        
                        <div style="font-size: 8px; color: #999; margin-top: 3px;">
                            *Covers manufacturing defects. See store policy.
                        </div>
                    </div>
                </div>
                @endif
                
                <div class="thank-you">
                    THANK YOU FOR YOUR BUSINESS!
                </div>
            </div>
        </div>
    </div>

    @if(!isset($is_pdf) && !isset($is_email))
    <script>
        function printReceipt() {
            // Store the original title
            const originalTitle = document.title;
            
            // Change title for print (optional)
            document.title = '{{ $receiptTitle }} - {{ $sale->invoice_number }}';
            
            // Trigger print dialog
            window.print();
            
            // Restore title after print dialog closes
            setTimeout(() => {
                document.title = originalTitle;
            }, 1000);
        }

        // Prevent automatic print on page load
        // window.onload is removed - user must click button
        
        // Fix for print dialog showing URL
        window.addEventListener('beforeprint', function() {
            // Add a class to body when printing
            document.body.classList.add('is-printing');
        });
        
        window.addEventListener('afterprint', function() {
            // Remove the class after printing
            document.body.classList.remove('is-printing');
        });
    </script>
    @endif
</body>

</html>