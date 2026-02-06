<x-filament-panels::page>
    {{-- 1. The Main Filament Table --}}
    {{ $this->table }}

    {{-- 2. Your Custom Analytics Section --}}
    <x-filament::section class="mt-8">
        <x-slot name="heading">
            Store Stock Repairs Analytics
        </x-slot>

        <x-slot name="description">
            Tracking repairs of internal store items and their resale status.
        </x-slot>

        <div class="overflow-x-auto border border-gray-200 rounded-lg dark:border-white/10">
            <table class="w-full text-sm text-left divide-y divide-gray-200 dark:divide-white/5">
                <thead class="bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th class="px-4 py-3 font-medium text-gray-900 dark:text-white">Repair #</th>
                        <th class="px-4 py-3 font-medium text-gray-900 dark:text-white">Original Stock #</th>
                        <th class="px-4 py-3 font-medium text-gray-900 dark:text-white">Item Description</th>
                        <th class="px-4 py-3 font-medium text-gray-900 dark:text-white">Status</th>
                        <th class="px-4 py-3 font-medium text-center text-gray-900 dark:text-white">Billed in POS?</th>
                        <th class="px-4 py-3 font-medium text-right text-gray-900 dark:text-white">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                    @php
                        // Fetch only repairs that came from store stock
                        $stockRepairs = \App\Models\Repair::with(['originalProduct', 'sale'])
                            ->where('is_from_store_stock', true)
                            ->latest()
                            ->get();
                    @endphp

                    @forelse($stockRepairs as $repair)
                        <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                            <td class="px-4 py-3 font-semibold text-primary-600">
                                <a href="{{ \App\Filament\Resources\RepairResource::getUrl('edit', ['record' => $repair->id]) }}" class="hover:underline">
                                    {{ $repair->repair_no }}
                                </a>
                            </td>
                            <td class="px-4 py-3">
                                {{ $repair->originalProduct->barcode ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-gray-500">
                                {{ $repair->item_description }}
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $color = match($repair->status) {
                                        'delivered' => 'text-success-600 bg-success-50',
                                        'received' => 'text-gray-600 bg-gray-50',
                                        default => 'text-info-600 bg-info-50',
                                    };
                                @endphp
                                <span class="px-2 py-1 rounded-full text-xs font-medium {{ $color }}">
                                    {{ ucfirst($repair->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($repair->sale)
                                    <a href="{{ \App\Filament\Resources\SaleResource::getUrl('edit', ['record' => $repair->sale->id]) }}" 
                                       class="inline-flex items-center gap-1 text-xs font-bold text-success-600 bg-success-100 px-2 py-1 rounded border border-success-200">
                                        <x-heroicon-m-check-badge class="w-4 h-4"/>
                                        YES (Inv: {{ $repair->sale->invoice_number }})
                                    </a>
                                @else
                                    <span class="text-gray-400 text-xs">— Pending —</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-mono">
                                ${{ number_format($repair->final_cost ?: $repair->estimated_cost, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-gray-500">
                                No store stock repairs found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>