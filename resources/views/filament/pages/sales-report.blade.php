<x-filament-panels::page>

<style>
/* ══════════════════════════════════════════════════════════
   SHOP TAKINGS — Daily Banking & Bank Deposit
   Clean, compact, data-dense professional report UI
══════════════════════════════════════════════════════════ */

@import url('https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap');

.st-wrap {
    font-family: 'IBM Plex Sans', sans-serif;
    color: #0f172a;
    max-width: 860px;
    margin: 0 auto;
}

/* ── Header ─────────────────────────────────────────── */
.st-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 2px solid #0e7490;
}

.st-header-left {}

.st-report-code {
    font-family: 'IBM Plex Mono', monospace;
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: #0e7490;
    margin-bottom: 4px;
}

.st-report-title {
    font-size: 20px;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.2;
}

.st-report-subtitle {
    font-size: 12px;
    color: #64748b;
    margin-top: 3px;
}

.st-header-meta {
    text-align: right;
    font-family: 'IBM Plex Mono', monospace;
    font-size: 11px;
    color: #64748b;
    line-height: 1.7;
}

/* ── Date Filter Card ────────────────────────────────── */
.st-filter-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 14px 18px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.st-filter-label {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 1.2px;
    text-transform: uppercase;
    color: #64748b;
    white-space: nowrap;
    flex-shrink: 0;
}

.st-filter-form {
    flex: 1;
}

.st-filter-form .fi-fo-field-wrp {
    margin-bottom: 0 !important;
}

/* ── Section wrapper ─────────────────────────────────── */
.st-section {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 16px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.06);
}

/* ── Section header bar ──────────────────────────────── */
.st-section-head {
    background: #0e7490;
    display: grid;
    grid-template-columns: 1fr 80px 110px 110px;
    padding: 8px 16px;
    gap: 8px;
}

.st-section-head span {
    font-family: 'IBM Plex Mono', monospace;
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    color: #ffffff;
}

.st-section-head span.right { text-align: right; }
.st-section-head span.center { text-align: center; }

/* ── Category group ──────────────────────────────────── */
.st-group {}

.st-group-label {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 9px 16px 7px;
    background: #f1f5f9;
    border-top: 1px solid #e2e8f0;
}

.st-group-label:first-child {
    border-top: none;
}

.st-group-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}

.st-group-name {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: #475569;
}

/* ── Payment method row ──────────────────────────────── */
.st-method-row {
    display: grid;
    grid-template-columns: 1fr 80px 110px 110px;
    align-items: center;
    padding: 7px 16px;
    gap: 8px;
    border-top: 1px solid #f1f5f9;
    transition: background 0.12s;
}

.st-method-row:hover {
    background: #f8fafc;
}

.st-method-name {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 13px;
    font-weight: 500;
    color: #0e7490;
    padding-left: 16px;
}

.st-method-name::before {
    content: '';
    display: inline-block;
    width: 3px;
    height: 14px;
    border-radius: 2px;
    background: currentColor;
    opacity: 0.4;
    flex-shrink: 0;
}

.st-method-times {
    font-family: 'IBM Plex Mono', monospace;
    font-size: 12px;
    color: #64748b;
    text-align: center;
}

.st-method-subtotal {
    font-family: 'IBM Plex Mono', monospace;
    font-size: 13px;
    font-weight: 500;
    color: #334155;
    text-align: right;
}

.st-method-total-col {
    text-align: right;
}

/* ── Empty state ─────────────────────────────────────── */
.st-empty-row {
    display: grid;
    grid-template-columns: 1fr 80px 110px 110px;
    padding: 7px 16px;
    gap: 8px;
    border-top: 1px solid #f1f5f9;
}

.st-empty-text {
    padding-left: 16px;
    font-size: 12px;
    color: #94a3b8;
    font-style: italic;
}

.st-empty-zero {
    font-family: 'IBM Plex Mono', monospace;
    font-size: 12px;
    color: #cbd5e1;
    text-align: center;
}

.st-empty-zero-right {
    font-family: 'IBM Plex Mono', monospace;
    font-size: 12px;
    color: #cbd5e1;
    text-align: right;
}

/* ── Group subtotal row ──────────────────────────────── */
.st-group-total {
    display: grid;
    grid-template-columns: 1fr 80px 110px 110px;
    padding: 7px 16px 10px;
    gap: 8px;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
}

.st-group-total-label {
    font-size: 11px;
    font-weight: 600;
    color: #64748b;
    padding-left: 16px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.st-group-total-amount {
    font-family: 'IBM Plex Mono', monospace;
    font-size: 13px;
    font-weight: 600;
    color: #0f172a;
    text-align: right;
}

/* ── Grand Total Card ────────────────────────────────── */
.st-grand-total {
    background: #0e7490;
    border-radius: 12px;
    padding: 18px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
    box-shadow: 0 4px 12px rgba(14,116,144,0.25);
}

.st-grand-total-left {}

.st-grand-total-code {
    font-family: 'IBM Plex Mono', monospace;
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 2px;
    color: rgba(255,255,255,0.6);
    text-transform: uppercase;
    margin-bottom: 2px;
}

.st-grand-total-label {
    font-size: 15px;
    font-weight: 700;
    color: #ffffff;
}

.st-grand-total-period {
    font-size: 11px;
    color: rgba(255,255,255,0.65);
    margin-top: 2px;
}

.st-grand-total-right {}

.st-grand-total-amount {
    font-family: 'IBM Plex Mono', monospace;
    font-size: 28px;
    font-weight: 600;
    color: #ffffff;
    letter-spacing: -0.5px;
}

/* ── Summary pills row ───────────────────────────────── */
.st-summary-row {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 20px;
}

.st-summary-pill {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 10px 16px;
    flex: 1;
    min-width: 120px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.st-summary-pill-label {
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 1.2px;
    text-transform: uppercase;
    color: #94a3b8;
    margin-bottom: 4px;
}

.st-summary-pill-value {
    font-family: 'IBM Plex Mono', monospace;
    font-size: 16px;
    font-weight: 600;
    color: #0e7490;
}

.st-summary-pill-sub {
    font-size: 10px;
    color: #94a3b8;
    margin-top: 2px;
}

/* ── Banking section ─────────────────────────────────── */
.st-banking-section {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 16px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.06);
}

.st-banking-head {
    background: #1e293b;
    display: grid;
    grid-template-columns: 1fr 110px 110px;
    padding: 8px 16px;
    gap: 8px;
}

.st-banking-head span {
    font-family: 'IBM Plex Mono', monospace;
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    color: #94a3b8;
}

.st-banking-head span.right { text-align: right; }

.st-banking-row {
    display: grid;
    grid-template-columns: 1fr 110px 110px;
    align-items: center;
    padding: 8px 16px;
    gap: 8px;
    border-top: 1px solid #f1f5f9;
}

.st-banking-row:hover { background: #f8fafc; }

.st-banking-row-label {
    font-size: 13px;
    font-weight: 500;
    color: #334155;
    padding-left: 12px;
}

.st-banking-row-sub {
    font-family: 'IBM Plex Mono', monospace;
    font-size: 12px;
    color: #64748b;
    text-align: right;
}

.st-banking-row-total {
    font-family: 'IBM Plex Mono', monospace;
    font-size: 13px;
    font-weight: 600;
    color: #0f172a;
    text-align: right;
}

.st-banking-final {
    display: grid;
    grid-template-columns: 1fr 110px 110px;
    align-items: center;
    padding: 14px 16px;
    background: #f8fafc;
    border-top: 2px solid #1e293b;
}

.st-banking-final-label {
    font-size: 13px;
    font-weight: 700;
    color: #0f172a;
    padding-left: 12px;
}

.st-banking-final-amount {
    font-family: 'IBM Plex Mono', monospace;
    font-size: 16px;
    font-weight: 700;
    color: #0e7490;
    text-align: right;
    border-top: 2px solid #0e7490;
    border-bottom: 4px double #0e7490;
    padding: 3px 0;
    margin-left: auto;
}

/* ── Print button ────────────────────────────────────── */
.st-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-bottom: 8px;
}

.st-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 16px;
    border-radius: 7px;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 0.5px;
    cursor: pointer;
    border: none;
    text-decoration: none;
    transition: all 0.15s;
}

.st-btn-primary {
    background: #0e7490;
    color: white;
}

.st-btn-primary:hover { background: #0c6478; }

.st-btn-ghost {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
}

.st-btn-ghost:hover { background: #e2e8f0; }

/* ── Color dots per category ─────────────────────────── */
.dot-amex     { background: #f59e0b; }
.dot-electronic { background: #0ea5e9; }
.dot-manual   { background: #10b981; }
.dot-other    { background: #8b5cf6; }

/* ── Print styles ────────────────────────────────────── */
@media print {
    .st-filter-card, .st-actions { display: none !important; }
    .st-wrap { max-width: 100%; }
    body { background: white !important; }
    .st-section, .st-banking-section { box-shadow: none !important; border: 1px solid #ccc !important; }
    .st-grand-total { background: #0e7490 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}
</style>

@php
    $data = $this->reportData;
    $dotClass = ['Amex' => 'dot-amex', 'Electronic' => 'dot-electronic', 'Manual' => 'dot-manual', 'Other' => 'dot-other'];
    $totalTransactions = collect($data['categories'])->flatten(1)->sum('count');
@endphp

<div class="st-wrap">

    {{-- Header --}}
    <div class="st-header">
        <div class="st-header-left">
            <div class="st-report-code">PEOD001 · Shop Takings</div>
            <div class="st-report-title">Daily Banking & Bank Deposit</div>
            <div class="st-report-subtitle">Payment method breakdown for selected period</div>
        </div>
        <div class="st-header-meta">
            <div>Generated: {{ now()->format('d M Y, H:i') }}</div>
            <div>Period: {{ \Carbon\Carbon::parse($fromDate)->format('d M Y') }} – {{ \Carbon\Carbon::parse($toDate)->format('d M Y') }}</div>
        </div>
    </div>

    {{-- Date Filter --}}
    <div class="st-filter-card">
        <div class="st-filter-label">📅 Period</div>
        <div class="st-filter-form">
            {{ $this->form }}
        </div>
    </div>

    {{-- Actions --}}
    <div class="st-actions">
        <button class="st-btn st-btn-ghost" onclick="window.print()">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
            Print
        </button>
    </div>

    {{-- Summary Pills --}}
    <div class="st-summary-row">
        @foreach($data['categories'] as $groupName => $payments)
        <div class="st-summary-pill">
            <div class="st-summary-pill-label">{{ $groupName }}</div>
            <div class="st-summary-pill-value">${{ number_format($data['groupTotals'][$groupName], 2) }}</div>
            <div class="st-summary-pill-sub">{{ collect($payments)->sum('count') }} txns</div>
        </div>
        @endforeach
        <div class="st-summary-pill" style="border-color: #0e7490; background: #f0fdfe;">
            <div class="st-summary-pill-label" style="color:#0e7490;">Grand Total</div>
            <div class="st-summary-pill-value" style="color:#0c6478; font-size:18px;">${{ number_format($data['grandTotal'], 2) }}</div>
            <div class="st-summary-pill-sub">{{ $totalTransactions }} total txns</div>
        </div>
    </div>

    {{-- ══ TRANSACTIONS TABLE ══ --}}
    <div class="st-section">
        <div class="st-section-head">
            <span>Payment Method</span>
            <span class="center">Times</span>
            <span class="right">Sub Total</span>
            <span class="right">Total In</span>
        </div>

        @foreach($data['categories'] as $groupName => $payments)
        <div class="st-group">
            <div class="st-group-label">
                <div class="st-group-dot {{ $dotClass[$groupName] ?? 'dot-other' }}"></div>
                <div class="st-group-name">{{ $groupName }}</div>
            </div>

            @if(empty($payments))
                <div class="st-empty-row">
                    <div class="st-empty-text">No transactions</div>
                    <div class="st-empty-zero">0</div>
                    <div class="st-empty-zero-right">$0.00</div>
                    <div></div>
                </div>
            @else
                @foreach($payments as $payment)
                <div class="st-method-row">
                    <div class="st-method-name">{{ ucwords(strtolower(str_replace('_', ' ', $payment->method))) }}</div>
                    <div class="st-method-times">{{ $payment->count }}</div>
                    <div class="st-method-subtotal">${{ number_format($payment->total, 2) }}</div>
                    <div class="st-method-total-col"></div>
                </div>
                @endforeach
            @endif

            <div class="st-group-total">
                <div class="st-group-total-label">{{ $groupName }} Total</div>
                <div></div>
                <div></div>
                <div class="st-group-total-amount">${{ number_format($data['groupTotals'][$groupName], 2) }}</div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- ══ GRAND TOTAL CARD ══ --}}
    <div class="st-grand-total">
        <div class="st-grand-total-left">
            <div class="st-grand-total-code">PEOD001 · Summary</div>
            <div class="st-grand-total-label">Grand Total</div>
            <div class="st-grand-total-period">
                {{ \Carbon\Carbon::parse($fromDate)->format('d M') }} – {{ \Carbon\Carbon::parse($toDate)->format('d M Y') }}
                &nbsp;·&nbsp; {{ $totalTransactions }} transactions
            </div>
        </div>
        <div class="st-grand-total-right">
            <div class="st-grand-total-amount">${{ number_format($data['grandTotal'], 2) }}</div>
        </div>
    </div>

    {{-- ══ BANKING TOTALS SECTION ══ --}}
    <div class="st-banking-section">
        <div class="st-banking-head">
            <span>Banking Totals</span>
            <span class="right">Sub Total</span>
            <span class="right">Total</span>
        </div>

        @foreach($data['categories'] as $groupName => $payments)
        <div class="st-banking-row">
            <div class="st-banking-row-label">
                <span style="display:inline-flex;align-items:center;gap:8px;">
                    <span class="st-group-dot {{ $dotClass[$groupName] ?? 'dot-other' }}" style="width:7px;height:7px;border-radius:50%;display:inline-block;"></span>
                    {{ $groupName }} — Today's
                </span>
            </div>
            <div class="st-banking-row-sub">${{ number_format($data['groupTotals'][$groupName], 2) }}</div>
            <div class="st-banking-row-total">${{ number_format($data['groupTotals'][$groupName], 2) }}</div>
        </div>
        @endforeach

        <div class="st-banking-final">
            <div class="st-banking-final-label">Grand Total</div>
            <div></div>
            <div class="st-banking-final-amount">${{ number_format($data['grandTotal'], 2) }}</div>
        </div>
    </div>

</div>

</x-filament-panels::page>