<x-filament-panels::page>
    <div class="space-y-6">
        <form wire:submit.prevent="saveMasterLayout">
            {{ $this->form }} {{-- This renders the designer preview --}}
            
            <div class="mt-4 flex justify-end gap-x-3 border-t border-slate-800 pt-6">
                <x-filament::button color="gray" wire:click="resetToDefault" wire:confirm="Reset all coordinates?">
                    Clear & Reset
                </x-filament::button>

                <x-filament::button type="submit" color="success">
                    Save Master Layout
                </x-filament::button>
            </div>
        </form>
    </div>
</x-filament-panels::page>