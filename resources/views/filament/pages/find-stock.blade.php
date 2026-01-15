<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Search Form Section --}}
        <form wire:submit="applyFilters">
            {{ $this->form }}
            
            <div class="flex justify-end mt-4">
                <x-filament::button type="submit" icon="heroicon-m-magnifying-glass" size="lg">
                    SEARCH
                </x-filament::button>
            </div>
        </form>

        <hr class="border-gray-200 dark:border-white/10" />

        {{-- Results Table Section --}}
        <div>
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>