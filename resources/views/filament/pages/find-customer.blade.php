<x-filament-panels::page>
    <form wire:submit.prevent="applyFilters">
        {{ $this->form }}
    </form>

    <div class="mt-6">
        {{ $this->table }}
    </div>
</x-filament-panels::page>