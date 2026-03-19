<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Repair Job Packet - {{ $repair->repair_no }}</title>
    <style>
        @page { margin: 0.4in; size: Letter; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 12px; color: #000; line-height: 1.4; margin: 0; padding: 0.25in; background: #f0f9f8; }
        .page-break { page-break-after: always; }
        .header { text-align: center; margin-bottom: 12px; padding: 12px 8px; background: #249E94; color: white; border-radius: 6px; }
        .brand-name { font-size: 26px; font-weight: 900; margin: 0; text-transform: uppercase; }
        .document-title { text-align: center; font-size: 18px; font-weight: bold; margin: 15px 0; padding: 8px; background: #249E94; color: white; border-radius: 4px; }
        .meta-table { width: 100%; margin-bottom: 10px; background: white; padding: 10px; border: 1px solid #e0e0e0; border-radius: 4px; }
        .workshop-table { width: 100%; border-collapse: collapse; margin: 10px 0; background: white; }
        .workshop-table th { background: #249E94; color: white; padding: 10px; text-align: left; }
        .workshop-table td { border: 1px solid #eee; padding: 10px; vertical-align: top; }
        .notes-section { margin: 15px 0; border: 2px solid #249E94; padding: 15px; background: #fff; min-height: 100px; }
        .signature-box { width: 45%; float: left; border-top: 2px solid #249E94; padding-top: 10px; margin-top: 30px; text-align: center; }
        .clearfix::after { content: ""; display: table; clear: both; }
    </style>
</head>
<body>

<div class="header">
    <h1 class="brand-name">{{ strtoupper($repair->customer->name . ' ' . $repair->customer->last_name) }}</h1>
    <div>{{ $repair->store?->legal_name ?? 'Diamond Square' }}</div>
</div>

<div class="document-title">REPAIR WORKSHOP PACKET</div>

<table class="meta-table">
    <tr>
        <td><strong>Repair #:</strong> {{ $repair->repair_no }}</td>
        <td><strong>Staff:</strong> {{ $repair->salesPerson?->name ?? 'N/A' }}</td>
        <td align="right"><strong>Date:</strong> {{ $repair->created_at->format('m/d/Y') }}</td>
    </tr>
</table>

<table class="workshop-table">
    <thead>
        <tr>
            <th width="50%">Item Description</th>
            <th width="50%">Instructions / Reported Issue</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>
                <strong>{{ strtoupper($repair->item_description) }}</strong><br>
                @if($repair->is_from_store_stock)
                    <small>Stock #: {{ $repair->originalProduct?->barcode }}</small>
                @endif
            </td>
            <td>
                <strong>Issue:</strong> {{ $repair->reported_issue }}<br>
                @if($repair->is_warranty)
                    <div style="color: red; font-weight: bold;">*** WARRANTY REPAIR ***</div>
                @endif
            </td>
        </tr>
    </tbody>
</table>

<div class="notes-section">
    <strong>JEWELER NOTES:</strong><br><br>
</div>

<div class="clearfix">
    <div class="signature-box">Jeweler Signature</div>
    <div class="signature-box" style="float:right">QC Check</div>
</div>

</body>
</html>