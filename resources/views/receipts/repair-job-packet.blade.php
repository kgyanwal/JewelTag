<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Repair Job Packet - {{ $repair->repair_no }}</title>
    <style>
        @page {
            margin: 0.3in;
            size: Letter;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            color: #000;
            line-height: 1.3;
            margin: 0;
            padding: 0;
            background: white;
        }

        /* ── CLEAN 2-PAGE FIX ── */
        .page { page-break-after: always; clear: both; }
        .page:last-child { page-break-after: avoid; }

        /* ── Header ── */
        .header {
            text-align: center;
            margin-bottom: 8px;
            padding: 8px 6px;
            background: #249E94;
            color: white;
            border-radius: 6px;
        }
        .brand-name {
            font-size: 22px;
            font-weight: 900;
            margin: 0 0 2px 0;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .legal-name {
            font-size: 12px;
            font-weight: bold;
            margin: 0 0 3px 0;
        }
        .store-details {
            font-size: 9px;
            line-height: 1.2;
        }

        .document-title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin: 8px 0;
            padding: 6px 10px;
            background: #249E94;
            color: white;
            border-radius: 4px;
            letter-spacing: 0.5px;
        }

        .badge-row {
            text-align: center;
            margin-bottom: 6px;
        }
        .badge {
            display: inline-block;
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 9px;
            font-weight: 900;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin: 0 4px;
        }
        .badge-warranty { background: #dc2626; color: white; }
        .badge-store    { background: #0284c7; color: white; }

        .meta-table {
            width: 100%;
            margin-bottom: 6px;
            background: white;
            padding: 8px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            font-size: 10px;
            border-spacing: 0;
            border-collapse: collapse;
        }
        .meta-table td {
            padding: 3px 6px;
            vertical-align: top;
        }
        .right-align {
            text-align: right;
            color: #249E94;
            font-weight: bold;
        }

        .customer-box {
            background: #f0f9f8;
            border: 2px solid #249E94;
            padding: 7px 12px;
            margin: 6px 0;
            border-radius: 4px;
            font-size: 10px;
        }
        .customer-box .cust-name {
            font-size: 13px;
            font-weight: 900;
            text-transform: uppercase;
            color: #249E94;
            margin-bottom: 3px;
        }

        /* ── Customer Copy Table (shows price) ── */
        .customer-table {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0;
            font-size: 10px;
        }
        .customer-table th {
            border: none;
            padding: 8px 6px;
            background: #249E94;
            color: white;
            font-weight: bold;
            font-size: 10px;
            text-align: left;
        }
        .customer-table td {
            border-bottom: 1px solid #e0e0e0;
            padding: 5px 6px;
            background: white;
            vertical-align: top;
        }
        .total-row td {
            border-top: 2px solid #249E94 !important;
            background: #e8f5f3 !important;
            font-weight: bold;
            font-size: 11px;
            color: #249E94;
        }

        /* ── Workshop Table (NO price shown) ── */
        .workshop-table {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0;
            font-size: 10px;
        }
        .workshop-table th {
            border: none;
            padding: 8px 6px;
            background: #249E94;
            color: white;
            font-weight: bold;
            text-align: left;
        }
        .workshop-table td {
            border-bottom: 1px solid #e0e0e0;
            padding: 8px 6px;
            vertical-align: top;
            background: white;
        }
        .workshop-table td.notes-col {
            background: #fff8e1 !important;
        }

        .status-pill {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 8px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .status-received  { background: #e5e7eb; color: #374151; }
        .status-progress  { background: #dbeafe; color: #1d4ed8; }
        .status-ready     { background: #dcfce7; color: #166534; }
        .status-delivered { background: #f3e8ff; color: #7e22ce; }

        .notes-section {
            margin: 10px 0;
            border: 2px solid #249E94;
            padding: 10px;
            background: #f0f9f8;
            border-radius: 4px;
            min-height: 70px;
            font-size: 10px;
        }

        .warranty-box {
            background: #fef2f2;
            border: 2px dashed #dc2626;
            padding: 6px 10px;
            border-radius: 4px;
            text-align: center;
            color: #dc2626;
            font-weight: 900;
            font-size: 12px;
            margin: 6px 0;
            letter-spacing: 0.05em;
        }

        .signatures { margin-top: 20px; }
        .signature-box {
            width: 45%;
            float: left;
            border-top: 2px solid #249E94;
            padding-top: 10px;
            text-align: center;
            font-weight: bold;
            font-size: 10px;
            color: #249E94;
        }
        .signature-box.right { float: right; }
        .clearfix::after { content: ""; display: table; clear: both; }

        .divider {
            border: none;
            border-top: 1px dashed #249E94;
            margin: 8px 0;
        }

        @media print {
            .header, .document-title,
            .customer-table th, .workshop-table th {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</head>
<body>

@php
    $repairItems = $repair->items ?? [];

    if (empty($repairItems) && !empty($repair->item_description)) {
        $repairItems = [[
            'item_description' => $repair->item_description,
            'reported_issue'   => $repair->reported_issue,
            'estimated_cost'   => $repair->estimated_cost,
            'final_cost'       => $repair->final_cost,
            'is_warranty'      => $repair->is_warranty,
        ]];
    }

    $grandEstimated = collect($repairItems)->sum(fn($i) => floatval($i['estimated_cost'] ?? 0));
    $grandFinal     = collect($repairItems)->sum(fn($i) => floatval($i['final_cost'] ?? 0));

    $statusMap = [
        'received'    => ['label' => 'Received',         'class' => 'status-received'],
        'in_progress' => ['label' => 'In Progress',      'class' => 'status-progress'],
        'ready'       => ['label' => 'Ready for Pickup', 'class' => 'status-ready'],
        'delivered'   => ['label' => 'Delivered',        'class' => 'status-delivered'],
    ];
    $st = $statusMap[$repair->status] ?? ['label' => ucfirst($repair->status), 'class' => 'status-received'];
@endphp

{{-- ═══════════════════════════════════════════
     PAGE 1 — CUSTOMER COPY (SHOWS PRICE)
════════════════════════════════════════════ --}}
<div class="page">

    <div class="header">
        <h1 class="brand-name">{{ strtoupper($repair->customer->name . ' ' . $repair->customer->last_name) }}</h1>
        <div class="legal-name">{{ $repair->store?->legal_name ?? 'Diamond Square' }}</div>
        <div class="store-details">
            {{ $repair->store?->street }},
            {{ $repair->store?->city }}, {{ $repair->store?->state }} {{ $repair->store?->postcode }}
            &nbsp;|&nbsp; {{ $repair->store?->email }} &nbsp;|&nbsp; {{ $repair->store?->phone }}
        </div>
    </div>

    <div class="document-title">REPAIR — CUSTOMER RECEIPT</div>

    <div class="badge-row">
        @if($repair->is_warranty)
            <span class="badge badge-warranty">★ WARRANTY REPAIR — NO CHARGE ★</span>
        @endif
        @if($repair->is_from_store_stock)
            <span class="badge badge-store">Store Purchase</span>
        @endif
    </div>

    <table class="meta-table">
        <tr>
            <td width="33%"><strong>Repair #:</strong> {{ $repair->repair_no }}</td>
            <td width="34%"><strong>Staff:</strong> {{ $repair->salesPerson?->name ?? 'N/A' }}</td>
            <td class="right-align" width="33%">{{ $repair->store?->legal_name ?? 'Diamond Square' }}</td>
        </tr>
        <tr>
            <td><strong>Date Received:</strong> {{ $repair->created_at->format('m/d/Y') }}</td>
            <td><strong>Status:</strong> <span class="status-pill {{ $st['class'] }}">{{ $st['label'] }}</span></td>
            <td class="right-align"><strong>Date:</strong> {{ now()->format('m/d/Y') }}</td>
        </tr>
    </table>

    <div class="customer-box">
        <div class="cust-name">{{ $repair->customer->name }} {{ $repair->customer->last_name }}</div>
        <strong>Phone:</strong> {{ $repair->customer->phone ?? 'N/A' }}
        @if($repair->customer->email)
            &nbsp;&nbsp;|&nbsp;&nbsp;<strong>Email:</strong> {{ $repair->customer->email }}
        @endif
    </div>

    @if($repair->is_warranty)
    <div class="warranty-box">⚠ WARRANTY REPAIR — Customer will NOT be charged ⚠</div>
    @endif

    <table class="customer-table">
        <thead>
            <tr>
                <th width="5%">#</th>
                <th width="40%">Item Description</th>
                <th width="33%">Issue Reported</th>
                <th width="22%" style="text-align:right;">Cost</th>
            </tr>
        </thead>
        <tbody>
            @foreach($repairItems as $index => $rItem)
            <tr>
                <td style="text-align:center;font-weight:bold;">{{ $index + 1 }}</td>
                <td>
                    @if($index === 0 && $repair->is_from_store_stock && $repair->originalProduct)
                        <strong>Stock #:</strong> {{ $repair->originalProduct->barcode }}<br>
                    @endif
                    <div style="font-weight:700;font-size:11px;margin-top:2px;">
                        {{ strtoupper($rItem['item_description'] ?? '') }}
                    </div>
                </td>
                <td>{{ $rItem['reported_issue'] ?? '—' }}</td>
                <td style="text-align:right;">
                    @if(!empty($rItem['is_warranty']))
                        <span style="color:#dc2626;font-weight:bold;">WARRANTY</span>
                    @elseif(!empty($rItem['final_cost']) && floatval($rItem['final_cost']) > 0)
                        <strong style="color:#249E94;">${{ number_format($rItem['final_cost'], 2) }}</strong>
                    @elseif(!empty($rItem['estimated_cost']) && floatval($rItem['estimated_cost']) > 0)
                        <span style="color:#888;">Est. ${{ number_format($rItem['estimated_cost'], 2) }}</span>
                    @else
                        <span style="color:#888;">TBD</span>
                    @endif
                </td>
            </tr>
            @endforeach

            @if(count($repairItems) > 1)
            <tr class="total-row">
                <td colspan="3" style="text-align:right;padding-right:10px;"><strong>TOTAL</strong></td>
                <td style="text-align:right;">
                    @if($repair->is_warranty)
                        <span style="color:#dc2626;">WARRANTY — $0.00</span>
                    @elseif($grandFinal > 0)
                        ${{ number_format($grandFinal, 2) }}
                    @else
                        Est. ${{ number_format($grandEstimated, 2) }}
                    @endif
                </td>
            </tr>
            @endif
        </tbody>
    </table>

    <hr class="divider">

    <div style="font-size:9px;color:#555;text-align:center;margin-top:6px;">
        Please keep this receipt. Present it when collecting your item.
        &nbsp;|&nbsp; <strong>{{ $repair->store?->phone ?? '' }}</strong>
    </div>

    <div class="signatures clearfix" style="margin-top:16px;">
        <div class="signature-box">Customer Signature<br>Date: ___________</div>
        <div class="signature-box right">Staff Initials<br>Date: ___________</div>
    </div>

</div>{{-- end page 1 --}}

{{-- ═══════════════════════════════════════════
     PAGE 2 — WORKSHOP COPY (NO PRICE SHOWN)
════════════════════════════════════════════ --}}
<div class="page">

    <div class="header">
        <h1 class="brand-name">{{ strtoupper($repair->customer->name . ' ' . $repair->customer->last_name) }}</h1>
        <div class="legal-name">{{ $repair->store?->legal_name ?? 'Diamond Square' }}</div>
        <div class="store-details">
            {{ $repair->store?->street }},
            {{ $repair->store?->city }}, {{ $repair->store?->state }} {{ $repair->store?->postcode }}
            &nbsp;|&nbsp; {{ $repair->store?->email }} &nbsp;|&nbsp; {{ $repair->store?->phone }}
        </div>
    </div>

    <div class="document-title">REPAIR — WORKSHOP COPY</div>

    <div class="badge-row">
        @if($repair->is_warranty)
            <span class="badge badge-warranty">★ WARRANTY — NO CHARGE ★</span>
        @endif
        @if($repair->is_from_store_stock)
            <span class="badge badge-store">Store Purchase</span>
        @endif
    </div>

    <table class="meta-table">
        <tr>
            <td width="33%"><strong>Repair #:</strong> {{ $repair->repair_no }}</td>
            <td width="34%"><strong>Staff:</strong> {{ $repair->salesPerson?->name ?? 'N/A' }}</td>
            <td class="right-align" width="33%">Date In: {{ $repair->created_at->format('m/d/Y') }}</td>
        </tr>
        <tr>
            <td><strong>Status:</strong> <span class="status-pill {{ $st['class'] }}">{{ $st['label'] }}</span></td>
            <td>&nbsp;</td>
            <td class="right-align" style="color:#94a3b8;font-size:9px;">Internal use only</td>
        </tr>
    </table>

    <div class="customer-box">
        <div class="cust-name">{{ $repair->customer->name }} {{ $repair->customer->last_name }}</div>
        <strong>Phone:</strong> {{ $repair->customer->phone ?? 'N/A' }}
    </div>

    @if($repair->is_warranty)
    <div class="warranty-box">⚠ WARRANTY REPAIR — DO NOT CHARGE CUSTOMER ⚠</div>
    @endif

    {{-- Workshop table — NO price column --}}
    <table class="workshop-table">
        <thead>
            <tr>
                <th width="6%">#</th>
                <th width="44%">Item Description</th>
                <th width="35%">Issue / Instructions</th>
                <th width="15%">Bench Notes</th>
            </tr>
        </thead>
        <tbody>
            @foreach($repairItems as $index => $rItem)
            <tr>
                <td style="text-align:center;font-weight:bold;">{{ $index + 1 }}</td>
                <td>
                    @if($index === 0 && $repair->is_from_store_stock && $repair->originalProduct)
                        <strong>Stock #:</strong> {{ $repair->originalProduct->barcode }}<br>
                    @endif
                    <div style="font-size:11px;font-weight:700;margin-top:3px;text-transform:uppercase;">
                        {{ $rItem['item_description'] ?? '' }}
                    </div>
                    @if(!empty($rItem['is_warranty']))
                        <div style="margin-top:3px;">
                            <span style="background:#dc2626;color:white;padding:1px 5px;border-radius:3px;font-size:8px;font-weight:900;">WARRANTY</span>
                        </div>
                    @endif
                </td>
                <td>
                    <strong>Issue:</strong><br>
                    {{ $rItem['reported_issue'] ?? '—' }}
                </td>
                <td class="notes-col">&nbsp;</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="notes-section">
        <strong>JEWELER BENCH NOTES / WORK CARRIED OUT:</strong><br><br><br>
    </div>

    <div style="background:white;border:1px solid #e0e0e0;border-radius:4px;padding:8px 12px;margin:8px 0;font-size:10px;">
        <strong>MATERIALS USED:</strong>
        <table style="width:100%;margin-top:5px;border-collapse:collapse;">
            <tr>
                <th style="border-bottom:1px solid #e0e0e0;padding:3px 4px;text-align:left;color:#249E94;font-size:9px;">Material</th>
                <th style="border-bottom:1px solid #e0e0e0;padding:3px 4px;text-align:left;color:#249E94;font-size:9px;">Weight / Qty</th>
                <th style="border-bottom:1px solid #e0e0e0;padding:3px 4px;text-align:left;color:#249E94;font-size:9px;">Notes</th>
            </tr>
            <tr><td style="padding:7px 4px;">&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
            <tr><td style="padding:7px 4px;border-top:1px solid #f0f0f0;">&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
        </table>
    </div>

    <div class="signatures clearfix">
        <div class="signature-box">Jeweler Signature<br>Date: ___________</div>
        <div class="signature-box right">Quality Control<br>Date: ___________</div>
    </div>

</div>{{-- end page 2 --}}

</body>
</html>