<x-filament-panels::page>

<style>
.wer-hero {
    background: linear-gradient(135deg, #0B3D3C 0%, #07292A 60%, #0B3D3C 100%);
    border-radius: 16px;
    padding: 24px 28px;
    margin-bottom: 20px;
    position: relative;
    overflow: hidden;
}
.wer-hero::before {
    content: '';
    position: absolute; top: -60px; right: -60px;
    width: 220px; height: 220px; border-radius: 50%;
    background: radial-gradient(circle, rgba(201,162,75,0.18) 0%, transparent 65%);
}
.wer-eyebrow {
    font-size: 10px; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase;
    color: rgba(228,205,142,0.75); margin-bottom: 6px; display: flex; align-items: center; gap: 8px;
}
.wer-eyebrow::before { content: ''; width: 18px; height: 1.5px; background: #C9A24B; display: block; }
.wer-title { font-size: 22px; font-weight: 700; color: #fff; margin: 0 0 4px; }
.wer-sub { font-size: 12px; color: rgba(248,246,241,0.5); max-width: 520px; }

.wer-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; margin-bottom: 20px; }
.wer-kpi {
    background: #fff; border: 1px solid rgba(11,61,60,0.08); border-radius: 12px;
    padding: 14px 16px; position: relative; overflow: hidden;
    box-shadow: 0 2px 6px rgba(11,61,60,0.04);
}
.wer-kpi::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; border-radius: 12px 12px 0 0;
}
.wer-kpi.red::before    { background: linear-gradient(90deg, #B8463F, #ef4444); }
.wer-kpi.orange::before { background: linear-gradient(90deg, #d97706, #fbbf24); }
.wer-kpi.green::before  { background: linear-gradient(90deg, #059669, #10b981); }
.wer-kpi.blue::before   { background: linear-gradient(90deg, #0B3D3C, #3D6B63); }
.wer-kpi.gray::before   { background: linear-gradient(90deg, #64748b, #94a3b8); }
.wer-kpi-label { font-size: 9px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #64748b; margin-bottom: 4px; }
.wer-kpi-val { font-size: 26px; font-weight: 800; line-height: 1; color: #1A2E2D; }
.wer-kpi.red    .wer-kpi-val { color: #B8463F; }
.wer-kpi.orange .wer-kpi-val { color: #b45309; }
.wer-kpi.green  .wer-kpi-val { color: #059669; }
.wer-kpi-sub { font-size: 10px; color: #94a3b8; margin-top: 3px; }

.wer-filter-bar {
    background: #fff; border: 1px solid rgba(11,61,60,0.08); border-radius: 12px;
    padding: 14px 18px; margin-bottom: 16px;
    box-shadow: 0 2px 6px rgba(11,61,60,0.04);
    display: flex; align-items: center; gap: 16px; flex-wrap: wrap;
}
.wer-filter-label { font-size: 11px; font-weight: 700; color: #0B3D3C; text-transform: uppercase; letter-spacing: 0.06em; white-space: nowrap; }

.wer-tips {
    background: #fffbeb; border: 1px solid #fde68a; border-radius: 12px;
    padding: 14px 18px; margin-bottom: 16px;
    display: flex; gap: 12px; align-items: flex-start;
}
.wer-tips-icon { font-size: 18px; flex-shrink: 0; margin-top: 1px; }
.wer-tips-body { font-size: 12px; color: #78350f; line-height: 1.6; }
.wer-tips-body strong { font-weight: 700; }

.wer-table-wrap { background: #fff; border-radius: 12px; border: 1px solid rgba(11,61,60,0.08); overflow: hidden; box-shadow: 0 2px 10px rgba(11,61,60,0.05); }
</style>

@php $stats = $this->getStats(); @endphp

{{-- HERO --}}
<div class="wer-hero">
    <div class="wer-eyebrow">CRM · Customer Care</div>
    <h1 class="wer-title">Warranty Expiry Reminders</h1>
    <p class="wer-sub">Reach out before warranties expire — invite customers in for a free inspection and offer renewal or an upgrade.</p>
</div>

{{-- KPI CARDS --}}
<div class="wer-kpis">
    <div class="wer-kpi red">
        <div class="wer-kpi-label">Already Expired</div>
        <div class="wer-kpi-val">{{ $stats['expiredCount'] }}</div>
        <div class="wer-kpi-sub">Call immediately</div>
    </div>
    <div class="wer-kpi orange">
        <div class="wer-kpi-label">Expiring in 30 days</div>
        <div class="wer-kpi-val">{{ $stats['within30'] }}</div>
        <div class="wer-kpi-sub">Urgent priority</div>
    </div>
    <div class="wer-kpi orange">
        <div class="wer-kpi-label">Expiring in 60 days</div>
        <div class="wer-kpi-val">{{ $stats['within60'] }}</div>
        <div class="wer-kpi-sub">Schedule soon</div>
    </div>
    <div class="wer-kpi blue">
        <div class="wer-kpi-label">Expiring in 90 days</div>
        <div class="wer-kpi-val">{{ $stats['within90'] }}</div>
        <div class="wer-kpi-sub">Plan ahead</div>
    </div>
    <div class="wer-kpi green">
        <div class="wer-kpi-label">Reminders Set</div>
        <div class="wer-kpi-val">{{ $stats['withReminder'] }}</div>
        <div class="wer-kpi-sub">In next 90 days</div>
    </div>
    <div class="wer-kpi gray">
        <div class="wer-kpi-label">No Reminder Yet</div>
        <div class="wer-kpi-val">{{ $stats['withoutReminder'] }}</div>
        <div class="wer-kpi-sub">Action needed</div>
    </div>
</div>

{{-- CALL SCRIPT TIP --}}
<div class="wer-tips">
    <div class="wer-tips-icon">💡</div>
    <div class="wer-tips-body">
        <strong>Suggested call script:</strong>
        "Hi [Name], this is [Staff] from JewelTag. I'm calling because your jewelry warranty is expiring soon and we'd love to invite you in for a complimentary inspection —
        we'll clean and check your piece, and can talk about renewal options while you're here. When works best for you?"
    </div>
</div>

{{-- FILTER --}}
<div class="wer-filter-bar">
    <span class="wer-filter-label">Showing:</span>
    <div style="flex: 1; max-width: 280px;">
        {{ $this->form }}
    </div>
    @if($stats['expiredCount'] > 0)
    <span style="background:#fef2f2;color:#B8463F;font-size:11px;font-weight:700;padding:4px 12px;border-radius:999px;">
        ⚠️ {{ $stats['expiredCount'] }} already expired — contact immediately
    </span>
    @endif
</div>

{{-- TABLE --}}
<div class="wer-table-wrap">
    {{ $this->table }}
</div>

<x-filament-actions::modals />

</x-filament-panels::page>