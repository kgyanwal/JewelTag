<x-filament-panels::page>
    @php
        $stats = $this->getStats();
    @endphp

    <div class="grid grid-cols-1 gap-4 mb-6 md:grid-cols-3">
        {{-- Stat Card: Total Sales --}}
        <div class="p-6 bg-white border rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
            <p class="text-sm font-medium text-gray-500">Total Transactions</p>
            <p class="text-3xl font-bold text-primary-600">{{ $stats['count'] }}</p>
        </div>

        {{-- Stat Card: Individual Share --}}
        <div class="p-6 bg-white border rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
            <p class="text-sm font-medium text-gray-500">My Net Sales Share (Subtotal)</p>
            <p class="text-3xl font-bold text-success-600">${{ number_format($stats['net_share'], 2) }}</p>
        </div>

        {{-- Stat Card: Total Tax --}}
        <div class="p-6 bg-white border rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
            <p class="text-sm font-medium text-gray-500">Tax Collected (Total)</p>
            <p class="text-3xl font-bold text-gray-700">${{ number_format($stats['tax'], 2) }}</p>
        </div>
    </div>

    {{-- The Table --}}
    <div>
        {{ $this->table }}
    </div>
</x-filament-panels::page>