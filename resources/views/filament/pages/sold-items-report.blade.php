<x-filament-panels::page>
    <div class="space-y-6">
        <form wire:submit.prevent="runAction('preview')">
            {{ $this->form }}

            <div class="flex justify-end gap-2 mt-4">
                <button type="button" wire:click="runAction('print')" class="blue-erp-btn">PRINT</button>
                <button type="button" wire:click="runAction('export')" class="blue-erp-btn">EXPORT</button>
                <button type="submit" class="blue-erp-btn">PREVIEW</button>
            </div>
        </form>

        <hr class="border-gray-200">

        @if($showTable)
            <div class="bg-white border border-gray-200 rounded-sm shadow-sm p-4">
                <h3 class="text-sm font-bold text-gray-700 mb-4 uppercase">Report Preview</h3>
                {{ $this->table }}
            </div>
        @else
            <div class="p-10 border-2 border-dashed border-gray-200 text-center text-gray-400 rounded-lg">
                Click "PREVIEW" to load data based on selected filters.
            </div>
        @endif
    </div>

    <style>
        .blue-erp-btn {
            background-color: #89cff0;
            color: white;
            font-weight: 800;
            padding: 8px 30px;
            border-radius: 2px;
            font-size: 12px;
            transition: 0.2s;
        }
        .blue-erp-btn:hover { background-color: #6fbce2; }
        .fi-ta-header-ctn { display: none !important; } /* Compact look */
    </style>
</x-filament-panels::page>