<x-filament-panels::page>
    <div class="space-y-6">
        {{-- 🔹 Modernized Search Header --}}
        <div class="p-6 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm">
            {{-- 🔹 Removed wire:submit="updatedData" to fix Internal Server Error --}}
            <div>
                {{ $this->form }}
                
                <div class="flex justify-end mt-4 gap-x-3">
                    <x-filament::button 
                        color="gray" 
                        wire:click="resetFilters" 
                        variant="outline"
                        icon="heroicon-m-arrow-path">
                        Reset Filters
                    </x-filament::button>
                    
                    {{-- 🔹 This button now just triggers a refresh, preventing the lifecycle error --}}
                    <x-filament::button 
                        wire:click="$refresh"
                        size="lg"
                        icon="heroicon-m-magnifying-glass"
                        class="px-8">
                        SEARCH SALES
                    </x-filament::button>
                </div>
            </div>
        </div>

        {{-- 🔹 Results Table --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden shadow-sm">
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>