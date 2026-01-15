<x-filament-panels::page>
    <form wire:submit="applyFilters">
        {{ $this->form }}
        
        <div class="flex gap-3 justify-start mt-4">
            <x-filament::button type="submit" size="lg" icon="heroicon-m-magnifying-glass">
                SEARCH
            </x-filament::button>

            <x-filament::button wire:click="clearFilters" color="gray" size="lg" variant="outline">
                CLEAR
            </x-filament::button>
        </div>
    </form>

    <hr class="border-gray-200 dark:border-white/10 my-6" />

    <div>
        {{ $this->table }}
    </div>
</x-filament-panels::page>