<x-filament-panels::page>
    {{-- Filters --}}
    <form wire:change.debounce.300ms="$refresh">
        {{ $this->form }}
    </form>

    {{-- Stats Dashboard --}}
    <div class="mt-4">
        {!! $this->getStatsHtml() !!}
    </div>

    {{-- Table --}}
    <div class="mt-6">
        {{ $this->table }}
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>