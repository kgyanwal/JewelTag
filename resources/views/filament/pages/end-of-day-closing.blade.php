<x-filament-panels::page>

<style>
    /* ── BASE & VARIABLES ── */
    .fi-body, .fi-main, .fi-page, .fi-main-ctn { background: #f4f6fb !important; }
    .dark .fi-body, .dark .fi-main, .dark .fi-page, .dark .fi-main-ctn { background: #0f1115 !important; }

    .eod { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; width: 100%; color: #1f2937; }
    .dark .eod { color: #f3f4f6; }

    /* ── HERO HEADER ── */
    .eod-hero {
        position: relative; overflow: hidden;
        background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #312e81 100%);
        border-radius: 16px; padding: 24px 32px; margin-bottom: 24px;
        display: flex; align-items: center; justify-content: space-between;
        flex-wrap: wrap; gap: 20px;
        box-shadow: 0 10px 30px -5px rgba(15, 23, 42, 0.3);
    }
    .eod-hero::before {
        content: ''; position: absolute; top: -50%; right: -10%;
        width: 400px; height: 400px; border-radius: 50%;
        background: radial-gradient(circle, rgba(99,102,241,0.15) 0%, transparent 70%);
        pointer-events: none;
    }
    .eod-hero-left { display: flex; align-items: center; gap: 20px; z-index: 1; }
    .eod-hero-icon {
        background: rgba(255,255,255,0.1); backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,0.15); border-radius: 12px;
        padding: 14px; box-shadow: 0 4px 20px rgba(0,0,0,0.2);
    }
    .eod-hero-icon svg { width: 28px; height: 28px; stroke: #fff; fill: none; stroke-width: 1.5; }
    .eod-hero-badge {
        display: inline-block; background: rgba(99,102,241,0.2);
        border: 1px solid rgba(99,102,241,0.3); color: #c7d2fe;
        font-size: 10px; font-weight: 700; letter-spacing: 0.1em;
        text-transform: uppercase; padding: 4px 12px; border-radius: 99px; margin-bottom: 8px;
    }
    .eod-hero-title { font-size: 24px; font-weight: 800; color: #fff; letter-spacing: -0.02em; margin: 0 0 4px; }
    .eod-hero-sub { font-size: 14px; color: #94a3b8; margin: 0; }
    .eod-hero-right { display: flex; align-items: center; gap: 16px; z-index: 1; flex-wrap: wrap; }

    /* ── FIXED DATE PICKER ── */
    /* Replaced the buggy opacity overlay with a cleanly styled native input */
    .eod-date-input {
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: #fff;
        padding: 10px 16px;
        border-radius: 10px;
        font-family: inherit;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        backdrop-filter: blur(8px);
        transition: all 0.2s ease;
        color-scheme: dark; /* Forces white calendar icon in modern browsers */
        outline: none;
    }
    .eod-date-input:hover, .eod-date-input:focus {
        background: rgba(255, 255, 255, 0.15);
        border-color: rgba(255, 255, 255, 0.4);
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
    }
    .eod-date-static {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: #fff; padding: 10px 16px; border-radius: 10px;
        font-size: 15px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;
    }

    /* ── PILLS ── */
    .eod-pill {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 10px 16px; border-radius: 10px;
        font-size: 11px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase;
    }
    .eod-pill.locked { background: rgba(34,197,94,0.15); color: #4ade80; border: 1px solid rgba(34,197,94,0.3); }
    .eod-pill.open   { background: rgba(245,158,11,0.15); color: #fbbf24; border: 1px solid rgba(245,158,11,0.3); }
    .eod-pill-dot { width: 8px; height: 8px; border-radius: 50%; background: currentColor; box-shadow: 0 0 8px currentColor; }
    .eod-pill.open .eod-pill-dot { animation: pulse 2s infinite; }
    @keyframes pulse { 0% { opacity: 1; transform: scale(1); } 50% { opacity: 0.4; transform: scale(0.8); } 100% { opacity: 1; transform: scale(1); } }

    /* ── STAT CARDS ── */
    .eod-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px; }
    .eod-sc {
        background: #fff; border-radius: 16px; padding: 20px;
        border: 1px solid #e5e7eb; display: flex; align-items: center; gap: 16px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02); transition: transform 0.2s, box-shadow 0.2s;
    }
    .dark .eod-sc { background: #18181b; border-color: #27272a; }
    .eod-sc:hover { transform: translateY(-2px); box-shadow: 0 8px 16px rgba(0,0,0,0.06); }
    .eod-sc-ico { border-radius: 12px; padding: 12px; flex-shrink: 0; }
    .eod-sc-ico svg { width: 22px; height: 22px; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .eod-sc-lbl { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 6px; color: #6b7280; }
    .dark .eod-sc-lbl { color: #9ca3af; }
    .eod-sc-val { font-size: 24px; font-weight: 800; font-variant-numeric: tabular-nums; line-height: 1; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; }

    /* ── MAIN LAYOUT ── */
    .eod-layout { display: grid; grid-template-columns: 1fr 320px; gap: 24px; align-items: start; }
    @media (max-width: 1024px) { .eod-layout { grid-template-columns: 1fr; } }

    /* ── TABLE CARD ── */
    .eod-card {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 16px;
        overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
    }
    .dark .eod-card { background: #18181b; border-color: #27272a; }
    
    .eod-ch {
        padding: 16px 24px; border-bottom: 1px solid #e5e7eb;
        background: #f9fafb; display: flex; align-items: center; gap: 12px;
    }
    .dark .eod-ch { background: #1f2937; border-color: #27272a; }
    .eod-ch-bar { width: 4px; height: 20px; border-radius: 4px; background: #4f46e5; flex-shrink: 0; }
    .eod-ch-title { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: #374151; }
    .dark .eod-ch-title { color: #d1d5db; }
    .eod-ch-date { margin-left: auto; font-size: 11px; color: #6b7280; font-weight: 600; background: #f3f4f6; padding: 4px 12px; border-radius: 99px; }
    .dark .eod-ch-date { background: #374151; color: #9ca3af; }

    /* ── TABLE ── */
    .eod-tbl { width: 100%; border-collapse: collapse; }
    .eod-tbl th {
        padding: 14px 24px; font-size: 10px; letter-spacing: 0.1em;
        text-transform: uppercase; font-weight: 700; color: #6b7280; background: #fff; border-bottom: 1px solid #e5e7eb;
    }
    .dark .eod-tbl th { background: #18181b; color: #9ca3af; border-color: #27272a; }
    .eod-tbl th:not(:first-child) { text-align: right; }
    .eod-tbl th:first-child { text-align: left; }
    
    .eod-tbl tbody tr { border-bottom: 1px solid #f3f4f6; transition: background 0.15s; }
    .dark .eod-tbl tbody tr { border-color: #27272a; }
    .eod-tbl tbody tr:last-child { border-bottom: none; }
    .eod-tbl tbody tr:hover { background: #f9fafb; }
    .dark .eod-tbl tbody tr:hover { background: #1f2937; }
    .eod-tbl td { padding: 16px 24px; vertical-align: middle; }
    
    .eod-method { display: inline-flex; align-items: center; gap: 12px; }
    .eod-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
    .eod-mname { font-size: 13px; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; color: #111827; }
    .dark .eod-mname { color: #f3f4f6; }
    .eod-badge { font-size: 10px; font-weight: 700; padding: 4px 10px; border-radius: 6px; letter-spacing: 0.05em; text-transform: uppercase; }
    .eod-badge.ok   { background: #dcfce7; color: #16a34a; }
    .eod-badge.diff { background: #fee2e2; color: #dc2626; }
    .dark .eod-badge.ok   { background: rgba(22,163,74,0.15); color: #4ade80; }
    .dark .eod-badge.diff { background: rgba(220,38,38,0.15); color: #f87171; }
    
    .eod-ev { text-align: right; font-size: 16px; font-weight: 700; font-family: ui-monospace, SFMono-Regular, monospace; color: #374151; }
    .dark .eod-ev { color: #d1d5db; }
    .eod-ev.nil { color: #9ca3af; }
    .dark .eod-ev.nil { color: #6b7280; }
    
    .eod-ic { text-align: right; }
    .eod-in {
        width: 140px; text-align: right; font-family: ui-monospace, SFMono-Regular, monospace;
        font-size: 15px; font-weight: 700; padding: 10px 14px;
        border: 1px solid #d1d5db; border-radius: 8px;
        background: #fff; color: #111827; outline: none;
        transition: all 0.2s; box-shadow: 0 1px 2px rgba(0,0,0,0.05) inset;
    }
    .dark .eod-in { background: #0f1115; border-color: #3f3f46; color: #f3f4f6; }
    .eod-in:focus { border-color: #4f46e5; box-shadow: 0 0 0 3px rgba(79,70,229,0.15); }
    .eod-in:hover:not(:disabled) { border-color: #9ca3af; }
    .dark .eod-in:hover:not(:disabled) { border-color: #6b7280; }
    .eod-in:disabled { background: #f3f4f6; color: #9ca3af; border-color: #e5e7eb; cursor: not-allowed; box-shadow: none; }
    .dark .eod-in:disabled { background: #18181b; color: #52525b; border-color: #27272a; }
    
    .eod-tbl tfoot tr { background: #f9fafb; border-top: 2px solid #e5e7eb; }
    .dark .eod-tbl tfoot tr { background: #1f2937; border-color: #374151; }
    .eod-tbl tfoot td { padding: 16px 24px; font-size: 11px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #4b5563; }
    .dark .eod-tbl tfoot td { color: #9ca3af; }
    .eod-fe { text-align: right; font-size: 18px !important; color: #111827 !important; font-family: ui-monospace, monospace; font-weight: 800; }
    .dark .eod-fe { color: #f3f4f6 !important; }
    .eod-fc { text-align: right; font-size: 18px !important; font-family: ui-monospace, monospace; font-weight: 800; }
    .eod-fc.ok  { color: #16a34a !important; }
    .eod-fc.bad { color: #dc2626 !important; }
    .dark .eod-fc.ok  { color: #4ade80 !important; }
    .dark .eod-fc.bad { color: #f87171 !important; }

    /* ── SIDE PANEL ── */
    .eod-side { display: flex; flex-direction: column; gap: 24px; }
    .eod-sh {
        padding: 16px 20px; font-size: 11px; font-weight: 700;
        letter-spacing: 0.1em; text-transform: uppercase; color: #4b5563;
        border-bottom: 1px solid #e5e7eb; background: #f9fafb;
        display: flex; align-items: center; gap: 10px;
    }
    .dark .eod-sh { background: #1f2937; border-color: #27272a; color: #9ca3af; }
    .eod-sd { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
    .eod-sb { padding: 20px; }
    
    .eod-sr { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px dashed #e5e7eb; }
    .dark .eod-sr { border-color: #3f3f46; }
    .eod-sr:first-child { padding-top: 0; }
    .eod-sr:last-child { border-bottom: none; padding-bottom: 0; }
    .eod-sl { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; }
    .dark .eod-sl { color: #9ca3af; }
    .eod-sv { font-size: 18px; font-weight: 800; font-family: ui-monospace, monospace; color: #111827; }
    .dark .eod-sv { color: #f3f4f6; }
    
    .eod-vb { border-radius: 12px; padding: 16px; margin-top: 16px; display: flex; flex-direction: column; gap: 4px; }
    .eod-vb.ok  { background: #f0fdf4; border: 1px solid #bbf7d0; }
    .eod-vb.bad { background: #fef2f2; border: 1px solid #fecaca; }
    .dark .eod-vb.ok  { background: rgba(22,163,74,0.1); border-color: rgba(22,163,74,0.2); }
    .dark .eod-vb.bad { background: rgba(220,38,38,0.1); border-color: rgba(220,38,38,0.2); }
    
    .eod-vt { font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; }
    .eod-vb.ok  .eod-vt { color: #166534; }
    .eod-vb.bad .eod-vt { color: #991b1b; }
    .dark .eod-vb.ok  .eod-vt { color: #4ade80; }
    .dark .eod-vb.bad .eod-vt { color: #f87171; }
    
    .eod-vn { font-size: 28px; font-weight: 900; font-family: ui-monospace, monospace; line-height: 1; }
    .eod-vb.ok  .eod-vn { color: #15803d; }
    .eod-vb.bad .eod-vn { color: #b91c1c; }
    .dark .eod-vb.ok  .eod-vn { color: #22c55e; }
    .dark .eod-vb.bad .eod-vn { color: #ef4444; }
    
    .eod-br { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #f3f4f6; }
    .dark .eod-br { border-color: #27272a; }
    .eod-br:last-child { border-bottom: none; padding-bottom: 0; }
    .eod-bk { display: flex; align-items: center; gap: 8px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #4b5563; }
    .dark .eod-bk { color: #d1d5db; }
    .eod-bv { font-size: 14px; font-weight: 800; font-family: ui-monospace, monospace; }

    /* ── BOTTOM ACTIONS ── */
    .eod-actions {
        display: flex; align-items: center; justify-content: space-between;
        margin-top: 24px; padding: 16px 24px;
        background: #fff; border: 1px solid #e5e7eb; border-radius: 16px;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); flex-wrap: wrap; gap: 16px;
    }
    .dark .eod-actions { background: #18181b; border-color: #27272a; }
    .eod-al { display: flex; gap: 12px; }
    .eod-btn {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 10px 20px; font-family: inherit;
        font-size: 12px; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase;
        border-radius: 8px; cursor: pointer;
        border: 1px solid #d1d5db; background: #fff; color: #374151; transition: all 0.2s;
    }
    .dark .eod-btn { background: #27272a; border-color: #3f3f46; color: #e5e7eb; }
    .eod-btn:hover { background: #f3f4f6; border-color: #9ca3af; color: #111827; }
    .dark .eod-btn:hover { background: #3f3f46; border-color: #52525b; color: #fff; }
    .eod-btn svg { width: 16px; height: 16px; flex-shrink: 0; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    
    .eod-lock {
        padding: 12px 28px;
        background: #4f46e5;
        color: #fff !important; border: none;
        font-weight: 800; box-shadow: 0 4px 12px rgba(79,70,229,0.3);
    }
    .dark .eod-lock { background: #6366f1; }
    .eod-lock:hover { background: #4338ca; box-shadow: 0 6px 16px rgba(79,70,229,0.4); transform: translateY(-1px); }
    .dark .eod-lock:hover { background: #4f46e5; }
    
    .eod-spill {
        display: inline-flex; align-items: center; gap: 8px;
        font-size: 11px; font-weight: 800; padding: 10px 16px;
        border-radius: 8px; letter-spacing: 0.05em; text-transform: uppercase;
    }
    .eod-spill.ok  { background: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d; }
    .eod-spill.bad { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; }
    .dark .eod-spill.ok  { background: rgba(22,163,74,0.1); border-color: rgba(22,163,74,0.2); color: #4ade80; }
    .dark .eod-spill.bad { background: rgba(220,38,38,0.1); border-color: rgba(220,38,38,0.2); color: #f87171; }

    /* Method Colors */
    .d-cash       { background: #10b981; }
    .d-visa       { background: #3b82f6; }
    .d-mastercard { background: #ef4444; }
    .d-amex       { background: #06b6d4; }
    .d-laybuy     { background: #f59e0b; }
    .d-split      { background: #8b5cf6; }
    .d-default    { background: #64748b; }
</style>

<div class="eod">

    {{-- ═══ HERO HEADER ═══ --}}
    <div class="eod-hero">
        <div class="eod-hero-left">
            <div class="eod-hero-icon">
                <svg viewBox="0 0 24 24"><path d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <div class="eod-hero-badge">End-of-Day Report</div>
                <h1 class="eod-hero-title">Daily Closing Register</h1>
                <p class="eod-hero-sub">Reconcile payments and lock the register for {{ \Carbon\Carbon::parse($date)->format('l, F j, Y') }}</p>
            </div>
        </div>

        <div class="eod-hero-right">
            @if(auth()->user()->hasRole('Superadmin'))
                {{-- Clean native date input --}}
                <input 
                    type="date" 
                    wire:model.live="date" 
                    max="{{ now()->format('Y-m-d') }}" 
                    class="eod-date-input"
                >
            @else
                <div class="eod-date-static">
                    <svg style="width:16px;height:16px;stroke:rgba(255,255,255,0.7);fill:none;stroke-width:2;" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    {{ \Carbon\Carbon::parse($date)->format('M d, Y') }}
                </div>
            @endif

            @if($isClosed)
                <span class="eod-pill locked"><span class="eod-pill-dot"></span> Record Locked</span>
            @else
                <span class="eod-pill open"><span class="eod-pill-dot"></span> Open for Entry</span>
            @endif
        </div>
    </div>

    @php
        $totalExpected = array_sum($expectedTotals);
        $totalActual   = array_sum($actualTotals ?? []);
        $isMatch       = round($totalActual, 2) == round($totalExpected, 2);
        $diff          = $totalActual - $totalExpected;
        $txCount       = collect($expectedTotals)->filter(fn($v) => $v > 0)->count();
        $dots = ['cash'=>'d-cash','visa'=>'d-visa','mastercard'=>'d-mastercard','amex'=>'d-amex','laybuy'=>'d-laybuy','split'=>'d-split'];
    @endphp

    {{-- ═══ STAT CARDS ═══ --}}
    <div class="eod-stats">
        <div class="eod-sc" style="border-left: 4px solid #3b82f6;">
            <div class="eod-sc-ico" style="background: rgba(59,130,246,0.1);">
                <svg stroke="#3b82f6"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M7 15h0M2 9h20"/></svg>
            </div>
            <div>
                <div class="eod-sc-lbl">Expected Total</div>
                <div class="eod-sc-val" style="color: #3b82f6;">${{ number_format($totalExpected, 2) }}</div>
            </div>
        </div>

        <div class="eod-sc" style="border-left: 4px solid {{ $isMatch ? '#10b981' : '#ef4444' }};">
            <div class="eod-sc-ico" style="background: {{ $isMatch ? 'rgba(16,185,129,0.1)' : 'rgba(239,68,68,0.1)' }};">
                <svg stroke="{{ $isMatch ? '#10b981' : '#ef4444' }}">
                    @if($isMatch)<path d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    @else<path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>@endif
                </svg>
            </div>
            <div>
                <div class="eod-sc-lbl">Counted Total</div>
                <div class="eod-sc-val" style="color: {{ $isMatch ? '#10b981' : '#ef4444' }};">${{ number_format($totalActual, 2) }}</div>
            </div>
        </div>

        <div class="eod-sc" style="border-left: 4px solid {{ $isMatch ? '#10b981' : '#f59e0b' }};">
            <div class="eod-sc-ico" style="background: {{ $isMatch ? 'rgba(16,185,129,0.1)' : 'rgba(245,158,11,0.1)' }};">
                <svg stroke="{{ $isMatch ? '#10b981' : '#f59e0b' }}"><path d="M3 7.5L7.5 3m0 0L12 7.5M7.5 3v13.5m13.5 0L16.5 21m0 0L12 16.5m4.5 4.5V7.5"/></svg>
            </div>
            <div>
                <div class="eod-sc-lbl">Variance</div>
                <div class="eod-sc-val" style="color: {{ $isMatch ? '#10b981' : '#f59e0b' }};">{{ $isMatch ? '±$0.00' : ($diff>=0?'+':'').'$'.number_format($diff, 2) }}</div>
            </div>
        </div>

        <div class="eod-sc" style="border-left: 4px solid #8b5cf6;">
            <div class="eod-sc-ico" style="background: rgba(139,92,246,0.1);">
                <svg stroke="#8b5cf6"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            </div>
            <div>
                <div class="eod-sc-lbl">Active Methods</div>
                <div class="eod-sc-val" style="color: #8b5cf6;">{{ $txCount }}<span style="font-size: 14px; color: #c4b5fd;"> / {{ count($expectedTotals) }}</span></div>
            </div>
        </div>
    </div>

    {{-- ═══ MAIN LAYOUT ═══ --}}
    <div class="eod-layout">

        {{-- LEFT: TABLE --}}
        <div class="eod-card">
            <div class="eod-ch">
                <div class="eod-ch-bar"></div>
                <span class="eod-ch-title">Payment Reconciliation</span>
                <span class="eod-ch-date">{{ \Carbon\Carbon::parse($date)->format('M d, Y') }}</span>
            </div>
            <div style="overflow-x:auto;">
                <table class="eod-tbl">
                    <thead>
                        <tr>
                            <th>Payment Type</th>
                            <th>Expected Total</th>
                            <th>Actual (Counted)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($expectedTotals as $key => $expected)
                        @php
                            $dot    = $dots[strtolower($key)] ?? 'd-default';
                            $actual = floatval($actualTotals[$key] ?? 0);
                            $rowOk  = $actual > 0 && round($actual,2)==round((float)$expected,2);
                            $rowBad = $actual > 0 && !$rowOk;
                        @endphp
                        <tr>
                            <td>
                                <div class="eod-method">
                                    <div class="eod-dot {{ $dot }}"></div>
                                    <span class="eod-mname">{{ str_replace('_', ' ', $key) }}</span>
                                    @if($rowOk)<span class="eod-badge ok">✓ Match</span>
                                    @elseif($rowBad)<span class="eod-badge diff">⚠ Diff</span>@endif
                                </div>
                            </td>
                            <td><div class="eod-ev {{ $expected > 0 ? 'pos' : 'nil' }}">${{ number_format($expected, 2) }}</div></td>
                            <td class="eod-ic">
                                <input type="number" wire:model.live="actualTotals.{{ $key }}"
                                    {{ $isClosed ? 'disabled' : '' }} placeholder="0.00" step="0.01" class="eod-in">
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    @if(auth()->user()->hasRole('Superadmin') || $isClosed)
                    <tfoot>
                        <tr>
                            <td>Day Summary</td>
                            <td class="eod-fe">${{ number_format($totalExpected, 2) }}</td>
                            <td class="eod-fc {{ $isMatch ? 'ok' : 'bad' }}">${{ number_format($totalActual, 2) }}</td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>

        {{-- RIGHT: SIDE PANEL --}}
        @if(auth()->user()->hasRole('Superadmin') || $isClosed)
        <div class="eod-side">

            <div class="eod-card">
                <div class="eod-sh"><div class="eod-sd bg-primary-500"></div>Financial Summary</div>
                <div class="eod-sb">
                    <div class="eod-sr">
                        <span class="eod-sl">Expected</span>
                        <span class="eod-sv text-primary-600 dark:text-primary-400">${{ number_format($totalExpected, 2) }}</span>
                    </div>
                    <div class="eod-sr">
                        <span class="eod-sl">Counted</span>
                        <span class="eod-sv" style="color: {{ $isMatch ? '#10b981' : '#ef4444' }};">${{ number_format($totalActual, 2) }}</span>
                    </div>
                    <div class="eod-vb {{ $isMatch ? 'ok' : 'bad' }}">
                        <div class="eod-vt">{{ $isMatch ? '✓ Balanced' : '⚠ Variance Detected' }}</div>
                        <div class="eod-vn">{{ $isMatch ? '$0.00' : ($diff>=0?'+':'').'$'.number_format($diff, 2) }}</div>
                    </div>
                </div>
            </div>

            <div class="eod-card">
                <div class="eod-sh"><div class="eod-sd bg-success-500"></div>Method Breakdown</div>
                <div class="eod-sb">
                    @foreach($expectedTotals as $key => $expected)
                    @php 
                        $actual = floatval($actualTotals[$key] ?? 0); 
                        $ok = round($actual,2)==round((float)$expected,2); 
                        $dot = $dots[strtolower($key)] ?? 'd-default'; 
                    @endphp
                    <div class="eod-br">
                        <span class="eod-bk"><span class="eod-dot {{ $dot }}"></span>{{ str_replace('_', ' ', $key) }}</span>
                        <span class="eod-bv" style="color: {{ $actual>0 ? ($ok ? '#10b981' : '#ef4444') : '#9ca3af' }}">${{ number_format($actual, 2) }}</span>
                    </div>
                    @endforeach
                </div>
            </div>

        </div>
        @endif
    </div>

    {{-- ═══ ACTIONS BAR ═══ --}}
    <div class="eod-actions">
        <div class="eod-al">
            <button class="eod-btn" onclick="window.print()">
                <svg viewBox="0 0 24 24"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                Print Slip
            </button>
            <button class="eod-btn">
                <svg viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                Email Report
            </button>
        </div>

        <div style="display:flex; align-items:center; gap:16px; flex-wrap:wrap;">
            @if(!$isClosed && $totalActual > 0)
                @if($isMatch)
                <span class="eod-spill ok"><span class="eod-spill-dot"></span>Balanced — Ready to Close</span>
                @else
                <span class="eod-spill bad"><span class="eod-spill-dot"></span>Variance: {{ ($diff>=0?'+':'').'$'.number_format($diff, 2) }}</span>
                @endif
            @endif

            @if(!$isClosed)
            <button wire:click="mountAction('post_closing')" class="eod-btn eod-lock">
                <svg viewBox="0 0 24 24" style="width:16px;height:16px;stroke-width:2.5;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                Post Closing &amp; Lock Day
            </button>
            @endif
        </div>
    </div>

</div>
</x-filament-panels::page>