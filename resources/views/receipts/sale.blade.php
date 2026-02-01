<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Tax Invoice: {{ $sale->invoice_number }} | Finest Gem LLC</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@700&family=Source+Code+Pro:wght@400;500&display=swap');
        
        :root {
            --primary: #1a6b8c; /* Your preferred blue */
            --primary-dark: #12506b;
            --primary-light: #2aa3cc;
            --primary-soft: rgba(26, 107, 140, 0.1);
            --accent: #d4af37; /* Gold accent */
            --accent-dark: #b8941f;
            --text-dark: #2c3e50;
            --text-medium: #546e7a;
            --text-light: #78909c;
            --bg-light: #f8fafc;
            --bg-card: #ffffff;
            --border: #e0e7ee;
            --shadow: rgba(26, 107, 140, 0.08);
            --success: #10b981;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body { 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; 
            font-size: 14px; 
            color: var(--text-dark); 
            line-height: 1.6;
            background: linear-gradient(135deg, #f0f9ff 0%, #f8fafc 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }
        
        .invoice-container { 
            max-width: 800px; 
            margin: 0 auto; 
            padding: 30px; 
            background: var(--bg-card);
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            position: relative;
            overflow: hidden;
        }
        
        /* Decorative accent strip - like original */
        .invoice-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--primary-light) 50%, var(--primary) 100%);
        }
        
        /* Header Section */
        .header { 
            display: flex; 
            justify-content: space-between; 
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--border);
        }
        
        .store-info h1 { 
            margin: 0 0 10px 0; 
            font-size: 28px; 
            color: var(--primary);
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        
        .store-tagline {
            color: var(--primary);
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
        }
        
        .store-details {
            color: #555;
            font-size: 14px;
            line-height: 1.6;
            background: var(--bg-light);
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid var(--primary-light);
        }
        
        .store-details i {
            color: var(--primary);
            width: 16px;
            margin-right: 8px;
        }
        
        .invoice-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(42, 163, 204, 0.2);
            min-width: 300px;
        }
        
        .invoice-header h2 {
            margin: 0 0 8px 0;
            font-size: 20px;
            font-weight: 600;
            opacity: 0.9;
        }
        
        .invoice-number {
            font-size: 24px;
            font-weight: 700;
            color: white;
            display: block;
            margin-bottom: 10px;
        }
        
        .invoice-meta {
            color: rgba(255, 255, 255, 0.9);
            font-size: 13px;
            line-height: 1.6;
        }
        
        .invoice-meta i {
            margin-right: 6px;
            width: 14px;
        }
        
        /* Customer & Payment Grid */
        .info-grid { 
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .info-card {
            background: #f9fbfc;
            border-radius: 8px;
            padding: 20px;
            border-left: 4px solid var(--primary-light);
            box-shadow: 0 2px 5px rgba(0,0,0,0.03);
        }
        
        .card-title {
            font-size: 13px;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .customer-name {
            font-size: 16px;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .customer-name::before {
            content: '';
            width: 28px;
            height: 28px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            content: '\f007';
            font-size: 12px;
        }
        
        .customer-details {
            color: var(--text-medium);
            font-size: 13px;
        }
        
        .customer-details div {
            padding: 6px 0;
            border-bottom: 1px solid rgba(224, 231, 238, 0.5);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .customer-details div:last-child {
            border-bottom: none;
        }
        
        .customer-details i {
            color: var(--primary);
            width: 14px;
        }
        
        .payment-details {
            list-style: none;
        }
        
        .payment-details li {
            padding: 8px 0;
            border-bottom: 1px solid rgba(224, 231, 238, 0.5);
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
        }
        
        .payment-details li:last-child {
            border-bottom: none;
        }
        
        .status-badge {
            background: linear-gradient(135deg, var(--success) 0%, #34d399 100%);
            color: white;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            padding: 6px 15px;
            border-radius: 20px;
            display: inline-block;
            margin-top: 10px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        /* Table Section */
        .section-title { 
            font-size: 16px;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--border);
        }
        
        .items-table { 
            width: 100%; 
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 30px; 
            border: 1px solid var(--border);
            border-radius: 0 0 8px 8px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0,0,0,0.03);
        }
        
        .items-table thead {
            background: linear-gradient(90deg, var(--primary), var(--primary-light));
        }
        
        .items-table th { 
            text-align: left; 
            padding: 15px 20px; 
            color: white;
            font-weight: 600;
            font-size: 13px;
            letter-spacing: 0.3px;
        }
        
        .items-table td { 
            padding: 15px 20px; 
            border-bottom: 1px solid #f0f0f0;
        }
        
        .items-table tbody tr:nth-child(even) {
            background-color: #fafcfd;
        }
        
        .items-table tbody tr:hover {
            background-color: #f0f7fa;
        }
        
        .stock-badge {
            background: #f0f7fa;
            color: var(--primary);
            padding: 3px 8px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-weight: 600;
            font-size: 12px;
            border: 1px dashed #c2e0ee;
            display: inline-block;
        }
        
        .item-description {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 5px;
            font-size: 14px;
        }
        
        .item-meta {
            font-size: 12px;
            color: #666;
        }
        
        .discount-tag {
            color: #e74c3c;
            font-weight: 600;
        }
        
        .original-price {
            text-decoration: line-through;
            color: #999;
            margin-right: 4px;
        }
        
        /* Totals Section */
        .totals-container { 
            display: flex;
            justify-content: flex-end;
            margin-bottom: 30px;
        }
        
        .totals-card { 
            width: 320px; 
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 3px 8px rgba(42, 163, 204, 0.3);
            color: white;
        }
        
        .total-row { 
            display: flex; 
            justify-content: space-between; 
            padding: 10px 0;
            border-bottom: 1px dashed rgba(255, 255, 255, 0.2);
        }
        
        .total-row i {
            margin-right: 8px;
            width: 14px;
        }
        
        .grand-total-row { 
            display: flex; 
            justify-content: space-between; 
            padding: 15px 0;
            margin-top: 10px;
            border-top: 2px solid rgba(255, 255, 255, 0.3);
            color: white;
            font-size: 18px;
            font-weight: 700;
        }
        
        /* Footer */
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
            text-align: center;
            color: #777;
            font-size: 12px;
        }
        
        .footer-title {
            font-size: 12px;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .footer-text {
            line-height: 1.6;
            max-width: 650px;
            margin: 0 auto 15px;
        }
        
        .footer-badges {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        
        .badge {
            display: flex;
            align-items: center;
            gap: 6px;
            color: var(--text-medium);
            font-size: 11px;
        }
        
        .badge i {
            color: var(--primary);
        }
        
        /* Print Styles */
        @media print { 
            .no-print { display: none; }
            body {
                background-color: white;
                padding: 0;
            }
            .invoice-container {
                box-shadow: none;
                padding: 25px;
                border: none;
            }
            
            /* Force colors for printing */
            .invoice-header, .totals-card, .items-table thead {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            body {
                padding: 15px;
            }
            
            .invoice-container {
                padding: 20px;
            }
            
            .header {
                flex-direction: column;
                gap: 20px;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .items-table {
                display: block;
                overflow-x: auto;
            }
            
            .totals-card {
                width: 100%;
            }
            
            .footer-badges {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="invoice-container">
        <!-- Header -->
        <div class="header">
            <div class="store-info">
                <h1>Finest Gem LLC</h1>
                <div class="store-tagline">Premium Jewelry & Luxury Timepieces</div>
                <div class="store-details">
                    <div><i class="fas fa-map-marker-alt"></i> 6600 Menaul Blvd NE #6508</div>
                    <div><i class="fas fa-city"></i> Albuquerque, NM 87110 USA</div>
                    <div><i class="fas fa-phone"></i> 505-810-7222</div>
                    <div><i class="fas fa-envelope"></i> finestgem@email.com</div>
                </div>
            </div>
            <div class="invoice-header">
                <h2>TAX INVOICE</h2>
                <span class="invoice-number">{{ $sale->invoice_number }}</span>
                <div class="invoice-meta">
                    <div><i class="fas fa-calendar"></i> Date: {{ $sale->created_at->format('m/d/Y') }}</div>
                    <div><i class="fas fa-clock"></i> Time: {{ $sale->created_at->format('h:i A') }}</div>
                    <div><i class="fas fa-receipt"></i> Receipt: #{{ $sale->id + 8000 }}</div>
                    <div><i class="fas fa-user-tie"></i> Sales: {{ $sale->sales_person_list }}</div>
                </div>
            </div>
        </div>

        <!-- Customer & Payment Info -->
        <div class="info-grid">
          <div class="info-card">
    <div class="card-title">
        <i class="fas fa-credit-card"></i> Payment Information
    </div>
    <ul class="payment-details">
        <li>
            <span>Payment Method:</span>
            <span style="font-weight: 600; color: var(--primary);">
                <i class="fas fa-wallet"></i> {{ strtoupper(str_replace('_', ' ', $sale->payment_method)) }}
            </span>
        </li>
        <li>
            <span>Invoice Status:</span>
            @if($sale->payment_method === 'laybuy' && ($sale->laybuy->balance_due ?? 1) > 0)
                <span style="font-weight: 600; color: #f59e0b;">
                    <i class="fas fa-clock"></i>LAYBY PENDING
                </span>
            @else
                <span style="font-weight: 600; color: var(--success);">
                    <i class="fas fa-check-circle"></i> PAID IN FULL
                </span>
            @endif
        </li>
    </ul>
    
    @if($sale->payment_method === 'laybuy' && ($sale->laybuy->balance_due ?? 1) > 0)
        <div class="status-badge" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
            <i class="fas fa-hourglass-half"></i> Installment Plan Active
        </div>
    @else
        <div class="status-badge">
            <i class="fas fa-check"></i> Payment Completed
        </div>
    @endif
</div>

            <div class="info-card">
                <div class="card-title">
                    <i class="fas fa-credit-card"></i> Payment Information
                </div>
                <ul class="payment-details">
                    <li>
                        <span>Payment Method:</span>
                        <span style="font-weight: 600; color: var(--primary);">
                            <i class="fas fa-wallet"></i> {{ strtoupper(str_replace('_', ' ', $sale->payment_method ?? 'Cash')) }}
                        </span>
                    </li>
                    <li>
                        <span>Transaction ID:</span>
                        <span style="font-family: 'Courier New', monospace; font-weight: 600;">
                            {{ strtoupper(substr(md5($sale->id), 0, 12)) }}
                        </span>
                    </li>
                    <li>
                        <span>Invoice Status:</span>
                        <span style="font-weight: 600; color: var(--success);">
                            <i class="fas fa-check-circle"></i> PAID
                        </span>
                    </li>
                </ul>
                <div class="status-badge">
                    <i class="fas fa-check"></i> Payment Completed
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <div class="section-title">
            <i class="fas fa-shopping-bag"></i> Items Purchased
        </div>
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 15%">Stock #</th>
                    <th>Description</th>
                    <th style="text-align: center; width: 8%">Qty</th>
                    <th style="text-align: right; width: 15%">Unit Price</th>
                    <th style="text-align: right; width: 18%">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sale->items as $item)
                <tr>
                    <td>
                        <span class="stock-badge">{{ $item->productItem->barcode ?? 'N/A' }}</span>
                    </td>
                    <td>
                        <div class="item-description">{{ $item->custom_description }}</div>
                        <div class="item-meta">
                            @if($item->discount > 0)
                                <span class="discount-tag">
                                    <i class="fas fa-tag"></i> {{ $item->discount }}% OFF
                                </span>
                                <br>
                            @endif
                            @if($item->original_price && $item->original_price > $item->sold_price)
                                <span style="color: #666;">
                                    <i class="fas fa-tag"></i> Original: 
                                    <span class="original-price">${{ number_format($item->original_price, 2) }}</span>
                                </span>
                            @endif
                        </div>
                    </td>
                    <td style="text-align: center; font-weight: 600; color: var(--primary);">
                        <span style="background: rgba(26, 107, 140, 0.1); padding: 4px 8px; border-radius: 4px;">
                            {{ $item->qty }}
                        </span>
                    </td>
                    <td style="text-align: right; color: var(--text-dark); font-weight: 600;">
                        ${{ number_format($item->sold_price, 2) }}
                    </td>
                    <td style="text-align: right; font-weight: 700; color: var(--text-dark);">
                        ${{ number_format($item->sold_price * $item->qty, 2) }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals Section -->
      <div class="totals-container">
    <div class="totals-card">
        <div class="total-row">
            <span><i class="fas fa-receipt"></i> Subtotal</span>
            <span>${{ number_format($sale->subtotal, 2) }}</span>
        </div>
        <div class="total-row">
            <span><i class="fas fa-percentage"></i> Sales Tax</span>
            <span>${{ number_format($sale->tax_amount, 2) }}</span>
        </div>
        
        @if($sale->has_trade_in)
        <div class="total-row" style="color: #ffeb3b;">
            <span><i class="fas fa-exchange-alt"></i> Trade-In Credit</span>
            <span>-${{ number_format($sale->trade_in_value, 2) }}</span>
        </div>
        @endif

        <div class="grand-total-row">
            <span><i class="fas fa-crown"></i> GRAND TOTAL</span>
            <span>${{ number_format($sale->final_total, 2) }}</span>
        </div>

        <div class="grand-total-row" style="border-top: 1px dashed rgba(255,255,255,0.5); margin-top: 5px; background: rgba(0,0,0,0.1); border-radius: 0 0 8px 8px;">
    <span><i class="fas fa-hand-holding-usd"></i> REMAINING BALANCE</span>
    <span>
        @if($sale->payment_method === 'laybuy')
            {{-- If Laybuy exists, show the balance; otherwise show the full total as pending --}}
            ${{ number_format($sale->laybuy->balance_due ?? $sale->final_total, 2) }}
        @else
            $0.00
        @endif
    </span>
</div>
    </div>
</div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-title">
                <i class="fas fa-gem"></i> Thank You For Your Business
            </div>
            <div class="footer-text">
                Thank you for choosing Finest Gem LLC. All items are certified authentic and undergo rigorous quality inspection. 
                Returns accepted within 14 days with original receipt and packaging. All sales are subject to store terms and conditions.
            </div>
            <div class="footer-badges">
                <div class="badge">
                    <i class="fas fa-shield-alt"></i> Authenticity Guaranteed
                </div>
                <div class="badge">
                    <i class="fas fa-lock"></i> Secure Transaction
                </div>
                <div class="badge">
                    <i class="fas fa-clock"></i> Generated: {{ date('m/d/Y h:i A') }}
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-print after delay
            setTimeout(function() {
                window.print();
            }, 1000);
            
            // Print event listener
            window.addEventListener('afterprint', function() {
                console.log('Invoice printed successfully.');
            });
        });
    </script>
</body>
</html>