<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Search Form Section --}}
        <x-filament::section>
            <form wire:submit="applyFilters">
                {{ $this->form }}
                
                <div class="flex justify-end mt-4">
                    <x-filament::button 
                        type="submit" 
                        icon="heroicon-m-magnifying-glass" 
                        size="lg"
                        wire:loading.attr="disabled"
                        wire:target="applyFilters"
                    >
                        <span wire:loading.remove wire:target="applyFilters">SEARCH INVENTORY</span>
                        <span wire:loading wire:target="applyFilters">FILTERING...</span>
                    </x-filament::button>
                </div>
            </form>
        </x-filament::section>

        <hr class="border-gray-200 dark:border-white/10" />

        {{-- Results Table Section --}}
        <div class="relative">
            {{-- Optional: Adds a subtle blur when the table is updating --}}
            <div wire:loading.delay wire:target="applyFilters" class="absolute inset-0 z-10 bg-white/50 dark:bg-gray-900/50 backdrop-blur-[1px] rounded-xl"></div>
            
            {{ $this->table }}
        </div>
    </div>

    {{-- 🚀 PRODUCTION ZEBRA PRINT LOGIC --}}
    @push('scripts')
    <script src="{{ asset('js/BrowserPrint-3.0.216.min.js') }}"></script>
    <script>
        window.addEventListener('print-zpl-locally', event => {
            // Delay execution by 200ms to ensure the Filament modal is fully closed
            // This prevents the "Livewire component not found" DOM error
            setTimeout(() => {
                const zplData = event.detail.zpl;

                if (typeof BrowserPrint === 'undefined') {
                    new FilamentNotification()
                        .title('Zebra SDK Missing')
                        .body('Please ensure BrowserPrint-3.0.216.min.js is in your public/js folder.')
                        .danger()
                        .send();
                    return;
                }

                // 1. Find the default printer on the LOCAL machine
                BrowserPrint.getDefaultDevice("printer", function(device) {
                    if (device) {
                        // 2. Send the ZPL data directly to the local printer
                        device.send(zplData, function(success) {
                            new FilamentNotification()
                                .title('Print Successful')
                                .body('Tags sent to Zebra printer.')
                                .success()
                                .send();
                        }, function(error) {
                            alert("Zebra Printer Error: " + error);
                        });
                    } else {
                        // 3. Handle case where Zebra Browser Print app isn't running
                        new FilamentNotification()
                            .title('Printer Not Found')
                            .body('Is the Zebra Browser Print app running on this computer?')
                            .warning()
                            .send();
                    }
                }, function(error) {
                    console.error("Zebra Discovery Error: ", error);
                });
            }, 200);
        });
    </script>
    @endpush
</x-filament-panels::page>