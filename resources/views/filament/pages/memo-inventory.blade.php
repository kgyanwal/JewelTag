<x-filament-panels::page>
    <div class="space-y-6">
        <div class="p-4 bg-yellow-50 border-l-4 border-yellow-400 text-yellow-700 rounded shadow-sm">
            <p class="text-sm font-bold uppercase tracking-wider">Memo Inventory Control</p>
            <p class="text-xs">Consignment stock from vendors. Items must be returned or marked as sold for payment settlement.</p>
        </div>

        {{ $this->table }}
    </div>
</x-filament-panels::page>