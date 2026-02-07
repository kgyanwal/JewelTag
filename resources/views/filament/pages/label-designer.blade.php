<x-filament-panels::page>
    <x-filament::section>
        <div class="space-y-6">
            {{-- This renders the form which contains the Designer Preview --}}
            <form wire:submit.prevent="saveMasterLayout">
                {{ $this->form }}
                
                {{-- Optional: Manual Footer Buttons (if not using Header Actions) --}}
                <div class="flex items-center justify-end gap-3 mt-6 pt-6 border-t border-gray-200 dark:border-white/10">
                    <x-filament::button 
                        type="submit" 
                        color="success" 
                        icon="heroicon-o-check-circle"
                    >
                        Save Master Layout
                    </x-filament::button>

                    <x-filament::button 
                        wire:click="resetToDefault" 
                        color="danger" 
                        variant="outline" 
                        icon="heroicon-o-arrow-path"
                        wire:confirm="Are you sure you want to reset all coordinates to 300 DPI defaults?"
                    >
                        Reset to Defaults
                    </x-filament::button>
                </div>
            </form>
        </div>
    </x-filament::section>

    {{-- Legend for the Staff --}}
    <x-filament::section class="mt-6">
        <x-slot name="heading">Designer Legend</x-slot>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 bg-red-400 border border-red-600 border-dashed"></div>
                <span>Red Dashed Line: The "Fold" of the Butterfly Tag</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                <span>Blue Ring: Currently selected element for editing</span>
            </div>
        </div>
    </x-filament::section>
</x-filament-panels::page>