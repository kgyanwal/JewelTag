<x-filament-panels::page>

    {{-- ═══════════════════════════════════════════════════════════════
         HERO HEADER
    ════════════════════════════════════════════════════════════════ --}}
    <div class="relative overflow-hidden rounded-2xl mb-6"
         style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 40%, #4338ca 70%, #6d28d9 100%);">

        {{-- Decorative circles --}}
        <div class="absolute -top-10 -right-10 w-48 h-48 rounded-full opacity-10"
             style="background: radial-gradient(circle, #a78bfa, transparent);"></div>
        <div class="absolute -bottom-8 -left-8 w-40 h-40 rounded-full opacity-10"
             style="background: radial-gradient(circle, #818cf8, transparent);"></div>

        <div class="relative px-8 py-6 flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center shadow-lg"
                     style="background: rgba(255,255,255,0.15); backdrop-filter: blur(10px);">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-black text-white tracking-tight">Customer Follow-Ups</h1>
                    <p class="text-white text-sm mt-0.5">
                        {{ now()->format('l, F j, Y') }} &nbsp;·&nbsp; Showing next 14 days + overdue
                    </p>
                </div>
            </div>

            {{-- Live clock --}}
            <div class="text-right">
                <div class="text-3xl font-black text-white tabular-nums" id="live-clock">
                    {{ now()->format('h:i A') }}
                </div>
                <div class="text-indigo-300 text-xs uppercase tracking-widest">Local Time</div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════
         STAT CARDS
    ════════════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

        {{-- Overdue --}}
        <div class="rounded-2xl p-5 relative overflow-hidden shadow-sm border"
             style="background: linear-gradient(135deg, #fff1f2, #ffe4e6); border-color: #fecdd3;">
            <div class="absolute top-3 right-3 w-10 h-10 rounded-full flex items-center justify-center"
                 style="background: rgba(239,68,68,0.12);">
                <svg class="w-5 h-5" style="color:#ef4444" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <div class="text-4xl font-black" style="color:#dc2626">{{ $this->overdueCount }}</div>
            <div class="text-sm font-bold mt-1" style="color:#991b1b">Overdue</div>
            <div class="text-xs mt-0.5" style="color:#b91c1c">Need immediate attention</div>
        </div>

        {{-- Today --}}
        <div class="rounded-2xl p-5 relative overflow-hidden shadow-sm border"
             style="background: linear-gradient(135deg, #fffbeb, #fef3c7); border-color: #fde68a;">
            <div class="absolute top-3 right-3 w-10 h-10 rounded-full flex items-center justify-center"
                 style="background: rgba(245,158,11,0.12);">
                <svg class="w-5 h-5" style="color:#f59e0b" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="text-4xl font-black" style="color:#d97706">{{ $this->todayCount }}</div>
            <div class="text-sm font-bold mt-1" style="color:#92400e">Due Today</div>
            <div class="text-xs mt-0.5" style="color:#b45309">Call or SMS now</div>
        </div>

        {{-- This Week --}}
        <div class="rounded-2xl p-5 relative overflow-hidden shadow-sm border"
             style="background: linear-gradient(135deg, #eff6ff, #dbeafe); border-color: #bfdbfe;">
            <div class="absolute top-3 right-3 w-10 h-10 rounded-full flex items-center justify-center"
                 style="background: rgba(59,130,246,0.12);">
                <svg class="w-5 h-5" style="color:#3b82f6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <div class="text-4xl font-black" style="color:#2563eb">{{ $this->thisWeekCount }}</div>
            <div class="text-sm font-bold mt-1" style="color:#1e40af">This Week</div>
            <div class="text-xs mt-0.5" style="color:#1d4ed8">Next 7 days</div>
        </div>

        {{-- Upcoming --}}
        <div class="rounded-2xl p-5 relative overflow-hidden shadow-sm border"
             style="background: linear-gradient(135deg, #f0fdf4, #dcfce7); border-color: #bbf7d0;">
            <div class="absolute top-3 right-3 w-10 h-10 rounded-full flex items-center justify-center"
                 style="background: rgba(34,197,94,0.12);">
                <svg class="w-5 h-5" style="color:#22c55e" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </div>
            <div class="text-4xl font-black" style="color:#16a34a">{{ $this->upcomingCount }}</div>
            <div class="text-sm font-bold mt-1" style="color:#14532d">Upcoming</div>
            <div class="text-xs mt-0.5" style="color:#15803d">Days 8 – 14</div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════
         QUICK-TIPS BANNER
    ════════════════════════════════════════════════════════════════ --}}
    @if($this->overdueCount > 0 || $this->todayCount > 0)
    <div class="rounded-xl px-5 py-3 mb-5 flex items-center gap-3 border"
         style="background:#fef9c3; border-color:#fde047;">
        <span class="text-xl">💡</span>
        <p class="text-sm font-medium" style="color:#713f12;">
            @if($this->overdueCount > 0)
                You have <strong>{{ $this->overdueCount }} overdue</strong> follow-up{{ $this->overdueCount > 1 ? 's' : '' }}.
            @endif
            @if($this->todayCount > 0)
                <strong>{{ $this->todayCount }}</strong> due today.
            @endif
            Use the <strong>Call</strong> or <strong>SMS</strong> buttons to reach out directly — no copy-pasting needed.
        </p>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════
         TABLE
    ════════════════════════════════════════════════════════════════ --}}
    <div class="rounded-2xl overflow-hidden shadow border"
         style="border-color: rgba(99,102,241,0.15);">
        {{ $this->table }}
    </div>

    {{-- ═══════════════════════════════════════════════════════════════
         SCRIPTS
    ════════════════════════════════════════════════════════════════ --}}
    <script>
        // Live clock
        function updateClock() {
            const el = document.getElementById('live-clock');
            if (!el) return;
            const now = new Date();
            let h = now.getHours(), m = now.getMinutes(), ampm = h >= 12 ? 'PM' : 'AM';
            h = h % 12 || 12;
            el.textContent = `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')} ${ampm}`;
        }
        updateClock();
        setInterval(updateClock, 1000);

        // SMS dispatch from Livewire
        document.addEventListener('open-sms', function(e) {
            window.location.href = e.detail[0]?.url ?? e.detail?.url;
        });
    </script>

</x-filament-panels::page>