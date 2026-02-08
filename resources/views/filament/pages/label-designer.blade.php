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
                        wire:confirm="Are you sure you want to reset all coordinates to defaults?"
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
                <span>Red Dashed Line: The "Fold" of the Butterfly Tag (450 dots)</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                <span>Blue Ring: Currently selected element for editing</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 border-2 border-green-500"></div>
                <span>Green Border: Side 1 (Left - 0 to 450 dots)</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 border-2 border-purple-500"></div>
                <span>Purple Border: Side 2 (Right - 450 to 900 dots)</span>
            </div>
        </div>
        
        <div class="mt-4 p-3 bg-gray-50 dark:bg-gray-900 rounded">
            <h4 class="font-semibold text-sm mb-2">Font Size Conversion (Stored → Actual Dots):</h4>
            <ul class="text-xs space-y-1">
                <li>• Stored: 1 → Actual: 12 dots (0.04" / 1.0mm) - Small</li>
                <li>• Stored: 2 → Actual: 14 dots (0.047" / 1.2mm) - Normal</li>
                <li>• Stored: 3 → Actual: 16 dots (0.053" / 1.35mm) - Large</li>
                <li>• Stored: 4 → Actual: 18 dots (0.06" / 1.5mm) - Extra Large</li>
                <li>• Stored: 5 → Actual: 20 dots (0.067" / 1.7mm) - Price Size</li>
            </ul>
            <p class="text-xs mt-2 text-gray-600 dark:text-gray-400">
                Note: Your database stores smaller numbers (1-5) which are converted to actual printer dots (12-20) at 300 DPI.
            </p>
        </div>
    </x-filament::section>
</x-filament-panels::page>