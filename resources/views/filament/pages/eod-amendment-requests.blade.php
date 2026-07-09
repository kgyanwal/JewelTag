<x-filament-panels::page>
<style>
.ear-hero {
    background: linear-gradient(135deg, #0B3D3C 0%, #07292A 60%, #0B3D3C 100%);
    border-radius: 16px; padding: 22px 28px; margin-bottom: 20px;
    position: relative; overflow: hidden;
}
.ear-hero::before {
    content: ''; position: absolute; top: -50px; right: -50px;
    width: 180px; height: 180px; border-radius: 50%;
    background: radial-gradient(circle, rgba(201,162,75,0.15) 0%, transparent 65%);
}
.ear-eyebrow { font-size: 10px; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; color: rgba(228,205,142,0.75); margin-bottom: 6px; display: flex; align-items: center; gap: 8px; }
.ear-eyebrow::before { content: ''; width: 18px; height: 1.5px; background: #C9A24B; display: block; }
.ear-title { font-size: 22px; font-weight: 700; color: #fff; margin: 0 0 4px; }
.ear-sub { font-size: 12px; color: rgba(248,246,241,0.5); }
.ear-kpis { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 20px; }
.ear-kpi { background: #fff; border: 1px solid rgba(11,61,60,0.08); border-radius: 12px; padding: 14px 18px; position: relative; overflow: hidden; box-shadow: 0 2px 6px rgba(11,61,60,0.04); }
.ear-kpi::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; border-radius: 12px 12px 0 0; }
.ear-kpi.warning::before { background: linear-gradient(90deg, #d97706, #fbbf24); }
.ear-kpi.success::before { background: linear-gradient(90deg, #059669, #10b981); }
.ear-kpi.danger::before  { background: linear-gradient(90deg, #B8463F, #ef4444); }
.ear-kpi-label { font-size: 9px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #64748b; margin-bottom: 4px; }
.ear-kpi-val { font-size: 28px; font-weight: 800; line-height: 1; }
.ear-kpi.warning .ear-kpi-val { color: #b45309; }
.ear-kpi.success .ear-kpi-val { color: #059669; }
.ear-kpi.danger  .ear-kpi-val { color: #B8463F; }
.ear-kpi-sub { font-size: 10px; color: #94a3b8; margin-top: 3px; }
.ear-flow { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 16px 20px; margin-bottom: 20px; }
.ear-flow-title { font-size: 11px; font-weight: 800; color: #065f46; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 12px; }
.ear-steps { display: flex; gap: 8px; flex-wrap: wrap; }
.ear-step { display: flex; align-items: flex-start; gap: 8px; flex: 1; min-width: 120px; }
.ear-step-num { width: 24px; height: 24px; border-radius: 50%; background: #059669; color: #fff; font-size: 11px; font-weight: 800; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.ear-step-body strong { display: block; font-size: 11px; font-weight: 700; color: #065f46; }
.ear-step-body span { font-size: 10px; color: #16a34a; }
.ear-table-wrap { background: #fff; border-radius: 12px; border: 1px solid rgba(11,61,60,0.08); overflow: hidden; box-shadow: 0 2px 10px rgba(11,61,60,0.05); }
</style>

@php
    $pending  = \App\Models\EodAmendmentRequest::where('status','pending')->count();
    $approved = \App\Models\EodAmendmentRequest::where('status','approved')->count();
    $rejected = \App\Models\EodAmendmentRequest::where('status','rejected')->count();
@endphp

<div class="ear-hero">
    <div class="ear-eyebrow">Superadmin · Finance Controls</div>
    <h1 class="ear-title">EOD Amendment Requests</h1>
    <p class="ear-sub">Review and approve staff requests to unlock a completed End of Day register.</p>
</div>

<div class="ear-kpis">
    <div class="ear-kpi warning">
        <div class="ear-kpi-label">Pending Review</div>
        <div class="ear-kpi-val">{{ $pending }}</div>
        <div class="ear-kpi-sub">Awaiting your action</div>
    </div>
    <div class="ear-kpi success">
        <div class="ear-kpi-label">Approved</div>
        <div class="ear-kpi-val">{{ $approved }}</div>
        <div class="ear-kpi-sub">All time</div>
    </div>
    <div class="ear-kpi danger">
        <div class="ear-kpi-label">Rejected</div>
        <div class="ear-kpi-val">{{ $rejected }}</div>
        <div class="ear-kpi-sub">All time</div>
    </div>
</div>

<div class="ear-flow">
    <div class="ear-flow-title">📋 How the workflow works</div>
    <div class="ear-steps">
        <div class="ear-step">
            <div class="ear-step-num">1</div>
            <div class="ear-step-body"><strong>Staff requests</strong><span>Submits reason & invoice from EOD page</span></div>
        </div>
        <div class="ear-step">
            <div class="ear-step-num">2</div>
            <div class="ear-step-body"><strong>You review</strong><span>Approve or reject with notes</span></div>
        </div>
        <div class="ear-step">
            <div class="ear-step-num">3</div>
            <div class="ear-step-body"><strong>Staff notified</strong><span>Gets a Filament notification</span></div>
        </div>
        <div class="ear-step">
            <div class="ear-step-num">4</div>
            <div class="ear-step-body"><strong>Unlock EOD</strong><span>Click "Unlock EOD" on approved row</span></div>
        </div>
        <div class="ear-step">
            <div class="ear-step-num">5</div>
            <div class="ear-step-body"><strong>Staff re-posts</strong><span>Adds payment & re-posts closing</span></div>
        </div>
    </div>
</div>

<div class="ear-table-wrap">
    {{ $this->table }}
</div>

<x-filament-actions::modals />
</x-filament-panels::page>