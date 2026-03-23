<x-filament-panels::page>
    <div class="flex flex-col gap-y-8">
        {{-- Custom Stats Header --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="p-6 bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Active Deposits</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ \App\Models\Laybuy::where('status', 'in_progress')->count() }}
                </p>
            </div>
            
            <div class="p-6 bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Outstanding Balance</p>
                <p class="text-2xl font-bold text-red-600">
                    ${{ number_format(\App\Models\Laybuy::where('status', 'in_progress')->sum('balance_due'), 2) }}
                </p>
            </div>

            <div class="p-6 bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Collected This Month</p>
                <p class="text-2xl font-bold text-green-600">
                    ${{ number_format(\App\Models\Payment::whereMonth('created_at', now()->month)->sum('amount'), 2) }}
                </p>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm dark:bg-gray-900">
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>