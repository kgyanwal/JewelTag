<x-filament-panels::page>
    <x-filament-panels::form wire:submit="resetFilters">
        {{ $this->form }}

        <div class="flex justify-end gap-x-3 mt-4">
            <x-filament::button type="submit" color="gray" variant="outline">
                Clear Filters
            </x-filament::button>
        </div>
    </x-filament-panels::form>

    <div class="mt-8">
        {{ $this->table }}
    </div>
</x-filament-panels::page>