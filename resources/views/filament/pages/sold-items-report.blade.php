<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Intelligence Hub Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <x-filament::section class="border-t-4 border-t-primary-600">
                <p class="text-xs text-gray-500 uppercase font-bold tracking-widest">Total Revenue</p>
                <h2 class="text-3xl font-black text-primary-600">${{ $intelligence['revenue'] }}</h2>
                <p class="text-[10px] text-gray-400">Avg Ticket: ${{ $intelligence['avg_ticket'] }}</p>
            </x-filament::section>

            <x-filament::section class="border-t-4 border-t-success-600">
                <p class="text-xs text-gray-500 uppercase font-bold tracking-widest">Net Profit</p>
                <h2 class="text-3xl font-black text-success-600">${{ $intelligence['profit'] }}</h2>
                <p class="text-[10px] text-gray-400">Yield Margin: {{ $intelligence['margin'] }}%</p>
            </x-filament::section>

            <x-filament::section class="border-t-4 border-t-amber-600">
                <p class="text-xs text-gray-500 uppercase font-bold tracking-widest">Quantity Sold</p>
                <h2 class="text-3xl font-black text-gray-800">{{ $intelligence['count'] }}</h2>
                <p class="text-[10px] text-gray-400">Inventory Turnover Count</p>
            </x-filament::section>

            <x-filament::section class="border-t-4 border-t-purple-600">
                <p class="text-xs text-gray-500 uppercase font-bold tracking-widest">Best Volume Day</p>
                <h2 class="text-xl font-bold text-purple-700 mt-2">{{ $intelligence['best_day'] }}</h2>
                <p class="text-[10px] text-gray-400">Top Performance Date</p>
            </x-filament::section>
        </div>

        {{-- Parameter Selection --}}
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800">
            {{ $this->form }}
            <div class="p-4 border-t border-gray-100 dark:border-gray-800 flex justify-end gap-3">
                <x-filament::button wire:click="runAction('preview')" icon="heroicon-m-magnifying-glass">
                    Apply Intelligence Filters
                </x-filament::button>
            </div>
        </div>

        {{-- Report Table --}}
        @if($showTable)
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-2">
                {{ $this->table }}
            </div>
        @else
            <div class="p-20 border-2 border-dashed border-gray-200 text-center rounded-2xl">
                <p class="text-gray-400">Select parameters and click preview to begin analysis.</p>
            </div>
        @endif
    </div>
</x-filament-panels::page>