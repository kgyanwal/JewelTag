<x-filament-panels::page>

<style>
.gmr-root { font-family: 'Inter', sans-serif; }

/* Hero */
.gmr-hero {
    background: linear-gradient(135deg, #0B3D3C 0%, #07292A 60%, #0B3D3C 100%);
    border-radius: 18px;
    padding: 28px 32px;
    margin-bottom: 20px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 14px 36px rgba(11,61,60,0.28);
}
.gmr-hero::before {
    content: '';
    position: absolute; top: -80px; right: -80px;
    width: 280px; height: 280px; border-radius: 50%;
    background: radial-gradient(circle, rgba(201,162,75,0.15) 0%, transparent 65%);
}
.gmr-hero::after {
    content: '';
    position: absolute; bottom: -40px; left: 40px;
    width: 160px; height: 160px; border-radius: 50%;
    background: radial-gradient(circle, rgba(201,162,75,0.08) 0%, transparent 65%);
}
.gmr-eyebrow {
    font-size: 10px; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase;
    color: rgba(228,205,142,0.8); margin-bottom: 8px; display: flex; align-items: center; gap: 8px;
}
.gmr-eyebrow::before { content: ''; width: 20px; height: 1.5px; background: #C9A24B; display: block; }
.gmr-title { font-family: 'Fraunces', serif; font-size: 26px; font-weight: 700; color: #fff; margin: 0 0 4px; }
.gmr-sub { font-size: 12.5px; color: rgba(248,246,241,0.55); max-width: 560px; }

/* KPI grid */
.gmr-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; margin-bottom: 24px; }
.gmr-kpi {
    background: #fff;
    border: 1px solid rgba(11,61,60,0.08);
    border-radius: 14px;
    padding: 18px 20px;
    box-shadow: 0 2px 8px rgba(11,61,60,0.04);
    position: relative;
    overflow: hidden;
}
.gmr-kpi::before {
    content: '';
    position: absolute; top: 0; left: 0; right: 0; height: 3px;
    border-radius: 14px 14px 0 0;
}
.gmr-kpi.green::before { background: linear-gradient(90deg, #059669, #10b981); }
.gmr-kpi.blue::before  { background: linear-gradient(90deg, #0B3D3C, #3D6B63); }
.gmr-kpi.gold::before  { background: linear-gradient(90deg, #C9A24B, #E4CD8E); }
.gmr-kpi.red::before   { background: linear-gradient(90deg, #B8463F, #ef4444); }
.gmr-kpi.gray::before  { background: linear-gradient(90deg, #64748b, #94a3b8); }

.gmr-kpi-label { font-size: 9.5px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #64748b; margin-bottom: 6px; }
.gmr-kpi-value { font-family: 'Fraunces', serif; font-size: 24px; font-weight: 700; line-height: 1; color: #1A2E2D; }
.gmr-kpi.green .gmr-kpi-value { color: #059669; }
.gmr-kpi.red .gmr-kpi-value   { color: #B8463F; }
.gmr-kpi.gold .gmr-kpi-value  { color: #8A6428; }
.gmr-kpi-sub { font-size: 10px; color: #94a3b8; margin-top: 4px; }

/* Margin meter */
.gmr-meter-card {
    background: #fff;
    border: 1px solid rgba(11,61,60,0.08);
    border-radius: 14px;
    padding: 20px 24px;
    margin-bottom: 24px;
    box-shadow: 0 2px 10px rgba(11,61,60,0.04);
    display: flex; align-items: center; gap: 24px;
}
.gmr-meter-track {
    flex: 1;
    height: 12px;
    background: rgba(11,61,60,0.06);
    border-radius: 999px;
    overflow: hidden;
    position: relative;
}
.gmr-meter-fill {
    height: 100%;
    border-radius: 999px;
    transition: width 600ms cubic-bezier(0.16,1,0.3,1);
}
.gmr-meter-labels { display: flex; justify-content: space-between; margin-top: 6px; font-size: 10px; color: #94a3b8; }
.gmr-meter-pct { font-family: 'Fraunces', serif; font-size: 28px; font-weight: 700; white-space: nowrap; }

/* View tabs */
.gmr-tabs { display: flex; gap: 6px; margin-bottom: 20px; background: rgba(11,61,60,0.04); padding: 4px; border-radius: 12px; width: fit-content; }
.gmr-tab {
    padding: 8px 18px; border-radius: 8px; font-size: 12px; font-weight: 600;
    cursor: pointer; border: none; background: transparent; color: #5B6764;
    transition: all 150ms ease;
}
.gmr-tab.active {
    background: #0B3D3C; color: #E4CD8E;
    box-shadow: 0 2px 8px rgba(11,61,60,0.2);
}
.gmr-tab:hover:not(.active) { background: rgba(11,61,60,0.08); color: #0B3D3C; }

/* Breakdown cards */
.gmr-breakdown { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 12px; margin-bottom: 24px; }
.gmr-bk-card {
    background: #fff; border: 1px solid rgba(11,61,60,0.08); border-radius: 12px; padding: 16px 18px;
    box-shadow: 0 2px 8px rgba(11,61,60,0.04);
}
.gmr-bk-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; }
.gmr-bk-name { font-size: 13px; font-weight: 700; color: #1A2E2D; }
.gmr-bk-pct { font-size: 11px; font-weight: 800; padding: 3px 10px; border-radius: 999px; }
.gmr-bk-bar { height: 6px; background: rgba(11,61,60,0.06); border-radius: 999px; overflow: hidden; margin: 8px 0; }
.gmr-bk-fill { height: 100%; border-radius: 999px; }
.gmr-bk-figures { display: flex; justify-content: space-between; font-size: 11px; color: #64748b; }
.gmr-bk-figures strong { color: #1A2E2D; font-weight: 700; }
.gmr-rank { font-size: 16px; width: 28px; flex-shrink: 0; }
.gmr-bk-top-inner { display: flex; align-items: center; gap: 8px; }

/* Leaderboard list */
.gmr-list { display: flex; flex-direction: column; gap: 8px; margin-bottom: 24px; }
.gmr-list-item {
    background: #fff; border: 1px solid rgba(11,61,60,0.08); border-radius: 12px;
    padding: 14px 18px; display: flex; align-items: center; gap: 14px;
    box-shadow: 0 1px 4px rgba(11,61,60,0.04);
    transition: box-shadow 150ms ease;
}
.gmr-list-item:hover { box-shadow: 0 6px 20px rgba(11,61,60,0.10); }
.gmr-list-main { flex: 1; min-width: 0; }
.gmr-list-name { font-size: 13px; font-weight: 700; color: #1A2E2D; margin-bottom: 6px; }
.gmr-list-bar-track { height: 6px; background: rgba(11,61,60,0.06); border-radius: 999px; overflow: hidden; }
.gmr-list-bar-fill { height: 100%; border-radius: 999px; }
.gmr-list-right { text-align: right; min-width: 100px; }
.gmr-list-margin { font-family: 'Fraunces', serif; font-size: 17px; font-weight: 700; }
.gmr-list-pct { font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 999px; margin-top: 4px; display: inline-block; }

/* Table wrapper */
.gmr-table-wrap { background: #fff; border-radius: 14px; border: 1px solid rgba(11,61,60,0.08); overflow: hidden; box-shadow: 0 4px 16px rgba(11,61,60,0.06); }
</style>

@php
    $stats     = $this->getStats();
    $mPct      = $stats['margin_pct'];
    $mColor    = $mPct >= 50 ? '#059669' : ($mPct >= 30 ? '#d97706' : '#B8463F');
    $mBg       = $mPct >= 50 ? '#ecfdf5' : ($mPct >= 30 ? '#fffbeb' : '#fef2f2');
    $medals    = ['🥇','🥈','🥉'];
@endphp

<div class="gmr-root">

    {{-- HERO --}}
    <div class="gmr-hero">
        <div class="gmr-eyebrow">Analytics & Reports</div>
        <h1 class="gmr-title">Gross Margin Report</h1>
        <p class="gmr-sub">Revenue minus cost of goods — see exactly where your profit comes from, down to staff, category, and vendor.</p>
    </div>

    {{-- FILTER FORM --}}
    <div style="background:#fff;border:1px solid rgba(11,61,60,0.08);border-radius:14px;padding:16px 20px;margin-bottom:20px;box-shadow:0 2px 8px rgba(11,61,60,0.04);overflow:visible;position:relative;z-index:40;">
        {{ $this->form }}
    </div>

    {{-- KPI CARDS --}}
    <div class="gmr-kpis">
        <div class="gmr-kpi green">
            <div class="gmr-kpi-label">Gross Profit</div>
            <div class="gmr-kpi-value">${{ number_format($stats['margin'], 0) }}</div>
            <div class="gmr-kpi-sub">Revenue − Cost of goods</div>
        </div>
        <div class="gmr-kpi blue">
            <div class="gmr-kpi-label">Total Revenue</div>
            <div class="gmr-kpi-value">${{ number_format($stats['revenue'], 0) }}</div>
            <div class="gmr-kpi-sub">Tagged stock only</div>
        </div>
        <div class="gmr-kpi gray">
            <div class="gmr-kpi-label">Cost of Goods</div>
            <div class="gmr-kpi-value">${{ number_format($stats['cost'], 0) }}</div>
            <div class="gmr-kpi-sub">Acquisition cost total</div>
        </div>
        <div class="gmr-kpi {{ $mPct >= 50 ? 'green' : ($mPct >= 30 ? 'gold' : 'red') }}">
            <div class="gmr-kpi-label">Margin %</div>
            <div class="gmr-kpi-value">{{ $stats['margin_pct'] }}%</div>
            <div class="gmr-kpi-sub">{{ $mPct >= 50 ? 'Excellent' : ($mPct >= 30 ? 'Good' : 'Below target') }}</div>
        </div>
        <div class="gmr-kpi blue">
            <div class="gmr-kpi-label">Avg Margin / Sale</div>
            <div class="gmr-kpi-value">${{ number_format($stats['avg_margin_per_sale'], 0) }}</div>
            <div class="gmr-kpi-sub">{{ number_format($stats['sale_count']) }} tagged sales</div>
        </div>
    </div>

    {{-- MARGIN METER --}}
    <div class="gmr-meter-card">
        <div>
            <div style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:4px;">Overall Gross Margin</div>
            <div class="gmr-meter-pct" style="color: {{ $mColor }};">{{ $stats['margin_pct'] }}%</div>
        </div>
        <div style="flex:1;">
            <div class="gmr-meter-track">
                <div class="gmr-meter-fill" style="width:{{ min(100, $stats['margin_pct']) }}%;background:{{ $mColor }};"></div>
            </div>
            <div class="gmr-meter-labels">
                <span>0%</span>
                <span style="color:#d97706;">30% Good</span>
                <span style="color:#059669;">50% Excellent</span>
                <span>100%</span>
            </div>
        </div>
        <div style="text-align:right;min-width:160px;">
            <div style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:4px;">Profit</div>
            <div style="font-family:'Fraunces',serif;font-size:18px;font-weight:700;color:{{ $mColor }};">${{ number_format($stats['margin'], 2) }}</div>
            <div style="font-size:10px;color:#94a3b8;margin-top:2px;">on ${{ number_format($stats['revenue'], 2) }} revenue</div>
        </div>
    </div>

    {{-- VIEW TABS --}}
    <div class="gmr-tabs">
        <button wire:click="setView('overview')"  class="gmr-tab {{ $this->active_view === 'overview'  ? 'active' : '' }}">📊 Overview</button>
        <button wire:click="setView('staff')"     class="gmr-tab {{ $this->active_view === 'staff'     ? 'active' : '' }}">👤 By Staff</button>
        <button wire:click="setView('category')"  class="gmr-tab {{ $this->active_view === 'category'  ? 'active' : '' }}">🏷️ By Category</button>
        <button wire:click="setView('vendor')"    class="gmr-tab {{ $this->active_view === 'vendor'    ? 'active' : '' }}">🏭 By Vendor</button>
    </div>

    {{-- STAFF VIEW --}}
    @if($this->active_view === 'staff')
        @php $staffData = $this->getStaffBreakdown(); $maxMargin = collect($staffData)->max('margin') ?: 1; @endphp
        <div class="gmr-list">
            @forelse($staffData as $i => $row)
            @php
                $pct   = $row['pct'];
                $mc    = $pct >= 50 ? '#059669' : ($pct >= 30 ? '#d97706' : '#B8463F');
                $mbg   = $pct >= 50 ? '#ecfdf5' : ($pct >= 30 ? '#fffbeb' : '#fef2f2');
                $barW  = $maxMargin > 0 ? round(($row['margin'] / $maxMargin) * 100) : 0;
                $barC  = $i === 0 ? '#C9A24B' : ($i === 1 ? '#94a3b8' : ($i === 2 ? '#cd7f32' : '#3D6B63'));
            @endphp
            <div class="gmr-list-item">
                <span class="gmr-rank">{{ $medals[$i] ?? '#'.($i+1) }}</span>
                <div class="gmr-list-main">
                    <div class="gmr-list-name">{{ $row['name'] }}</div>
                    <div style="font-size:10px;color:#94a3b8;margin-bottom:6px;">
                        {{ number_format($row['sales']) }} sales &nbsp;·&nbsp;
                        Revenue: ${{ number_format($row['revenue'], 2) }} &nbsp;·&nbsp;
                        Cost: ${{ number_format($row['cost'], 2) }}
                    </div>
                    <div class="gmr-list-bar-track">
                        <div class="gmr-list-bar-fill" style="width:{{ $barW }}%;background:{{ $barC }};"></div>
                    </div>
                </div>
                <div class="gmr-list-right">
                    <div class="gmr-list-margin" style="color:{{ $mc }};">${{ number_format($row['margin'], 2) }}</div>
                    <span class="gmr-list-pct" style="background:{{ $mbg }};color:{{ $mc }};">{{ $pct }}%</span>
                </div>
            </div>
            @empty
            <div style="text-align:center;color:#94a3b8;padding:32px;font-style:italic;">No staff margin data for this period.</div>
            @endforelse
        </div>
    @endif

    {{-- CATEGORY VIEW --}}
    @if($this->active_view === 'category')
        @php $catData = $this->getCategoryBreakdown(); $maxCatRev = collect($catData)->max('revenue') ?: 1; @endphp
        <div class="gmr-breakdown">
            @forelse($catData as $i => $row)
            @php
                $pct  = $row['pct'];
                $mc   = $pct >= 50 ? '#059669' : ($pct >= 30 ? '#d97706' : '#B8463F');
                $mbg  = $pct >= 50 ? '#ecfdf5' : ($pct >= 30 ? '#fffbeb' : '#fef2f2');
                $barW = $maxCatRev > 0 ? round(($row['revenue'] / $maxCatRev) * 100) : 0;
                $barC = $pct >= 50 ? '#059669' : ($pct >= 30 ? '#d97706' : '#B8463F');
            @endphp
            <div class="gmr-bk-card">
                <div class="gmr-bk-top">
                    <div class="gmr-bk-top-inner">
                        <span class="gmr-rank">{{ $medals[$i] ?? '' }}</span>
                        <div class="gmr-bk-name">{{ $row['name'] }}</div>
                    </div>
                    <span class="gmr-bk-pct" style="background:{{ $mbg }};color:{{ $mc }};">{{ $pct }}%</span>
                </div>
                <div class="gmr-bk-bar">
                    <div class="gmr-bk-fill" style="width:{{ $barW }}%;background:{{ $barC }};"></div>
                </div>
                <div class="gmr-bk-figures">
                    <span>Revenue: <strong>${{ number_format($row['revenue'], 2) }}</strong></span>
                    <span>Profit: <strong style="color:{{ $mc }};">${{ number_format($row['margin'], 2) }}</strong></span>
                    <span>{{ $row['item_count'] }} items</span>
                </div>
            </div>
            @empty
            <div style="text-align:center;color:#94a3b8;padding:32px;font-style:italic;grid-column:1/-1;">No category data for this period.</div>
            @endforelse
        </div>
    @endif

    {{-- VENDOR VIEW --}}
    @if($this->active_view === 'vendor')
        @php $venData = $this->getVendorBreakdown(); $maxVenRev = collect($venData)->max('revenue') ?: 1; @endphp
        <div class="gmr-list">
            @forelse($venData as $i => $row)
            @php
                $pct  = $row['pct'];
                $mc   = $pct >= 50 ? '#059669' : ($pct >= 30 ? '#d97706' : '#B8463F');
                $mbg  = $pct >= 50 ? '#ecfdf5' : ($pct >= 30 ? '#fffbeb' : '#fef2f2');
                $barW = $maxVenRev > 0 ? round(($row['revenue'] / $maxVenRev) * 100) : 0;
                $barC = $i === 0 ? '#C9A24B' : ($i === 1 ? '#94a3b8' : ($i === 2 ? '#cd7f32' : '#3D6B63'));
            @endphp
            <div class="gmr-list-item">
                <span class="gmr-rank">{{ $medals[$i] ?? '#'.($i+1) }}</span>
                <div class="gmr-list-main">
                    <div class="gmr-list-name">{{ $row['name'] }}</div>
                    <div style="font-size:10px;color:#94a3b8;margin-bottom:6px;">
                        {{ $row['item_count'] }} items sold &nbsp;·&nbsp;
                        Revenue: ${{ number_format($row['revenue'], 2) }} &nbsp;·&nbsp;
                        Cost: ${{ number_format($row['cost'], 2) }}
                    </div>
                    <div class="gmr-list-bar-track">
                        <div class="gmr-list-bar-fill" style="width:{{ $barW }}%;background:{{ $barC }};"></div>
                    </div>
                </div>
                <div class="gmr-list-right">
                    <div class="gmr-list-margin" style="color:{{ $mc }};">${{ number_format($row['margin'], 2) }}</div>
                    <span class="gmr-list-pct" style="background:{{ $mbg }};color:{{ $mc }};">{{ $pct }}%</span>
                </div>
            </div>
            @empty
            <div style="text-align:center;color:#94a3b8;padding:32px;font-style:italic;">No vendor data for this period.</div>
            @endforelse
        </div>
    @endif

    {{-- OVERVIEW / SALE-LEVEL TABLE --}}
    @if($this->active_view === 'overview')

        {{-- Quick breakdown panels --}}
        @php
            $staffTop = array_slice($this->getStaffBreakdown(), 0, 3);
            $catTop   = array_slice($this->getCategoryBreakdown(), 0, 3);
            $venTop   = array_slice($this->getVendorBreakdown(), 0, 3);
        @endphp
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:24px;">

            {{-- Top Staff --}}
            <div style="background:#fff;border:1px solid rgba(11,61,60,0.08);border-radius:14px;padding:16px;box-shadow:0 2px 8px rgba(11,61,60,0.04);">
                <div style="font-size:10px;font-weight:900;color:#0B3D3C;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:12px;">👤 Top Staff by Margin</div>
                @forelse($staffTop as $i => $row)
                @php $mc = $row['pct'] >= 50 ? '#059669' : ($row['pct'] >= 30 ? '#d97706' : '#B8463F'); @endphp
                <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid rgba(11,61,60,0.05);">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span style="font-size:14px;">{{ $medals[$i] ?? '#'.($i+1) }}</span>
                        <div>
                            <div style="font-size:12px;font-weight:700;color:#1A2E2D;">{{ $row['name'] }}</div>
                            <div style="font-size:10px;color:#94a3b8;">{{ $row['pct'] }}% margin</div>
                        </div>
                    </div>
                    <div style="font-family:'Fraunces',serif;font-size:14px;font-weight:700;color:{{ $mc }};">${{ number_format($row['margin'], 0) }}</div>
                </div>
                @empty
                <div style="color:#94a3b8;font-size:11px;font-style:italic;padding:8px 0;">No data</div>
                @endforelse
                <button wire:click="setView('staff')" style="font-size:11px;color:#0B3D3C;font-weight:600;margin-top:10px;background:none;border:none;cursor:pointer;padding:0;">View all →</button>
            </div>

            {{-- Top Categories --}}
            <div style="background:#fff;border:1px solid rgba(11,61,60,0.08);border-radius:14px;padding:16px;box-shadow:0 2px 8px rgba(11,61,60,0.04);">
                <div style="font-size:10px;font-weight:900;color:#0B3D3C;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:12px;">🏷️ Top Categories</div>
                @forelse($catTop as $i => $row)
                @php $mc = $row['pct'] >= 50 ? '#059669' : ($row['pct'] >= 30 ? '#d97706' : '#B8463F'); @endphp
                <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid rgba(11,61,60,0.05);">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span style="font-size:14px;">{{ $medals[$i] ?? '#'.($i+1) }}</span>
                        <div>
                            <div style="font-size:12px;font-weight:700;color:#1A2E2D;">{{ $row['name'] }}</div>
                            <div style="font-size:10px;color:#94a3b8;">{{ $row['item_count'] }} items · {{ $row['pct'] }}%</div>
                        </div>
                    </div>
                    <div style="font-family:'Fraunces',serif;font-size:14px;font-weight:700;color:{{ $mc }};">${{ number_format($row['margin'], 0) }}</div>
                </div>
                @empty
                <div style="color:#94a3b8;font-size:11px;font-style:italic;padding:8px 0;">No data</div>
                @endforelse
                <button wire:click="setView('category')" style="font-size:11px;color:#0B3D3C;font-weight:600;margin-top:10px;background:none;border:none;cursor:pointer;padding:0;">View all →</button>
            </div>

            {{-- Top Vendors --}}
            <div style="background:#fff;border:1px solid rgba(11,61,60,0.08);border-radius:14px;padding:16px;box-shadow:0 2px 8px rgba(11,61,60,0.04);">
                <div style="font-size:10px;font-weight:900;color:#0B3D3C;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:12px;">🏭 Top Vendors</div>
                @forelse($venTop as $i => $row)
                @php $mc = $row['pct'] >= 50 ? '#059669' : ($row['pct'] >= 30 ? '#d97706' : '#B8463F'); @endphp
                <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid rgba(11,61,60,0.05);">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span style="font-size:14px;">{{ $medals[$i] ?? '#'.($i+1) }}</span>
                        <div>
                            <div style="font-size:12px;font-weight:700;color:#1A2E2D;">{{ $row['name'] }}</div>
                            <div style="font-size:10px;color:#94a3b8;">{{ $row['item_count'] }} items · {{ $row['pct'] }}%</div>
                        </div>
                    </div>
                    <div style="font-family:'Fraunces',serif;font-size:14px;font-weight:700;color:{{ $mc }};">${{ number_format($row['margin'], 0) }}</div>
                </div>
                @empty
                <div style="color:#94a3b8;font-size:11px;font-style:italic;padding:8px 0;">No data</div>
                @endforelse
                <button wire:click="setView('vendor')" style="font-size:11px;color:#0B3D3C;font-weight:600;margin-top:10px;background:none;border:none;cursor:pointer;padding:0;">View all →</button>
            </div>

        </div>

        {{-- Sale-level table --}}
        <div style="font-size:13px;font-weight:800;color:#0B3D3C;margin-bottom:10px;display:flex;align-items:center;gap:8px;">
            <svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/>
            </svg>
            Sale-Level Margin Detail
        </div>
        <div class="gmr-table-wrap">
            {{ $this->table }}
        </div>
    @endif

</div>
</x-filament-panels::page>