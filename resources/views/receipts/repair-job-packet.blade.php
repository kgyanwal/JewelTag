<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Repair Job Packet - {{ $repair->repair_no }}</title>
    <style>
        @page {
            margin: 0.25in;
            size: Letter;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10px;
            color: #000;
            line-height: 1.2;
            margin: 0;
            padding: 0;
            background: white;
        }

        .page {
            page-break-after: always;
            clear: both;
        }

        .page:last-child {
            page-break-after: avoid;
        }

        .header {
            text-align: center;
            margin-bottom: 5px;
            padding: 5px 6px;
            background: #ffffff;
            color: #1a6b65;
            border: 2px solid #249E94;
            border-radius: 6px;
        }

        .brand-name {
            font-size: 18px;
            font-weight: 900;
            margin: 0 0 2px 0;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #1a6b65;
        }

        .legal-name {
            font-size: 11px;
            font-weight: bold;
            margin: 0 0 2px 0;
            color: #249E94;
        }

        .store-details {
            font-size: 8.5px;
            line-height: 1.15;
            color: #444;
        }

        .document-title {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            margin: 5px 0;
            padding: 4px 10px;
            background: #ffffff;
            color: #1a6b65;
            border: 2px solid #249E94;
            border-radius: 4px;
            letter-spacing: 0.5px;
        }

        .badge-row {
            text-align: center;
            margin-bottom: 4px;
        }

        .badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 8.5px;
            font-weight: 900;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin: 0 3px;
        }

        .badge-warranty {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #dc2626;
        }

        .badge-store {
            background: #eff6ff;
            color: #0284c7;
            border: 1px solid #0284c7;
        }

        .meta-table {
            width: 100%;
            margin-bottom: 4px;
            background: white;
            padding: 6px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            font-size: 9.5px;
            border-spacing: 0;
            border-collapse: collapse;
        }

        .meta-table td {
            padding: 2px 6px;
            vertical-align: top;
        }

        .right-align {
            text-align: right;
            color: #249E94;
            font-weight: bold;
        }

        .customer-box {
            background: #f9fefe;
            border: 1.5px solid #249E94;
            padding: 5px 10px;
            margin: 4px 0;
            border-radius: 4px;
            font-size: 9.5px;
        }

        .customer-box .cust-name {
            font-size: 12px;
            font-weight: 900;
            text-transform: uppercase;
            color: #1a6b65;
            margin-bottom: 2px;
        }

        .customer-table {
            width: 100%;
            border-collapse: collapse;
            margin: 5px 0;
            font-size: 9.5px;
        }

        .customer-table th {
            border: none;
            padding: 5px 6px;
            background: #e8f5f4;
            color: #1a6b65;
            font-weight: bold;
            font-size: 9.5px;
            text-align: left;
            border-bottom: 2px solid #249E94;
        }

        .customer-table td {
            border-bottom: 1px solid #e0e0e0;
            padding: 4px 6px;
            background: white;
            vertical-align: top;
        }

        .total-row td {
            border-top: 2px solid #249E94 !important;
            background: #e8f5f3 !important;
            font-weight: bold;
            font-size: 10px;
            color: #1a6b65;
        }

        .workshop-table {
            width: 100%;
            border-collapse: collapse;
            margin: 5px 0;
            font-size: 9.5px;
        }

        .workshop-table th {
            border: none;
            padding: 5px 6px;
            background: #e8f5f4;
            color: #1a6b65;
            font-weight: bold;
            text-align: left;
            border-bottom: 2px solid #249E94;
        }

        .workshop-table td {
            border-bottom: 1px solid #e0e0e0;
            padding: 5px 6px;
            vertical-align: top;
            background: white;
        }

        .workshop-table td.notes-col {
            background: #fffdf0 !important;
        }

        .service-badge {
            display: inline-block;
            padding: 1px 6px;
            border-radius: 4px;
            font-size: 7.5px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin: 1px 2px 1px 0;
        }

        .badge-job {
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
        }

        .badge-metal {
            background: #fef9c3;
            color: #854d0e;
            border: 1px solid #fde68a;
        }

        .badge-sizing {
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .badge-date {
            background: #fdf4ff;
            color: #7e22ce;
            border: 1px solid #e9d5ff;
        }

        .status-pill {
            display: inline-block;
            padding: 1px 7px;
            border-radius: 12px;
            font-size: 7.5px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .status-received {
            background: #f3f4f6;
            color: #374151;
            border: 1px solid #d1d5db;
        }

        .status-progress {
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
        }

        .status-ready {
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .status-delivered {
            background: #faf5ff;
            color: #7e22ce;
            border: 1px solid #e9d5ff;
        }

        .notes-section {
            margin: 6px 0;
            border: 1.5px solid #249E94;
            padding: 6px 8px;
            background: #f9fefe;
            border-radius: 4px;
            min-height: 40px;
            font-size: 9.5px;
        }

        .warranty-box {
            background: #fff5f5;
            border: 2px dashed #dc2626;
            padding: 4px 10px;
            border-radius: 4px;
            text-align: center;
            color: #dc2626;
            font-weight: 900;
            font-size: 11px;
            margin: 4px 0;
            letter-spacing: 0.05em;
        }

        .signatures {
            margin-top: 10px;
        }

        .signature-box {
            width: 45%;
            float: left;
            border-top: 2px solid #249E94;
            padding-top: 6px;
            text-align: center;
            font-weight: bold;
            font-size: 9.5px;
            color: #1a6b65;
        }

        .signature-box.right {
            float: right;
        }

        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }

        .divider {
            border: none;
            border-top: 1px dashed #249E94;
            margin: 5px 0;
        }

        .agreement-box {
            margin-top: 8px;
            border: 1.5px solid #249E94;
            background: #f9fefe;
            border-radius: 4px;
            padding: 7px 10px;
            font-size: 9px;
            line-height: 1.35;
            color: #333;
        }

        .agreement-box .items-list {
            margin: 4px 0;
            font-weight: 700;
            text-transform: uppercase;
            color: #1a6b65;
        }

        .item-photo {
            width: 45px;
            height: 45px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #249E94;
            margin-top: 3px;
            display: inline-block;
        }

        @media print {

            .header,
            .document-title,
            .customer-table th,
            .workshop-table th {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
        .check-icon {
    display: inline-block;
    width: 28px;
    height: 28px;
    position: relative;
}

.check-icon .mark {
    position: absolute;
    left: 9px;
    top: 4px;
    width: 8px;
    height: 16px;
    border-bottom: 3px solid;
    border-right: 3px solid;
    transform: rotate(45deg);
}
    </style>
</head>

<body>

    @php
    $repairItems = $repair->items ?? [];

    if (empty($repairItems) && !empty($repair->item_description)) {
    $repairItems = [[
    'item_description' => $repair->item_description,
    'reported_issue' => $repair->reported_issue,
    'estimated_cost' => $repair->estimated_cost,
    'final_cost' => $repair->final_cost,
    'is_warranty' => $repair->is_warranty,
    ]];
    }

    $grandEstimated = collect($repairItems)->sum(fn($i) => floatval($i['estimated_cost'] ?? 0));
    $grandFinal = collect($repairItems)->sum(fn($i) => floatval($i['final_cost'] ?? 0));

    $statusMap = [
    'received' => ['label' => 'Received', 'class' => 'status-received'],
    'in_progress' => ['label' => 'In Progress', 'class' => 'status-progress'],
    'ready' => ['label' => 'Ready for Pickup', 'class' => 'status-ready'],
    'delivered' => ['label' => 'Delivered', 'class' => 'status-delivered'],
    ];
    $st = $statusMap[$repair->status] ?? ['label' => ucfirst($repair->status), 'class' => 'status-received'];

    $isReady = in_array($repair->status, ['ready', 'delivered']);

    // Combine an item's main photo + any captured photos into one flat list,
    // capped so a busy item can't blow out the row height.
    $itemPhotos = function(array $item, int $limit = 4) {
    $photos = [];
    if (!empty($item['item_photo'])) {
    $photos[] = $item['item_photo'];
    }
    if (!empty($item['captured_photos']) && is_array($item['captured_photos'])) {
    foreach ($item['captured_photos'] as $p) {
    $photos[] = $p;
    }
    }
    return array_slice($photos, 0, $limit);
    };

    $serviceInfo = function(array $item, bool $showBadges = true) {
    $parts = [];
    if (!empty($item['job_type'])) {
    $parts[] = "<span class='service-badge badge-job'>" . e($item['job_type']) . "</span>";
    }
    if (!empty($item['metal_type'])) {
    $parts[] = "<span class='service-badge badge-metal'>" . e($item['metal_type']) . "</span>";
    }
    if (!empty($item['job_type']) && $item['job_type'] === 'Resize'
    && (!empty($item['current_size']) || !empty($item['target_size']))) {
    $from = $item['current_size'] ?? '?';
    $to = $item['target_size'] ?? '?';
    $parts[] = "<span class='service-badge badge-sizing'>Size: {$from} -&gt; {$to}</span>";
    }
    if (!empty($item['date_required'])) {
    $parts[] = "<span class='service-badge badge-date'>Due: " . \Carbon\Carbon::parse($item['date_required'])->format('m/d/Y') . "</span>";
    }
    return implode(' ', $parts);
    };
    @endphp

    {{-- ═══════════════════════════════════════════
     PAGE 1 — CUSTOMER COPY
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
            <span class="badge badge-warranty">&#9733; WARRANTY REPAIR — NO CHARGE &#9733;</span>
            @endif
            @if($repair->is_from_store_stock)
            <span class="badge badge-store">Store Purchase</span>
            @endif
        </div>

        <table class="meta-table">
            <tr>
                <td width="33%"><strong>Repair #:</strong> {{ $repair->repair_no }}</td>
                <td width="34%"><strong>Sales Assistant:</strong> {{ $repair->dropped_by ?? 'N/A' }}</td>
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
        <div class="warranty-box">&#9888; WARRANTY REPAIR — Customer will NOT be charged &#9888;</div>
        @endif

        <table class="customer-table">
            <thead>
                <tr>
                    <th width="5%">#</th>
                    <th width="35%">Item Description</th>
                    <th width="38%">Issue Reported &amp; Service</th>
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
                        <div style="font-weight:700;font-size:10px;margin-top:1px;">
                            {{ strtoupper($rItem['item_description'] ?? '') }}
                        </div>
                        @php $photos = $itemPhotos($rItem); @endphp
                        @if(!empty($photos))
                        <div style="margin-top:3px;">
                            @foreach($photos as $photo)
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->path($photo) }}" class="item-photo">
                            @endforeach
                        </div>
                        @endif
                    </td>
                    <td>
                        <div style="margin-bottom:3px;">{{ $rItem['reported_issue'] ?? '—' }}</div>
                        @php $badges = $serviceInfo($rItem); @endphp
                        @if($badges)
                        <div style="margin-top:3px;">{!! $badges !!}</div>
                        @endif
                    </td>
                    <td style="text-align:right;">
                        @if(!empty($rItem['is_warranty']))
                        <span style="color:#dc2626;font-weight:bold;">WARRANTY</span>
                        @elseif(!empty($rItem['final_cost']) && floatval($rItem['final_cost']) > 0)
                        <strong style="color:#1a6b65;">${{ number_format($rItem['final_cost'], 2) }}</strong>
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

        <div style="font-size:8.5px;color:#555;text-align:center;margin-top:4px;">
            Please keep this receipt. Present it when collecting your item.
            &nbsp;|&nbsp; <strong>{{ $repair->store?->phone ?? '' }}</strong>
        </div>

        <div class="agreement-box">
            <strong style="color:#1a6b65;">CUSTOMER AGREEMENT:</strong>
            I, the undersigned, confirm that I have dropped off the following item(s) for repair as described above:
            <div class="items-list">
                @foreach($repairItems as $rItem)
                {{ $rItem['item_description'] ?? '' }}@if(!$loop->last), @endif
                @endforeach
            </div>
            I agree to the service described on the description and understand any estimated costs are subject to change upon any modification. I authorize {{ $repair->store?->legal_name ?? 'Diamond Square' }} to proceed with the work outlined above.
        </div>

        <div class="signatures clearfix">
            <div class="signature-box">Customer Signature<br>Date: ___________</div>
            <div class="signature-box right">Staff Initials<br>Date: ___________</div>
        </div>

    </div>{{-- end page 1 --}}

    {{-- ═══════════════════════════════════════════
     PAGE 2 — WORKSHOP COPY
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
            <span class="badge badge-warranty">&#9733; WARRANTY — NO CHARGE &#9733;</span>
            @endif
            @if($repair->is_from_store_stock)
            <span class="badge badge-store">Store Purchase</span>
            @endif
        </div>

        <table class="meta-table">
            <tr>
                <td width="33%"><strong>Repair #:</strong> {{ $repair->repair_no }}</td>
                <td width="34%"><strong>Staff:</strong> {{ $repair->dropped_by ?? 'N/A' }}</td>
                <td class="right-align" width="33%">Date In: {{ $repair->created_at->format('m/d/Y') }}</td>
            </tr>
            <tr>
                <td><strong>Status:</strong> <span class="status-pill {{ $st['class'] }}">{{ $st['label'] }}</span></td>
                <td>&nbsp;</td>
                <td class="right-align" style="color:#94a3b8;font-size:8.5px;">Internal use only</td>
            </tr>
        </table>

        {{-- ── LOCATION + READY CHECKBOX ── --}}
        <table style="width:100%;border-collapse:collapse;border:3px solid #1a6b65;margin:5px 0;">
            <tr>
                {{-- Location --}}
                <td style="width:52%;padding:7px 10px;border-right:2px solid #1a6b65;vertical-align:middle;">
                    <div style="font-size:7.5px;font-weight:900;text-transform:uppercase;letter-spacing:0.1em;color:#1a6b65;margin-bottom:3px;">Repair Location</div>
                    <div style="font-size:14px;font-weight:900;color:#000;">{{ $repair->repair_location ?: '— Not Set —' }}</div>
                </td>
                {{-- Mark When Ready checkboxes --}}
                <td style="width:48%;padding:7px 10px;vertical-align:middle;">
                    <div style="font-size:7.5px;font-weight:900;text-transform:uppercase;letter-spacing:0.1em;color:#1a6b65;margin-bottom:6px;text-align:center;">Mark When Ready</div>
                    <table style="width:100%;border-collapse:collapse;">
                        <tr>
                            {{-- NO box --}}
                            <td style="width:50%;text-align:center;padding-right:8px;">
                                <table style="border-collapse:collapse;margin:0 auto 4px auto;">
                                    <tr>
                                        <td style="vertical-align:middle;padding-right:8px;">
                                            <div style="display:inline-block;width:28px;height:28px;border:2.5px solid #dc2626;background:#fff;"></div>
                                        </td>
                                        <td style="vertical-align:middle;font-size:11px;font-weight:900;color:#dc2626;">NO</td>
                                    </tr>
                                </table>
                            </td>
                            {{-- YES box --}}
                            <td style="width:50%;text-align:center;padding-left:8px;border-left:1px solid #e5e7eb;">
                                <table style="border-collapse:collapse;margin:0 auto 4px auto;">
                                    <tr>
                                        <td style="vertical-align:middle;padding-right:8px;">
                                            <div style="display:inline-block;width:28px;height:28px;border:2.5px solid #166534;background:#fff;"></div>
                                        </td>
                                        <td style="vertical-align:middle;font-size:11px;font-weight:900;color:#166534;">YES</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

       {{-- ── CALL / TEXT CONTACT SECTION ── --}}
<table style="width:100%;border-collapse:collapse;border:2px solid #1a6b65;margin:5px 0;">
    <tr>
        <td style="width:40%;padding:6px 10px;border-right:2px solid #1a6b65;vertical-align:middle;">
            <div style="font-size:7.5px;font-weight:900;text-transform:uppercase;letter-spacing:0.1em;color:#1a6b65;margin-bottom:3px;">Customer Contact</div>
            <div style="font-size:11px;font-weight:700;color:#000;">{{ $repair->customer->phone ?? 'No phone on file' }}</div>
        </td>
        <td style="width:60%;padding:6px 10px;vertical-align:middle;">
            <table style="width:100%;border-collapse:collapse;">
                <tr>
                    {{-- CALL box --}}
                    <td style="width:50%;text-align:center;padding-right:8px;">
                        <table style="border-collapse:collapse;margin:0 auto 3px auto;">
                            <tr>
                                <td style="vertical-align:middle;padding-right:6px;">
                                    @if($repair->customer_called)
                                    <div style="display:inline-block;width:28px;height:28px;border:2.5px solid #166534;background:#f0fdf4;">
                                        <div class="check-icon"><div class="mark" style="border-color:#166534;"></div></div>
                                    </div>
                                    @else
                                    <div style="display:inline-block;width:28px;height:28px;border:2.5px solid #166534;background:#fff;"></div>
                                    @endif
                                </td>
                                <td style="vertical-align:middle;">
                                    <div style="font-size:11px;font-weight:900;color:#166534;">Call</div>
                                    <div style="font-size:7.5px;color:#555;">Customer answered</div>
                                </td>
                            </tr>
                        </table>
                    </td>
                    {{-- TEXT box --}}
                    <td style="width:50%;text-align:center;padding-left:8px;border-left:1px solid #e5e7eb;">
                        <table style="border-collapse:collapse;margin:0 auto 3px auto;">
                            <tr>
                                <td style="vertical-align:middle;padding-right:6px;">
                                    @if($repair->customer_texted)
                                    <div style="display:inline-block;width:28px;height:28px;border:2.5px solid #0284c7;background:#f0f9ff;">
                                        <div class="check-icon"><div class="mark" style="border-color:#0284c7;"></div></div>
                                    </div>
                                    @else
                                    <div style="display:inline-block;width:28px;height:28px;border:2.5px solid #0284c7;background:#fff;"></div>
                                    @endif
                                </td>
                                <td style="vertical-align:middle;">
                                    <div style="font-size:11px;font-weight:900;color:#0284c7;">Text</div>
                                    <div style="font-size:7.5px;color:#555;">No answer — texted</div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

        <div class="customer-box">
            <div class="cust-name">{{ $repair->customer->name }} {{ $repair->customer->last_name }}</div>
            <strong>Phone:</strong> {{ $repair->customer->phone ?? 'N/A' }}
        </div>

        @if($repair->is_warranty)
        <div class="warranty-box">&#9888; WARRANTY REPAIR — DO NOT CHARGE CUSTOMER &#9888;</div>
        @endif

        <table class="workshop-table">
            <thead>
                <tr>
                    <th width="5%">#</th>
                    <th width="32%">Item Description</th>
                    <th width="45%">Issue / Service Instructions</th>
                    <th width="18%">Bench Notes</th>
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
                        <div style="font-size:10px;font-weight:700;margin-top:2px;text-transform:uppercase;">
                            {{ $rItem['item_description'] ?? '' }}
                        </div>
                        @php $photos = $itemPhotos($rItem); @endphp
                        @if(!empty($photos))
                        <div style="margin-top:3px;">
                            @foreach($photos as $photo)
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->path($photo) }}" class="item-photo">
                            @endforeach
                        </div>
                        @endif
                        @if(!empty($rItem['is_warranty']))
                        <div style="margin-top:2px;">
                            <span style="background:#fef2f2;color:#dc2626;padding:1px 5px;border-radius:3px;font-size:7.5px;font-weight:900;border:1px solid #dc2626;">WARRANTY</span>
                        </div>
                        @endif
                    </td>
                    <td>
                        <div style="margin-bottom:4px;">
                            <strong style="color:#1a6b65;font-size:8.5px;">ISSUE:</strong>
                            {{ $rItem['reported_issue'] ?? '—' }}
                        </div>
                        {{-- Multiple services support --}}
                        @if(!empty($rItem['services']) && is_array($rItem['services']) && count($rItem['services']) > 0)
                        @foreach($rItem['services'] as $si => $svc)
                        <div style="margin-bottom:4px;padding:3px 6px;background:#f0fdf4;border-left:3px solid #166534;border-radius:0 4px 4px 0;font-size:9px;">
                            <strong style="color:#166534;font-size:9.5px;">{{ $si + 1 }}. {{ $svc['job_type'] ?? $svc['service_type'] ?? '—' }}</strong>
                            @if(!empty($svc['metal_type']))
                            <span style="color:#555;"> — {{ $svc['metal_type'] }}</span>
                            @endif
                            @if(!empty($svc['date_required']))
                            <span style="color:#7e22ce;"> | Due: {{ \Carbon\Carbon::parse($svc['date_required'])->format('m/d/Y') }}</span>
                            @endif
                            @if(!empty($svc['send_to']))
                            <span style="color:#b45309;font-weight:900;"> | &gt;&gt; {{ strtoupper($svc['send_to']) }}</span>
                            @endif
                            @if(!empty($svc['current_size']) || !empty($svc['target_size']))
                            <span style="background:#fff;border:1px solid #bbf7d0;border-radius:3px;padding:1px 5px;margin-left:3px;display:inline-block;">
                                Resize: {{ $svc['current_size'] ?? '?' }} -&gt; {{ $svc['target_size'] ?? '?' }}
                            </span>
                            @endif
                            @if(!empty($svc['job_instructions']))
                            <div style="color:#374151;margin-top:2px;font-style:italic;">{{ $svc['job_instructions'] }}</div>
                            @endif
                            @if(!empty($svc['estimated_cost']) && floatval($svc['estimated_cost']) > 0)
                            <span style="color:#1a6b65;font-weight:700;"> Est: ${{ number_format($svc['estimated_cost'], 2) }}</span>
                            @endif
                        </div>
                        @endforeach
                        @else
                        {{-- Fallback for old single-service repairs --}}
                        @php $badges = $serviceInfo($rItem); @endphp
                        @if($badges)
                        <div style="margin-bottom:3px;">{!! $badges !!}</div>
                        @endif
                        @if(!empty($rItem['job_type']) && $rItem['job_type'] === 'Resize'
                        && (!empty($rItem['current_size']) || !empty($rItem['target_size'])))
                        <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:4px;padding:3px 6px;margin-top:3px;font-size:9px;">
                            <strong style="color:#166534;">RESIZE:</strong>
                            Size {{ $rItem['current_size'] ?? '?' }}
                            -&gt;
                            Size {{ $rItem['target_size'] ?? '?' }}
                        </div>
                        @endif
                        @endif
                    </td>
                    <td class="notes-col">&nbsp;</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="notes-section">
            <strong style="color:#1a6b65;">JEWELER BENCH NOTES / WORK CARRIED OUT:</strong>
            @php
            $allInstructions = collect($repairItems)
            ->filter(fn($i) => !empty($i['reported_issue']) || !empty($i['job_type']))
            ->map(function($item, $idx) {
            $line = '';
            if (!empty($item['job_type'])) {
            $line .= strtoupper($item['job_type']);
            if (!empty($item['metal_type'])) $line .= ' — ' . $item['metal_type'];
            if ($item['job_type'] === 'Resize' && (!empty($item['current_size']) || !empty($item['target_size']))) {
            $line .= ' (Size ' . ($item['current_size'] ?? '?') . ' -> ' . ($item['target_size'] ?? '?') . ')';
            }
            }
            if (!empty($item['reported_issue'])) {
            $line .= ($line ? ': ' : '') . $item['reported_issue'];
            }
            return ($idx + 1) . '. ' . $line;
            })
            ->values()
            ->toArray();
            @endphp
            @if(!empty($allInstructions))
            <div style="margin-top:4px;color:#374151;font-size:9px;line-height:1.4;">
                @foreach($allInstructions as $instruction)
                <div>{{ $instruction }}</div>
                @endforeach
            </div>
            @endif
        </div>

        <div style="background:white;border:1px solid #e0e0e0;border-radius:4px;padding:6px 10px;margin:5px 0;font-size:9.5px;">
            <strong>MATERIALS USED:</strong>
            <table style="width:100%;margin-top:3px;border-collapse:collapse;">
                <tr>
                    <th style="border-bottom:1px solid #e0e0e0;padding:2px 4px;text-align:left;color:#1a6b65;font-size:8.5px;">Material</th>
                    <th style="border-bottom:1px solid #e0e0e0;padding:2px 4px;text-align:left;color:#1a6b65;font-size:8.5px;">Weight / Qty</th>
                    <th style="border-bottom:1px solid #e0e0e0;padding:2px 4px;text-align:left;color:#1a6b65;font-size:8.5px;">Notes</th>
                </tr>
                <tr>
                    <td style="padding:5px 4px;">&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>
            </table>
        </div>

        <div class="agreement-box">
            <strong style="color:#1a6b65;">CUSTOMER AGREEMENT (WORKSHOP COPY):</strong>
            Customer agrees to the service described below for the following item(s):
            <div class="items-list">
                @foreach($repairItems as $rItem)
                {{ $rItem['item_description'] ?? '' }}@if(!$loop->last), @endif
                @endforeach
            </div>
            This copy confirms the work order accepted by the customer. Retain on file with the item during repair.
        </div>

        <div class="signatures clearfix">
            <div class="signature-box">Customer Signature<br>Date: ___________</div>
            <div class="signature-box right">Jeweler Signature<br>Date: ___________</div>
        </div>

    </div>{{-- end page 2 --}}

</body>

</html>