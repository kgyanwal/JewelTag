<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        {{-- Stat Card: Total Count --}}
        <div class="p-6 bg-white border rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
            <p class="text-sm font-medium text-gray-500">Total Invoices</p>
            <p class="text-3xl font-bold">{{ $this->getViewData()['totalSalesCount'] }}</p>
        </div>

        {{-- Stat Card: Aggregate Subtotal --}}
        <div class="p-6 bg-white border rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
            <p class="text-sm font-medium text-gray-500">Aggregate Subtotal (Before Tax)</p>
            <p class="text-3xl font-bold text-primary-600">${{ number_format($this->getViewData()['aggregateSubtotal'], 2) }}</p>
        </div>

        {{-- Stat Card: Total Tax --}}
        <div class="p-6 bg-white border rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
            <p class="text-sm font-medium text-gray-500">Tax Collected</p>
            <p class="text-3xl font-bold text-gray-700">${{ number_format($this->getViewData()['totalTax'], 2) }}</p>
        </div>
    </div>

    {{-- The Table --}}
    <div>
        {{ $this->table }}
    </div>
</x-filament-panels::page>