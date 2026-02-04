<!DOCTYPE html>
<html lang="en">
<head>
    @php
    $totalSavings = 0;
    foreach($sale->items as $item) {
        if($item->discount_percent > 0) {
            // Calculate what the price would have been without the discount
            $originalPrice = $item->sold_price / (1 - ($item->discount_percent / 100));
            $totalSavings += ($originalPrice - $item->sold_price) * $item->qty;
        }
    }
@endphp
    <meta charset="utf-8">
    <title>Tax Invoice: {{ $sale->invoice_number }} | Diamond Square</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap');
        
        :root {
            --primary: #1a6b8c;
            --primary-dark: #12506b;
            --primary-light: #2aa3cc;
            --accent: #d4af37;
            --text-dark: #2c3e50;
            --text-medium: #546e7a;
            --bg-light: #f8fafc;
            --border: #e0e7ee;
            --success: #10b981;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
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
            content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 6px;
            background: linear-gradient(90deg, var(--primary), var(--accent), var(--primary-light));
        }
        
        /* Header */
        .header { display: flex; justify-content: space-between; margin-bottom: 25px; padding-bottom: 20px; border-bottom: 2px solid var(--border); }
        .store-info h1 { font-family: 'Playfair Display', serif; font-size: 32px; color: var(--primary); margin-bottom: 5px; }
        .store-tagline { font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--accent); letter-spacing: 2px; margin-bottom: 12px; }
        .store-details { font-size: 12px; background: var(--bg-light); padding: 12px; border-radius: 8px; border-left: 4px solid var(--accent); color: var(--text-medium); line-height: 1.6; }
        
        .invoice-header-card { background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: white; padding: 20px; border-radius: 8px; min-width: 300px; text-align: right; }
        .invoice-label { font-size: 11px; text-transform: uppercase; letter-spacing: 2px; opacity: 0.8; font-weight: 600; }
        .invoice-number { font-size: 26px; font-weight: 700; display: block; margin: 5px 0; }
        .invoice-meta { font-size: 12px; opacity: 0.9; margin-top: 10px; line-height: 1.5; }

        /* Grid Layout */
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px; }
        .info-card { background: #f9fbfc; border-radius: 8px; padding: 20px; border: 1px solid var(--border); border-left: 4px solid var(--primary-light); }
        .card-title { font-size: 11px; color: var(--primary); text-transform: uppercase; font-weight: 700; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; letter-spacing: 1px; }
        .customer-name { font-size: 18px; font-weight: 700; color: var(--primary); margin-bottom: 8px; }
        
        /* Items Table */
        .section-title { font-size: 16px; font-weight: 700; color: var(--primary); margin-bottom: 12px; display: flex; align-items: center; gap: 10px; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 25px; border: 1px solid var(--border); overflow: hidden; border-radius: 8px; }
        .items-table thead { background: var(--primary); color: white; }
        .items-table th { padding: 15px; text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; }
        .items-table td { padding: 15px; border-bottom: 1px solid #eee; background: #fff; }
        .stock-badge { background: #f0f7fa; color: var(--primary); padding: 4px 8px; border-radius: 4px; font-family: monospace; font-size: 12px; border: 1px dashed #c2e0ee; font-weight: 700; }

        /* Totals & Footer Grid */
        .footer-layout { display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 30px; align-items: start; }
        
        .totals-card { background: linear-gradient(135deg, var(--primary), var(--primary-dark)); border-radius: 8px; padding: 25px; color: white; box-shadow: 0 4px 15px rgba(26, 107, 140, 0.2); }
        .total-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px dashed rgba(255,255,255,0.2); font-size: 14px; }
        .grand-total-row { display: flex; justify-content: space-between; padding-top: 15px; margin-top: 10px; border-top: 2px solid var(--accent); font-size: 22px; font-weight: 700; color: #fff; }

        .signature-box { margin-top: 30px; border-top: 2px solid var(--text-dark); width: 250px; padding-top: 10px; font-weight: 700; font-size: 11px; text-transform: uppercase; color: var(--primary); }

        .social-qr-card { background: var(--bg-light); border: 1px solid var(--border); border-radius: 8px; padding: 20px; display: flex; align-items: center; gap: 20px; margin-top: 20px; }
        .qr-code { width: 90px; height: 90px; border: 4px solid white; border-radius: 4px; }
        .social-links { display: flex; flex-direction: column; gap: 8px; }
        .social-links a { text-decoration: none; color: var(--text-dark); font-weight: 600; font-size: 12px; display: flex; align-items: center; gap: 8px; }
        .social-links i { color: var(--primary); width: 16px; }

        @media print {
            body { background: white; padding: 0; }
            .invoice-container { box-shadow: none; padding: 0; width: 100%; }
            @page { margin: 0.4in; }
            .invoice-header-card, .totals-card, .items-table thead { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="invoice-container">
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
            <span class="invoice-label">Official Tax Invoice</span>
            <span class="invoice-number">{{ $sale->invoice_number }}</span>
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

    <div class="section-title"><i class="fas fa-gem"></i> Purchased Items</div>
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 15%">Stock #</th>
                <th>Item Description</th>
                <th style="text-align: center; width: 10%">Qty</th>
                <th style="text-align: center; width: 10%">Disc %</th>
                <th style="text-align: right; width: 20%">Price</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->items as $item)
            <tr>
                <td><span class="stock-badge">{{ $item->productItem->barcode ?? 'N/A' }}</span></td>
                <td><div style="font-weight: 700; color: var(--text-dark); font-size: 14px;">{{ $item->custom_description }}</div></td>
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
                    All sales are final. Returns are accepted within 14 days of purchase for exchange or store credit only, provided the item is unworn and accompanied by the original receipt.
                </p>
            </div>
            
            <div class="social-qr-card">
                <img class="qr-code" src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=https://www.thediamondsq.com/" alt="QR">
                <div class="social-links">
                    <div style="font-weight: 700; color: var(--primary); margin-bottom: 4px;">Follow Us Online</div>
                    <a href="https://www.thediamondsq.com/" target="_blank"><i class="fas fa-globe"></i> thediamondsq.com</a>
                    <a href="https://www.facebook.com/diamondsquareabq/" target="_blank"><i class="fab fa-facebook-square"></i> diamondsquareabq</a>
                    <a href="https://www.instagram.com/thediamondsq/" target="_blank"><i class="fab fa-instagram"></i> thediamondsq</a>
                </div>
            </div>

            <div class="signature-box">Your Trusted Jewelry Store In Town</div>
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
                    <span>TOTAL</span>
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

<script>
    window.onload = function() {
        setTimeout(function() { window.print(); }, 1000);
    };
</script>
</body>
</html>