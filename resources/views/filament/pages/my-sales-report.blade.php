<x-filament-panels::page>
    @php $stats = $this->getStats(); @endphp

    <div class="flex flex-col gap-4">

        {{-- ── STAT CARDS ─────────────────────────────────────────────────── --}}
        <div class="flex flex-wrap items-stretch gap-3">

            {{-- Viewing --}}
            <div class="flex flex-col justify-center px-4 py-3 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm min-w-[180px]">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest">Viewing</p>
                <p class="text-base font-black text-gray-900 dark:text-white mt-0.5">
                    @if(!$stats['is_privileged'])
                        👤 {{ $stats['user_name'] }}
                    @elseif($stats['filtered_assoc'])
                        👤 {{ $stats['filtered_assoc'] }}
                    @else
                        📊 All Staff
                    @endif
                </p>
                <p class="text-[11px] text-gray-400 mt-0.5">
                    @if(!$stats['is_privileged'])
                        Your sales only
                    @elseif($stats['filtered_assoc'])
                        Filtered by associate
                    @else
                        Use filter to drill down
                    @endif
                </p>
            </div>

            {{-- Transactions --}}
            <div class="flex flex-col justify-center px-4 py-3 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm flex-1 min-w-[130px]">
                <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-widest">Transactions</p>
                <p class="text-3xl font-black text-primary-600 mt-0.5">{{ number_format($stats['count']) }}</p>
                <p class="text-[11px] text-gray-400">
                    Completed
                    @if($stats['laybuy_count'] > 0)
                        <span class="ml-1 px-1.5 py-0.5 bg-warning-100 text-warning-700 rounded text-[10px] font-bold">
                            ⏳ {{ $stats['laybuy_count'] }} laybuy
                        </span>
                    @endif
                </p>
            </div>

            {{-- My / Associate Share --}}
            <div class="flex flex-col justify-center px-4 py-3 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm flex-1 min-w-[160px]">
                <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-widest">
                    {{ $stats['filtered_assoc'] ? $stats['filtered_assoc'] . "'s Share" : 'My Sales Share' }}
                </p>
                <p class="text-3xl font-black text-success-600 mt-0.5">${{ number_format($stats['net_share'], 2) }}</p>
                <p class="text-[11px] text-gray-400">Subtotal, split adjusted</p>
            </div>

            {{-- Tax --}}
            <div class="flex flex-col justify-center px-4 py-3 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm flex-1 min-w-[130px]">
                <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-widest">Tax Collected</p>
                <p class="text-3xl font-black text-warning-600 mt-0.5">${{ number_format($stats['tax'], 2) }}</p>
                <p class="text-[11px] text-gray-400">
                    {{ $stats['filtered_assoc'] ? "On {$stats['filtered_assoc']}'s sales" : 'On your sales' }}
                </p>
            </div>

            {{-- Store Total — admin only --}}
            @if($stats['is_privileged'])
            <div class="flex flex-col justify-center px-4 py-3 bg-primary-50 dark:bg-primary-900 rounded-xl border border-primary-100 dark:border-primary-700 shadow-sm flex-1 min-w-[160px]">
                <p class="text-[11px] font-semibold text-primary-400 uppercase tracking-widest">Store Total</p>
                <p class="text-3xl font-black text-primary-700 dark:text-primary-300 mt-0.5">${{ number_format($stats['store_total'], 2) }}</p>
                <p class="text-[11px] text-primary-400">All staff combined</p>
            </div>
            @endif

        </div>

        {{-- ── STAFF LEADERBOARD (privileged only) ─────────────────────────── --}}
        @if($stats['is_privileged'] && count($stats['staff_breakdown']) > 0)
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-black text-gray-900 dark:text-white uppercase tracking-wider">
                        🏆 Staff Leaderboard
                    </h3>
                    <p class="text-[11px] text-gray-400 mt-0.5">
                        Totals based on <strong>completed_at</strong> date — laybuy sales count when paid off, not when created.
                    </p>
                </div>
            </div>

            <div class="divide-y divide-gray-50 dark:divide-gray-700">
                @php
                    $rank      = 1;
                    $maxAmount = max(array_values($stats['staff_breakdown']));
                    $medals    = ['🥇', '🥈', '🥉'];
                @endphp
                @foreach($stats['staff_breakdown'] as $staffName => $amount)
                @php
                    $pct   = $maxAmount > 0 ? round(($amount / $maxAmount) * 100) : 0;
                    $medal = $medals[$rank - 1] ?? "#{$rank}";
                    $isViewing = $staffName === $stats['filtered_assoc'];
                @endphp
                <div class="flex items-center gap-3 px-4 py-3 {{ $isViewing ? 'bg-primary-50 dark:bg-primary-900/30' : 'hover:bg-gray-50 dark:hover:bg-gray-700/30' }} transition-colors">
                    {{-- Rank --}}
                    <span class="text-lg w-8 text-center flex-shrink-0">{{ $medal }}</span>

                    {{-- Name + bar --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-bold text-gray-900 dark:text-white truncate {{ $isViewing ? 'text-primary-700' : '' }}">
                                {{ $staffName }}
                                @if($isViewing)
                                    <span class="ml-1 text-[10px] bg-primary-100 text-primary-700 px-1.5 py-0.5 rounded font-bold uppercase">Viewing</span>
                                @endif
                            </span>
                            <span class="text-sm font-black text-gray-900 dark:text-white ml-4 flex-shrink-0">
                                ${{ number_format($amount, 2) }}
                            </span>
                        </div>
                        <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-1.5">
                            <div class="h-1.5 rounded-full transition-all duration-500
                                {{ $rank === 1 ? 'bg-warning-500' : ($rank === 2 ? 'bg-gray-400' : ($rank === 3 ? 'bg-orange-400' : 'bg-primary-400')) }}"
                                style="width: {{ $pct }}%">
                            </div>
                        </div>
                    </div>
                </div>
                @php $rank++ @endphp
                @endforeach
            </div>
        </div>
        @endif

        {{-- ── SALES TABLE ───────────────────────────────────────────────────── --}}
        <div class="rounded-xl overflow-hidden border border-gray-100 dark:border-gray-700 shadow-sm bg-white dark:bg-gray-800">
            {{ $this->table }}
        </div>

    </div>
</x-filament-panels::page>