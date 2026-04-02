<x-filament-panels::page>
    <div class="flex flex-col gap-y-6">

        {{--
            STATS HEADER
            Shows 3 summary cards at the top of the page.

            Total Active Deposits = count of laybuys where status = 'active'
            Total Outstanding     = sum of balance_due on active laybuys
            Collected This Month  = sum of payments.amount for current month
                                    using paid_at column (not payment_date)
                                    using Payment model (not SalePayment)
        --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

            <div class="p-6 bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Active Deposits</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">
                    {{ \App\Models\Laybuy::where('status', 'active')->count() }}
                </p>
            </div>

            <div class="p-6 bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Outstanding Balance</p>
                <p class="text-3xl font-bold text-red-600 mt-1">
                    ${{ number_format(\App\Models\Laybuy::where('status', 'active')->sum('balance_due'), 2) }}
                </p>
            </div>

            <div class="p-6 bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Collected This Month</p>
                {{--
                    Uses Payment model (app/Models/Payment.php)
                    payments table columns: id, sale_id, amount, method, paid_at
                    Uses paid_at (NOT payment_date — that's in sale_payments table)
                --}}
                <p class="text-3xl font-bold text-green-600 mt-1">
                    ${{ number_format(
                        \App\Models\Payment::whereMonth('paid_at', now()->month)
                            ->whereYear('paid_at', now()->year)
                            ->sum('amount'),
                        2
                    ) }}
                </p>
            </div>

        </div>

        {{-- TABLE from DepositSalesReport.php --}}
        <div class="bg-white rounded-xl shadow-sm dark:bg-gray-900">
            {{ $this->table }}
        </div>

    </div>
</x-filament-panels::page>