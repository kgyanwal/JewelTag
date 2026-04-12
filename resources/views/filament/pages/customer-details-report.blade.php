<x-filament-panels::page>
<div class="space-y-6">

    {{-- ── HERO HEADER ──────────────────────────────────────────────────────── --}}
    <div style="background:linear-gradient(135deg,#0f172a 0%,#1e1b4b 50%,#0f172a 100%);border-radius:20px;padding:28px 32px;position:relative;overflow:hidden;">
        <div style="position:absolute;top:-50px;right:-50px;width:220px;height:220px;border-radius:50%;background:rgba(139,92,246,0.1);pointer-events:none;"></div>
        <div style="position:absolute;bottom:-40px;left:40%;width:160px;height:160px;border-radius:50%;background:rgba(99,102,241,0.07);pointer-events:none;"></div>
        <div style="position:absolute;top:20px;right:200px;width:80px;height:80px;border-radius:50%;background:rgba(167,139,250,0.06);pointer-events:none;"></div>

        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;position:relative;">
            <div style="display:flex;align-items:center;gap:16px;">
                <div style="background:linear-gradient(135deg,#8b5cf6,#6366f1);border-radius:14px;padding:14px;box-shadow:0 8px 24px rgba(139,92,246,0.45);">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="white" style="width:28px;height:28px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                </div>
                <div>
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                        <h1 style="color:white;font-size:22px;font-weight:900;letter-spacing:-0.5px;margin:0;">Customer Report Engine</h1>
                        <span style="background:rgba(139,92,246,0.3);border:1px solid rgba(139,92,246,0.5);color:#c4b5fd;font-size:10px;font-weight:800;padding:2px 8px;border-radius:20px;letter-spacing:.06em;">ANALYTICS</span>
                    </div>
                    <p style="color:#94a3b8;font-size:13px;margin:0;font-weight:400;">Extract precisely mapped customer data to CSV or Print</p>
                </div>
            </div>

            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">

                {{-- PREVIEW SLIDE-OVER --}}
                <x-filament::modal id="customer-preview-modal" width="screen" slide-over>
                    <x-slot name="trigger">
                        <button type="button"
                            style="display:inline-flex;align-items:center;gap:8px;padding:11px 20px;background:rgba(139,92,246,0.15);border:1px solid rgba(139,92,246,0.4);border-radius:10px;color:#c4b5fd;font-size:13px;font-weight:700;cursor:pointer;backdrop-filter:blur(8px);transition:all .2s;"
                            onmouseover="this.style.background='rgba(139,92,246,0.25)'"
                            onmouseout="this.style.background='rgba(139,92,246,0.15)'">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:16px;height:16px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            PREVIEW DATA
                        </button>
                    </x-slot>

                    <x-slot name="heading">
                        <span style="display:flex;align-items:center;gap:10px;font-size:16px;font-weight:800;">
                            <span style="background:linear-gradient(135deg,#8b5cf6,#6366f1);border-radius:6px;padding:4px 10px;font-size:11px;color:white;font-weight:800;letter-spacing:.06em;">LIVE PREVIEW</span>
                            Customer Report &mdash; {{ count($this->selectedFields) }} Field(s) Selected
                        </span>
                    </x-slot>

                    <div class="py-4 space-y-4">
                        <div style="background:linear-gradient(135deg,#f5f3ff,#ede9fe);border:1px solid #ddd6fe;border-radius:12px;padding:14px 18px;display:flex;align-items:center;gap:12px;">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="#7c3aed" style="width:18px;height:18px;flex-shrink:0;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                            </svg>
                            <p style="font-size:12px;color:#5b21b6;margin:0;font-weight:500;">
                                This is a live preview of your report with current filters applied. The table below shows exactly what will be exported to CSV.
                            </p>
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
                    style="display:inline-flex;align-items:center;gap:8px;padding:11px 22px;background:linear-gradient(135deg,#10b981,#059669);border:none;border-radius:10px;color:white;font-size:13px;font-weight:800;cursor:pointer;box-shadow:0 4px 16px rgba(16,185,129,0.45);transition:all .2s;"
                    onmouseover="this.style.transform='translateY(-1px)';this.style.boxShadow='0 6px 20px rgba(16,185,129,0.55)'"
                    onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='0 4px 16px rgba(16,185,129,0.45)'">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width:16px;height:16px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    <span wire:loading.remove wire:target="exportReport">DOWNLOAD CSV</span>
                    <span wire:loading wire:target="exportReport">Exporting...</span>
                </button>
            </div>
        </div>
    </div>

    {{-- ── STAT PILLS ──────────────────────────────────────────────────────── --}}
    @php
        $totalCustomers = \App\Models\Customer::query()
            ->when($this->filterData['date_from'] ?? null, fn($q,$d) => $q->whereDate('created_at','>=',$d))
            ->when($this->filterData['date_to'] ?? null,   fn($q,$d) => $q->whereDate('created_at','<=',$d))
            ->when($this->filterData['postcode'] ?? null,  fn($q,$v) => $q->where('postcode','like',"%{$v}%"))
            ->count();
        $activeCustomers = \App\Models\Customer::where('is_active', true)
            ->when($this->filterData['date_from'] ?? null, fn($q,$d) => $q->whereDate('created_at','>=',$d))
            ->when($this->filterData['date_to'] ?? null,   fn($q,$d) => $q->whereDate('created_at','<=',$d))
            ->count();
        $thisMonth = \App\Models\Customer::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();
        $fieldsSelected = count($this->selectedFields);
    @endphp

    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;">
        @foreach([
            ['MATCHED CUSTOMERS', number_format($totalCustomers),  '#8b5cf6','#f5f3ff','#ddd6fe', 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z'],
            ['ACTIVE ACCOUNTS',   number_format($activeCustomers), '#0ea5e9','#f0f9ff','#bae6fd', 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
            ['ADDED THIS MONTH',  number_format($thisMonth),       '#10b981','#f0fdf4','#bbf7d0', 'M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z'],
            ['COLUMNS SELECTED',  $fieldsSelected . ' / ' . count($this->getColumnOptions()), '#f59e0b','#fffbeb','#fde68a', 'M9 4.5v15m6-15v15m-10.875 0h15.75c.621 0 1.125-.504 1.125-1.125V5.625c0-.621-.504-1.125-1.125-1.125H4.125C3.504 4.5 3 5.004 3 5.625v12.75c0 .621.504 1.125 1.125 1.125z'],
        ] as [$label, $value, $color, $bg, $border, $icon])
        <div style="background:{{ $bg }};border:1px solid {{ $border }};border-radius:14px;padding:16px 18px;display:flex;align-items:center;gap:12px;box-shadow:0 1px 8px {{ $color }}14;">
            <div style="background:{{ $color }}1a;border-radius:10px;padding:9px;flex-shrink:0;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="{{ $color }}" style="width:20px;height:20px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" />
                </svg>
            </div>
            <div>
                <div style="font-size:9px;font-weight:800;color:{{ $color }};text-transform:uppercase;letter-spacing:.08em;">{{ $label }}</div>
                <div style="font-size:22px;font-weight:900;color:{{ $color }};line-height:1.15;margin-top:2px;">{{ $value }}</div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- ── FILTER FORM CARD ─────────────────────────────────────────────────── --}}
    <div style="background:white;border:1px solid #e2e8f0;border-radius:18px;overflow:hidden;box-shadow:0 2px 16px rgba(0,0,0,0.05);" class="dark:bg-gray-900 dark:border-gray-800">

        {{-- Card Header with colored left accent --}}
        <div style="border-left:4px solid #8b5cf6;background:linear-gradient(135deg,#faf5ff,#f5f3ff);border-bottom:1px solid #ede9fe;padding:14px 22px;display:flex;align-items:center;justify-content:space-between;" class="dark:bg-gray-800/60 dark:border-gray-700">
            <div style="display:flex;align-items:center;gap:10px;">
                <div style="background:linear-gradient(135deg,#8b5cf6,#6366f1);border-radius:8px;padding:6px;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="white" style="width:15px;height:15px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
                    </svg>
                </div>
                <div>
                    <h3 style="font-size:13px;font-weight:800;color:#4c1d95;margin:0;" class="dark:text-purple-300">Report Configuration</h3>
                    <p style="font-size:11px;color:#7c3aed;margin:0;" class="dark:text-purple-400">Set date ranges, filters and select output columns</p>
                </div>
            </div>

            <div style="background:#8b5cf620;border:1px solid #ddd6fe;border-radius:8px;padding:5px 12px;font-size:11px;font-weight:700;color:#7c3aed;">
                {{ $fieldsSelected }} field(s) active
            </div>
        </div>

        <div style="padding:20px 22px;">
            {{ $this->form }}
        </div>
    </div>

    {{-- ── HOW IT WORKS ──────────────────────────────────────────────────────── --}}
    <div style="background:linear-gradient(135deg,#faf5ff,#f0f9ff);border:1px solid #ddd6fe;border-radius:14px;padding:16px 22px;display:flex;align-items:flex-start;gap:16px;">
        <div style="background:linear-gradient(135deg,#8b5cf6,#6366f1);border-radius:10px;padding:10px;flex-shrink:0;box-shadow:0 4px 12px rgba(139,92,246,0.3);">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="white" style="width:18px;height:18px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
            </svg>
        </div>
        <div style="flex:1;">
            <h4 style="font-size:13px;font-weight:800;color:#4c1d95;margin:0 0 6px;">How to build your report</h4>
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;">
                @foreach([
                    ['1','Set Filters','Choose date range and postcode to narrow results','#8b5cf6','#f5f3ff'],
                    ['2','Pick Columns','Tick the data points you need in the output','#0ea5e9','#f0f9ff'],
                    ['3','Preview Data','Click PREVIEW DATA to verify the layout first','#f59e0b','#fffbeb'],
                    ['4','Download','Hit DOWNLOAD CSV to export your filtered report','#10b981','#f0fdf4'],
                ] as [$step,$title,$desc,$color,$bg])
                <div style="background:{{ $bg }};border-radius:10px;padding:10px 12px;">
                    <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;">
                        <span style="background:{{ $color }};color:white;font-size:10px;font-weight:900;width:18px;height:18px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">{{ $step }}</span>
                        <span style="font-size:11px;font-weight:800;color:{{ $color }};">{{ $title }}</span>
                    </div>
                    <p style="font-size:10px;color:#64748b;margin:0;line-height:1.4;">{{ $desc }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </div>

</div>
</x-filament-panels::page>