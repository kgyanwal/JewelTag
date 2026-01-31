<x-filament-panels::page>
    <x-filament-panels::form wire:submit="save">
        {{-- This renders the fields you defined in your form() method --}}
        {{ $this->form }}

        <x-filament-panels::form.actions 
            :actions="$this->getFormActions()" 
        />
    </x-filament-panels::form>
</x-filament-panels::page>