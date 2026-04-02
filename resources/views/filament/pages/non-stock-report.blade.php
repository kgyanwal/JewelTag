<x-filament-panels::page>
    <div class="space-y-4">
        <div class="bg-white p-4 rounded-xl shadow-sm border dark:bg-gray-800 dark:border-gray-700">
            <h2 class="text-lg font-bold">Custom Labor & Non-Stock Items Summary</h2>
            <p class="text-sm text-gray-500 italic">This report tracks repairs, grills, and custom services that aren't in standard stock.</p>
        </div>
        
        {{ $this->table }}
    </div>
</x-filament-panels::page>