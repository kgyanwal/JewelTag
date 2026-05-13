<x-filament-panels::page>
    <div class="mb-4 p-4 bg-white rounded-xl border border-gray-200 shadow-sm">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-warning-100 flex items-center justify-center">
                <x-heroicon-o-arrow-path class="w-5 h-5 text-warning-600" />
            </div>
            <div>
                <h2 class="text-sm font-bold text-gray-800">Restock Activity Log</h2>
                <p class="text-xs text-gray-500">Track every item that has been moved from Sold → In Stock. Superadmin only.</p>
            </div>
        </div>
    </div>

    {{ $this->table }}
</x-filament-panels::page>