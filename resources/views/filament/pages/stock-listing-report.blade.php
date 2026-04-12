<x-filament-panels::page>
<div class="space-y-6">

    {{-- ── HERO HEADER ────────────────────────────────────────────────────── --}}
    <div style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f2744 100%); border-radius: 20px; padding: 28px 32px; position: relative; overflow: hidden;">
        <div style="position:absolute;top:-40px;right:-40px;width:200px;height:200px;border-radius:50%;background:rgba(99,179,237,0.08);pointer-events:none;"></div>
        <div style="position:absolute;bottom:-60px;right:80px;width:140px;height:140px;border-radius:50%;background:rgba(167,139,250,0.06);pointer-events:none;"></div>

        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;position:relative;">
            <div style="display:flex;align-items:center;gap:16px;">
                <div style="background:linear-gradient(135deg,#3b82f6,#6366f1);border-radius:14px;padding:14px;box-shadow:0 8px 24px rgba(99,102,241,0.4);">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="white" style="width:28px;height:28px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                    </svg>
                </div>
                <div>
                    <h1 style="color:white;font-size:22px;font-weight:900;letter-spacing:-0.5px;margin:0;">Stock Listing Report</h1>
                    <p style="color:#94a3b8;font-size:13px;margin:4px 0 0;font-weight:400;">Filter, preview and export your full inventory data to CSV</p>
                </div>
            </div>

            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">

                {{-- PREVIEW SLIDE-OVER --}}
                <x-filament::modal id="stock-preview-modal" width="screen" slide-over>
                    <x-slot name="trigger">
                        <button type="button"
                            style="display:inline-flex;align-items:center;gap:8px;padding:11px 20px;background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.2);border-radius:10px;color:white;font-size:13px;font-weight:700;cursor:pointer;backdrop-filter:blur(8px);">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:16px;height:16px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            PREVIEW DATA
                        </button>
                    </x-slot>

                    <x-slot name="heading">
                        <span style="display:flex;align-items:center;gap:10px;font-size:16px;font-weight:800;">
                            <span style="background:linear-gradient(135deg,#3b82f6,#6366f1);border-radius:6px;padding:4px 10px;font-size:11px;color:white;font-weight:800;letter-spacing:.06em;">LIVE PREVIEW</span>
                            Stock Listing &mdash; {{ count($this->selectedFields) }} Column(s) Selected
                        </span>
                    </x-slot>

                    <div class="py-4 space-y-4">
                        @php $s = $this->getStockSummary(); @endphp

                        {{-- mini cards inside preview --}}
                        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;">
                            @foreach([
                                ['Total',     number_format($s['total']),              '#64748b','#f8fafc','#e2e8f0'],
                                ['In Stock',  number_format($s['in_stock']),            '#16a34a','#f0fdf4','#bbf7d0'],
                                ['Sold',      number_format($s['sold']),               '#dc2626','#fef2f2','#fecaca'],
                                ['On Hold',   number_format($s['on_hold']),             '#d97706','#fffbeb','#fde68a'],
                                ['Retail $',  '$'.number_format($s['retail_val'],0),   '#2563eb','#eff6ff','#bfdbfe'],
                                ['Cost $',    '$'.number_format($s['cost_val'],0),     '#7c3aed','#f5f3ff','#ddd6fe'],
                            ] as [$lbl,$val,$color,$bg,$border])
                            <div style="background:{{ $bg }};border:1px solid {{ $border }};border-radius:10px;padding:10px 14px;display:flex;align-items:center;justify-content:space-between;">
                                <span style="font-size:10px;font-weight:800;color:{{ $color }};text-transform:uppercase;letter-spacing:.06em;">{{ $lbl }}</span>
                                <span style="font-size:16px;font-weight:900;color:{{ $color }};">{{ $val }}</span>
                            </div>
                            @endforeach
                        </div>

                        <div style="border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;background:white;">
                            {{ $this->table }}
                        </div>
                    </div>
                </x-filament::modal>

                {{-- DOWNLOAD CSV --}}
                <button type="button"
                    wire:click="exportReport"
                    wire:loading.attr="disabled"
                    style="display:inline-flex;align-items:center;gap:8px;padding:11px 22px;background:linear-gradient(135deg,#10b981,#059669);border:none;border-radius:10px;color:white;font-size:13px;font-weight:800;cursor:pointer;box-shadow:0 4px 16px rgba(16,185,129,0.45);">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width:16px;height:16px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    <span wire:loading.remove wire:target="exportReport">DOWNLOAD CSV</span>
                    <span wire:loading wire:target="exportReport">Exporting...</span>
                </button>
            </div>
        </div>
    </div>

    {{-- ── SUMMARY CARDS ────────────────────────────────────────────────────── --}}
    @php $summary = $this->getStockSummary(); @endphp
    <div style="display:grid;grid-template-columns:repeat(6,1fr);gap:12px;">
        @foreach([
            ['TOTAL',         number_format($summary['total']),              '#64748b','#f8fafc','#e2e8f0', 'M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5'],
            ['IN STOCK',      number_format($summary['in_stock']),            '#16a34a','#f0fdf4','#bbf7d0', 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
            ['SOLD',          number_format($summary['sold']),               '#dc2626','#fef2f2','#fecaca', 'M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
            ['ON HOLD',       number_format($summary['on_hold']),             '#d97706','#fffbeb','#fde68a', 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126z'],
            ['RETAIL VAL',    '$'.number_format($summary['retail_val'],0),   '#2563eb','#eff6ff','#bfdbfe', 'M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
            ['COST VAL',      '$'.number_format($summary['cost_val'],0),     '#7c3aed','#f5f3ff','#ddd6fe', 'M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z'],
        ] as [$label, $value, $color, $bg, $border, $icon])
        <div style="background:{{ $bg }};border:1px solid {{ $border }};border-radius:14px;padding:14px 16px;display:flex;align-items:center;gap:10px;box-shadow:0 1px 6px {{ $color }}14;">
            <div style="background:{{ $color }}18;border-radius:9px;padding:7px;flex-shrink:0;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="{{ $color }}" style="width:18px;height:18px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" />
                </svg>
            </div>
            <div style="min-width:0;">
                <div style="font-size:8px;font-weight:800;color:{{ $color }};text-transform:uppercase;letter-spacing:.08em;white-space:nowrap;">{{ $label }}</div>
                <div style="font-size:18px;font-weight:900;color:{{ $color }};line-height:1.2;margin-top:1px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $value }}</div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- ── FILTERS CARD ─────────────────────────────────────────────────────── --}}
    <div style="background:white;border:1px solid #e2e8f0;border-radius:18px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,0.05);" class="dark:bg-gray-900 dark:border-gray-800">

        {{-- Card Header --}}
        <div style="background:linear-gradient(135deg,#f8fafc,#f1f5f9);border-bottom:1px solid #e2e8f0;padding:14px 22px;display:flex;align-items:center;gap:10px;" class="dark:bg-gray-800/60 dark:border-gray-700">
            <div style="background:linear-gradient(135deg,#6366f1,#8b5cf6);border-radius:8px;padding:6px;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="white" style="width:15px;height:15px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
                </svg>
            </div>
            <div>
                <h3 style="font-size:13px;font-weight:800;color:#1e293b;margin:0;" class="dark:text-white">Filters &amp; Column Builder</h3>
                <p style="font-size:11px;color:#64748b;margin:0;" class="dark:text-gray-400">Narrow your inventory then pick which columns to export</p>
            </div>
        </div>

        <div style="padding:18px 22px;">
            {{ $this->form }}
        </div>
    </div>

    {{-- ── HOW IT WORKS ──────────────────────────────────────────────────────── --}}
    <div style="background:linear-gradient(135deg,#eff6ff,#f0f9ff);border:1px solid #bfdbfe;border-radius:14px;padding:14px 20px;display:flex;align-items:center;gap:14px;">
        <div style="background:linear-gradient(135deg,#3b82f6,#0ea5e9);border-radius:10px;padding:9px;flex-shrink:0;box-shadow:0 4px 12px rgba(59,130,246,0.3);">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="white" style="width:17px;height:17px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
            </svg>
        </div>
        <div>
            <h4 style="font-size:12px;font-weight:800;color:#1e40af;margin:0 0 3px;">How to use this report</h4>
            <p style="font-size:11px;color:#1d4ed8;margin:0;line-height:1.6;">
                <strong>1.</strong> Apply filters to narrow your stock &nbsp;→&nbsp;
                <strong>2.</strong> Check the columns you need &nbsp;→&nbsp;
                <strong>3.</strong> Click <span style="background:#1d4ed8;color:white;padding:1px 7px;border-radius:4px;font-size:10px;font-weight:700;">PREVIEW DATA</span> to verify &nbsp;→&nbsp;
                <strong>4.</strong> Hit <span style="background:#059669;color:white;padding:1px 7px;border-radius:4px;font-size:10px;font-weight:700;">DOWNLOAD CSV</span> to export
            </p>
        </div>
    </div>

</div>
</x-filament-panels::page>