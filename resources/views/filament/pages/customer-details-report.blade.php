<x-filament-panels::page>
    <div class="space-y-6">
        
        {{-- Section 1: Top Control Bar --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-6 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm">
            <div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Customer Report Engine</h2>
                <p class="text-sm text-gray-500">Extract precisely mapped customer data to CSV or Print.</p>
            </div>
            
            <div class="flex items-center gap-3">
                {{-- 🚀 PREVIEW POPUP (SLIDE-OVER) --}}
                <x-filament::modal id="preview-modal" width="screen" slide-over>
                    <x-slot name="trigger">
                        <x-filament::button icon="heroicon-m-eye" color="info" size="lg" variant="outline">
                            PREVIEW POPUP
                        </x-filament::button>
                    </x-slot>
                    
                    <x-slot name="heading">Report Data Preview ({{ count($this->selectedFields) }} Fields)</x-slot>
                    
                    <div class="py-4">
                        <div class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden bg-white dark:bg-gray-900">
                            {{ $this->table }}
                        </div>
                    </div>
                </x-filament::modal>

                <x-filament::button wire:click="exportReport" icon="heroicon-m-arrow-down-tray" color="success" size="lg">
                    DOWNLOAD CSV
                </x-filament::button>
            </div>
        </div>

        {{-- Section 2: The Main Config Form --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-2">
            <form wire:submit.prevent="save">
                {{ $this->form }}
            </form>
        </div>

        {{-- Section 3: Bottom Helper --}}
        <div class="p-6 bg-blue-50 border border-blue-100 rounded-2xl flex items-center gap-4">
            <div class="bg-blue-500 p-2 rounded-lg">
                <x-filament::icon icon="heroicon-o-information-circle" class="w-6 h-6 text-white" />
            </div>
            <div>
                <h4 class="text-sm font-bold text-blue-900">How it works</h4>
                <p class="text-xs text-blue-700">Select your date ranges and toggle columns. Click <strong>PREVIEW POPUP</strong> to verify the data layout before downloading.</p>
            </div>
        </div>

    </div>
</x-filament-panels::page>