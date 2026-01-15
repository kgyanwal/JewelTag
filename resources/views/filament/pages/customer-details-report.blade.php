<x-filament-panels::page>
    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-9 space-y-6">
            {{-- Quick Month Selector (Diamond Square Style) --}}
            <div class="p-4 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl">
                <div class="flex flex-wrap gap-2 mb-4">
                    @foreach(['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'] as $m)
                        <x-filament::button size="xs" color="gray" variant="outline">{{ $m }}</x-filament::button>
                    @endforeach
                </div>
                {{ $this->form }}
            </div>

            {{-- Main Table View --}}
            <div class="border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden bg-white dark:bg-gray-900">
                {{ $this->table }}
            </div>
        </div>

        {{-- Sidebar Actions --}}
        <div class="col-span-3 space-y-6">
            <div class="p-4 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm">
                <h3 class="text-xs font-bold uppercase text-gray-400 mb-4 tracking-widest">Select Fields</h3>
                <form>
                    {{ $this->makeForm()->schema($this->getFieldsFormSchema()) }}
                </form>
            </div>

            <div class="p-4 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl space-y-3">
                {{-- 🔹 PREVIEW POPUP BUTTON --}}
                <x-filament::modal id="preview-modal" width="6xl">
                    <x-slot name="trigger">
                        <x-filament::button icon="heroicon-m-eye" color="gray" class="w-full justify-start" variant="outline">
                            PREVIEW POPUP
                        </x-filament::button>
                    </x-slot>
                    
                    <x-slot name="heading">Report Data Preview</x-slot>
                    <div class="py-4">
                        {{ $this->table }}
                    </div>
                </x-filament::button>

                <x-filament::button wire:click="exportReport" icon="heroicon-m-arrow-down-tray" color="success" class="w-full justify-start">
                    EXPORT CSV
                </x-filament::button>

                <x-filament::button onclick="window.print()" icon="heroicon-m-printer" color="info" class="w-full justify-start">
                    PRINT
                </x-filament::button>
            </div>
        </div>
    </div>
</x-filament-panels::page>