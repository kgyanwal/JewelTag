<x-filament-panels::page>
<style>
    /* ── RESET & BASE ── */
    .fi-body, .fi-main, .fi-page, .fi-main-ctn { background: #f8fafc !important; }
    .dark .fi-body, .dark .fi-main, .dark .fi-page, .dark .fi-main-ctn { background: #09090b !important; }

    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600;700&display=swap');

    .eod {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        width: 100%;
        max-width: 1300px;
        margin: 0 auto;
        padding: 1rem 0.5rem 2rem;
        color: #1e293b;
    }
    .dark .eod { color: #f1f5f9; }

    /* ── HERO ── */
    .eod-hero {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 20px 24px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 16px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    }
    .dark .eod-hero { background: #18181b; border-color: #27272a; }

    .eod-hero-left { display: flex; align-items: center; gap: 16px; }
    .eod-hero-icon {
        width: 44px; height: 44px;
        background: rgba(99,102,241,0.1);
        border: 1px solid rgba(99,102,241,0.2);
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .eod-hero-icon svg { width: 22px; height: 22px; stroke: #6366f1; fill: none; stroke-width: 1.75; stroke-linecap: round; stroke-linejoin: round; }
    
    .eod-hero-title { font-size: 18px; font-weight: 700; color: #0f172a; margin: 0 0 4px; letter-spacing: -0.01em; }
    .dark .eod-hero-title { color: #ffffff; }
    .eod-hero-sub { font-size: 13px; color: #64748b; margin: 0; }

    .eod-hero-right { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
    .eod-date-input {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        color: #1e293b;
        padding: 8px 14px;
        border-radius: 8px;
        font-family: 'JetBrains Mono', monospace;
        font-size: 13px;
        cursor: pointer;
        outline: none;
        transition: border-color 0.2s;
    }
    .dark .eod-date-input { background: #18181b; border-color: #3f3f46; color: #e2e8f0; color-scheme: dark; }
    .eod-date-input:hover, .eod-date-input:focus { border-color: #6366f1; }
    
    .eod-date-static {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        color: #475569;
        padding: 8px 14px;
        border-radius: 8px;
        font-family: 'JetBrains Mono', monospace;
        font-size: 13px;
        display: inline-flex; align-items: center; gap: 8px;
    }
    .dark .eod-date-static { background: #18181b; border-color: #3f3f46; color: #a1a1aa; }
    .eod-date-static svg { width: 14px; height: 14px; stroke: #6366f1; fill: none; stroke-width: 2; }

    .eod-status-pill {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 8px 14px; border-radius: 40px;
        font-size: 11px; font-weight: 700;
        letter-spacing: 0.05em; text-transform: uppercase;
    }
    .eod-status-pill.locked { background: #ecfdf5; border: 1px solid #d1fae5; color: #059669; }
    .eod-status-pill.open   { background: #fffbeb; border: 1px solid #fef3c7; color: #d97706; }
    .dark .eod-status-pill.locked { background: rgba(16,185,129,0.1); border-color: rgba(16,185,129,0.2); color: #34d399; }
    .dark .eod-status-pill.open   { background: rgba(245,158,11,0.1); border-color: rgba(245,158,11,0.2); color: #fbbf24; }
    .eod-status-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
    .eod-status-pill.open .eod-status-dot { animation: blink 2s ease-in-out infinite; }
    @keyframes blink { 0%,100% { opacity:1; } 50% { opacity:0.3; } }

    /* ── STAT CARDS ── */
    .eod-stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
        margin-bottom: 20px;
    }
    @media(max-width: 860px) { .eod-stats { grid-template-columns: repeat(2, minmax(0,1fr)); } }
    @media(max-width: 480px) { .eod-stats { grid-template-columns: 1fr; } }

    .eod-sc {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 16px 20px;
        display: flex; align-items: center; gap: 14px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    }
    .dark .eod-sc { background: #18181b; border-color: #27272a; }

    .eod-sc-ico {
        width: 40px; height: 40px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .eod-sc-ico svg { width: 20px; height: 20px; fill: none; stroke-width: 1.75; stroke-linecap: round; stroke-linejoin: round; }
    .eod-sc-lbl { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; margin-bottom: 4px; }
    .eod-sc-val { font-family: 'JetBrains Mono', monospace; font-size: 20px; font-weight: 700; line-height: 1.2; letter-spacing: -0.02em;}

    /* ── EDITABLE INPUT ── */
    .eod-editable-input {
        font-family: 'JetBrains Mono', monospace;
        font-size: 20px; font-weight: 700; color: inherit;
        background: transparent; border: 1px dashed transparent; border-radius: 6px;
        padding: 2px 6px; margin-left: 2px; width: 130px;
        outline: none; transition: all 0.2s ease;
        -moz-appearance: textfield;
    }
    .eod-editable-input::-webkit-outer-spin-button,
    .eod-editable-input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    .eod-editable-input:hover { border-color: #cbd5e1; background: #f8fafc; cursor: text; }
    .eod-editable-input:focus { border-style: solid; border-color: #6366f1; background: #fff; box-shadow: 0 0 0 3px rgba(99,102,241,0.15); }
    .dark .eod-editable-input:hover { border-color: #3f3f46; background: #27272a; }
    .dark .eod-editable-input:focus { border-color: #6366f1; background: #18181b; }

    /* ── LAYOUT ── */
    .eod-layout {
        display: grid;
        grid-template-columns: 1fr 320px;
        gap: 16px;
        align-items: start;
    }
    @media(max-width: 960px) { .eod-layout { grid-template-columns: 1fr; } }

    /* ── CARDS ── */
    .eod-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    }
    .dark .eod-card { background: #18181b; border-color: #27272a; }

    .eod-card-header {
        padding: 14px 20px;
        border-bottom: 1px solid #f1f5f9;
        background: #f8fafc;
        display: flex; align-items: center; gap: 10px;
    }
    .dark .eod-card-header { background: #1c1c21; border-color: #27272a; }
    .eod-card-bar { width: 3px; height: 16px; border-radius: 2px; background: #6366f1; flex-shrink: 0; }
    .eod-card-title { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #334155; }
    .dark .eod-card-title { color: #e2e8f0; }
    .eod-card-date {
        margin-left: auto; font-family: 'JetBrains Mono', monospace;
        font-size: 11px; color: #64748b;
        background: #f1f5f9; padding: 4px 10px; border-radius: 6px;
    }
    .dark .eod-card-date { background: #27272a; color: #a1a1aa; }

    /* ── TABLE & ZEBRA STRIPING ── */
    .eod-tbl { width: 100%; border-collapse: collapse; }

    .eod-tbl thead th {
        padding: 10px 20px;
        font-size: 11px; font-weight: 600; letter-spacing: 0.05em;
        text-transform: uppercase; color: #64748b;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
    }
    .dark .eod-tbl thead th { background: #1c1c21; border-color: #27272a; color: #94a3b8; }
    .eod-tbl thead th:not(:first-child) { text-align: right; }

    .eod-tbl tbody tr {
        border-bottom: 1px solid #f1f5f9;
        transition: background 0.1s;
    }
    .eod-tbl tbody tr:nth-child(even) { background-color: #f8fafc; }
    .eod-tbl tbody tr:hover { background-color: #f1f5f9 !important; }
    
    .dark .eod-tbl tbody tr { border-color: #27272a; }
    .dark .eod-tbl tbody tr:nth-child(even) { background-color: #1a1a1e; }
    .dark .eod-tbl tbody tr:hover { background-color: #27272a !important; }
    
    .eod-tbl tbody tr:last-child { border-bottom: none; }
    .eod-tbl tbody td { padding: 10px 16px; vertical-align: middle; }

    .eod-method { display: inline-flex; align-items: center; gap: 10px; }
    .eod-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
    .eod-mname { font-size: 13px; font-weight: 600; color: #334155; }
    .dark .eod-mname { color: #e2e8f0; }
    .eod-match-badge {
        font-size: 9px; font-weight: 700; padding: 2px 6px; border-radius: 4px;
        letter-spacing: 0.05em; text-transform: uppercase;
        background: #f0fdf4; color: #166534; border: 1px solid #dcfce7;
    }
    .dark .eod-match-badge { background: rgba(22,101,52,0.15); color: #4ade80; border-color: rgba(34,197,94,0.2); }

    .eod-expected-val {
        text-align: right;
        font-family: 'JetBrains Mono', monospace;
        font-size: 14px; font-weight: 500; color: #64748b;
    }
    .dark .eod-expected-val { color: #94a3b8; }

    .eod-actual-wrap { display: flex; justify-content: flex-end; }
    .eod-actual-val {
        font-family: 'JetBrains Mono', monospace;
        font-size: 14px; font-weight: 600;
        padding: 6px 12px; border-radius: 8px;
        min-width: 120px; text-align: right;
        display: inline-block;
    }
    .eod-actual-val.match {
        background: #f0fdf4; color: #166534;
        border: 1px solid #dcfce7;
    }
    .dark .eod-actual-val.match {
        background: rgba(22,101,52,0.15); color: #4ade80;
        border-color: rgba(34,197,94,0.2);
    }
    .eod-actual-val.mismatch {
        background: #fef2f2; color: #991b1b;
        border: 1px solid #fee2e2;
    }
    .dark .eod-actual-val.mismatch {
        background: rgba(153,27,27,0.15); color: #f87171;
        border-color: rgba(239,68,68,0.2);
    }
    .eod-actual-val.empty {
        background: #f8fafc; color: #94a3b8;
        border: 1px solid #f1f5f9;
    }
    .dark .eod-actual-val.empty { background: #1c1c21; color: #52525b; border-color: #27272a; }

    /* ── TABLE FOOTER ── */
    .eod-tbl tfoot tr { background: #f8fafc; border-top: 2px solid #e2e8f0; }
    .dark .eod-tbl tfoot tr { background: #1c1c21; border-color: #27272a; }
    .eod-tbl tfoot td { padding: 12px 16px; }
    .eod-tfoot-label { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #475569; }
    .dark .eod-tfoot-label { color: #94a3b8; }
    .eod-tfoot-expected {
        text-align: right;
        font-family: 'JetBrains Mono', monospace;
        font-size: 16px; font-weight: 600; color: #4f46e5;
    }
    .dark .eod-tfoot-expected { color: #818cf8; }
    .eod-tfoot-actual-wrap { display: flex; justify-content: flex-end; }
    .eod-tfoot-actual {
        font-family: 'JetBrains Mono', monospace;
        font-size: 16px; font-weight: 700;
        padding: 8px 16px; border-radius: 8px;
        min-width: 120px; text-align: right; display: inline-block;
    }
    .eod-tfoot-actual.ok { background: #f0fdf4; color: #166534; border: 1px solid #dcfce7; }
    .eod-tfoot-actual.bad { background: #fef2f2; color: #991b1b; border: 1px solid #fee2e2; }
    .dark .eod-tfoot-actual.ok { background: rgba(22,101,52,0.15); color: #4ade80; border-color: rgba(34,197,94,0.2); }
    .dark .eod-tfoot-actual.bad { background: rgba(153,27,27,0.15); color: #f87171; border-color: rgba(239,68,68,0.2); }

    /* ── SIDE PANEL ── */
    .eod-side { display: flex; flex-direction: column; gap: 16px; }

    .eod-side-header {
        padding: 12px 20px; font-size: 11px; font-weight: 600;
        letter-spacing: 0.05em; text-transform: uppercase; color: #64748b;
        border-bottom: 1px solid #f1f5f9; background: #f8fafc;
        display: flex; align-items: center; gap: 8px;
    }
    .dark .eod-side-header { background: #1c1c21; border-color: #27272a; color: #94a3b8; }
    .eod-side-dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
    .eod-side-body { padding: 16px 20px; }

    .eod-side-row {
        display: flex; justify-content: space-between; align-items: center;
        padding: 10px 0; border-bottom: 1px dashed #e2e8f0;
    }
    .dark .eod-side-row { border-color: #27272a; }
    .eod-side-row:first-child { padding-top: 0; }
    .eod-side-row:last-child { border-bottom: none; padding-bottom: 0; }
    .eod-side-label { font-size: 12px; font-weight: 600; color: #64748b; }
    .eod-side-value { font-family: 'JetBrains Mono', monospace; font-size: 15px; font-weight: 600; color: #1e293b; }
    .dark .eod-side-value { color: #f1f5f9; }

    .eod-variance-box { border-radius: 10px; padding: 16px; margin-top: 12px; text-align: center; }
    .eod-variance-box.ok  { background: #ecfdf5; border: 1px solid #d1fae5; }
    .eod-variance-box.bad { background: #fff7ed; border: 1px solid #ffedd5; }
    .dark .eod-variance-box.ok  { background: rgba(16,185,129,0.05); border-color: #065f46; }
    .dark .eod-variance-box.bad { background: rgba(249,115,22,0.05); border-color: #9a3412; }
    
    .eod-variance-tag { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px; }
    .eod-variance-box.ok  .eod-variance-tag { color: #059669; }
    .eod-variance-box.bad .eod-variance-tag { color: #c2410c; }
    .dark .eod-variance-box.ok  .eod-variance-tag { color: #34d399; }
    .dark .eod-variance-box.bad .eod-variance-tag { color: #fb923c; }
    
    .eod-variance-num { font-family: 'JetBrains Mono', monospace; font-size: 26px; font-weight: 700; line-height: 1; }
    .eod-variance-box.ok  .eod-variance-num { color: #047857; }
    .eod-variance-box.bad .eod-variance-num { color: #b91c1c; }
    .dark .eod-variance-box.ok  .eod-variance-num { color: #10b981; }
    .dark .eod-variance-box.bad .eod-variance-num { color: #ef4444; }

    .eod-breakdown-row {
        display: flex; justify-content: space-between; align-items: center;
        padding: 8px 0; border-bottom: 1px solid #f1f5f9;
    }
    .dark .eod-breakdown-row { border-color: #27272a; }
    .eod-breakdown-row:last-child { border-bottom: none; padding-bottom: 0; }
    .eod-breakdown-key { display: flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 600; color: #475569; }
    .dark .eod-breakdown-key { color: #a1a1aa; }
    .eod-breakdown-val { font-family: 'JetBrains Mono', monospace; font-size: 13px; font-weight: 600; }

    /* ── ACTIONS ── */
    .eod-actions {
        display: flex; align-items: center; justify-content: space-between;
        margin-top: 20px; padding: 16px 20px;
        background: #fff; border: 1px solid #e2e8f0; border-radius: 12px;
        flex-wrap: wrap; gap: 14px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    }
    .dark .eod-actions { background: #18181b; border-color: #27272a; }

    .eod-action-left { display: flex; gap: 10px; }
    .eod-btn {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 8px 16px;
        font-family: 'Inter', sans-serif;
        font-size: 12px; font-weight: 600;
        border-radius: 8px; cursor: pointer;
        border: 1px solid #e2e8f0;
        background: #fff; color: #475569;
        transition: all 0.15s ease;
    }
    .dark .eod-btn { background: #27272a; border-color: #3f3f46; color: #e2e8f0; }
    .eod-btn:hover { background: #f8fafc; border-color: #cbd5e1; color: #0f172a; }
    .dark .eod-btn:hover { background: #3f3f46; border-color: #52525b; color: #fff; }
    .eod-btn svg { width: 14px; height: 14px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }

    .eod-btn-lock {
        background: #4f46e5; color: #fff; border-color: #4f46e5;
    }
    .dark .eod-btn-lock { background: #6366f1; border-color: #6366f1; }
    .eod-btn-lock:hover { background: #4338ca; border-color: #4338ca; color: #fff; }
    .dark .eod-btn-lock:hover { background: #4f46e5; border-color: #4f46e5; }

    .eod-action-right { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; }

    .eod-spill {
        display: inline-flex; align-items: center; gap: 6px;
        font-size: 12px; font-weight: 600; padding: 8px 16px;
        border-radius: 8px;
    }
    .eod-spill.ok  { background: #ecfdf5; border: 1px solid #d1fae5; color: #059669; }
    .eod-spill.bad { background: #fef2f2; border: 1px solid #fee2e2; color: #b91c1c; }
    .dark .eod-spill.ok  { background: rgba(16,185,129,0.1); border-color: rgba(16,185,129,0.2); color: #34d399; }
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
        // 1. Get the actual and expected sums from the arrays
        $systemExpected = array_sum($expectedTotals ?? []);
        $totalActual    = array_sum($actualTotals ?? []);

        // 2. Safe Fallback Logic:
        //    If the user types a valid number, use it.
        //    If they leave it completely empty or null, default back to the System Expected.
        $safeExpected = $totalExpected ?? null;
        $calcExpected = is_numeric($safeExpected) ? (float) $safeExpected : $systemExpected;

        // 3. Mathematical Calculations
        $isMatch = round($totalActual, 2) == round($calcExpected, 2);
        $diff    = $totalActual - $calcExpected;
        $txCount = collect($expectedTotals)->filter(fn($v) => $v > 0)->count();

        // 4. Color map for the table dots
        $dotMap = [
            'cash'       => 'd-cash',
            'visa'       => 'd-visa',
            'mastercard' => 'd-mastercard',
            'amex'       => 'd-amex',
            'laybuy'     => 'd-laybuy',
            'split'      => 'd-split',
            'katapult'   => 'd-katapult',
        ];

        // 5. String Formatting Fix: Ensure negative numbers actually show a minus sign (-)
        $diffSign = $diff > 0 ? '+' : ($diff < 0 ? '-' : '');
        $diffFormatted = $diffSign . '$' . number_format(abs($diff), 2);
    @endphp

    {{-- ═══════════ STAT CARDS ═══════════ --}}
    <div class="eod-stats">

        {{-- Expected (Editable) --}}
        <div class="eod-sc">
            <div class="eod-sc-ico ico-indigo">
                <svg stroke="#6366f1" viewBox="0 0 24 24">
                    <rect x="2" y="4" width="20" height="16" rx="2"/>
                    <path d="M7 15h0M2 9h20"/>
                </svg>
            </div>
            <div>
                <div class="eod-sc-lbl" style="display: flex; align-items: center; gap: 6px;">
                    Expected
                    <svg style="width:10px; height:10px; color:#94a3b8;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                </div>
                <div class="eod-sc-val" style="color:#4f46e5; display: flex; align-items: center;">
                    $<input 
                        type="number" 
                        step="0.01" 
                        wire:model.live="totalExpected" 
                        class="eod-editable-input" 
                        placeholder="{{ number_format($systemExpected, 2, '.', '') }}"
                    >
                </div>
            </div>
        </div>

        {{-- Counted --}}
        <div class="eod-sc">
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
        <div class="eod-sc">
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
        <div class="eod-sc">
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
                                <td class="eod-tfoot-expected">${{ number_format($calcExpected, 2) }}</td>
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
                        <span class="eod-side-value" style="color:#4f46e5;">${{ number_format($calcExpected, 2) }}</span>
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
                                : '#94a3b8';
                        @endphp
                        <div class="eod-breakdown-row">
                            <span class="eod-breakdown-key">
                                <span class="eod-dot {{ $dotClass }}"></span>
                                {{ str_replace('_', ' ', strtoupper($key)) }}
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
                    <span class="eod-spill ok">
                        <svg style="width:14px; height:14px; fill:none; stroke:currentColor; stroke-width:2; stroke-linecap:round; stroke-linejoin:round; display:inline-block; vertical-align:middle; margin-bottom:2px;" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                        Balanced — Ready to Close
                    </span>
                @else
                    <span class="eod-spill bad">
                        <svg style="width:14px; height:14px; fill:none; stroke:currentColor; stroke-width:2; stroke-linecap:round; stroke-linejoin:round; display:inline-block; vertical-align:middle; margin-bottom:2px;" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                        Variance: {{ $diffFormatted }}
                    </span>
                @endif
            @endif

            @if(!$isClosed)
                <button wire:click="mountAction('post_closing')" class="eod-btn eod-btn-lock">
                    <svg viewBox="0 0 24 24">
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