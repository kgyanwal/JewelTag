<x-filament-panels::page>
<style>
    /* ── RESET & BASE ── */
    .fi-body, .fi-main, .fi-page, .fi-main-ctn { background: #f0f2f7 !important; }
    .dark .fi-body, .dark .fi-main, .dark .fi-page, .dark .fi-main-ctn { background: #0d0d12 !important; }

    @import url('https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=Syne:wght@400;500;600;700;800&display=swap');

    .eod {
        font-family: 'Syne', -apple-system, BlinkMacSystemFont, sans-serif;
        width: 100%;
        max-width: 1300px;
        margin: 0 auto;
        padding: 1.25rem 1rem 2rem;
        color: #111827;
    }
    .dark .eod { color: #f1f5f9; }

    /* ── HERO ── */
    .eod-hero {
        background: #0f1117;
        border-radius: 18px;
        padding: 28px 32px;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 18px;
        position: relative;
        overflow: hidden;
    }
    .eod-hero::after {
        content: '';
        position: absolute;
        top: -80px;
        right: -60px;
        width: 280px;
        height: 280px;
        background: radial-gradient(circle, rgba(99,102,241,0.18) 0%, transparent 65%);
        pointer-events: none;
    }
    .eod-hero-left { display: flex; align-items: center; gap: 18px; z-index: 1; }
    .eod-hero-icon {
        width: 48px; height: 48px;
        background: rgba(99,102,241,0.15);
        border: 1px solid rgba(99,102,241,0.3);
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .eod-hero-icon svg { width: 22px; height: 22px; stroke: #818cf8; fill: none; stroke-width: 1.75; stroke-linecap: round; stroke-linejoin: round; }
    .eod-hero-eyebrow {
        font-size: 10px; font-weight: 700; letter-spacing: 0.13em;
        text-transform: uppercase; color: #6366f1; margin-bottom: 5px;
        display: flex; align-items: center; gap: 7px;
    }
    .eod-hero-eyebrow::before { content: ''; width: 16px; height: 1px; background: #6366f1; }
    .eod-hero-title { font-size: 22px; font-weight: 800; color: #fff; margin: 0 0 4px; letter-spacing: -0.02em; }
    .eod-hero-sub { font-size: 13px; color: #64748b; margin: 0; }

    .eod-hero-right { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; z-index: 1; }
    .eod-date-input {
        background: rgba(255,255,255,0.06);
        border: 1px solid rgba(255,255,255,0.1);
        color: #e2e8f0;
        padding: 9px 16px;
        border-radius: 10px;
        font-family: 'IBM Plex Mono', monospace;
        font-size: 13px;
        cursor: pointer;
        color-scheme: dark;
        outline: none;
        transition: border-color 0.2s;
    }
    .eod-date-input:hover, .eod-date-input:focus { border-color: rgba(99,102,241,0.5); }
    .eod-date-static {
        background: rgba(255,255,255,0.06);
        border: 1px solid rgba(255,255,255,0.1);
        color: #e2e8f0;
        padding: 9px 16px;
        border-radius: 10px;
        font-family: 'IBM Plex Mono', monospace;
        font-size: 13px;
        display: inline-flex; align-items: center; gap: 8px;
    }
    .eod-date-static svg { width: 14px; height: 14px; stroke: #6366f1; fill: none; stroke-width: 2; }

    .eod-status-pill {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 8px 16px; border-radius: 40px;
        font-size: 11px; font-weight: 700;
        letter-spacing: 0.07em; text-transform: uppercase;
    }
    .eod-status-pill.locked { background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.25); color: #34d399; }
    .eod-status-pill.open   { background: rgba(245,158,11,0.12); border: 1px solid rgba(245,158,11,0.25); color: #fbbf24; }
    .eod-status-dot { width: 7px; height: 7px; border-radius: 50%; background: currentColor; }
    .eod-status-pill.open .eod-status-dot { animation: blink 2s ease-in-out infinite; }
    @keyframes blink { 0%,100% { opacity:1; } 50% { opacity:0.3; } }

    /* ── STAT CARDS ── */
    .eod-stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 14px;
        margin-bottom: 24px;
    }
    @media(max-width: 860px) { .eod-stats { grid-template-columns: repeat(2, minmax(0,1fr)); } }
    @media(max-width: 480px) { .eod-stats { grid-template-columns: 1fr; } }

    .eod-sc {
        background: #fff;
        border: 1px solid #e8ecf2;
        border-radius: 14px;
        padding: 18px 20px;
        display: flex; align-items: center; gap: 14px;
        transition: transform 0.18s ease, box-shadow 0.18s ease;
    }
    .dark .eod-sc { background: #161620; border-color: #242430; }
    .eod-sc:hover { transform: translateY(-2px); box-shadow: 0 8px 24px -8px rgba(0,0,0,0.1); }
    .dark .eod-sc:hover { box-shadow: 0 8px 24px -8px rgba(0,0,0,0.4); }

    .eod-sc-ico {
        width: 44px; height: 44px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .eod-sc-ico svg { width: 20px; height: 20px; fill: none; stroke-width: 1.75; stroke-linecap: round; stroke-linejoin: round; }
    .eod-sc-lbl { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: #94a3b8; margin-bottom: 5px; }
    .eod-sc-val { font-family: 'IBM Plex Mono', monospace; font-size: 21px; font-weight: 600; line-height: 1.2; }

    /* ── LAYOUT ── */
    .eod-layout {
        display: grid;
        grid-template-columns: 1fr 300px;
        gap: 20px;
        align-items: start;
    }
    @media(max-width: 960px) { .eod-layout { grid-template-columns: 1fr; } }

    /* ── CARDS ── */
    .eod-card {
        background: #fff;
        border: 1px solid #e8ecf2;
        border-radius: 16px;
        overflow: hidden;
    }
    .dark .eod-card { background: #161620; border-color: #242430; }

    .eod-card-header {
        padding: 16px 24px;
        border-bottom: 1px solid #f0f3f8;
        background: #fafbfe;
        display: flex; align-items: center; gap: 10px;
    }
    .dark .eod-card-header { background: #12121a; border-color: #242430; }
    .eod-card-bar { width: 3px; height: 18px; border-radius: 2px; background: #6366f1; flex-shrink: 0; }
    .eod-card-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: #374151; }
    .dark .eod-card-title { color: #e2e8f0; }
    .eod-card-date {
        margin-left: auto; font-family: 'IBM Plex Mono', monospace;
        font-size: 11px; color: #94a3b8;
        background: #f0f3f8; padding: 4px 12px; border-radius: 40px;
    }
    .dark .eod-card-date { background: #1e1e2a; color: #64748b; }

    /* ── TABLE ── */
    .eod-tbl { width: 100%; border-collapse: collapse; }

    .eod-tbl thead th {
        padding: 12px 24px;
        font-size: 10px; font-weight: 700; letter-spacing: 0.1em;
        text-transform: uppercase; color: #94a3b8;
        background: #fafbfe;
        border-bottom: 1px solid #f0f3f8;
    }
    .dark .eod-tbl thead th { background: #12121a; border-color: #242430; color: #4b5563; }
    .eod-tbl thead th:not(:first-child) { text-align: right; }

    .eod-tbl tbody tr {
        border-bottom: 1px solid #f4f6fb;
        transition: background 0.12s;
    }
    .dark .eod-tbl tbody tr { border-color: #1e1e2a; }
    .eod-tbl tbody tr:last-child { border-bottom: none; }
    .eod-tbl tbody tr:hover { background: #fafbfe; }
    .dark .eod-tbl tbody tr:hover { background: #1a1a24; }
    .eod-tbl tbody td { padding: 18px 24px; vertical-align: middle; }

    .eod-method { display: inline-flex; align-items: center; gap: 12px; }
    .eod-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
    .eod-mname { font-size: 14px; font-weight: 700; color: #111827; letter-spacing: 0.01em; }
    .dark .eod-mname { color: #f1f5f9; }
    .eod-match-badge {
        font-size: 9px; font-weight: 700; padding: 3px 9px; border-radius: 40px;
        letter-spacing: 0.06em; text-transform: uppercase;
        background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0;
    }
    .dark .eod-match-badge { background: rgba(34,197,94,0.1); color: #4ade80; border-color: rgba(34,197,94,0.2); }

    .eod-expected-val {
        text-align: right;
        font-family: 'IBM Plex Mono', monospace;
        font-size: 15px; font-weight: 500; color: #6b7280;
    }
    .dark .eod-expected-val { color: #4b5563; }

    .eod-actual-wrap { display: flex; justify-content: flex-end; }
    .eod-actual-val {
        font-family: 'IBM Plex Mono', monospace;
        font-size: 15px; font-weight: 600;
        padding: 9px 18px; border-radius: 10px;
        min-width: 140px; text-align: right;
        display: inline-block;
    }
    .eod-actual-val.match {
        background: #f0fdf4; color: #15803d;
        border: 1px solid #bbf7d0;
    }
    .dark .eod-actual-val.match {
        background: rgba(34,197,94,0.08); color: #4ade80;
        border-color: rgba(34,197,94,0.2);
    }
    .eod-actual-val.mismatch {
        background: #fff7ed; color: #c2410c;
        border: 1px solid #fed7aa;
    }
    .dark .eod-actual-val.mismatch {
        background: rgba(249,115,22,0.08); color: #fb923c;
        border-color: rgba(249,115,22,0.2);
    }
    .eod-actual-val.empty {
        background: #f8fafc; color: #cbd5e1;
        border: 1px solid #e8ecf2;
    }
    .dark .eod-actual-val.empty { background: #1a1a24; color: #374151; border-color: #242430; }

    /* ── TABLE FOOTER ── */
    .eod-tbl tfoot tr { background: #f8fafd; border-top: 2px solid #e8ecf2; }
    .dark .eod-tbl tfoot tr { background: #12121a; border-color: #242430; }
    .eod-tbl tfoot td { padding: 18px 24px; }
    .eod-tfoot-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #374151; }
    .dark .eod-tfoot-label { color: #94a3b8; }
    .eod-tfoot-expected {
        text-align: right;
        font-family: 'IBM Plex Mono', monospace;
        font-size: 19px; font-weight: 600; color: #4f46e5;
    }
    .dark .eod-tfoot-expected { color: #818cf8; }
    .eod-tfoot-actual-wrap { display: flex; justify-content: flex-end; }
    .eod-tfoot-actual {
        font-family: 'IBM Plex Mono', monospace;
        font-size: 19px; font-weight: 700;
        padding: 10px 20px; border-radius: 10px;
        min-width: 140px; text-align: right; display: inline-block;
    }
    .eod-tfoot-actual.ok { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
    .eod-tfoot-actual.bad { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
    .dark .eod-tfoot-actual.ok { background: rgba(34,197,94,0.08); color: #4ade80; border-color: rgba(34,197,94,0.2); }
    .dark .eod-tfoot-actual.bad { background: rgba(239,68,68,0.08); color: #f87171; border-color: rgba(239,68,68,0.2); }

    /* ── SIDE PANEL ── */
    .eod-side { display: flex; flex-direction: column; gap: 16px; }

    .eod-side-header {
        padding: 13px 20px; font-size: 10px; font-weight: 700;
        letter-spacing: 0.1em; text-transform: uppercase; color: #94a3b8;
        border-bottom: 1px solid #f0f3f8; background: #fafbfe;
        display: flex; align-items: center; gap: 8px;
    }
    .dark .eod-side-header { background: #12121a; border-color: #242430; color: #4b5563; }
    .eod-side-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
    .eod-side-body { padding: 18px 20px; }

    .eod-side-row {
        display: flex; justify-content: space-between; align-items: center;
        padding: 12px 0; border-bottom: 1px dashed #f0f3f8;
    }
    .dark .eod-side-row { border-color: #1e1e2a; }
    .eod-side-row:first-child { padding-top: 0; }
    .eod-side-row:last-child { border-bottom: none; padding-bottom: 0; }
    .eod-side-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.07em; color: #9ca3af; }
    .eod-side-value { font-family: 'IBM Plex Mono', monospace; font-size: 18px; font-weight: 600; color: #111827; }
    .dark .eod-side-value { color: #f1f5f9; }

    .eod-variance-box { border-radius: 12px; padding: 16px 18px; margin-top: 16px; }
    .eod-variance-box.ok  { background: #f0fdf4; border: 1px solid #bbf7d0; }
    .eod-variance-box.bad { background: #fef2f2; border: 1px solid #fecaca; }
    .dark .eod-variance-box.ok  { background: rgba(34,197,94,0.07); border-color: rgba(34,197,94,0.2); }
    .dark .eod-variance-box.bad { background: rgba(239,68,68,0.07); border-color: rgba(239,68,68,0.2); }
    .eod-variance-tag { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.09em; margin-bottom: 6px; }
    .eod-variance-box.ok  .eod-variance-tag { color: #16a34a; }
    .eod-variance-box.bad .eod-variance-tag { color: #dc2626; }
    .dark .eod-variance-box.ok  .eod-variance-tag { color: #4ade80; }
    .dark .eod-variance-box.bad .eod-variance-tag { color: #f87171; }
    .eod-variance-num { font-family: 'IBM Plex Mono', monospace; font-size: 30px; font-weight: 700; line-height: 1; }
    .eod-variance-box.ok  .eod-variance-num { color: #15803d; }
    .eod-variance-box.bad .eod-variance-num { color: #b91c1c; }
    .dark .eod-variance-box.ok  .eod-variance-num { color: #22c55e; }
    .dark .eod-variance-box.bad .eod-variance-num { color: #ef4444; }

    .eod-breakdown-row {
        display: flex; justify-content: space-between; align-items: center;
        padding: 10px 0; border-bottom: 1px solid #f4f6fb;
    }
    .dark .eod-breakdown-row { border-color: #1e1e2a; }
    .eod-breakdown-row:last-child { border-bottom: none; padding-bottom: 0; }
    .eod-breakdown-key { display: flex; align-items: center; gap: 10px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #6b7280; }
    .dark .eod-breakdown-key { color: #4b5563; }
    .eod-breakdown-val { font-family: 'IBM Plex Mono', monospace; font-size: 14px; font-weight: 600; }

    /* ── ACTIONS ── */
    .eod-actions {
        display: flex; align-items: center; justify-content: space-between;
        margin-top: 20px; padding: 18px 24px;
        background: #fff; border: 1px solid #e8ecf2; border-radius: 16px;
        flex-wrap: wrap; gap: 14px;
    }
    .dark .eod-actions { background: #161620; border-color: #242430; }

    .eod-action-left { display: flex; gap: 10px; }
    .eod-btn {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 9px 20px;
        font-family: 'Syne', sans-serif;
        font-size: 11px; font-weight: 700; letter-spacing: 0.07em; text-transform: uppercase;
        border-radius: 40px; cursor: pointer;
        border: 1px solid #e2e8f0;
        background: #fff; color: #6b7280;
        transition: all 0.15s ease;
    }
    .dark .eod-btn { background: #1e1e2a; border-color: #2d2d3d; color: #64748b; }
    .eod-btn:hover { background: #f8fafc; border-color: #cbd5e1; color: #374151; transform: translateY(-1px); }
    .dark .eod-btn:hover { background: #252535; border-color: #3a3a4e; color: #94a3b8; }
    .eod-btn svg { width: 13px; height: 13px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }

    .eod-btn-lock {
        background: #4f46e5; color: #fff; border-color: #4f46e5;
        box-shadow: 0 4px 14px -2px rgba(79,70,229,0.4);
    }
    .dark .eod-btn-lock { background: #6366f1; border-color: #6366f1; box-shadow: 0 4px 14px -2px rgba(99,102,241,0.4); }
    .eod-btn-lock:hover { background: #4338ca; border-color: #4338ca; color: #fff; box-shadow: 0 6px 18px -2px rgba(79,70,229,0.5); }
    .dark .eod-btn-lock:hover { background: #4f46e5; border-color: #4f46e5; }

    .eod-action-right { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; }

    .eod-spill {
        display: inline-flex; align-items: center; gap: 8px;
        font-size: 11px; font-weight: 700; padding: 9px 20px;
        border-radius: 40px; letter-spacing: 0.06em; text-transform: uppercase;
    }
    .eod-spill.ok  { background: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d; }
    .eod-spill.bad { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; }
    .dark .eod-spill.ok  { background: rgba(34,197,94,0.1); border-color: rgba(34,197,94,0.2); color: #4ade80; }
    .dark .eod-spill.bad { background: rgba(239,68,68,0.1); border-color: rgba(239,68,68,0.2); color: #f87171; }

    /* ── DOT COLORS ── */
    .d-cash       { background: #10b981; }
    .d-visa       { background: #3b82f6; }
    .d-mastercard { background: #ef4444; }
    .d-amex       { background: #06b6d4; }
    .d-laybuy     { background: #f59e0b; }
    .d-split      { background: #8b5cf6; }
    .d-katapult   { background: #8b5cf6; }
    .d-default    { background: #94a3b8; }

    /* ── STAT ICON BG HELPERS ── */
    .ico-indigo { background: rgba(99,102,241,0.1); }
    .ico-green  { background: rgba(16,185,129,0.1); }
    .ico-amber  { background: rgba(245,158,11,0.1); }
    .ico-purple { background: rgba(139,92,246,0.1); }
</style>

<div class="eod">

    {{-- ═══════════ HERO ═══════════ --}}
    <div class="eod-hero">
        <div class="eod-hero-left">
            <div class="eod-hero-icon">
                <svg viewBox="0 0 24 24">
                    <path d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <div class="eod-hero-eyebrow">End-of-day report</div>
                <h1 class="eod-hero-title">Daily Closing Register</h1>
                <p class="eod-hero-sub">Reconcile payments &amp; lock the register for {{ \Carbon\Carbon::parse($date)->format('l, F j, Y') }}</p>
            </div>
        </div>

        <div class="eod-hero-right">
            @if(auth()->user()->hasRole('Superadmin'))
                <input
                    type="date"
                    wire:model.live="date"
                    max="{{ now()->format('Y-m-d') }}"
                    class="eod-date-input"
                >
            @else
                <div class="eod-date-static">
                    <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    {{ \Carbon\Carbon::parse($date)->format('M d, Y') }}
                </div>
            @endif

            @if($isClosed)
                <span class="eod-status-pill locked">
                    <span class="eod-status-dot"></span>
                    Record Locked
                </span>
            @else
                <span class="eod-status-pill open">
                    <span class="eod-status-dot"></span>
                    Open for Entry
                </span>
            @endif
        </div>
    </div>

    {{-- ═══════════ COMPUTED VALUES ═══════════ --}}
    @php
        $totalExpected = array_sum($expectedTotals);
        $totalActual   = array_sum($actualTotals ?? []);
        $isMatch       = round($totalActual, 2) == round($totalExpected, 2);
        $diff          = $totalActual - $totalExpected;
        $txCount       = collect($expectedTotals)->filter(fn($v) => $v > 0)->count();

        $dotMap = [
            'cash'       => 'd-cash',
            'visa'       => 'd-visa',
            'mastercard' => 'd-mastercard',
            'amex'       => 'd-amex',
            'laybuy'     => 'd-laybuy',
            'split'      => 'd-split',
            'katapult'   => 'd-katapult',
        ];

        $diffFormatted = ($diff >= 0 ? '+' : '') . '$' . number_format(abs($diff), 2);
    @endphp

    {{-- ═══════════ STAT CARDS ═══════════ --}}
    <div class="eod-stats">

        {{-- Expected --}}
        <div class="eod-sc" style="border-left: 3px solid #6366f1;">
            <div class="eod-sc-ico ico-indigo">
                <svg stroke="#6366f1" viewBox="0 0 24 24">
                    <rect x="2" y="4" width="20" height="16" rx="2"/>
                    <path d="M7 15h0M2 9h20"/>
                </svg>
            </div>
            <div>
                <div class="eod-sc-lbl">Expected</div>
                <div class="eod-sc-val" style="color:#4f46e5;">${{ number_format($totalExpected, 2) }}</div>
            </div>
        </div>

        {{-- Counted --}}
        <div class="eod-sc" style="border-left: 3px solid {{ $isMatch ? '#10b981' : '#f97316' }};">
            <div class="eod-sc-ico" style="background: {{ $isMatch ? 'rgba(16,185,129,0.1)' : 'rgba(249,115,22,0.1)' }}">
                <svg stroke="{{ $isMatch ? '#10b981' : '#f97316' }}" viewBox="0 0 24 24">
                    @if($isMatch)
                        <path d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    @else
                        <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    @endif
                </svg>
            </div>
            <div>
                <div class="eod-sc-lbl">Counted</div>
                <div class="eod-sc-val" style="color: {{ $isMatch ? '#10b981' : '#f97316' }};">${{ number_format($totalActual, 2) }}</div>
            </div>
        </div>

        {{-- Variance --}}
        <div class="eod-sc" style="border-left: 3px solid {{ $isMatch ? '#10b981' : '#f59e0b' }};">
            <div class="eod-sc-ico" style="background: {{ $isMatch ? 'rgba(16,185,129,0.1)' : 'rgba(245,158,11,0.1)' }}">
                <svg stroke="{{ $isMatch ? '#10b981' : '#f59e0b' }}" viewBox="0 0 24 24">
                    <path d="M3 7.5L7.5 3m0 0L12 7.5M7.5 3v13.5m13.5 0L16.5 21m0 0L12 16.5m4.5 4.5V7.5"/>
                </svg>
            </div>
            <div>
                <div class="eod-sc-lbl">Variance</div>
                <div class="eod-sc-val" style="color: {{ $isMatch ? '#10b981' : '#f59e0b' }};">
                    {{ $isMatch ? '±$0.00' : $diffFormatted }}
                </div>
            </div>
        </div>

        {{-- Active Methods --}}
        <div class="eod-sc" style="border-left: 3px solid #8b5cf6;">
            <div class="eod-sc-ico ico-purple">
                <svg stroke="#8b5cf6" viewBox="0 0 24 24">
                    <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
            </div>
            <div>
                <div class="eod-sc-lbl">Active Methods</div>
                <div class="eod-sc-val" style="color:#7c3aed;">
                    {{ $txCount }}<span style="font-size:14px;font-weight:400;color:#9ca3af;"> / {{ count($expectedTotals) }}</span>
                </div>
            </div>
        </div>

    </div>

    {{-- ═══════════ MAIN LAYOUT ═══════════ --}}
    <div class="eod-layout">

        {{-- LEFT: PAYMENT RECONCILIATION TABLE --}}
        <div class="eod-card">
            <div class="eod-card-header">
                <div class="eod-card-bar"></div>
                <span class="eod-card-title">Payment Reconciliation</span>
                <span class="eod-card-date">{{ \Carbon\Carbon::parse($date)->format('M d, Y') }}</span>
            </div>

            <div style="overflow-x: auto;">
                <table class="eod-tbl">
                    <thead>
                        <tr>
                            <th style="text-align:left;">Payment Type</th>
                            <th>Expected Total</th>
                            <th>Actual (Counted)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($expectedTotals as $key => $expected)
                            @php
                                $dotClass = $dotMap[strtolower($key)] ?? 'd-default';
                                $actual   = floatval($actualTotals[$key] ?? 0);
                                $rowMatch = $actual > 0 && round($actual, 2) == round((float)$expected, 2);
                                $valClass = $actual > 0 ? ($rowMatch ? 'match' : 'mismatch') : 'empty';
                            @endphp
                            <tr>
                                <td>
                                    <div class="eod-method">
                                        <div class="eod-dot {{ $dotClass }}"></div>
                                        <span class="eod-mname">{{ str_replace('_', ' ', strtoupper($key)) }}</span>
                                        @if($rowMatch)
                                            <span class="eod-match-badge">✓ Match</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="eod-expected-val">${{ number_format($expected, 2) }}</div>
                                </td>
                                <td>
                                    <div class="eod-actual-wrap">
                                        <div class="eod-actual-val {{ $valClass }}">
                                            ${{ number_format($actual, 2) }}
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>

                    @if(auth()->user()->hasRole('Superadmin') || $isClosed)
                        <tfoot>
                            <tr>
                                <td><span class="eod-tfoot-label">Day Summary</span></td>
                                <td class="eod-tfoot-expected">${{ number_format($totalExpected, 2) }}</td>
                                <td>
                                    <div class="eod-tfoot-actual-wrap">
                                        <div class="eod-tfoot-actual {{ $isMatch ? 'ok' : 'bad' }}">
                                            ${{ number_format($totalActual, 2) }}
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>

        {{-- RIGHT: SIDE PANEL --}}
        @if(auth()->user()->hasRole('Superadmin') || $isClosed)
        <div class="eod-side">

            {{-- Financial Summary --}}
            <div class="eod-card">
                <div class="eod-side-header">
                    <div class="eod-side-dot" style="background:#6366f1;"></div>
                    Financial Summary
                </div>
                <div class="eod-side-body">
                    <div class="eod-side-row">
                        <span class="eod-side-label">Expected</span>
                        <span class="eod-side-value" style="color:#4f46e5;">${{ number_format($totalExpected, 2) }}</span>
                    </div>
                    <div class="eod-side-row">
                        <span class="eod-side-label">Counted</span>
                        <span class="eod-side-value" style="color:{{ $isMatch ? '#10b981' : '#f97316' }};">${{ number_format($totalActual, 2) }}</span>
                    </div>
                    <div class="eod-variance-box {{ $isMatch ? 'ok' : 'bad' }}">
                        <div class="eod-variance-tag">
                            {{ $isMatch ? '✓ Balanced' : '⚠ Variance Detected' }}
                        </div>
                        <div class="eod-variance-num">
                            {{ $isMatch ? '$0.00' : $diffFormatted }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Method Breakdown --}}
            <div class="eod-card">
                <div class="eod-side-header">
                    <div class="eod-side-dot" style="background:#10b981;"></div>
                    Method Breakdown
                </div>
                <div class="eod-side-body">
                    @foreach($expectedTotals as $key => $expected)
                        @php
                            $actual   = floatval($actualTotals[$key] ?? 0);
                            $ok       = round($actual, 2) == round((float)$expected, 2);
                            $dotClass = $dotMap[strtolower($key)] ?? 'd-default';
                            $valColor = $actual > 0
                                ? ($ok ? '#10b981' : '#f97316')
                                : '#cbd5e1';
                        @endphp
                        <div class="eod-breakdown-row">
                            <span class="eod-breakdown-key">
                                <span class="eod-dot {{ $dotClass }}"></span>
                                {{ str_replace('_', ' ', $key) }}
                            </span>
                            <span class="eod-breakdown-val" style="color:{{ $valColor }};">
                                ${{ number_format($actual, 2) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>
        @endif

    </div>

    {{-- ═══════════ ACTION BAR ═══════════ --}}
    <div class="eod-actions">

        <div class="eod-action-left">
            <button class="eod-btn" onclick="window.print()">
                <svg viewBox="0 0 24 24"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                Print Slip
            </button>
            <button class="eod-btn">
                <svg viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                Email Report
            </button>
        </div>

        <div class="eod-action-right">

            @if(!$isClosed && $totalActual > 0)
                @if($isMatch)
                    <span class="eod-spill ok">✓ Balanced — Ready to Close</span>
                @else
                    <span class="eod-spill bad">⚠ Variance: {{ $diffFormatted }}</span>
                @endif
            @endif

            @if(!$isClosed)
                <button wire:click="mountAction('post_closing')" class="eod-btn eod-btn-lock">
                    <svg viewBox="0 0 24 24" style="stroke-width:2.5;">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                        <path d="M7 11V7a5 5 0 0110 0v4"/>
                    </svg>
                    Post Closing &amp; Lock Day
                </button>
            @endif

        </div>
    </div>

</div>
</x-filament-panels::page>