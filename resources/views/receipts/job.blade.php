<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Job Packet - {{ $sale->invoice_number }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 13px; color: #000; line-height: 1.4; }
        .page-break { page-break-after: always; }
        
        /* Store Header Styles */
        .header { text-align: center; margin-bottom: 25px; }
        .brand-name { font-size: 36px; font-weight: 900; margin: 0; letter-spacing: 2px; }
        .legal-name { font-size: 18px; font-weight: bold; margin: 2px 0; }
        .store-details { font-size: 12px; margin-top: 5px; }

        .document-title { 
            text-align: center; 
            font-size: 22px; 
            font-weight: bold; 
            margin: 20px 0;
            border-bottom: 2px solid #000;
            padding-bottom: 5px;
        }

        .meta-table { width: 100%; margin-bottom: 10px; }
        .meta-table td { padding: 4px 0; vertical-align: top; }
        .right-align { text-align: right; }

        /* Job Description Table */
        .items-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .items-table th { border: 2px solid black; padding: 8px; background-color: #f2f2f2; text-align: left; }
        .items-table td { border: 2px solid black; padding: 10px; vertical-align: top; }
        
        .workshop-notes-box { 
            margin-top: 20px; 
            border: 2px solid #000; 
            min-height: 120px; 
            padding: 10px; 
        }
        
        .footer-sig { margin-top: 40px; border-top: 1px solid #000; width: 250px; text-align: center; padding-top: 5px; }
    </style>
</head>
<body>

    <div class="header">
        <h1 class="brand-name">{{ strtoupper($sale->customer->name . ' ' . $sale->customer->last_name) }}</h1>
        <div class="legal-name">{{ $sale->store->legal_name }}</div>
        <div class="store-details">
            {{ $sale->store->street }}<br>
            {{ $sale->store->city }}, {{ $sale->store->state }} {{ $sale->store->postcode }} {{ $sale->store->country }}<br>
            {{ $sale->store->email }}<br>
            Phone: {{ $sale->store->phone }}
        </div>
    </div>

    <div class="document-title">Job Receipt For Customer</div>

    <table class="meta-table">
        <tr>
            <td width="35%"><strong>Job No.:</strong> {{ $sale->invoice_number }}</td>
            <td width="45%"><strong>Sales Assistant:</strong> {{ is_array($sale->sales_person_list) ? implode(', ', $sale->sales_person_list) : $sale->sales_person_list }}</td>
            <td class="right-align" width="20%">({{ $sale->store->legal_name }})</td>
        </tr>
        <tr>
            <td><strong>Date Received:</strong> {{ $sale->created_at->format('m/d/Y') }}</td>
            <td><strong>Date Required:</strong> {{ $sale->date_required ?? '-' }}</td>
            <td></td>
        </tr>
    </table>

    <div style="margin: 15px 0; padding: 10px; border: 1px solid #ccc;">
        <strong>Customer:</strong> {{ $sale->customer->name }} {{ $sale->customer->last_name }}<br>
        <strong>Phone:</strong> {{ $sale->customer->phone }}
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th width="10%">Item#</th>
                <th width="70%">Description</th>
                <th width="20%">Price</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->items as $index => $item)
            <tr>
                <td style="text-align: center;"><strong>{{ $index + 1 }}:</strong></td>
                <td>
                    <strong>Stock Number:</strong> ({{ $item->productItem?->barcode ?? 'N/A' }})<br>
                    {{ strtoupper($item->custom_description) }}
                </td>
                <td class="right-align">${{ number_format($item->sold_price, 2) }}</td>
            </tr>
            @endforeach
            <tr>
                <td colspan="2" class="right-align"><strong>TOTAL:</strong></td>
                <td class="right-align"><strong>${{ number_format($sale->final_total, 2) }}</strong></td>
            </tr>
        </tbody>
    </table>

    <div class="page-break"></div>

    <div class="header">
        <h1 class="brand-name">{{ strtoupper($sale->customer->name . ' ' . $sale->customer->last_name) }}</h1>
        <div class="legal-name">{{ $sale->store->legal_name }}</div>
        <div class="store-details">
            {{ $sale->store->street }}<br>
            {{ $sale->store->city }}, {{ $sale->store->state }} {{ $sale->store->postcode }} USA<br>
            {{ $sale->store->email }}<br>
            Phone: {{ $sale->store->phone }}
        </div>
    </div>

    <div class="document-title">Job Receipt For Store</div>

    <table class="meta-table">
        <tr>
            <td width="35%"><strong>Job No.:</strong> {{ $sale->invoice_number }}</td>
            <td width="45%"><strong>Sales Assistant:</strong> {{ is_array($sale->sales_person_list) ? implode(', ', $sale->sales_person_list) : $sale->sales_person_list }}</td>
            <td class="right-align" width="20%">({{ $sale->store->legal_name }})</td>
        </tr>
        <tr>
            <td><strong>Date Received:</strong> {{ $sale->created_at->format('m/d/Y') }}</td>
            <td><strong>Date Required:</strong> {{ $sale->date_required ?? '-' }}</td>
            <td></td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th width="10%">Item#</th>
                <th width="65%">Description</th>
                <th width="25%"></th> </tr>
        </thead>
        <tbody>
            @foreach($sale->items as $index => $item)
            <tr>
                <td style="text-align: center;"><strong>{{ $index + 1 }}:</strong></td>
                <td>
                    @if($item->productItem?->barcode)
                        <strong>Stock Number:</strong> ({{ $item->productItem->barcode }})
                    @endif
                    <div style="font-size: 14px; margin-top: 5px;">
                        {{ strtoupper($item->custom_description) }}
                    </div>
                    @if($sale->notes)
                        <div style="margin-top: 10px; border-top: 1px dashed #ccc; padding-top: 5px;">
                            <strong>Instructions:</strong> {{ $sale->notes }}
                        </div>
                    @endif
                </td>
                <td></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="workshop-notes-box">
        <strong>Workshop / Bench Notes:</strong>
    </div>

    <table width="100%" style="margin-top: 30px;">
        <tr>
            <td>
                <div class="footer-sig">Jeweler Signature</div>
            </td>
            <td class="right-align">
                <div class="footer-sig" style="margin-left: auto;">Quality Control Check</div>
            </td>
        </tr>
    </table>

</body>
</html>