<x-filament-panels::page>
    <div class="space-y-6">
        <form wire:submit.prevent="saveMasterLayout" class="space-y-6">
            {{ $this->form }}
            
            <div class="flex items-center justify-between p-6 bg-slate-950 rounded-[2.5rem] border border-slate-800">
                <div class="px-6">
                    <h3 class="text-emerald-400 font-black text-xs uppercase tracking-widest leading-none">Jewelry Master Engine</h3>
                    <p class="text-[10px] text-slate-500 uppercase mt-1">v2.1 Precision dot-mapped</p>
                </div>
                <div class="flex gap-4">
                    <x-filament::button wire:click="resetToDefault" color="danger" variant="outline" class="rounded-2xl border-rose-500/20 text-rose-400">
                        Reset Defaults
                    </x-filament::button>
                    <x-filament::button type="submit" color="success" class="rounded-2xl bg-emerald-600 shadow-xl px-10">
                        Synchronize Layout
                    </x-filament::button>
                </div>
            </div>
        </form>
    </div>
</x-filament-panels::page>