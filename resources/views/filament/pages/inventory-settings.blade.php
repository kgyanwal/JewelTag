<x-filament-panels::page>
    {{-- Section 1: The Form --}}
    <x-filament-panels::form wire:submit="save">
        {{ $this->form }}

        <div class="flex justify-end mt-4">
            <x-filament-panels::form.actions 
                :actions="$this->getFormActions()" 
            />
        </div>
    </x-filament-panels::form>

    <hr class="my-8 border-gray-200">

    {{-- Section 2: The Table --}}
    <section>
        <h2 class="text-xl font-bold tracking-tight mb-4">Current Active Settings</h2>
        {{ $this->table }}
    </section>
</x-filament-panels::page>