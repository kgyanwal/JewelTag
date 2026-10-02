<x-filament-panels::page>
    <div class="space-y-6">

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="rounded-2xl p-5 bg-white dark:bg-gray-900 border-l-4" style="border-color:#0B3D3C;">
                <div class="flex items-center justify-between">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Total Revenue</p>
                </div>
                <h2 class="text-2xl font-black mt-1" style="color:#0B3D3C;">${{ $intelligence['revenue'] }}</h2>
                <p class="text-[11px] text-gray-400 mt-1">Avg Ticket: ${{ $intelligence['avg_ticket'] }}</p>
            </div>

            <div class="rounded-2xl p-5 bg-white dark:bg-gray-900 border-l-4" style="border-color:#0B3D3C;">
                <div class="flex items-center justify-between">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Net Profit</p>
                </div>
                <h2 class="text-2xl font-black mt-1" style="color:#0B3D3C;">${{ $intelligence['profit'] }}</h2>
                <p class="text-[11px] text-gray-400 mt-1">Yield Margin: {{ $intelligence['margin'] }}%</p>
            </div>

            <div class="rounded-2xl p-5 bg-white dark:bg-gray-900 border-l-4" style="border-color:#0B3D3C;">
                <div class="flex items-center justify-between">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Items Sold</p>
                </div>
                <h2 class="text-2xl font-black mt-1" style="color:#0B3D3C;">{{ $intelligence['count'] }}</h2>
                <p class="text-[11px] text-gray-400 mt-1">Line Items in Range</p>
            </div>

            <div class="rounded-2xl p-5 bg-white dark:bg-gray-900 border-l-4" style="border-color:#0B3D3C;">
                <div class="flex items-center justify-between">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Best Volume Day</p>
                </div>
                <h2 class="text-lg font-black mt-1" style="color:#0B3D3C;">{{ $intelligence['best_day'] }}</h2>
                <p class="text-[11px] text-gray-400 mt-1">Top Performance Date</p>
            </div>
        </div>

        @if(!empty($breakdowns['top_categories']) || !empty($breakdowns['top_suppliers']))
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

            @if(!empty($breakdowns['top_categories']))
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-5">
                <p class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-3">Top Categories</p>
                @php $maxCat = collect($breakdowns['top_categories'])->max('revenue') ?: 1; @endphp
                <div class="space-y-2">
                    @foreach($breakdowns['top_categories'] as $cat)
                        <div>
                            <div class="flex justify-between text-xs mb-1">
                                <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $cat['label'] }}</span>
                                <span class="text-gray-500">${{ number_format($cat['revenue'], 0) }} · {{ $cat['qty'] }} items</span>
                            </div>
                            <div class="h-2 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                                <div class="h-full rounded-full" style="width:{{ ($cat['revenue']/$maxCat)*100 }}%;background:#0B3D3C;"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            @if(!empty($breakdowns['top_suppliers']))
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-5">
                <p class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-3">Top Vendors</p>
                @php $maxSup = collect($breakdowns['top_suppliers'])->max('revenue') ?: 1; @endphp
                <div class="space-y-2">
                    @foreach($breakdowns['top_suppliers'] as $sup)
                        <div>
                            <div class="flex justify-between text-xs mb-1">
                                <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $sup['label'] }}</span>
                                <span class="text-gray-500">${{ number_format($sup['revenue'], 0) }} · {{ $sup['qty'] }} items</span>
                            </div>
                            <div class="h-2 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                                <div class="h-full rounded-full" style="width:{{ ($sup['revenue']/$maxSup)*100 }}%;background:#0B3D3C;"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
        @endif

        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden">
            <div class="px-6 py-4 border-b-2" style="border-color:#0B3D3C;background:#f0fdf9;">
                <h3 class="font-bold text-sm uppercase tracking-widest" style="color:#0B3D3C;">Report Configuration</h3>
                <p class="text-gray-500 text-xs mt-0.5">Narrow down the dataset, then choose which columns to display</p>
            </div>

            <div class="p-6">
                {{ $this->form }}
            </div>

            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-100 dark:border-gray-800 flex justify-end gap-3">
                <x-filament::button
                    wire:click="applyFilters"
                    icon="heroicon-m-magnifying-glass"
                    size="lg"
                    color="primary"
                >
                    Apply Filters &amp; Generate Report
                </x-filament::button>
            </div>
        </div>

        @if($showTable)
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-2"
                 x-data x-init="$nextTick(() => $el.scrollIntoView({ behavior: 'smooth', block: 'start' }))">
                {{ $this->table }}
            </div>
        @else
            <div class="p-20 border-2 border-dashed border-gray-200 dark:border-gray-700 text-center rounded-2xl">
                <p class="text-gray-400">Select parameters above and click <strong>Apply Filters &amp; Generate Report</strong> to begin analysis.</p>
            </div>
        @endif
    </div>
</x-filament-panels::page>