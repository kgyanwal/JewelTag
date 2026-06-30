<x-filament-panels::page>

<style>
.cop-root { font-family: 'Inter', sans-serif; }
.cop-onyx       { --o-onyx: #15120F; --o-onyx-soft: #1F1A15; --o-felt: #FAF6EE; --o-brass: #B8863B; --o-brass-bright: #E0AE5C; --o-brass-dim: #8A6428; --o-loupe: #6FCF97; --o-loupe-dim: #3E7C5A; --o-ink: #211C16; --o-ink-soft: #5C5346; --o-brick: #B8463F; }

.cop-hero {
    background: linear-gradient(135deg, var(--o-onyx) 0%, var(--o-onyx-soft) 100%);
    border-radius: 18px;
    padding: 28px 32px;
    margin-bottom: 22px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 14px 36px rgba(21,18,15,0.28);
}
.cop-hero::before {
    content: '';
    position: absolute; top: -60px; right: -60px;
    width: 240px; height: 240px; border-radius: 50%;
    background: radial-gradient(circle, rgba(184,134,59,0.18) 0%, transparent 65%);
}
.cop-hero-eyebrow {
    font-size: 10px; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase;
    color: rgba(224,174,92,0.85); margin-bottom: 8px; display: flex; align-items: center; gap: 8px;
}
.cop-hero-eyebrow::before { content: ''; width: 18px; height: 1.5px; background: var(--o-brass-bright); }
.cop-hero-title { font-family: 'Fraunces', serif; font-size: 24px; font-weight: 700; color: #fff; margin: 0 0 4px; }
.cop-hero-sub { font-size: 12.5px; color: rgba(250,246,238,0.6); }

.cop-filters { display: flex; gap: 8px; margin-top: 16px; position: relative; z-index: 1; }
.cop-filter-btn {
    padding: 6px 16px; border-radius: 999px; font-size: 11px; font-weight: 700;
    border: 1px solid rgba(184,134,59,0.35); color: rgba(250,246,238,0.7); background: rgba(184,134,59,0.08);
    cursor: pointer; transition: all 150ms;
}
.cop-filter-btn.active { background: var(--o-brass-bright); color: var(--o-onyx); border-color: var(--o-brass-bright); }

.cop-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; margin-bottom: 24px; }
.cop-stat { background: #fff; border: 1px solid rgba(33,28,22,0.08); border-radius: 12px; padding: 14px 16px; box-shadow: 0 2px 8px rgba(33,28,22,0.04); }
.cop-stat-label { font-size: 9.5px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: var(--o-ink-soft); margin-bottom: 5px; }
.cop-stat-value { font-family: 'Fraunces', serif; font-size: 22px; font-weight: 700; color: var(--o-ink); line-height: 1; }
.cop-stat.warn .cop-stat-value { color: var(--o-brick); }
.cop-stat.gold .cop-stat-value { color: var(--o-brass-dim); }
.cop-stat.green .cop-stat-value { color: var(--o-loupe-dim); }

.cop-funnel { display: grid; grid-template-columns: repeat(6, 1fr); gap: 10px; margin-bottom: 28px; }
@media (max-width: 1100px) { .cop-funnel { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 640px) { .cop-funnel { grid-template-columns: 1fr; } }

.cop-stage-card { background: #fff; border-radius: 14px; border: 1px solid rgba(33,28,22,0.08); overflow: hidden; box-shadow: 0 4px 14px rgba(33,28,22,0.05); display: flex; flex-direction: column; }
.cop-stage-head { padding: 14px 14px 10px; border-bottom: 1px solid rgba(33,28,22,0.06); }
.cop-stage-icon { width: 30px; height: 30px; border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-bottom: 8px; }
.cop-stage-icon svg { width: 16px; height: 16px; color: #fff; }
.cop-stage-label { font-size: 11.5px; font-weight: 700; color: var(--o-ink); margin-bottom: 2px; }
.cop-stage-count { font-family: 'Fraunces', serif; font-size: 26px; font-weight: 700; color: var(--o-ink); line-height: 1; }
.cop-stage-value { font-size: 10.5px; color: var(--o-ink-soft); margin-top: 2px; }
.cop-stage-avg { font-size: 9.5px; color: var(--o-brass-dim); margin-top: 6px; font-weight: 600; }
.cop-stage-body { padding: 8px; flex: 1; max-height: 260px; overflow-y: auto; }
.cop-order-pill {
    display: block; padding: 8px 10px; border-radius: 8px; background: var(--o-felt);
    border: 1px solid rgba(33,28,22,0.06); margin-bottom: 6px; text-decoration: none; transition: background 150ms;
}
.cop-order-pill:hover { background: rgba(184,134,59,0.08); }
.cop-order-pill-no { font-size: 10.5px; font-weight: 700; color: var(--o-brass-dim); }
.cop-order-pill-name { font-size: 10px; color: var(--o-ink-soft); margin-top: 1px; }
.cop-order-pill-due { font-size: 9px; margin-top: 3px; font-weight: 600; }
.cop-order-pill-due.overdue { color: var(--o-brick); }
.cop-order-pill-due.ok { color: var(--o-loupe-dim); }
.cop-stage-empty { text-align: center; padding: 20px 10px; color: rgba(33,28,22,0.25); font-size: 10.5px; font-style: italic; }

.cop-overdue-section { background: #fff; border-radius: 14px; border: 1.5px solid rgba(184,70,63,0.25); overflow: hidden; box-shadow: 0 4px 14px rgba(184,70,63,0.06); }
.cop-overdue-head { background: rgba(184,70,63,0.06); padding: 14px 18px; border-bottom: 1px solid rgba(184,70,63,0.15); display: flex; align-items: center; gap: 10px; }
.cop-overdue-head svg { width: 18px; height: 18px; color: var(--o-brick); }
.cop-overdue-title { font-size: 13px; font-weight: 800; color: var(--o-brick); }
.cop-overdue-row { display: grid; grid-template-columns: 100px 1fr 140px 100px 90px; gap: 12px; padding: 11px 18px; border-bottom: 1px solid rgba(33,28,22,0.05); align-items: center; font-size: 12px; }
.cop-overdue-row:last-child { border-bottom: none; }
.cop-overdue-row:hover { background: rgba(184,70,63,0.03); }
.cop-od-days { background: rgba(184,70,63,0.1); color: var(--o-brick); font-weight: 800; font-size: 10.5px; padding: 3px 8px; border-radius: 999px; text-align: center; }
</style>

<div class="cop-root cop-onyx">

    {{-- HERO --}}
    <div class="cop-hero">
        <div class="cop-hero-eyebrow">Custom Orders</div>
        <h1 class="cop-hero-title">Pipeline Report</h1>
        <p class="cop-hero-sub">Every custom piece, from quote to pickup — where it's stuck, what it's worth.</p>

        <div class="cop-filters">
            <button wire:click="$set('rangeFilter', 'all')" class="cop-filter-btn {{ $rangeFilter === 'all' ? 'active' : '' }}">All Time</button>
            <button wire:click="$set('rangeFilter', '90')" class="cop-filter-btn {{ $rangeFilter === '90' ? 'active' : '' }}">Last 90 Days</button>
            <button wire:click="$set('rangeFilter', '30')" class="cop-filter-btn {{ $rangeFilter === '30' ? 'active' : '' }}">Last 30 Days</button>
        </div>
    </div>

    @php
        $stats = $this->getStats();
        $stageData = $this->getPipelineData();
        $avgDays = $this->getAvgDaysInStage();
        $overdue = $this->getOverdueOrders();
        $stages = $this->getStages();
    @endphp

    {{-- STATS --}}
    <div class="cop-stats">
        <div class="cop-stat">
            <div class="cop-stat-label">Active Orders</div>
            <div class="cop-stat-value">{{ $stats['total_active'] }}</div>
        </div>
        <div class="cop-stat gold">
            <div class="cop-stat-label">Pipeline Value</div>
            <div class="cop-stat-value">${{ number_format($stats['pipeline_value'], 0) }}</div>
        </div>
        <div class="cop-stat green">
            <div class="cop-stat-label">Deposits Held</div>
            <div class="cop-stat-value">${{ number_format($stats['deposits_held'], 0) }}</div>
        </div>
        <div class="cop-stat">
            <div class="cop-stat-label">Outstanding Balance</div>
            <div class="cop-stat-value">${{ number_format($stats['outstanding'], 0) }}</div>
        </div>
        <div class="cop-stat {{ $stats['overdue_count'] > 0 ? 'warn' : '' }}">
            <div class="cop-stat-label">Overdue</div>
            <div class="cop-stat-value">{{ $stats['overdue_count'] }}</div>
        </div>
        <div class="cop-stat">
            <div class="cop-stat-label">Completion Rate</div>
            <div class="cop-stat-value">{{ $stats['completion_rate'] }}%</div>
        </div>
    </div>

    {{-- FUNNEL --}}
    <div class="cop-funnel">
        @foreach($stages as $key => $meta)
            @php $data = $stageData[$key] ?? ['count' => 0, 'value' => 0, 'items' => collect()]; @endphp
            <div class="cop-stage-card">
                <div class="cop-stage-head">
                    <div class="cop-stage-icon" style="background: {{ $meta['color'] }};">
                        <x-dynamic-component :component="$meta['icon']" />
                    </div>
                    <div class="cop-stage-label">{{ $meta['label'] }}</div>
                    <div class="cop-stage-count">{{ $data['count'] }}</div>
                    <div class="cop-stage-value">${{ number_format($data['value'], 0) }}</div>
                    @if($key !== 'completed' && ($avgDays[$key] ?? 0) > 0)
                        <div class="cop-stage-avg">avg {{ $avgDays[$key] }}d in stage</div>
                    @endif
                </div>
                <div class="cop-stage-body">
                    @forelse($data['items']->take(8) as $order)
                        @php
                            $isOverdue = $order->due_date && \Carbon\Carbon::parse($order->due_date)->isPast() && $key !== 'completed';
                        @endphp
                        <a href="{{ \App\Filament\Resources\CustomOrderResource::getUrl('edit', ['record' => $order->id]) }}" class="cop-order-pill">
                            <div class="cop-order-pill-no">#{{ $order->order_no ?? $order->id }}</div>
                            <div class="cop-order-pill-name">{{ \Illuminate\Support\Str::limit($order->product_name ?? 'Custom Piece', 22) }}</div>
                            @if($order->due_date)
                                <div class="cop-order-pill-due {{ $isOverdue ? 'overdue' : 'ok' }}">
                                    {{ $isOverdue ? '⚠ ' : '' }}Due {{ \Carbon\Carbon::parse($order->due_date)->format('M d') }}
                                </div>
                            @endif
                        </a>
                    @empty
                        <div class="cop-stage-empty">No orders here</div>
                    @endforelse
                    @if($data['count'] > 8)
                        <div class="cop-stage-empty">+{{ $data['count'] - 8 }} more</div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    {{-- OVERDUE TABLE --}}
    @if($overdue->count() > 0)
    <div class="cop-overdue-section">
        <div class="cop-overdue-head">
            <x-heroicon-o-exclamation-triangle />
            <span class="cop-overdue-title">Overdue Orders — Need Attention</span>
        </div>
        @foreach($overdue as $order)
            @php $daysLate = \Carbon\Carbon::parse($order->due_date)->diffInDays(now()); @endphp
            <div class="cop-overdue-row">
                <a href="{{ \App\Filament\Resources\CustomOrderResource::getUrl('edit', ['record' => $order->id]) }}" style="font-weight:700;color:var(--o-brass-dim);text-decoration:none;">
                    #{{ $order->order_no ?? $order->id }}
                </a>
                <div>
                    <div style="font-weight:600;">{{ \Illuminate\Support\Str::limit($order->product_name, 30) }}</div>
                    <div style="font-size:10.5px;color:var(--o-ink-soft);">{{ trim(($order->customer->name ?? '') . ' ' . ($order->customer->last_name ?? '')) ?: 'Walk-in' }}</div>
                </div>
                <div style="font-size:11px;color:var(--o-ink-soft);">Due {{ \Carbon\Carbon::parse($order->due_date)->format('M d, Y') }}</div>
                <div class="cop-od-days">{{ $daysLate }}d late</div>
                <div style="font-weight:700;font-size:12px;">${{ number_format(floatval($order->balance_due ?? 0), 2) }}</div>
            </div>
        @endforeach
    </div>
    @endif

</div>
</x-filament-panels::page>