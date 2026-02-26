<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Filter Form --}}
        <form wire:submit.prevent="submit">
            {{ $this->form }}
        </form>

        {{-- Data Table --}}
        <div class="border rounded-xl shadow-sm bg-white dark:bg-gray-900 overflow-hidden">
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>