<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Job Packet - {{ $sale->invoice_number }}</title>
    <style>
        @page {
            margin: 0.4in;
            size: Letter;
        }
        
        body { 
            font-family: 'DejaVu Sans', Arial, sans-serif; 
            font-size: 12px; 
            color: #000; 
            line-height: 1.4; 
            margin: 0;
            padding: 0.25in;
            background: #f0f9f8;
        }
        
        .page-break { page-break-after: always; }
        
        /* Header Styles */
        .header { 
            text-align: center; 
            margin-bottom: 12px; 
            padding: 12px 8px;
            background: #249E94;
            color: white;
            border-radius: 6px;
        }
        .brand-name { 
            font-size: 26px; 
            font-weight: 900; 
            margin: 0 0 3px 0; 
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .legal-name { 
            font-size: 14px; 
            font-weight: bold; 
            margin: 0 0 4px 0; 
        }
        .store-details { 
            font-size: 10px; 
            line-height: 1.2;
        }

        .document-title { 
            text-align: center; 
            font-size: 18px; 
            font-weight: bold; 
            margin: 15px 0 12px 0;
            padding: 8px 12px;
            background: #249E94;
            color: white;
            border-radius: 4px;
            letter-spacing: 0.5px;
        }

        /* Meta Info */
        .meta-table { 
            width: 100%; 
            margin-bottom: 10px; 
            background: white;
            padding: 10px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            font-size: 11px;
        }
        .meta-table td { 
            padding: 4px 0; 
            vertical-align: top; 
        }
        .right-align { text-align: right; color: #249E94; font-weight: bold; }

        /* Customer Info Box */
        .customer-box {
            background: #f0f9f8;
            border: 2px solid #249E94;
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            font-size: 11px;
        }

        /* Items Table - Customer Version */
        .customer-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 10px 0;
            font-size: 11px;
            border-radius: 4px;
            overflow: hidden;
        }
        .customer-table th { 
            border: none; 
            padding: 10px 6px; 
            background: #249E94; 
            color: white;
            font-weight: bold;
            font-size: 11px;
        }
        .customer-table td { 
            border-bottom: 1px solid #e0e0e0; 
            padding: 8px 6px; 
            background: white;
        }
        .total-row td {
            border-top: 2px solid #249E94 !important;
            background: #e8f5f3 !important;
            font-weight: bold;
            font-size: 12px;
            color: #249E94;
        }

        /* Items Table - Workshop Version */
        .workshop-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 10px 0;
            font-size: 11px;
            border-radius: 4px;
            overflow: hidden;
        }
        .workshop-table th { 
            border: none; 
            padding: 10px 6px; 
            background: #249E94; 
            color: white;
            font-weight: bold;
        }
        .workshop-table td { 
            border-bottom: 1px solid #e0e0e0; 
            padding: 10px 6px; 
            vertical-align: top;
            background: white;
        }
        .workshop-table td:last-child {
            background: #fff8e1 !important;
        }

        .notes-section {
            margin: 15px 0;
            border: 2px solid #249E94;
            padding: 15px;
            background: #f0f9f8;
            border-radius: 4px;
            min-height: 100px;
            font-size: 11px;
        }
        
        .instructions {
            margin-top: 8px;
            padding: 8px;
            background: #fff8e1;
            border-radius: 3px;
            font-size: 10px;
        }

        /* Signatures */
        .signatures {
            margin-top: 25px;
        }
        .signature-box {
            width: 45%;
            float: left;
            border-top: 2px solid #249E94;
            padding-top: 12px;
            text-align: center;
            font-weight: bold;
            font-size: 11px;
            color: #249E94;
        }
        .signature-box.right {
            float: right;
        }
        
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }

        @media print {
            .customer-table th, .workshop-table th, .header, .document-title {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            body { background: white !important; }
        }
    </style>
</head>
<body>

<!-- PAGE 1: CUSTOMER COPY -->
<div class="header">
    <h1 class="brand-name">{{ strtoupper($sale->customer->name . ' ' . $sale->customer->last_name) }}</h1>
    <div class="legal-name">{{ $sale->store->legal_name }}</div>
    <div class="store-details">
        {{ $sale->store->street }}<br>
        {{ $sale->store->city }}, {{ $sale->store->state }} {{ $sale->store->postcode }}<br>
        {{ $sale->store->email }} | {{ $sale->store->phone }}
    </div>
</div>

<div class="document-title">CUSTOMER RECEIPT</div>

<table class="meta-table">
    <tr>
        <td width="35%"><strong>Job #:</strong> {{ $sale->invoice_number }}</td>
        <td width="45%"><strong>Sales Assistant:</strong> {{ is_array($sale->sales_person_list) ? implode(', ', $sale->sales_person_list) : $sale->sales_person_list }}</td>
        <td class="right-align" width="20%">{{ $sale->store->legal_name }}</td>
    </tr>
    <tr>
        <td><strong>Date Received:</strong> {{ $sale->created_at->format('m/d/Y') }}</td>
       <td><strong>Date Required:</strong> {{ $sale->date_required ? \Carbon\Carbon::parse($sale->date_required)->format('m/d/Y') : 'ASAP' }}</td>
        <td></td>
    </tr>
</table>

<div class="customer-box">
    <strong>CUSTOMER:</strong> {{ $sale->customer->name }} {{ $sale->customer->last_name }}<br>
    <strong>Phone:</strong> {{ $sale->customer->phone }}
</div>

<table class="customer-table">
    <thead>
        <tr>
            <th width="8%">Item</th>
            <th width="67%">Description</th>
            <th width="25%">Price</th>
        </tr>
    </thead>
    <tbody>
        @foreach($sale->items as $index => $item)
        <tr>
            <td style="text-align: center; font-weight: bold;">{{ $index + 1 }}</td>
            <td>
                <strong>Stock #:</strong> {{ $item->productItem?->barcode ?? 'N/A' }}<br>
                <div style="margin-top: 2px; font-weight: 600;">
                    {{ strtoupper($item->custom_description) }}
                </div>
            </td>
            <td style="text-align: right; font-weight: bold;">${{ number_format($item->sold_price, 2) }}</td>
        </tr>
        @endforeach
        <tr class="total-row">
            <td colspan="2" style="text-align: right;">TOTAL:</td>
            <td style="text-align: right;">${{ number_format($sale->final_total, 2) }}</td>
        </tr>
    </tbody>
</table>

<div class="page-break"></div>

<!-- PAGE 2: WORKSHOP COPY -->
<div class="header">
    <h1 class="brand-name">{{ strtoupper($sale->customer->name . ' ' . $sale->customer->last_name) }}</h1>
    <div class="legal-name">{{ $sale->store->legal_name }}</div>
    <div class="store-details">
        {{ $sale->store->street }}<br>
        {{ $sale->store->city }}, {{ $sale->store->state }} {{ $sale->store->postcode }}<br>
        {{ $sale->store->email }} | {{ $sale->store->phone }}
    </div>
</div>

<div class="document-title">Merchant Receipt</div>

<table class="meta-table">
    <tr>
        <td width="35%"><strong>Job #:</strong> {{ $sale->invoice_number }}</td>
        <td width="45%"><strong>Sales Assistant:</strong> {{ is_array($sale->sales_person_list) ? implode(', ', $sale->sales_person_list) : $sale->sales_person_list }}</td>
        <td class="right-align" width="20%">{{ $sale->store->legal_name }}</td>
    </tr>
    <tr>
        <td><strong>Date Received:</strong> {{ $sale->created_at->format('m/d/Y') }}</td>
<td><strong>Date Required:</strong> {{ $sale->date_required ? \Carbon\Carbon::parse($sale->date_required)->format('m/d/Y') : 'ASAP' }}</td>
        <td></td>
    </tr>
</table>

<table class="workshop-table">
    <thead>
        <tr>
            <th width="8%">Item</th>
            <th width="72%">Description & Instructions</th>
            <th width="20%">Notes</th>
        </tr>
    </thead>
    <tbody>
        @foreach($sale->items as $index => $item)
        <tr>
            <td style="text-align: center; font-weight: bold;">{{ $index + 1 }}</td>
            <td>
                @if($item->productItem?->barcode)
                    <strong>Stock #:</strong> {{ $item->productItem->barcode }}<br>
                @endif
                <div style="font-size: 12px; margin-top: 4px; font-weight: 700;">
                    {{ strtoupper($item->custom_description) }}
                </div>
                @if($sale->notes)
                    <div class="instructions">
                        <strong>Sales Notes:</strong><br>{{ $sale->notes }}
                    </div>
                @endif
            </td>
            <td></td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="notes-section">
    <strong>WORKSHOP / BENCH NOTES:</strong><br><br><br>
</div>

<div class="signatures clearfix">
    <div class="signature-box">
        Jeweler Signature<br>
        Date: ___________
    </div>
    <div class="signature-box right">
        Quality Control<br>
        Date: ___________
    </div>
</div>

</body>
</html>
