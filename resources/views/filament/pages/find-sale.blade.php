<x-filament-panels::page>
    <div class="space-y-6">
        {{-- 🔹 Modernized Search Header with Execute Button --}}
        <div class="p-6 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm">
            <form wire:submit="updatedData">
                {{ $this->form }}
                
                <div class="flex justify-end mt-4 gap-x-3">
                    <x-filament::button 
                        color="gray" 
                        wire:click="$refresh" 
                        variant="outline"
                        icon="heroicon-m-arrow-path">
                        Reset Filters
                    </x-filament::button>
                    
                    <x-filament::button 
                        type="submit" 
                        size="lg"
                        icon="heroicon-m-magnifying-glass"
                        class="px-8">
                        SEARCH SALES
                    </x-filament::button>
                </div>
            </form>
        </div>

        {{-- 🔹 Results Table --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden shadow-sm">
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>