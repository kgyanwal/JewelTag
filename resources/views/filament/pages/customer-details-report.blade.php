<x-filament-panels::page>
<div class="space-y-5">

    {{-- ── HERO HEADER ──────────────────────────────────────────────────────── --}}
    <div style="background:linear-gradient(135deg,#0f172a 0%,#1e1b4b 50%,#0f172a 100%);border-radius:20px;padding:24px 28px;position:relative;overflow:hidden;">
        <div style="position:absolute;top:-50px;right:-50px;width:200px;height:200px;border-radius:50%;background:rgba(139,92,246,0.1);pointer-events:none;"></div>
        <div style="position:absolute;bottom:-40px;left:40%;width:140px;height:140px;border-radius:50%;background:rgba(99,102,241,0.07);pointer-events:none;"></div>

        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;position:relative;">
            <div style="display:flex;align-items:center;gap:14px;">
                <div style="background:linear-gradient(135deg,#8b5cf6,#6366f1);border-radius:12px;padding:12px;box-shadow:0 8px 24px rgba(139,92,246,0.45);">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="white" style="width:26px;height:26px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                </div>
                <div>
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:3px;">
                        <h1 style="color:white;font-size:20px;font-weight:900;letter-spacing:-0.5px;margin:0;">Customer Report Builder</h1>
                        <span style="background:rgba(139,92,246,0.3);border:1px solid rgba(139,92,246,0.5);color:#c4b5fd;font-size:10px;font-weight:800;padding:2px 8px;border-radius:20px;letter-spacing:.06em;">ANALYTICS</span>
                    </div>
                    <p style="color:#94a3b8;font-size:12px;margin:0;">Filter → Pick Columns → Preview → Export CSV</p>
                </div>
            </div>

            {{-- DOWNLOAD BUTTON in header --}}
            <button type="button"
                wire:click="exportReport"
                wire:loading.attr="disabled"
                style="display:inline-flex;align-items:center;gap:8px;padding:10px 22px;background:linear-gradient(135deg,#10b981,#059669);border:none;border-radius:10px;color:white;font-size:13px;font-weight:800;cursor:pointer;box-shadow:0 4px 16px rgba(16,185,129,0.45);transition:all .2s;"
                onmouseover="this.style.transform='translateY(-1px)'"
                onmouseout="this.style.transform='translateY(0)'">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width:15px;height:15px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                <span wire:loading.remove wire:target="exportReport">DOWNLOAD CSV</span>
                <span wire:loading wire:target="exportReport">Exporting...</span>
            </button>
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

    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;">
        @foreach([
            ['MATCHED',   number_format($totalCustomers),  '#8b5cf6','#f5f3ff','#ddd6fe', 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z'],
            ['ACTIVE',    number_format($activeCustomers), '#0ea5e9','#f0f9ff','#bae6fd', 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
            ['THIS MONTH',number_format($thisMonth),       '#10b981','#f0fdf4','#bbf7d0', 'M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z'],
            ['COLUMNS',   $fieldsSelected . ' / ' . count($this->getColumnOptions()), '#f59e0b','#fffbeb','#fde68a', 'M9 4.5v15m6-15v15m-10.875 0h15.75c.621 0 1.125-.504 1.125-1.125V5.625c0-.621-.504-1.125-1.125-1.125H4.125C3.504 4.5 3 5.004 3 5.625v12.75c0 .621.504 1.125 1.125 1.125z'],
        ] as [$label, $value, $color, $bg, $border, $icon])
        <div style="background:{{ $bg }};border:1px solid {{ $border }};border-radius:12px;padding:14px 16px;display:flex;align-items:center;gap:10px;">
            <div style="background:{{ $color }}1a;border-radius:8px;padding:8px;flex-shrink:0;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="{{ $color }}" style="width:18px;height:18px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" />
                </svg>
            </div>
            <div>
                <div style="font-size:9px;font-weight:800;color:{{ $color }};text-transform:uppercase;letter-spacing:.08em;">{{ $label }}</div>
                <div style="font-size:20px;font-weight:900;color:{{ $color }};line-height:1.2;margin-top:1px;">{{ $value }}</div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- ── MAIN LAYOUT: LEFT = FILTERS, RIGHT = PREVIEW ─────────────────────── --}}
    <div style="display:grid;grid-template-columns:380px 1fr;gap:16px;align-items:start;">

        {{-- ── LEFT: FILTER + COLUMN PICKER ──────────────────────────────────── --}}
        <div style="position:sticky;top:20px;display:flex;flex-direction:column;gap:12px;">

            {{-- Step indicator --}}
            <div style="display:flex;gap:6px;">
                @foreach([['1','Filter','#8b5cf6'],['2','Columns','#0ea5e9'],['3','Preview','#f59e0b'],['4','Export','#10b981']] as [$n,$t,$c])
                <div style="flex:1;background:{{ $c }}15;border:1px solid {{ $c }}40;border-radius:8px;padding:6px 8px;text-align:center;">
                    <div style="font-size:10px;font-weight:900;color:{{ $c }};">{{ $n }}</div>
                    <div style="font-size:9px;font-weight:700;color:{{ $c }};opacity:.8;">{{ $t }}</div>
                </div>
                @endforeach
            </div>

            {{-- Filter Card --}}
            <div style="background:white;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,0.05);">
                <div style="border-left:4px solid #8b5cf6;background:linear-gradient(135deg,#faf5ff,#f5f3ff);border-bottom:1px solid #ede9fe;padding:12px 18px;display:flex;align-items:center;gap:8px;">
                    <div style="background:linear-gradient(135deg,#8b5cf6,#6366f1);border-radius:7px;padding:5px;">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="white" style="width:13px;height:13px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
                        </svg>
                    </div>
                    <div>
                        <div style="font-size:12px;font-weight:800;color:#4c1d95;">Step 1 & 2 — Filters & Columns</div>
                        <div style="font-size:10px;color:#7c3aed;">{{ $fieldsSelected }} column(s) selected</div>
                    </div>
                </div>
                <div style="padding:16px 18px;">
                    {{ $this->form }}
                </div>
            </div>

            {{-- Quick tip --}}
            <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:10px 14px;display:flex;gap:8px;align-items:flex-start;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="#10b981" style="width:15px;height:15px;flex-shrink:0;margin-top:1px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p style="font-size:11px;color:#065f46;margin:0;line-height:1.5;">
                    The preview table updates live as you change filters. When it looks right, click <strong>DOWNLOAD CSV</strong> at the top.
                </p>
            </div>
        </div>

        {{-- ── RIGHT: LIVE PREVIEW TABLE ──────────────────────────────────────── --}}
        <div style="background:white;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,0.05);">

            {{-- Preview header --}}
            <div style="border-left:4px solid #0ea5e9;background:linear-gradient(135deg,#f0f9ff,#e0f2fe);border-bottom:1px solid #bae6fd;padding:12px 18px;display:flex;align-items:center;justify-content:space-between;">
                <div style="display:flex;align-items:center;gap:8px;">
                    <div style="background:linear-gradient(135deg,#0ea5e9,#0284c7);border-radius:7px;padding:5px;">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="white" style="width:13px;height:13px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <div>
                        <div style="font-size:12px;font-weight:800;color:#0c4a6e;">Step 3 — Live Data Preview</div>
                        <div style="font-size:10px;color:#0369a1;">Updates instantly as you change filters or columns</div>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:6px;">
                    <div style="width:7px;height:7px;border-radius:50%;background:#10b981;animation:pulse 2s infinite;"></div>
                    <span style="font-size:11px;font-weight:700;color:#0369a1;">LIVE</span>
                </div>
            </div>

            @if(count($this->selectedFields) === 0)
            <div style="padding:48px;text-align:center;">
                <div style="background:#f1f5f9;border-radius:50%;width:56px;height:56px;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#94a3b8" style="width:24px;height:24px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 4.5v15m6-15v15m-10.875 0h15.75c.621 0 1.125-.504 1.125-1.125V5.625c0-.621-.504-1.125-1.125-1.125H4.125C3.504 4.5 3 5.004 3 5.625v12.75c0 .621.504 1.125 1.125 1.125z" />
                    </svg>
                </div>
                <p style="font-size:13px;font-weight:700;color:#475569;margin:0 0 4px;">No columns selected</p>
                <p style="font-size:12px;color:#94a3b8;margin:0;">Tick at least one column on the left to see the preview.</p>
            </div>
            @else
            <div style="overflow-x:auto;">
                {{ $this->table }}
            </div>
            @endif
        </div>
    </div>

</div>

<style>
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.4; }
}
</style>
</x-filament-panels::page>