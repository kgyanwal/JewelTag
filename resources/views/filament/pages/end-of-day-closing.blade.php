<x-filament-panels::page>
    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800 rounded-xl mb-4 border border-gray-200 dark:border-gray-700">
        <div class="flex items-center gap-4">
            @if(auth()->user()->hasRole('Superadmin'))
                <label class="font-bold text-sm uppercase tracking-wider">Historical Audit:</label>
                <input type="date" wire:model.live="date" class="border-gray-300 rounded-lg dark:bg-gray-900 focus:ring-primary-500">
            @else
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-m-calendar" class="w-5 h-5 text-gray-400" />
                    <span class="font-bold text-gray-700 dark:text-gray-200">Date: {{ \Carbon\Carbon::parse($date)->format('M d, Y') }}</span>
                </div>
            @endif
        </div>
        
        <div class="flex items-center gap-3">
            @if($isClosed)
                <span class="flex items-center gap-1.5 px-3 py-1 text-xs font-black text-green-700 bg-green-100 rounded-full border border-green-200">
                    <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                    RECORD LOCKED
                </span>
            @else
                <span class="px-3 py-1 text-xs font-black text-amber-700 bg-amber-100 rounded-full border border-amber-200 uppercase">
                    Open for Entry
                </span>
            @endif
        </div>
    </div>

    <div class="bg-white dark:bg-gray-900 shadow-sm rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
        <table class="w-full text-left divide-y divide-gray-200 dark:divide-gray-800">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-6 py-4 text-xs font-black text-gray-500 uppercase tracking-widest">Payment Type</th>
                    <th class="px-6 py-4 text-xs font-black text-gray-500 uppercase text-right tracking-widest">Expected Total</th>
                    <th class="px-6 py-4 text-xs font-black text-gray-500 uppercase text-right tracking-widest">Actual Total (Counted)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                @foreach($expectedTotals as $key => $expected)
                    <tr class="{{ $isClosed ? 'bg-gray-50/50 dark:bg-gray-800/50' : '' }}">
                        <td class="px-6 py-4 font-bold text-gray-700 dark:text-gray-300 capitalize">{{ str_replace('_', ' ', $key) }}</td>
                        <td class="px-6 py-4 text-right font-mono text-lg {{ $expected > 0 ? 'text-primary-600' : 'text-gray-400' }}">
                            ${{ number_format($expected, 2) }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            <input 
                                type="number" 
                                wire:model.live="actualTotals.{{ $key }}"
                                {{ $isClosed ? 'disabled' : '' }}
                                placeholder="0.00"
                                class="w-32 text-right border-gray-300 rounded-lg dark:bg-gray-900 focus:ring-primary-600 disabled:opacity-50 disabled:cursor-not-allowed font-mono text-lg font-bold"
                            >
                        </td>
                    </tr>
                @endforeach
            </tbody>

            {{-- 🔹 FOOTER: Only show actual money sums to Superadmin OR after closing --}}
            @if(auth()->user()->hasRole('Superadmin') || $isClosed)
            <tfoot class="bg-gray-50 dark:bg-gray-800 font-black">
                <tr>
                    <td class="px-6 py-4 text-gray-950 dark:text-white uppercase tracking-tighter">Day Summary</td>
                    <td class="px-6 py-4 text-right text-xl text-primary-600">
                        ${{ number_format(array_sum($expectedTotals), 2) }}
                    </td>
                    <td class="px-6 py-4 text-right text-xl {{ array_sum($actualTotals) == array_sum($expectedTotals) ? 'text-green-600' : 'text-red-600' }}">
                        ${{ number_format(array_sum($actualTotals), 2) }}
                    </td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>

    <div class="flex items-center justify-between mt-6">
        <div class="flex gap-3">
            <x-filament::button color="gray" icon="heroicon-o-printer" outlined>PRINT SLIP</x-filament::button>
            <x-filament::button color="gray" icon="heroicon-o-envelope" outlined>EMAIL REPORT</x-filament::button>
        </div>
        
        @if(!$isClosed)
             <x-filament::button 
                wire:click="mountAction('post_closing')" 
                color="success" 
                size="xl" 
                icon="heroicon-m-check-circle"
                class="px-8"
            >
                POST CLOSING & LOCK DAY
            </x-filament::button>
        @endif
    </div>
</x-filament-panels::page>