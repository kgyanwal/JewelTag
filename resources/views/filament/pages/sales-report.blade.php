<x-filament-panels::page>

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap');

/* ═══════════════════════════════════════
   RESET & BASE
═══════════════════════════════════════ */
.sr-root {
    font-family: 'Inter', sans-serif;
    color: #0f172a;
    max-width: 960px;
    margin: 0 auto;
    padding: 8px 0 40px;
}

/* ═══════════════════════════════════════
   HERO HEADER
═══════════════════════════════════════ */
.sr-hero {
    background: linear-gradient(135deg, #0c4a58 0%, #0e7490 55%, #0891b2 100%);
    border-radius: 20px;
    padding: 32px 36px;
    margin-bottom: 28px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 12px 40px rgba(14,116,144,0.3);
}

.sr-hero::before {
    content: '';
    position: absolute;
    top: -80px; right: -80px;
    width: 320px; height: 320px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 65%);
    pointer-events: none;
}

.sr-hero::after {
    content: '';
    position: absolute;
    bottom: -60px; left: 40%;
    width: 200px; height: 200px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(255,255,255,0.04) 0%, transparent 65%);
    pointer-events: none;
}

.sr-hero-inner {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 20px;
    position: relative;
    z-index: 1;
}

.sr-hero-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    color: rgba(186,230,253,0.9);
    margin-bottom: 10px;
}
.sr-hero-eyebrow::before {
    content: '';
    width: 20px; height: 1.5px;
    background: rgba(186,230,253,0.7);
}

.sr-hero-title {
    font-size: 26px;
    font-weight: 800;
    color: #ffffff;
    margin: 0 0 6px;
    letter-spacing: -0.03em;
    line-height: 1.2;
}

.sr-hero-sub {
    font-size: 13px;
    color: rgba(255,255,255,0.6);
    margin: 0;
}

.sr-hero-meta {
    text-align: right;
    flex-shrink: 0;
}

.sr-hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(255,255,255,0.12);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 8px;
    padding: 7px 14px;
    font-family: 'JetBrains Mono', monospace;
    font-size: 11px;
    color: rgba(255,255,255,0.85);
    margin-bottom: 8px;
    white-space: nowrap;
}

.sr-hero-badge-dot {
    width: 6px; height: 6px;
    border-radius: 50%;
    background: #4ade80;
    box-shadow: 0 0 6px #4ade80;
}

.sr-hero-generated {
    font-size: 10px;
    color: rgba(255,255,255,0.45);
    text-align: right;
    font-family: 'JetBrains Mono', monospace;
}

/* ═══════════════════════════════════════
   FILTER STRIP
═══════════════════════════════════════ */
.sr-filter {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 16px 20px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}

.sr-filter-icon {
    width: 36px; height: 36px;
    background: #f0fdfe;
    border: 1px solid #a5f3fc;
    border-radius: 9px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    color: #0e7490;
}

.sr-filter-icon svg { width: 16px; height: 16px; }

.sr-filter-label {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: #94a3b8;
    white-space: nowrap;
    flex-shrink: 0;
}

.sr-filter-fields { flex: 1; }
.sr-filter-fields .fi-fo-field-wrp { margin-bottom: 0 !important; }

/* ═══════════════════════════════════════
   ACTIONS
═══════════════════════════════════════ */
.sr-actions {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    margin-bottom: 24px;
}

.sr-btn {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 9px 18px;
    border-radius: 9px;
    font-family: 'Inter', sans-serif;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 0.03em;
    cursor: pointer;
    border: none;
    text-decoration: none;
    transition: all 0.15s ease;
}
.sr-btn-ghost {
    background: #f8fafc;
    color: #475569;
    border: 1px solid #e2e8f0;
}
.sr-btn-ghost:hover { background: #f1f5f9; border-color: #cbd5e1; }
.sr-btn svg { width: 14px; height: 14px; }

/* ═══════════════════════════════════════
   SUMMARY STAT CARDS
═══════════════════════════════════════ */
.sr-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 12px;
    margin-bottom: 28px;
}

.sr-stat {
    background: #ffffff;
    border: 1px solid #e8ecf3;
    border-radius: 14px;
    padding: 18px 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    transition: transform 0.15s, box-shadow 0.15s;
}
.sr-stat:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(0,0,0,0.08); }

.sr-stat-label {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: #94a3b8;
    margin-bottom: 8px;
    display: flex; align-items: center; gap: 7px;
}

.sr-stat-dot {
    width: 7px; height: 7px;
    border-radius: 50%;
    flex-shrink: 0;
}

.sr-stat-value {
    font-family: 'JetBrains Mono', monospace;
    font-size: 20px;
    font-weight: 600;
    color: #0e7490;
    line-height: 1;
    margin-bottom: 5px;
}

.sr-stat-sub {
    font-size: 11px;
    color: #94a3b8;
}

.sr-stat.grand {
    border-color: #a5f3fc;
    background: linear-gradient(135deg, #f0fdfe 0%, #e0f7fa 100%);
}
.sr-stat.grand .sr-stat-label { color: #0e7490; }
.sr-stat.grand .sr-stat-value { font-size: 22px; color: #0c4a58; }

/* ═══════════════════════════════════════
   TRANSACTION TABLE CARD
═══════════════════════════════════════ */
.sr-card {
    background: #ffffff;
    border: 1px solid #e8ecf3;
    border-radius: 18px;
    overflow: hidden;
    margin-bottom: 20px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.05);
}

/* Table header bar */
.sr-table-head {
    background: #0e7490;
    display: grid;
    grid-template-columns: 1fr 90px 130px 130px;
    padding: 11px 20px;
    gap: 8px;
}
.sr-table-head span {
    font-family: 'JetBrains Mono', monospace;
    font-size: 9.5px;
    font-weight: 600;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: rgba(255,255,255,0.75);
}
.sr-table-head span.r { text-align: right; }
.sr-table-head span.c { text-align: center; }

/* Group label */
.sr-group-label {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 20px 8px;
    background: #f8fafc;
    border-top: 1px solid #e8ecf3;
}
.sr-group-label:first-of-type { border-top: none; }

.sr-group-dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}

.sr-group-name {
    font-size: 10.5px;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: #64748b;
}

/* Method row */
.sr-method-row {
    display: grid;
    grid-template-columns: 1fr 90px 130px 130px;
    align-items: center;
    padding: 10px 20px;
    gap: 8px;
    border-top: 1px solid #f1f5f9;
    transition: background 0.1s;
}
.sr-method-row:hover { background: #fafbff; }

.sr-method-name {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 13px;
    font-weight: 500;
    color: #1e3a4a;
    padding-left: 20px;
    position: relative;
}
.sr-method-name::before {
    content: '';
    position: absolute;
    left: 8px;
    width: 3px; height: 14px;
    border-radius: 2px;
    background: #0e7490;
    opacity: 0.3;
}

.sr-method-count {
    font-family: 'JetBrains Mono', monospace;
    font-size: 12px;
    color: #94a3b8;
    text-align: center;
}

.sr-method-sub {
    font-family: 'JetBrains Mono', monospace;
    font-size: 13px;
    font-weight: 500;
    color: #475569;
    text-align: right;
}

/* Empty row */
.sr-empty-row {
    display: grid;
    grid-template-columns: 1fr 90px 130px 130px;
    padding: 10px 20px;
    gap: 8px;
    border-top: 1px solid #f1f5f9;
}
.sr-empty-text { padding-left: 20px; font-size: 12px; color: #cbd5e1; font-style: italic; }
.sr-empty-num  { font-family: 'JetBrains Mono', monospace; font-size: 12px; color: #e2e8f0; text-align: center; }
.sr-empty-numr { font-family: 'JetBrains Mono', monospace; font-size: 12px; color: #e2e8f0; text-align: right; }

/* Group subtotal row */
.sr-group-total {
    display: grid;
    grid-template-columns: 1fr 90px 130px 130px;
    padding: 9px 20px 12px;
    gap: 8px;
    background: #f8fafc;
    border-top: 1px solid #e8ecf3;
}
.sr-group-total-label {
    font-size: 11px;
    font-weight: 600;
    color: #64748b;
    padding-left: 20px;
    text-transform: uppercase;
    letter-spacing: 0.06em;
}
.sr-group-total-amount {
    font-family: 'JetBrains Mono', monospace;
    font-size: 13px;
    font-weight: 700;
    color: #0c4a58;
    text-align: right;
}

/* ═══════════════════════════════════════
   GRAND TOTAL BANNER
═══════════════════════════════════════ */
.sr-grand-banner {
    background: linear-gradient(135deg, #0c4a58 0%, #0e7490 100%);
    border-radius: 16px;
    padding: 24px 28px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
    box-shadow: 0 8px 30px rgba(14,116,144,0.3);
    flex-wrap: wrap;
    gap: 16px;
}

.sr-grand-left {}
.sr-grand-code {
    font-family: 'JetBrains Mono', monospace;
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 0.18em;
    color: rgba(255,255,255,0.5);
    text-transform: uppercase;
    margin-bottom: 4px;
}
.sr-grand-label {
    font-size: 16px;
    font-weight: 700;
    color: #ffffff;
    margin-bottom: 3px;
}
.sr-grand-period {
    font-size: 11px;
    color: rgba(255,255,255,0.55);
}

.sr-grand-right {}
.sr-grand-amount {
    font-family: 'JetBrains Mono', monospace;
    font-size: 34px;
    font-weight: 600;
    color: #ffffff;
    letter-spacing: -1px;
    line-height: 1;
}

/* ═══════════════════════════════════════
   BANKING SECTION
═══════════════════════════════════════ */
.sr-banking-card {
    background: #ffffff;
    border: 1px solid #e8ecf3;
    border-radius: 18px;
    overflow: hidden;
    margin-bottom: 20px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.05);
}

.sr-banking-head {
    background: #1e293b;
    display: grid;
    grid-template-columns: 1fr 130px 130px;
    padding: 11px 20px;
    gap: 8px;
}
.sr-banking-head span {
    font-family: 'JetBrains Mono', monospace;
    font-size: 9.5px;
    font-weight: 600;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: rgba(255,255,255,0.5);
}
.sr-banking-head span.r { text-align: right; }

.sr-banking-row {
    display: grid;
    grid-template-columns: 1fr 130px 130px;
    align-items: center;
    padding: 12px 20px;
    gap: 8px;
    border-top: 1px solid #f1f5f9;
    transition: background 0.1s;
}
.sr-banking-row:hover { background: #fafbff; }

.sr-banking-row-label {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 13px;
    font-weight: 500;
    color: #334155;
    padding-left: 12px;
}

.sr-banking-row-sub {
    font-family: 'JetBrains Mono', monospace;
    font-size: 12px;
    color: #94a3b8;
    text-align: right;
}

.sr-banking-row-total {
    font-family: 'JetBrains Mono', monospace;
    font-size: 13px;
    font-weight: 600;
    color: #0f172a;
    text-align: right;
}

.sr-banking-final {
    display: grid;
    grid-template-columns: 1fr 130px 130px;
    align-items: center;
    padding: 16px 20px;
    background: #f8fafc;
    border-top: 2px solid #e2e8f0;
}

.sr-banking-final-label {
    font-size: 13px;
    font-weight: 700;
    color: #0f172a;
    padding-left: 12px;
}

.sr-banking-final-amount {
    font-family: 'JetBrains Mono', monospace;
    font-size: 18px;
    font-weight: 700;
    color: #0e7490;
    text-align: right;
    text-decoration: underline double;
    text-underline-offset: 4px;
}

/* ═══════════════════════════════════════
   DOT COLORS
═══════════════════════════════════════ */
.dot-amex         { background: #f59e0b; }
.dot-electronic   { background: #0ea5e9; }
.dot-manual       { background: #10b981; }
.dot-other        { background: #8b5cf6; }
.dot-credit       { background: #3b82f6; }
.dot-cash         { background: #10b981; }
.dot-layby        { background: #f59e0b; }

/* ═══════════════════════════════════════
   PRINT
═══════════════════════════════════════ */
@media print {
    .sr-filter, .sr-actions { display: none !important; }
    .sr-root { max-width: 100%; }
    body { background: white !important; }
    .sr-card, .sr-banking-card { box-shadow: none !important; border: 1px solid #ccc !important; }
    .sr-grand-banner, .sr-hero { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}
</style>

@php
    $data = $this->reportData;
    $dotClass = [
        'Amex'                      => 'dot-amex',
        'Credit Card and Finance'   => 'dot-credit',
        'Cash Entry'                => 'dot-cash',
        'Laybys'                    => 'dot-layby',
        'Electronic'                => 'dot-electronic',
        'Manual'                    => 'dot-manual',
        'Other'                     => 'dot-other',
    ];
    $totalTransactions = collect($data['categories'])->flatten(1)->sum('count');
@endphp

<div class="sr-root">

    {{-- ══ HERO ══ --}}
    <div class="sr-hero">
        <div class="sr-hero-inner">
            <div>
                <div class="sr-hero-eyebrow">Shop Takings</div>
                <h1 class="sr-hero-title">Daily Banking & Bank Deposit</h1>
                <p class="sr-hero-sub">Payment method breakdown for selected period</p>
            </div>
            <div class="sr-hero-meta">
                <div class="sr-hero-badge">
                    <span class="sr-hero-badge-dot"></span>
                    {{ \Carbon\Carbon::parse($fromDate)->format('d M Y') }} – {{ \Carbon\Carbon::parse($toDate)->format('d M Y') }}
                </div>
                <div class="sr-hero-generated">Generated: {{ now()->format('d M Y, H:i') }}</div>
            </div>
        </div>
    </div>

    {{-- ══ FILTER ══ --}}
    <div class="sr-filter">
        <div class="sr-filter-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
        </div>
        <div class="sr-filter-label">Period</div>
        <div class="sr-filter-fields">{{ $this->form }}</div>
    </div>

    {{-- ══ ACTIONS ══ --}}
    <div class="sr-actions">
        <button class="sr-btn sr-btn-ghost" onclick="window.print()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/>
            </svg>
            Print
        </button>
    </div>

    {{-- ══ STAT CARDS ══ --}}
    <div class="sr-stats">
        @foreach($data['categories'] as $groupName => $payments)
        <div class="sr-stat">
            <div class="sr-stat-label">
                <span class="sr-stat-dot {{ $dotClass[$groupName] ?? 'dot-other' }}"></span>
                {{ $groupName }}
            </div>
            <div class="sr-stat-value">${{ number_format($data['groupTotals'][$groupName], 2) }}</div>
            <div class="sr-stat-sub">{{ collect($payments)->sum('count') }} transactions</div>
        </div>
        @endforeach

        <div class="sr-stat grand">
            <div class="sr-stat-label">Grand Total</div>
            <div class="sr-stat-value">${{ number_format($data['grandTotal'], 2) }}</div>
            <div class="sr-stat-sub">{{ $totalTransactions }} total transactions</div>
        </div>
    </div>

    {{-- ══ TRANSACTION TABLE ══ --}}
    <div class="sr-card">
        <div class="sr-table-head">
            <span>Payment Method</span>
            <span class="c">Times</span>
            <span class="r">Sub Total</span>
            <span class="r">Total In</span>
        </div>

        @foreach($data['categories'] as $groupName => $payments)
        <div class="sr-group-label">
            <div class="sr-group-dot {{ $dotClass[$groupName] ?? 'dot-other' }}"></div>
            <div class="sr-group-name">{{ $groupName }}</div>
        </div>

        @if(empty($payments))
            <div class="sr-empty-row">
                <div class="sr-empty-text">No transactions</div>
                <div class="sr-empty-num">0</div>
                <div class="sr-empty-numr">$0.00</div>
                <div></div>
            </div>
        @else
            @foreach($payments as $payment)
            <div class="sr-method-row">
                <div class="sr-method-name">{{ ucwords(strtolower(str_replace('_', ' ', $payment->method))) }}</div>
                <div class="sr-method-count">{{ $payment->count }}</div>
                <div class="sr-method-sub">${{ number_format($payment->total, 2) }}</div>
                <div></div>
            </div>
            @endforeach
        @endif

        <div class="sr-group-total">
            <div class="sr-group-total-label">{{ $groupName }} Total</div>
            <div></div>
            <div></div>
            <div class="sr-group-total-amount">${{ number_format($data['groupTotals'][$groupName], 2) }}</div>
        </div>
        @endforeach
    </div>

    {{-- ══ GRAND TOTAL BANNER ══ --}}
    <div class="sr-grand-banner">
        <div class="sr-grand-left">
            <div class="sr-grand-code">PEOD001 · Summary</div>
            <div class="sr-grand-label">Grand Total</div>
            <div class="sr-grand-period">
                {{ \Carbon\Carbon::parse($fromDate)->format('d M') }} – {{ \Carbon\Carbon::parse($toDate)->format('d M Y') }}
                &nbsp;·&nbsp; {{ $totalTransactions }} transactions
            </div>
        </div>
        <div class="sr-grand-right">
            <div class="sr-grand-amount">${{ number_format($data['grandTotal'], 2) }}</div>
        </div>
    </div>

    {{-- ══ BANKING TOTALS ══ --}}
    <div class="sr-banking-card">
        <div class="sr-banking-head">
            <span>Banking Totals</span>
            <span class="r">Sub Total</span>
            <span class="r">Total</span>
        </div>

        @foreach($data['categories'] as $groupName => $payments)
        <div class="sr-banking-row">
            <div class="sr-banking-row-label">
                <span class="sr-group-dot {{ $dotClass[$groupName] ?? 'dot-other' }}" style="width:8px;height:8px;border-radius:50%;display:inline-block;flex-shrink:0;"></span>
                {{ $groupName }} — Today's
            </div>
            <div class="sr-banking-row-sub">${{ number_format($data['groupTotals'][$groupName], 2) }}</div>
            <div class="sr-banking-row-total">${{ number_format($data['groupTotals'][$groupName], 2) }}</div>
        </div>
        @endforeach

        <div class="sr-banking-final">
            <div class="sr-banking-final-label">Grand Total</div>
            <div></div>
            <div class="sr-banking-final-amount">${{ number_format($data['grandTotal'], 2) }}</div>
        </div>
    </div>

</div>

</x-filament-panels::page>