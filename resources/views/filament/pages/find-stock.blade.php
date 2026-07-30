<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Search Form Section --}}
        <x-filament::section>
            <form wire:submit="applyFilters">
                {{ $this->form }}
                
                <div class="flex justify-end mt-4">
                    <x-filament::button 
                        type="submit" 
                        icon="heroicon-m-magnifying-glass" 
                        size="lg"
                        wire:loading.attr="disabled"
                        wire:target="applyFilters"
                    >
                        <span wire:loading.remove wire:target="applyFilters">SEARCH INVENTORY</span>
                        <span wire:loading wire:target="applyFilters">FILTERING...</span>
                    </x-filament::button>
                </div>
            </form>
        </x-filament::section>

        <hr class="border-gray-200 dark:border-white/10" />

        {{-- Results Table Section --}}
        <div class="relative">
            {{-- Optional: Adds a subtle blur when the table is updating --}}
            <div wire:loading.delay wire:target="applyFilters" class="absolute inset-0 z-10 bg-white/50 dark:bg-gray-900/50 backdrop-blur-[1px] rounded-xl"></div>
            
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>
