<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Tax Invoice: {{ $sale->invoice_number }}</title>
    <style>
        body { 
            font-family: 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif; 
            font-size: 14px; 
            color: #333; 
            line-height: 1.5;
            background-color: #f8f9fa;
            margin: 0;
            padding: 20px;
        }
        
        .invoice-container { 
            max-width: 800px; 
            margin: auto; 
            padding: 30px; 
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            position: relative;
            overflow: hidden;
        }
        
        /* Decorative accent strip */
        .invoice-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, #1a6b8c 0%, #2aa3cc 50%, #1a6b8c 100%);
        }
        
        .header { 
            display: flex; 
            justify-content: space-between; 
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .store-info h1 { 
            margin: 0 0 10px 0; 
            font-size: 28px; 
            color: #1a6b8c;
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        
        .store-info p {
            color: #555;
            line-height: 1.6;
        }
        
        .invoice-header {
            background: linear-gradient(135deg, #1a6b8c, #2aa3cc);
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(42, 163, 204, 0.2);
        }
        
        .invoice-header h2 {
            margin: 0 0 8px 0;
            font-size: 20px;
            font-weight: 600;
        }
        
        .invoice-header p {
            margin: 5px 0;
            opacity: 0.9;
        }
        
        .customer-info-section { 
            display: flex; 
            justify-content: space-between; 
            margin-bottom: 25px;
            padding: 20px;
            background: #f9fbfc;
            border-radius: 8px;
            border-left: 4px solid #2aa3cc;
            box-shadow: 0 2px 5px rgba(0,0,0,0.03);
        }
        
        .customer-info-section div {
            padding-right: 20px;
        }
        
        .customer-info-section strong {
            font-size: 16px;
            color: #1a6b8c;
            display: block;
            margin-bottom: 8px;
        }
        
        .items-header { 
            background: linear-gradient(90deg, #1a6b8c, #2aa3cc);
            color: white;
            padding: 12px 20px;
            font-weight: 600;
            font-size: 16px;
            border-radius: 8px 8px 0 0;
            letter-spacing: 0.3px;
            box-shadow: 0 3px 5px rgba(0,0,0,0.05);
        }
        
        .items-table { 
            width: 100%; 
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 30px; 
            border: 1px solid #e0e7ee;
            border-radius: 0 0 8px 8px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0,0,0,0.03);
        }
        
        .items-table thead {
            background-color: #f0f7fa;
        }
        
        .items-table th { 
            text-align: left; 
            padding: 15px 20px; 
            border-bottom: 2px solid #e0e7ee;
            color: #1a6b8c;
            font-weight: 600;
        }
        
        .items-table td { 
            padding: 15px 20px; 
            vertical-align: top; 
            border-bottom: 1px solid #f0f0f0;
        }
        
        .items-table tbody tr:nth-child(even) {
            background-color: #fafcfd;
        }
        
        .items-table tbody tr:hover {
            background-color: #f0f7fa;
            transition: background-color 0.2s;
        }
        
        .totals-section { 
            float: right; 
            width: 320px; 
            background: #f9fbfc;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e0e7ee;
            box-shadow: 0 3px 10px rgba(0,0,0,0.03);
        }
        
        .total-row { 
            display: flex; 
            justify-content: space-between; 
            padding: 10px 0;
            border-bottom: 1px dashed #e0e7ee;
        }
        
        .total-row:last-child {
            border-bottom: none;
        }
        
        .grand-total { 
            background: linear-gradient(90deg, #1a6b8c, #2aa3cc);
            color: white;
            padding: 15px;
            font-weight: 700;
            border-radius: 6px;
            font-size: 18px;
            margin-top: 15px;
            box-shadow: 0 3px 8px rgba(42, 163, 204, 0.3);
        }
        
        .footer-note {
            clear: both;
            margin-top: 60px;
            font-size: 12px;
            color: #777;
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .footer-note:before {
            content: "• • •";
            letter-spacing: 4px;
            color: #2aa3cc;
            display: block;
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        /* Print-specific styles */
        @media print { 
            .no-print { display: none; }
            body {
                background-color: white;
                padding: 0;
            }
            .invoice-container {
                box-shadow: none;
                padding: 25px;
            }
        }
        
        /* Product barcode styling */
        .product-barcode {
            display: inline-block;
            background: #f0f7fa;
            padding: 3px 8px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-weight: 600;
            color: #1a6b8c;
            font-size: 13px;
            border: 1px dashed #c2e0ee;
        }
        
        /* Price styling */
        .price-cell {
            font-weight: 600;
            color: #1a6b8c;
        }
        
        /* Original price styling */
        .original-price {
            text-decoration: line-through;
            color: #999;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="header">
            <div class="store-info">
                <h1>Finest Gem LLC</h1>
                <p>6600 Menaul Blvd NE #6508<br>
                Albuquerque NM 87110 USA<br>
                Phone: 505-810-7222</p>
            </div>
            <div class="invoice-header">
                <h2>TAX INVOICE: {{ $sale->invoice_number }}</h2>
                <p>Date: {{ $sale->created_at->format('m/d/Y') }}<br>
                Receipt Number: {{ $sale->id + 8000 }}<br>
                Sales Assistant: {{ is_array($sale->sales_person_list) ? implode(', ', $sale->sales_person_list) : $sale->sales_person_list }}</p>
            </div>
        </div>

        <div class="customer-info-section">
            <div>
                <strong>{{ $sale->customer->first_name ?? 'Walk-in' }} {{ $sale->customer->last_name ?? 'Customer' }}</strong><br>
                {{ $sale->customer->street ?? '' }}<br>
                {{ $sale->customer->city ?? '' }} {{ $sale->customer->state ?? '' }} {{ $sale->customer->postcode ?? '' }}<br>
                m: {{ $sale->customer->phone ?? 'N/A' }}<br>
                {{ $sale->customer->email ?? '' }}
            </div>
        </div>

        <div class="items-header">Items Purchased</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 70%">Description</th>
                    <th style="text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sale->items as $item)
                <tr>
                    <td>
                        <span class="product-barcode">{{ $item->productItem->barcode ?? 'N/A' }}</span><br>
                        <div style="margin: 8px 0 5px 0;">{{ $item->custom_description }}</div>
                        <span style="font-size: 12px; color: #666;">
                            Discount: <span style="color: #e74c3c; font-weight: 600;">{{ $item->discount }}%</span><br>
                            Original Price: <span class="original-price">${{ number_format($item->original_price, 2) }}</span>
                        </span>
                    </td>
                    <td style="text-align: right; vertical-align: middle;" class="price-cell">
                        ${{ number_format($item->sold_price * $item->qty, 2) }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals-section">
            <div class="total-row">
                <span>Total Before Tax</span>
                <span>${{ number_format($sale->subtotal, 2) }}</span>
            </div>
            <div class="total-row">
                <span>Sales Tax (8.25%)</span>
                <span>${{ number_format($sale->tax_amount, 2) }}</span>
            </div>
            <div class="grand-total total-row">
                <span>Outstanding Balance</span>
                <span>${{ number_format($sale->final_total, 2) }}</span>
            </div>
        </div>
        
        <div class="footer-note">
            Thank you for choosing Finest Gem LLC. All sales are subject to store terms and conditions.
        </div>
    </div>

    <script>window.onload = function() { window.print(); }</script>
</body>
</html>