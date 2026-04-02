<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        {{-- Stats Card 1 --}}
        <div class="p-6 bg-white border rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-success-100 rounded-lg dark:bg-success-900">
                    <x-heroicon-o-shopping-cart class="w-6 h-6 text-success-600" />
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Total Items Sold</p>
                    <p class="text-2xl font-bold">{{ $this->getViewData()['soldCount'] }}</p>
                </div>
            </div>
        </div>

        {{-- Stats Card 2 --}}
        <div class="p-6 bg-white border rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-primary-100 rounded-lg dark:bg-primary-900">
                    <x-heroicon-o-currency-dollar class="w-6 h-6 text-primary-600" />
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Total Revenue (Sold Inventory Value)</p>
                    <p class="text-2xl font-bold text-primary-600">${{ number_format($this->getViewData()['totalSoldValue'], 2) }}</p>
                </div>
            </div>
        </div>
    </div>

    <div>
        {{ $this->table }}
    </div>
</x-filament-panels::page>