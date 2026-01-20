<x-filament-panels::page>
    <div class="bg-white dark:bg-gray-900 shadow-sm rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
        <table class="w-full text-left divide-y divide-gray-200 dark:divide-gray-800">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase">Payment Type</th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase text-right">Expected Total</th>
                    <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase text-right">Actual Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                @foreach($expectedTotals as $key => $expected)
                    <tr>
                        <td class="px-6 py-4 font-medium capitalize">{{ str_replace('_', ' ', $key) }}</td>
                        <td class="px-6 py-4 text-right font-mono text-blue-600">
                            ${{ number_format($expected, 2) }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            <input 
                                type="number" 
                                wire:model="actualTotals.{{ $key }}"
                                class="w-32 text-right border-gray-300 rounded-lg dark:bg-gray-800 dark:border-gray-700"
                            >
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-green-50 dark:bg-green-900/20">
                <tr class="font-bold">
                    <td class="px-6 py-4 text-green-700 dark:text-green-400">TOTAL</td>
                    <td class="px-6 py-4 text-right text-green-700">
                        ${{ number_format(array_sum($expectedTotals), 2) }}
                    </td>
                    <td class="px-6 py-4 text-right text-green-700">
                        ${{ number_format(array_sum($actualTotals), 2) }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="flex gap-4 mt-4">
        <x-filament::button color="gray" icon="heroicon-o-printer">
            PRINT DEPOSIT SLIP
        </x-filament::button>
        <x-filament::button color="gray" icon="heroicon-o-eye">
            PREVIEW DEPOSIT SLIP
        </x-filament::button>
    </div>
</x-filament-panels::page>