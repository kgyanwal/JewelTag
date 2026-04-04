<x-filament-panels::page>

<style>
    /* Kill Filament teal background */
    .fi-body { background: #f0f0eb !important; }
    .dark .fi-body { background: #0a0a0a !important; }

    .eod { font-family: ui-monospace, 'JetBrains Mono', monospace; width: 100%; }

    /* ── TOP BAR ── */
    .eod-bar {
        display: flex; align-items: center; justify-content: space-between;
        padding: 14px 20px; background: #0f0f0f;
        border-radius: 10px; margin-bottom: 20px;
    }
    .eod-bar-left { display: flex; align-items: center; gap: 32px; }
    .eod-section-label {
        font-size: 10px; letter-spacing: .14em; text-transform: uppercase;
        color: #555; margin-bottom: 4px;
    }
    .eod-date-static {
        font-size: 15px; font-weight: 700; color: #fff; letter-spacing: .02em;
    }

    /* Date picker — keep native input 100% functional, styled display on top */
    .eod-datepicker {
    position: relative; display: inline-block;
    cursor: default;
}
.eod-datepicker-face {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 7px 14px; border: 1px solid #2d2d2d; border-radius: 7px;
    background: #1a1a1a; cursor: default; transition: border-color .15s;
    pointer-events: none;
}
.eod-datepicker input[type="date"] {
    position: absolute; inset: 0; width: 100%; height: 100%;
    opacity: 0; cursor: default !important; z-index: 10;
    border: none; background: transparent;
    -webkit-appearance: none;
}
    
    .eod-datepicker:hover .eod-datepicker-face { border-color: #484848; }
    .eod-datepicker-face svg { width: 14px; height: 14px; stroke: #888; flex-shrink:0; fill:none; }
    .eod-datepicker-face span { font-size: 13px; color: #e0e0e0; font-weight: 600; letter-spacing: .02em; }
    .eod-datepicker input[type="date"] {
        position: absolute; inset: 0; width: 100%; height: 100%;
        opacity: 0; cursor: pointer; z-index: 10;
        border: none; background: transparent;
    }

    /* Status pills */
    .eod-pill {
        display: inline-flex; align-items: center; gap: 7px;
        padding: 6px 14px; border-radius: 99px;
        font-size: 10px; font-weight: 800; letter-spacing: .12em; text-transform: uppercase;
    }
    .eod-pill.locked { background: #052e16; color: #4ade80; border: 1px solid #15803d; }
    .eod-pill.open   { background: #431407; color: #fb923c; border: 1px solid #9a3412; }
    .eod-pill-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
    .eod-pill.locked .eod-pill-dot { animation: pulse 2s ease-in-out infinite; }
    @keyframes pulse { 0%,100%{opacity:1}50%{opacity:.2} }

    /* ── TWO-COL LAYOUT ── */
    .eod-layout { display: grid; grid-template-columns: 1fr 300px; gap: 20px; align-items: start; }

    /* ── TABLE CARD ── */
    .eod-table-card { background:#fff; border:1px solid #e4e4e0; border-radius:10px; overflow:hidden; }
    .dark .eod-table-card { background:#111; border-color:#222; }

    .eod-table { width:100%; border-collapse:collapse; }

    .eod-table thead tr { background:#f7f7f4; border-bottom:1px solid #e4e4e0; }
    .dark .eod-table thead tr { background:#181818; border-color:#252525; }
    .eod-table th {
        padding: 11px 20px;
        font-size: 10px; letter-spacing: .12em; text-transform: uppercase;
        font-weight: 800; color: #aaa;
    }
    .dark .eod-table th { color: #555; }
    .eod-table th:not(:first-child) { text-align: right; }
    .eod-table th:first-child { text-align: left; }

    .eod-table tbody tr { border-bottom: 1px solid #f0f0eb; transition: background .1s; }
    .dark .eod-table tbody tr { border-color: #1c1c1c; }
    .eod-table tbody tr:last-child { border-bottom: none; }
    .eod-table tbody tr:hover { background: #fafaf7; }
    .dark .eod-table tbody tr:hover { background: #151515; }

    .eod-table td { padding: 13px 20px; vertical-align: middle; }

    .eod-method-name {
        font-size: 13px; font-weight: 700; letter-spacing: .05em;
        text-transform: uppercase; color: #222;
    }
    .dark .eod-method-name { color: #ddd; }

    .eod-expected-val {
        text-align: right; font-size: 15px; font-weight: 700;
        font-variant-numeric: tabular-nums; letter-spacing: .02em;
    }
    .eod-expected-val.pos { color: #1d4ed8; }
    .eod-expected-val.nil { color: #ccc; }

    .eod-counted-cell { text-align: right; }
    .eod-counted-input {
        width: 130px; text-align: right; font-family: inherit;
        font-size: 14px; font-weight: 700; padding: 7px 11px;
        border: 1px solid #ddd; border-radius: 7px;
        background: #fff; color: #111; outline: none;
        font-variant-numeric: tabular-nums;
        transition: border-color .15s, box-shadow .15s;
    }
    .eod-counted-input:focus { border-color: #1d4ed8; box-shadow: 0 0 0 3px rgba(29,78,216,.12); }
    .eod-counted-input:disabled { background: #f5f5f2; color: #bbb; border-color: #e8e8e4; cursor: not-allowed; }
    .dark .eod-counted-input { background: #1a1a1a; border-color: #2a2a2a; color: #ddd; }
    .dark .eod-counted-input:disabled { background: #0f0f0f; color: #333; border-color: #1a1a1a; }

    /* tfoot */
    .eod-table tfoot tr { background: #f7f7f4; border-top: 2px solid #e4e4e0; }
    .dark .eod-table tfoot tr { background: #181818; border-color: #252525; }
    .eod-table tfoot td {
        padding: 13px 20px;
        font-size: 11px; font-weight: 800; letter-spacing: .1em;
        text-transform: uppercase; color: #888;
    }
    .dark .eod-table tfoot td { color: #555; }
    .eod-tfoot-exp { text-align:right; font-size:16px !important; color:#1d4ed8 !important; font-variant-numeric:tabular-nums; }
    .eod-tfoot-cnt { text-align:right; font-size:16px !important; font-variant-numeric:tabular-nums; }
    .eod-tfoot-match { color:#16a34a !important; }
    .eod-tfoot-miss  { color:#dc2626 !important; }

    /* ── SIDE CARDS ── */
    .eod-side { display:flex; flex-direction:column; gap:14px; }
    .eod-side-card { background:#fff; border:1px solid #e4e4e0; border-radius:10px; overflow:hidden; }
    .dark .eod-side-card { background:#111; border-color:#222; }
    .eod-side-head {
        padding: 10px 16px; font-size: 10px; font-weight: 800;
        letter-spacing: .12em; text-transform: uppercase;
        color: #aaa; background: #f7f7f4; border-bottom: 1px solid #e4e4e0;
    }
    .dark .eod-side-head { background:#181818; border-color:#252525; color:#555; }
    .eod-side-body { padding: 16px; }

    .eod-stat { margin-bottom: 16px; }
    .eod-stat:last-child { margin-bottom: 0; }
    .eod-stat-lbl { font-size: 10px; letter-spacing: .1em; text-transform: uppercase; color: #aaa; margin-bottom: 4px; }
    .eod-stat-num { font-size: 22px; font-weight: 800; font-variant-numeric: tabular-nums; letter-spacing: .02em; color: #111; }
    .dark .eod-stat-num { color: #e2e2e2; }
    .eod-stat-num.blue  { color: #1d4ed8; }
    .eod-stat-num.green { color: #16a34a; }
    .eod-stat-num.red   { color: #dc2626; }

    .eod-row { display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid #f0f0eb; }
    .dark .eod-row { border-color:#1c1c1c; }
    .eod-row:last-child { border-bottom:none; padding-bottom:0; }
    .eod-row-key { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#999; }
    .eod-row-val { font-size:13px; font-weight:800; font-variant-numeric:tabular-nums; }

    /* ── ACTIONS ── */
    .eod-actions { display:flex; align-items:center; justify-content:space-between; margin-top:18px; }
    .eod-action-left { display:flex; gap:10px; }
    .eod-btn {
        display:inline-flex; align-items:center; gap:7px;
        padding:8px 16px; font-family:inherit;
        font-size:11px; font-weight:700; letter-spacing:.08em; text-transform:uppercase;
        border-radius:7px; cursor:pointer; border:1px solid #d8d8d4;
        background:#fff; color:#666; transition:all .15s;
    }
    .eod-btn:hover { background:#f5f5f2; border-color:#bbb; color:#111; }
    .dark .eod-btn { background:#1a1a1a; border-color:#2a2a2a; color:#777; }
    .dark .eod-btn:hover { background:#222; color:#ddd; }
    .eod-btn svg { width:13px; height:13px; flex-shrink:0; fill:none; }
    .eod-btn-lock {
        padding:9px 22px; background:#0f0f0f; color:#fff !important;
        border-color:#0f0f0f; font-size:11px;
    }
    .eod-btn-lock:hover { background:#222 !important; border-color:#222 !important; color:#fff !important; }
    .dark .eod-btn-lock { background:#efefeb; color:#111 !important; border-color:#efefeb; }
    .dark .eod-btn-lock:hover { background:#fff !important; }
</style>

<div class="eod">

    {{-- ── TOP BAR ── --}}
    <div class="eod-bar">
        <div class="eod-bar-left">
            <div>
                <div class="eod-section-label">End-of-day report</div>

                @if(auth()->user()->hasRole('Superadmin'))
                    {{-- Native date input hidden behind styled face --}}
                    <div class="eod-datepicker">
                        <div class="eod-datepicker-face">
                            <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="4" width="18" height="18" rx="2" stroke="currentColor"/>
                                <line x1="16" y1="2" x2="16" y2="6" stroke="currentColor"/>
                                <line x1="8"  y1="2" x2="8"  y2="6" stroke="currentColor"/>
                                <line x1="3"  y1="10" x2="21" y2="10" stroke="currentColor"/>
                            </svg>
                            <span>{{ \Carbon\Carbon::parse($date)->format('M d, Y') }}</span>
                        </div>
                        {{-- wire:model.live keeps Livewire reactivity exactly as original --}}
                        <input
                            type="date"
                            wire:model.live="date"
                            max="{{ now()->format('Y-m-d') }}"
                            value="{{ $date }}"
                        >
                    </div>
                @else
                    <div class="eod-date-static">{{ \Carbon\Carbon::parse($date)->format('M d, Y') }}</div>
                @endif
            </div>
        </div>

        <div>
            @if($isClosed)
                <span class="eod-pill locked">
                    <span class="eod-pill-dot"></span> Record locked
                </span>
            @else
                <span class="eod-pill open">
                    <span class="eod-pill-dot"></span> Open for entry
                </span>
            @endif
        </div>
    </div>

    @php
        $totalExpected = array_sum($expectedTotals);
        $totalActual   = array_sum($actualTotals ?? []);
        $isMatch       = round($totalActual, 2) == round($totalExpected, 2);
        $diff          = $totalActual - $totalExpected;
    @endphp

    {{-- ── MAIN LAYOUT ── --}}
    <div class="eod-layout">

        {{-- LEFT: payment table --}}
        <div class="eod-table-card">
            <table class="eod-table">
                <thead>
                    <tr>
                        <th>Payment type</th>
                        <th>Expected total</th>
                        <th>Actual (counted)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($expectedTotals as $key => $expected)
                    <tr class="{{ $isClosed ? 'opacity-80' : '' }}">
                        <td>
                            <span class="eod-method-name">{{ str_replace('_', ' ', $key) }}</span>
                        </td>
                        <td>
                            <div class="eod-expected-val {{ $expected > 0 ? 'pos' : 'nil' }}">
                                ${{ number_format($expected, 2) }}
                            </div>
                        </td>
                        <td class="eod-counted-cell">
                            <input
                                type="number"
                                wire:model.live="actualTotals.{{ $key }}"
                                {{ $isClosed ? 'disabled' : '' }}
                                placeholder="0.00"
                                step="0.01"
                                class="eod-counted-input"
                            >
                        </td>
                    </tr>
                    @endforeach
                </tbody>

                @if(auth()->user()->hasRole('Superadmin') || $isClosed)
                <tfoot>
                    <tr>
                        <td>Day summary</td>
                        <td class="eod-tfoot-exp">${{ number_format($totalExpected, 2) }}</td>
                        <td class="eod-tfoot-cnt {{ $isMatch ? 'eod-tfoot-match' : 'eod-tfoot-miss' }}">
                            ${{ number_format($totalActual, 2) }}
                        </td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>

        {{-- RIGHT: summary panel --}}
        @if(auth()->user()->hasRole('Superadmin') || $isClosed)
        <div class="eod-side">

            <div class="eod-side-card">
                <div class="eod-side-head">Summary</div>
                <div class="eod-side-body">
                    <div class="eod-stat">
                        <div class="eod-stat-lbl">Expected total</div>
                        <div class="eod-stat-num blue">${{ number_format($totalExpected, 2) }}</div>
                    </div>
                    <div class="eod-stat">
                        <div class="eod-stat-lbl">Counted total</div>
                        <div class="eod-stat-num {{ $isMatch ? 'green' : 'red' }}">${{ number_format($totalActual, 2) }}</div>
                    </div>
                    @if(!$isMatch)
                    <div class="eod-stat">
                        <div class="eod-stat-lbl">Variance</div>
                        <div class="eod-stat-num red">{{ $diff >= 0 ? '+' : '' }}${{ number_format($diff, 2) }}</div>
                    </div>
                    @endif
                </div>
            </div>

            <div class="eod-side-card">
                <div class="eod-side-head">Per-method breakdown</div>
                <div class="eod-side-body">
                    @foreach($expectedTotals as $key => $expected)
                    @php
                        $actual   = $actualTotals[$key] ?? 0;
                        $rowMatch = round((float)$actual, 2) == round((float)$expected, 2);
                    @endphp
                    <div class="eod-row">
                        <span class="eod-row-key">{{ str_replace('_', ' ', $key) }}</span>
                        <span class="eod-row-val" style="color: {{ $rowMatch ? '#16a34a' : '#dc2626' }}">
                            ${{ number_format((float)$actual, 2) }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>

        </div>
        @endif

    </div>

    {{-- ── ACTIONS ── --}}
    <div class="eod-actions">
        <div class="eod-action-left">
            <button class="eod-btn">
                <svg viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 6 2 18 2 18 9"/>
                    <path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/>
                    <rect x="6" y="14" width="12" height="8"/>
                </svg>
                Print slip
            </button>
            <button class="eod-btn">
                <svg viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                    <polyline points="22,6 12,13 2,6"/>
                </svg>
                Email report
            </button>
        </div>

        @if(!$isClosed)
            <button wire:click="mountAction('post_closing')" class="eod-btn eod-btn-lock">
                <svg viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width:12px;height:12px">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                Post closing &amp; lock day
            </button>
        @endif
    </div>

</div>
</x-filament-panels::page>