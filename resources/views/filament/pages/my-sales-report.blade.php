<x-filament-panels::page>
    @php $stats = $this->getStats(); @endphp

    <div class="flex flex-col gap-4">

        <div class="flex flex-wrap items-stretch gap-3">

            {{-- Viewing card --}}
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
                    @if($stats['filtered_assoc'])
                        {{ $stats['filtered_assoc'] }}'s completed sales
                    @else
                        Fully paid & completed
                    @endif
                </p>
            </div>

            {{-- My / Associate Share --}}
            <div class="flex flex-col justify-center px-4 py-3 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm flex-1 min-w-[160px]">
                <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-widest">
                    @if($stats['filtered_assoc'])
                        {{ $stats['filtered_assoc'] }}'s Share
                    @else
                        My Sales Share
                    @endif
                </p>
                <p class="text-3xl font-black text-success-600 mt-0.5">${{ number_format($stats['net_share'], 2) }}</p>
                <p class="text-[11px] text-gray-400">Subtotal, split adjusted</p>
            </div>

            {{-- Tax --}}
            <div class="flex flex-col justify-center px-4 py-3 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm flex-1 min-w-[130px]">
                <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-widest">Tax Collected</p>
                <p class="text-3xl font-black text-warning-600 mt-0.5">${{ number_format($stats['tax'], 2) }}</p>
                <p class="text-[11px] text-gray-400">
                    @if($stats['filtered_assoc'])
                        On {{ $stats['filtered_assoc'] }}'s sales
                    @else
                        On your sales
                    @endif
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

        {{-- Table --}}
        <div class="rounded-xl overflow-hidden border border-gray-100 dark:border-gray-700 shadow-sm bg-white dark:bg-gray-800">
            {{ $this->table }}
        </div>

    </div>
</x-filament-panels::page>