<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Job Packet - {{ $sale->invoice_number }}</title>
    <style>
        body { 
            font-family: 'DejaVu Sans', sans-serif; 
            font-size: 13px; 
            color: #000; 
            line-height: 1.4;
            background-color: #f0f7ff;
        }
        .page-break { page-break-after: always; }
        
        /* Store Header Styles */
        .header { text-align: center; margin-bottom: 25px; }
        .brand-name { 
            font-size: 36px; 
            font-weight: 900; 
            margin: 0; 
            letter-spacing: 2px;
            color: #c41e3a;
        }
        .legal-name { 
            font-size: 18px; 
            font-weight: bold; 
            margin: 2px 0;
            color: #2c5282;
        }
        .store-details { 
            font-size: 12px; 
            margin-top: 5px;
            color: #2d3748;
        }

        .document-title { 
            text-align: center; 
            font-size: 22px; 
            font-weight: bold; 
            margin: 20px 0;
            border-bottom: 2px solid #000;
            padding-bottom: 5px;
            color: #b22222;
        }

        .meta-table { width: 100%; margin-bottom: 10px; }
        .meta-table td { 
            padding: 4px 0; 
            vertical-align: top;
            color: #1a365d;
        }
        .meta-table td strong {
            color: #b22222;
        }
        .right-align { text-align: right; }

        /* Job Description Table */
        .items-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .items-table th { 
            border: 2px solid black; 
            padding: 8px; 
            background-color: #fed7d7;
            text-align: left;
            color: #822727;
        }
        .items-table td { 
            border: 2px solid black; 
            padding: 10px; 
            vertical-align: top;
            background-color: #fff5f5;
        }
        
        .workshop-notes-box { 
            margin-top: 20px; 
            border: 2px solid #000; 
            min-height: 120px; 
            padding: 10px;
            background-color: #fef5e7;
        }
        
        .footer-sig { 
            margin-top: 40px; 
            border-top: 1px solid #000; 
            width: 250px; 
            text-align: center; 
            padding-top: 5px;
            color: #2c5282;
        }
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

    <div style="margin: 15px 0; padding: 10px; border: 1px solid #ccc; background-color: #e6f3ff;">
        <strong style="color: #b22222;">Customer:</strong> <span style="color: #1a365d;">{{ $sale->customer->name }} {{ $sale->customer->last_name }}</span><br>
        <strong style="color: #b22222;">Phone:</strong> <span style="color: #1a365d;">{{ $sale->customer->phone }}</span>
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
                    <strong style="color: #b22222;">Stock Number:</strong> <span style="color: #1a365d;">({{ $item->productItem?->barcode ?? 'N/A' }})</span><br>
                    <span style="color: #c41e3a;">{{ strtoupper($item->custom_description) }}</span>
                </td>
                <td class="right-align" style="color: #2e7d32;">${{ number_format($item->sold_price, 2) }}</td>
            </tr>
            @endforeach
            <tr>
                <td colspan="2" class="right-align"><strong style="color: #b22222;">TOTAL:</strong></td>
                <td class="right-align"><strong style="color: #b22222;">${{ number_format($sale->final_total, 2) }}</strong></td>
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
                <th width="25%"></th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->items as $index => $item)
            <tr>
                <td style="text-align: center;"><strong>{{ $index + 1 }}:</strong></td>
                <td>
                    @if($item->productItem?->barcode)
                        <strong style="color: #b22222;">Stock Number:</strong> <span style="color: #1a365d;">({{ $item->productItem->barcode }})</span>
                    @endif
                    <div style="font-size: 14px; margin-top: 5px; color: #c41e3a;">
                        {{ strtoupper($item->custom_description) }}
                    </div>
                    @if($sale->notes)
                        <div style="margin-top: 10px; border-top: 1px dashed #ccc; padding-top: 5px;">
                            <strong style="color: #b22222;">Instructions:</strong> <span style="color: #1a365d;">{{ $sale->notes }}</span>
                        </div>
                    @endif
                </td>
                <td></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="workshop-notes-box">
        <strong style="color: #b22222;">Workshop / Bench Notes:</strong>
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