<x-filament-panels::page>

<style>
.lhr-root { font-family: 'Inter', sans-serif; }
.lhr-onyx { --o-onyx: #15120F; --o-onyx-soft: #1F1A15; --o-felt: #FAF6EE; --o-brass: #B8863B; --o-brass-bright: #E0AE5C; --o-brass-dim: #8A6428; --o-loupe: #6FCF97; --o-loupe-dim: #3E7C5A; --o-ink: #211C16; --o-ink-soft: #5C5346; --o-brick: #B8463F; --o-amber: #C9A24B; }

.lhr-hero {
    background: linear-gradient(135deg, var(--o-onyx) 0%, var(--o-onyx-soft) 100%);
    border-radius: 18px;
    padding: 28px 32px;
    margin-bottom: 22px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 14px 36px rgba(21,18,15,0.28);
}
.lhr-hero::before {
    content: '';
    position: absolute; top: -60px; right: -60px;
    width: 240px; height: 240px; border-radius: 50%;
    background: radial-gradient(circle, rgba(184,134,59,0.18) 0%, transparent 65%);
}
.lhr-hero-eyebrow {
    font-size: 10px; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase;
    color: rgba(224,174,92,0.85); margin-bottom: 8px; display: flex; align-items: center; gap: 8px;
}
.lhr-hero-eyebrow::before { content: ''; width: 18px; height: 1.5px; background: var(--o-brass-bright); }
.lhr-hero-title { font-family: 'Fraunces', serif; font-size: 24px; font-weight: 700; color: #fff; margin: 0 0 4px; }
.lhr-hero-sub { font-size: 12.5px; color: rgba(250,246,238,0.6); }

.lhr-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; margin-bottom: 24px; }
.lhr-stat { background: #fff; border: 1px solid rgba(33,28,22,0.08); border-radius: 12px; padding: 14px 16px; box-shadow: 0 2px 8px rgba(33,28,22,0.04); }
.lhr-stat-label { font-size: 9.5px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: var(--o-ink-soft); margin-bottom: 5px; }
.lhr-stat-value { font-family: 'Fraunces', serif; font-size: 22px; font-weight: 700; color: var(--o-ink); line-height: 1; }
.lhr-stat.warn .lhr-stat-value { color: var(--o-brick); }
.lhr-stat.gold .lhr-stat-value { color: var(--o-brass-dim); }
.lhr-stat.green .lhr-stat-value { color: var(--o-loupe-dim); }

/* Collection rate ring */
.lhr-rate-card { background: #fff; border: 1px solid rgba(33,28,22,0.08); border-radius: 14px; padding: 18px 20px; margin-bottom: 22px; display: flex; align-items: center; gap: 18px; box-shadow: 0 2px 10px rgba(33,28,22,0.04); }
.lhr-rate-ring { width: 64px; height: 64px; border-radius: 50%; flex-shrink: 0; display: flex; align-items: center; justify-content: center; position: relative; }
.lhr-rate-ring svg { transform: rotate(-90deg); }
.lhr-rate-pct { position: absolute; font-family: 'Fraunces', serif; font-weight: 700; font-size: 13px; color: var(--o-ink); }
.lhr-rate-text { font-size: 12px; color: var(--o-ink-soft); }
.lhr-rate-text strong { color: var(--o-ink); font-weight: 700; }

/* Risk tier sections */
.lhr-tier-section { margin-bottom: 22px; }
.lhr-tier-head { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }
.lhr-tier-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
.lhr-tier-title { font-size: 13.5px; font-weight: 800; color: #FAF6EE; }
.lhr-tier-count { background: rgba(250,246,238,0.12); color: rgba(250,246,238,0.75); font-size: 10.5px; font-weight: 700; padding: 2px 9px; border-radius: 999px; }
.lhr-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 12px; }

.lhr-card {
    background: #fff; border-radius: 14px; padding: 16px 18px; position: relative; overflow: hidden;
    box-shadow: 0 4px 14px rgba(33,28,22,0.06); transition: transform 180ms, box-shadow 180ms;
}
.lhr-card:hover { transform: translateY(-3px); box-shadow: 0 12px 28px rgba(33,28,22,0.1); }
.lhr-card::before { content: ''; position: absolute; top: 0; left: 0; bottom: 0; width: 4px; }
.lhr-card.tier-overdue { border: 1px solid rgba(184,70,63,0.25); }
.lhr-card.tier-overdue::before { background: var(--o-brick); }
.lhr-card.tier-at_risk { border: 1px solid rgba(201,162,75,0.3); }
.lhr-card.tier-at_risk::before { background: var(--o-amber); }
.lhr-card.tier-watch { border: 1px solid rgba(184,134,59,0.2); }
.lhr-card.tier-watch::before { background: var(--o-brass); }
.lhr-card.tier-healthy { border: 1px solid rgba(111,207,151,0.25); }
.lhr-card.tier-healthy::before { background: var(--o-loupe-dim); }

.lhr-card-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; }
.lhr-card-no { font-size: 12px; font-weight: 800; color: var(--o-brass-dim); }
.lhr-card-customer { font-size: 13px; font-weight: 700; color: var(--o-ink); margin-top: 2px; }
.lhr-card-badge { font-size: 9px; font-weight: 800; letter-spacing: 0.04em; text-transform: uppercase; padding: 3px 8px; border-radius: 999px; white-space: nowrap; }
.lhr-card-badge.tier-overdue { background: rgba(184,70,63,0.12); color: var(--o-brick); }
.lhr-card-badge.tier-at_risk { background: rgba(201,162,75,0.15); color: #8A6428; }
.lhr-card-badge.tier-watch { background: rgba(184,134,59,0.1); color: var(--o-brass-dim); }
.lhr-card-badge.tier-healthy { background: rgba(111,207,151,0.15); color: var(--o-loupe-dim); }

.lhr-progress-track { height: 7px; background: rgba(33,28,22,0.06); border-radius: 999px; overflow: hidden; margin: 10px 0 6px; }
.lhr-progress-fill { height: 100%; border-radius: 999px; transition: width 400ms; }
.lhr-progress-fill.tier-overdue { background: var(--o-brick); }
.lhr-progress-fill.tier-at_risk { background: var(--o-amber); }
.lhr-progress-fill.tier-watch { background: var(--o-brass); }
.lhr-progress-fill.tier-healthy { background: var(--o-loupe-dim); }

.lhr-card-figures { display: flex; justify-content: space-between; font-size: 11px; color: var(--o-ink-soft); margin-bottom: 8px; }
.lhr-card-figures strong { color: var(--o-ink); font-weight: 700; }

.lhr-card-meta { display: flex; justify-content: space-between; align-items: center; padding-top: 8px; border-top: 1px solid rgba(33,28,22,0.06); font-size: 10.5px; }
.lhr-card-due { color: var(--o-ink-soft); }
.lhr-card-due.overdue { color: var(--o-brick); font-weight: 700; }
.lhr-card-link { color: var(--o-brass-dim); font-weight: 700; text-decoration: none; }
.lhr-card-link:hover { text-decoration: underline; }

.lhr-empty-tier { background: var(--o-felt); border-radius: 12px; padding: 24px; text-align: center; color: rgba(33,28,22,0.3); font-size: 12px; font-style: italic; }
</style>

<div class="lhr-root lhr-onyx">

    {{-- HERO --}}
    <div class="lhr-hero">
        <div class="lhr-hero-eyebrow">Layaway</div>
        <h1 class="lhr-hero-title">Laybuy Health Report</h1>
        <p class="lhr-hero-sub">Which payment plans are on track, and which need a phone call before they're written off.</p>
    </div>

    @php
        $stats   = $this->getStats();
        $buckets = $this->getClassifiedLaybuys();

        $tierMeta = [
            'overdue' => ['label' => 'Overdue',        'dot' => '#B8463F', 'desc' => 'Past due date with balance remaining — contact immediately'],
            'at_risk' => ['label' => 'At Risk',        'dot' => '#C9A24B', 'desc' => 'Due within 7 days, under 50% paid'],
            'watch'   => ['label' => 'Watch',          'dot' => '#B8863B', 'desc' => 'Slow payment pace or no recent activity'],
            'healthy' => ['label' => 'On Track',       'dot' => '#3E7C5A', 'desc' => 'Paying on schedule'],
        ];

        $circumference = 2 * M_PI * 26;
        $offset = $circumference * (1 - ($stats['collection_rate'] / 100));
    @endphp

    {{-- STATS --}}
    <div class="lhr-stats">
        <div class="lhr-stat">
            <div class="lhr-stat-label">Active Layaways</div>
            <div class="lhr-stat-value">{{ $stats['total_active'] }}</div>
        </div>
        <div class="lhr-stat gold">
            <div class="lhr-stat-label">Total Outstanding</div>
            <div class="lhr-stat-value">${{ number_format($stats['total_outstanding'], 0) }}</div>
        </div>
        <div class="lhr-stat green">
            <div class="lhr-stat-label">Total Collected</div>
            <div class="lhr-stat-value">${{ number_format($stats['total_collected'], 0) }}</div>
        </div>
        <div class="lhr-stat warn">
            <div class="lhr-stat-label">At-Risk Value</div>
            <div class="lhr-stat-value">${{ number_format($stats['at_risk_value'], 0) }}</div>
        </div>
        <div class="lhr-stat {{ $stats['overdue_count'] > 0 ? 'warn' : '' }}">
            <div class="lhr-stat-label">Overdue Plans</div>
            <div class="lhr-stat-value">{{ $stats['overdue_count'] }}</div>
        </div>
    </div>

    {{-- COLLECTION RATE --}}
    <div class="lhr-rate-card">
        <div class="lhr-rate-ring">
            <svg width="64" height="64">
                <circle cx="32" cy="32" r="26" fill="none" stroke="rgba(33,28,22,0.08)" stroke-width="6"/>
                <circle cx="32" cy="32" r="26" fill="none" stroke="#3E7C5A" stroke-width="6"
                    stroke-dasharray="{{ $circumference }}" stroke-dashoffset="{{ $offset }}" stroke-linecap="round"/>
            </svg>
            <span class="lhr-rate-pct">{{ $stats['collection_rate'] }}%</span>
        </div>
        <div class="lhr-rate-text">
            <strong>${{ number_format($stats['total_collected'], 0) }}</strong> collected of
            <strong>${{ number_format($stats['total_value'], 0) }}</strong> total layaway value across
            <strong>{{ $stats['total_active'] }}</strong> active plans.
        </div>
    </div>

    {{-- RISK TIERS --}}
    @foreach(['overdue', 'at_risk', 'watch', 'healthy'] as $tier)
        @php $items = $buckets[$tier] ?? collect(); @endphp
        <div class="lhr-tier-section">
            <div class="lhr-tier-head">
                <span class="lhr-tier-dot" style="background: {{ $tierMeta[$tier]['dot'] }};"></span>
                <span class="lhr-tier-title">{{ $tierMeta[$tier]['label'] }}</span>
                <span class="lhr-tier-count">{{ $items->count() }}</span>
                <span style="font-size:10.5px;color:rgba(250,246,238,0.65);">— {{ $tierMeta[$tier]['desc'] }}</span>
            </div>

            @if($items->isEmpty())
                <div class="lhr-empty-tier">Nothing here right now.</div>
            @else
                <div class="lhr-grid">
                    @foreach($items as $laybuy)
                        @php
                            $total   = floatval($laybuy->total_amount ?? 0);
                            $paid    = floatval($laybuy->amount_paid ?? 0);
                            $balance = floatval($laybuy->balance_due ?? max(0, $total - $paid));
                            $pct     = $total > 0 ? min(100, round(($paid / $total) * 100)) : 0;
                            $isOverdue = $laybuy->due_date && \Carbon\Carbon::parse($laybuy->due_date)->isPast();
                        @endphp
                        <div class="lhr-card tier-{{ $tier }}">
                            <div class="lhr-card-top">
                                <div>
                                    <div class="lhr-card-no">{{ $laybuy->laybuy_no }}</div>
                                    <div class="lhr-card-customer">{{ trim(($laybuy->customer->name ?? '') . ' ' . ($laybuy->customer->last_name ?? '')) ?: 'Unknown Customer' }}</div>
                                </div>
                                <span class="lhr-card-badge tier-{{ $tier }}">{{ $pct }}% paid</span>
                            </div>

                            <div class="lhr-progress-track">
                                <div class="lhr-progress-fill tier-{{ $tier }}" style="width: {{ $pct }}%;"></div>
                            </div>

                            <div class="lhr-card-figures">
                                <span>Paid: <strong>${{ number_format($paid, 2) }}</strong></span>
                                <span>Owing: <strong>${{ number_format($balance, 2) }}</strong></span>
                            </div>

                            <div class="lhr-card-meta">
                                <span class="lhr-card-due {{ $isOverdue ? 'overdue' : '' }}">
                                    @if($laybuy->due_date)
                                        {{ $isOverdue ? '⚠ ' : '' }}Due {{ \Carbon\Carbon::parse($laybuy->due_date)->format('M d, Y') }}
                                    @else
                                        No due date set
                                    @endif
                                </span>
                                <a href="{{ \App\Filament\Resources\LaybuyResource::getUrl('edit', ['record' => $laybuy->id]) }}" class="lhr-card-link">
                                    View →
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endforeach

</div>
</x-filament-panels::page>