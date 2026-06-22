<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Repair Job Packet - {{ $repair->repair_no }}</title>
    <style>
        @page { margin: 0.3in; size: Letter; }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px; color: #000; line-height: 1.3;
            margin: 0; padding: 0; background: white;
        }
        .page { page-break-after: always; clear: both; }
        .page:last-child { page-break-after: avoid; }
        .header {
            text-align: center; margin-bottom: 8px; padding: 8px 6px;
            background: #ffffff; color: #1a6b65;
            border: 2px solid #249E94; border-radius: 6px;
        }
        .brand-name { font-size: 22px; font-weight: 900; margin: 0 0 2px 0; letter-spacing: 1px; text-transform: uppercase; color: #1a6b65; }
        .legal-name { font-size: 12px; font-weight: bold; margin: 0 0 3px 0; color: #249E94; }
        .store-details { font-size: 9px; line-height: 1.2; color: #444; }
        .document-title {
            text-align: center; font-size: 16px; font-weight: bold;
            margin: 8px 0; padding: 6px 10px;
            background: #ffffff; color: #1a6b65;
            border: 2px solid #249E94; border-radius: 4px; letter-spacing: 0.5px;
        }
        .badge-row { text-align: center; margin-bottom: 6px; }
        .badge {
            display: inline-block; padding: 3px 12px; border-radius: 20px;
            font-size: 9px; font-weight: 900; letter-spacing: 0.08em;
            text-transform: uppercase; margin: 0 4px;
        }
        .badge-warranty { background: #fef2f2; color: #dc2626; border: 1px solid #dc2626; }
        .badge-store    { background: #eff6ff; color: #0284c7; border: 1px solid #0284c7; }
        .meta-table {
            width: 100%; margin-bottom: 6px; background: white; padding: 8px;
            border: 1px solid #e0e0e0; border-radius: 4px; font-size: 10px;
            border-spacing: 0; border-collapse: collapse;
        }
        .meta-table td { padding: 3px 6px; vertical-align: top; }
        .right-align { text-align: right; color: #249E94; font-weight: bold; }
        .customer-box {
            background: #f9fefe; border: 1.5px solid #249E94;
            padding: 7px 12px; margin: 6px 0; border-radius: 4px; font-size: 10px;
        }
        .customer-box .cust-name { font-size: 13px; font-weight: 900; text-transform: uppercase; color: #1a6b65; margin-bottom: 3px; }
        .customer-table { width: 100%; border-collapse: collapse; margin: 8px 0; font-size: 10px; }
        .customer-table th {
            border: none; padding: 8px 6px; background: #e8f5f4; color: #1a6b65;
            font-weight: bold; font-size: 10px; text-align: left; border-bottom: 2px solid #249E94;
        }
        .customer-table td { border-bottom: 1px solid #e0e0e0; padding: 5px 6px; background: white; vertical-align: top; }
        .total-row td { border-top: 2px solid #249E94 !important; background: #e8f5f3 !important; font-weight: bold; font-size: 11px; color: #1a6b65; }
        .workshop-table { width: 100%; border-collapse: collapse; margin: 8px 0; font-size: 10px; }
        .workshop-table th {
            border: none; padding: 8px 6px; background: #e8f5f4; color: #1a6b65;
            font-weight: bold; text-align: left; border-bottom: 2px solid #249E94;
        }
        .workshop-table td { border-bottom: 1px solid #e0e0e0; padding: 8px 6px; vertical-align: top; background: white; }
        .workshop-table td.notes-col { background: #fffdf0 !important; }
        .service-badge {
            display: inline-block; padding: 2px 7px; border-radius: 4px;
            font-size: 8px; font-weight: 900; text-transform: uppercase;
            letter-spacing: 0.05em; margin: 1px 2px 1px 0;
        }
        .badge-job    { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
        .badge-metal  { background: #fef9c3; color: #854d0e; border: 1px solid #fde68a; }
        .badge-sizing { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .badge-date   { background: #fdf4ff; color: #7e22ce; border: 1px solid #e9d5ff; }
        .status-pill {
            display: inline-block; padding: 2px 8px; border-radius: 12px;
            font-size: 8px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.05em;
        }
        .status-received  { background: #f3f4f6; color: #374151; border: 1px solid #d1d5db; }
        .status-progress  { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
        .status-ready     { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .status-delivered { background: #faf5ff; color: #7e22ce; border: 1px solid #e9d5ff; }
        .notes-section {
            margin: 10px 0; border: 1.5px solid #249E94; padding: 10px;
            background: #f9fefe; border-radius: 4px; min-height: 70px; font-size: 10px;
        }
        .warranty-box {
            background: #fff5f5; border: 2px dashed #dc2626; padding: 6px 10px;
            border-radius: 4px; text-align: center; color: #dc2626;
            font-weight: 900; font-size: 12px; margin: 6px 0; letter-spacing: 0.05em;
        }
        .signatures { margin-top: 20px; }
        .signature-box {
            width: 45%; float: left; border-top: 2px solid #249E94;
            padding-top: 10px; text-align: center; font-weight: bold; font-size: 10px; color: #1a6b65;
        }
        .signature-box.right { float: right; }
        .clearfix::after { content: ""; display: table; clear: both; }
        .divider { border: none; border-top: 1px dashed #249E94; margin: 8px 0; }
        .agreement-box {
            margin-top: 14px; border: 1.5px solid #249E94; background: #f9fefe;
            border-radius: 4px; padding: 10px 12px; font-size: 9.5px; line-height: 1.5; color: #333;
        }
        .agreement-box .items-list {
            margin: 6px 0; font-weight: 700; text-transform: uppercase; color: #1a6b65;
        }
        .item-photo {
            width: 70px; height: 70px; object-fit: cover; border-radius: 4px;
            border: 1px solid #249E94; margin-top: 5px; display: block;
        }
        @media print {
            .header, .document-title, .customer-table th, .workshop-table th {
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

    // Helper to render service/metal/sizing badges for a repair item
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
            $to   = $item['target_size']  ?? '?';
            $parts[] = "<span class='service-badge badge-sizing'>Size: {$from} → {$to}</span>";
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
        <h1 class="brand-name">{{ strtoupper($repair->store?->name ?? $repair->store?->legal_name ?? 'Diamond Square') }}</h1>
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
    <div class="warranty-box">⚠ WARRANTY REPAIR — Customer will NOT be charged ⚠</div>
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
                    <div style="font-weight:700;font-size:11px;margin-top:2px;">
                        {{ strtoupper($rItem['item_description'] ?? '') }}
                    </div>
                    @if(!empty($rItem['item_photo']))
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->path($rItem['item_photo']) }}"
                             class="item-photo">
                    @endif
                </td>
                <td>
                    {{-- Issue reported --}}
                    <div style="margin-bottom:4px;">{{ $rItem['reported_issue'] ?? '—' }}</div>
                    {{-- Service type / metal / sizing badges --}}
                    @php $badges = $serviceInfo($rItem); @endphp
                    @if($badges)
                        <div style="margin-top:4px;">{!! $badges !!}</div>
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

    <hr class="divider">

    <div style="font-size:9px;color:#555;text-align:center;margin-top:6px;">
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

    <div class="signatures clearfix" style="margin-top:16px;">
        <div class="signature-box">Customer Signature<br>Date: ___________</div>
        <div class="signature-box right">Staff Initials<br>Date: ___________</div>
    </div>

</div>{{-- end page 1 --}}

{{-- ═══════════════════════════════════════════
     PAGE 2 — WORKSHOP COPY
════════════════════════════════════════════ --}}
<div class="page">

    <div class="header">
        <h1 class="brand-name">{{ strtoupper($repair->store?->name ?? $repair->store?->legal_name ?? 'Diamond Square') }}</h1>
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
            <td width="34%"><strong>Staff:</strong> {{ $repair->dropped_by ?? 'N/A' }}</td>
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
                    <div style="font-size:11px;font-weight:700;margin-top:3px;text-transform:uppercase;">
                        {{ $rItem['item_description'] ?? '' }}
                    </div>
                    @if(!empty($rItem['item_photo']))
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->path($rItem['item_photo']) }}"
                             class="item-photo">
                    @endif
                    @if(!empty($rItem['is_warranty']))
                        <div style="margin-top:3px;">
                            <span style="background:#fef2f2;color:#dc2626;padding:1px 5px;border-radius:3px;font-size:8px;font-weight:900;border:1px solid #dc2626;">WARRANTY</span>
                        </div>
                    @endif
                </td>
                <td>
                    {{-- Issue reported --}}
                    <div style="margin-bottom:5px;">
                        <strong style="color:#1a6b65;font-size:9px;">ISSUE:</strong><br>
                        {{ $rItem['reported_issue'] ?? '—' }}
                    </div>

                    {{-- Service type / metal / sizing badges --}}
                    @php $badges = $serviceInfo($rItem); @endphp
                    @if($badges)
                        <div style="margin-bottom:5px;">{!! $badges !!}</div>
                    @endif

                    {{-- Sizing detail (prominent for workshop) --}}
                    @if(!empty($rItem['job_type']) && $rItem['job_type'] === 'Resize'
                        && (!empty($rItem['current_size']) || !empty($rItem['target_size'])))
                        <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:4px;padding:4px 8px;margin-top:4px;font-size:10px;">
                            <strong style="color:#166534;">RESIZE:</strong>
                            Size {{ $rItem['current_size'] ?? '?' }}
                            <strong style="font-size:13px;color:#166534;">→</strong>
                            Size {{ $rItem['target_size'] ?? '?' }}
                        </div>
                    @endif
                </td>
                <td class="notes-col">&nbsp;</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- ── JEWELER BENCH NOTES — pre-filled with instructions ── --}}
    <div class="notes-section">
        <strong style="color:#1a6b65;">JEWELER BENCH NOTES / WORK CARRIED OUT:</strong>
        @php
            // Collect all instructions from items to pre-fill the bench notes section
            $allInstructions = collect($repairItems)
                ->filter(fn($i) => !empty($i['reported_issue']) || !empty($i['job_type']))
                ->map(function($item, $idx) {
                    $line = '';
                    if (!empty($item['job_type'])) {
                        $line .= strtoupper($item['job_type']);
                        if (!empty($item['metal_type'])) $line .= ' — ' . $item['metal_type'];
                        if ($item['job_type'] === 'Resize' && (!empty($item['current_size']) || !empty($item['target_size']))) {
                            $line .= ' (Size ' . ($item['current_size'] ?? '?') . ' → ' . ($item['target_size'] ?? '?') . ')';
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
            <div style="margin-top:6px;color:#374151;font-size:10px;line-height:1.6;">
                @foreach($allInstructions as $instruction)
                    <div>{{ $instruction }}</div>
                @endforeach
            </div>
            <div style="margin-top:8px;border-top:1px dashed #249E94;padding-top:6px;color:#94a3b8;font-size:9px;font-style:italic;">
                Jeweler — please note any additional work performed below:
            </div>
        @endif
        <br><br>
    </div>

    <div style="background:white;border:1px solid #e0e0e0;border-radius:4px;padding:8px 12px;margin:8px 0;font-size:10px;">
        <strong>MATERIALS USED:</strong>
        <table style="width:100%;margin-top:5px;border-collapse:collapse;">
            <tr>
                <th style="border-bottom:1px solid #e0e0e0;padding:3px 4px;text-align:left;color:#1a6b65;font-size:9px;">Material</th>
                <th style="border-bottom:1px solid #e0e0e0;padding:3px 4px;text-align:left;color:#1a6b65;font-size:9px;">Weight / Qty</th>
                <th style="border-bottom:1px solid #e0e0e0;padding:3px 4px;text-align:left;color:#1a6b65;font-size:9px;">Notes</th>
            </tr>
            <tr><td style="padding:7px 4px;">&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
            <tr><td style="padding:7px 4px;border-top:1px solid #f0f0f0;">&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
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

    <div class="signatures clearfix" style="margin-top:16px;">
        <div class="signature-box">Customer Signature<br>Date: ___________</div>
        <div class="signature-box right">Jeweler Signature<br>Date: ___________</div>
    </div>

</div>{{-- end page 2 --}}

</body>
</html>