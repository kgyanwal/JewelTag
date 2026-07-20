<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Exchange Receipt — {{ $exchange->exchange_no }}</title>
    <style>
        @page { margin: 0.3in; size: Letter; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10px; color: #000; margin: 0; background: white; }
        .header { text-align: center; padding: 8px; background: #fff; border: 2px solid #0B3D3C; border-radius: 6px; margin-bottom: 8px; }
        .brand { font-size: 18px; font-weight: 900; color: #0B3D3C; letter-spacing: 1px; text-transform: uppercase; }
        .sub { font-size: 9px; color: #444; }
        .doc-title { text-align: center; font-size: 14px; font-weight: 900; padding: 5px; background: #0B3D3C; color: #E4CD8E; border-radius: 4px; margin-bottom: 8px; letter-spacing: 1px; }
        .meta { width: 100%; border-collapse: collapse; margin-bottom: 8px; font-size: 9.5px; }
        .meta td { padding: 3px 6px; }
        .meta td:last-child { text-align: right; font-weight: 700; color: #0B3D3C; }
        .cust-box { background: #f9fefe; border: 1.5px solid #0B3D3C; padding: 6px 10px; border-radius: 4px; font-size: 9.5px; margin-bottom: 8px; }
        .cust-name { font-size: 13px; font-weight: 900; text-transform: uppercase; color: #0B3D3C; }
        .section-title { font-size: 10px; font-weight: 900; text-transform: uppercase; color: #0B3D3C; background: #e8f5f4; padding: 4px 8px; border-radius: 4px; margin: 8px 0 4px; letter-spacing: 0.05em; }
        table.items { width: 100%; border-collapse: collapse; font-size: 9.5px; margin-bottom: 6px; }
        table.items th { background: #0B3D3C; color: #E4CD8E; padding: 4px 6px; text-align: left; font-size: 9px; }
        table.items td { border-bottom: 1px solid #e0e0e0; padding: 4px 6px; vertical-align: top; }
        .summary { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .summary td { padding: 4px 8px; font-size: 10px; }
        .summary .total-row td { border-top: 2px solid #0B3D3C; font-weight: 900; font-size: 12px; color: #0B3D3C; }
        .credit { color: #B8463F; font-weight: 700; }
        .charge { color: #059669; font-weight: 700; }
        .sig { margin-top: 16px; }
        .sig-box { width: 45%; float: left; border-top: 2px solid #0B3D3C; padding-top: 6px; text-align: center; font-size: 9.5px; font-weight: 700; color: #0B3D3C; }
        .sig-box.right { float: right; }
        .clearfix::after { content: ''; display: table; clear: both; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 8px; font-weight: 900; text-transform: uppercase; }
        .badge-upgrade { background: #ecfdf5; color: #065f46; border: 1px solid #6ee7b7; }
        .badge-downgrade { background: #fef2f2; color: #991b1b; border: 1px solid #fca5a5; }
        .badge-same { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
        .badge-type { display:inline-block;font-size:8px;font-weight:800;padding:1px 6px;border-radius:8px;margin-left:6px;background:#f5f3ff;color:#7c3aed;border:1px solid #ddd6fe; }
        @media print { * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; } }
    </style>
</head>
<body>
@php
    $store    = $exchange->store;
    $customer = $exchange->customer;
    $diff     = floatval($exchange->difference_amount);
    $newItems = $exchange->new_items ?? [];

    $originalItemsById = collect();
    if ($exchange->originalSale) {
        $exchange->originalSale->loadMissing('items.productItem');
        foreach ($exchange->originalSale->items as $si) {
            $originalItemsById->put('sale_item:' . $si->id, $si);
            if ($si->product_item_id) {
                $originalItemsById->put('product_item:' . $si->product_item_id, $si);
            }
        }
    }

    $resolveReturnedItem = function (array $item) use ($originalItemsById) {
        $stockNo     = $item['stock_no'] ?? null;
        $description = $item['description'] ?? null;

        if (empty($stockNo) || $stockNo === '—' || empty($description)) {
            $match = null;
            if (!empty($item['sale_item_id'])) {
                $match = $originalItemsById->get('sale_item:' . $item['sale_item_id']);
            }
            if (!$match && !empty($item['product_item_id'])) {
                $match = $originalItemsById->get('product_item:' . $item['product_item_id']);
            }
            if ($match) {
                $stockNo     = $stockNo ?: ($match->productItem?->barcode ?? '—');
                $description = $description ?: ($match->custom_description ?? $match->productItem?->custom_description ?? '—');
            }
        }

        return [
            'stock_no'    => $stockNo ?: '—',
            'description' => $description ?: '—',
        ];
    };
@endphp

<div class="header">
    <div class="brand">{{ $store?->name ?? 'JewelTag' }}</div>
    <div class="sub">{{ $store?->street }}, {{ $store?->city }}, {{ $store?->state }} {{ $store?->postcode }} | {{ $store?->phone }}</div>
</div>

<div class="doc-title">EXCHANGE RECEIPT</div>

<table class="meta">
    <tr>
        <td><strong>Exchange #:</strong> {{ $exchange->exchange_no }}</td>
        <td>{{ now()->format('m/d/Y h:i A') }}</td>
    </tr>
    <tr>
        <td><strong>Original Invoice:</strong> {{ $exchange->originalSale?->invoice_number ?? '—' }}</td>
        <td>
            @if($exchange->exchange_type === 'upgrade')
                <span class="badge badge-upgrade">⬆️ Upgrade</span>
            @elseif($exchange->exchange_type === 'downgrade')
                <span class="badge badge-downgrade">⬇️ Downgrade</span>
            @else
                <span class="badge badge-same">↔️ Same Value</span>
            @endif
        </td>
    </tr>
    <tr>
        <td><strong>Staff:</strong> {{ $exchange->requester?->name ?? 'N/A' }}</td>
        <td><strong>Approved By:</strong> {{ $exchange->approver?->name ?? 'N/A' }}</td>
    </tr>
    @if($exchange->newSale)
    <tr>
        <td><strong>New Sale Invoice:</strong> {{ $exchange->newSale->invoice_number }}</td>
        <td></td>
    </tr>
    @endif
</table>

<div class="cust-box">
    <div class="cust-name">{{ $customer?->name }} {{ $customer?->last_name }}</div>
    <strong>Phone:</strong> {{ $customer?->phone ?? 'N/A' }}
    @if($customer?->email) &nbsp;|&nbsp; <strong>Email:</strong> {{ $customer->email }} @endif
</div>

{{-- RETURNED ITEMS --}}
<div class="section-title">↩ Items Returned by Customer</div>
<table class="items">
    <thead>
        <tr>
            <th>#</th>
            <th>Stock #</th>
            <th>Description</th>
            <th style="text-align:right;">Credit</th>
        </tr>
    </thead>
    <tbody>
        @foreach($exchange->returned_items ?? [] as $i => $item)
        @if(!empty($item['returning']))
        @php $resolved = $resolveReturnedItem($item); @endphp
        <tr>
            <td>{{ $i + 1 }}</td>
            <td><strong>{{ $resolved['stock_no'] }}</strong></td>
            <td>{{ $resolved['description'] }}</td>
            <td style="text-align:right;" class="credit">(${{ number_format($item['credit_amount'] ?? 0, 2) }})</td>
        </tr>
        @endif
        @endforeach
        <tr style="background:#fef2f2;">
            <td colspan="3" style="text-align:right;font-weight:900;color:#B8463F;">Total Credit:</td>
            <td style="text-align:right;font-weight:900;color:#B8463F;">(${{ number_format($exchange->total_credit, 2) }})</td>
        </tr>
    </tbody>
</table>

{{-- NEW ITEMS --}}
<div class="section-title">✅ New Item(s) Taken</div>
<table class="items">
    <thead>
        <tr>
            <th>#</th>
            <th>Type</th>
            <th>Description</th>
            <th style="text-align:right;">Price</th>
        </tr>
    </thead>
    <tbody>
        @foreach($newItems as $i => $item)
        @php
            $qty  = intval($item['qty'] ?? 1);
            $line = (isset($item['sale_price_override']) && floatval($item['sale_price_override']) > 0)
                ? floatval($item['sale_price_override'])
                : floatval($item['sold_price'] ?? 0) * $qty;
            $typeLabel = match($item['selection_type'] ?? null) {
                'stock' => 'STOCK — ' . ($item['stock_no_display_persist'] ?? $item['stock_no_display'] ?? ''),
                'custom_order' => 'CUSTOM ORDER',
                default => 'NON-TAG',
            };
            $description = $item['custom_description'] ?? '—';
        @endphp
        <tr>
            <td>{{ $i + 1 }}</td>
            <td><span class="badge-type">{{ $typeLabel }}</span></td>
            <td>{{ $description }} @if($qty > 1) &times;{{ $qty }} @endif @if(!empty($item['is_tax_free'])) <em style="color:#888;">(tax free)</em> @endif</td>
            <td style="text-align:right;color:#059669;font-weight:700;">${{ number_format($line, 2) }}</td>
        </tr>
        @endforeach
        <tr style="background:#f0fdf4;">
            <td colspan="3" style="text-align:right;font-weight:900;color:#059669;">New Sale Total (incl. tax):</td>
            <td style="text-align:right;font-weight:900;color:#059669;">${{ number_format($exchange->new_sale_amount, 2) }}</td>
        </tr>
    </tbody>
</table>

{{-- FINANCIAL SUMMARY --}}
<table class="summary">
    <tr>
        <td style="color:#555;">Credit Applied:</td>
        <td style="text-align:right;" class="credit">-${{ number_format($exchange->total_credit, 2) }}</td>
    </tr>
    <tr>
        <td style="color:#555;">New Item(s) Total:</td>
        <td style="text-align:right;">${{ number_format($exchange->new_sale_amount, 2) }}</td>
    </tr>
    @if($exchange->reason)
    <tr>
        <td colspan="2" style="color:#555;font-style:italic;padding-top:6px;">Reason: {{ $exchange->reason }}</td>
    </tr>
    @endif
    <tr class="total-row">
        @if($diff > 0)
        <td>Amount Paid by Customer:</td>
        <td style="text-align:right;" class="charge">+${{ number_format($diff, 2) }}</td>
        @elseif($diff < 0)
        <td>Amount Refunded to Customer:</td>
        <td style="text-align:right;" class="credit">-${{ number_format(abs($diff), 2) }}</td>
        @else
        <td>Net Difference:</td>
        <td style="text-align:right;">$0.00 (Same Value)</td>
        @endif
    </tr>
    @if($diff != 0)
        @if($exchange->is_split_payment)
            @foreach($exchange->split_payments ?? [] as $p)
            <tr>
                <td style="color:#555;font-size:9px;">Payment ({{ strtoupper($p['method'] ?? '') }}):</td>
                <td style="text-align:right;font-size:9px;color:#555;">${{ number_format($p['amount'] ?? 0, 2) }}</td>
            </tr>
            @endforeach
        @elseif($exchange->difference_payment_method)
        <tr>
            <td style="color:#555;font-size:9px;">Payment Method:</td>
            <td style="text-align:right;font-size:9px;color:#555;">{{ $exchange->difference_payment_method }}</td>
        </tr>
        @endif
    @endif
</table>

<div class="sig clearfix" style="margin-top:20px;">
    <div class="sig-box">Customer Signature<br>Date: ___________</div>
    <div class="sig-box right">Staff Initials<br>Date: ___________</div>
</div>

<div style="text-align:center;font-size:8.5px;color:#555;margin-top:12px;border-top:1px dashed #ccc;padding-top:6px;">
    Thank you for shopping at {{ $store?->name ?? 'JeewelTag' }}. Please retain this receipt for your records.<br>
    {{ $store?->phone ?? '' }} | {{ $store?->email ?? '' }}
</div>

</body>
</html>