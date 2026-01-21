<x-filament-panels::page>
    <div class="flex flex-col items-center justify-center min-h-[50vh] space-y-4">
        <div class="p-8 bg-white shadow-sm rounded-xl border border-gray-200 dark:bg-gray-900 dark:border-gray-800 w-full max-w-md">
            <div class="text-center mb-6">
                <h1 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
                    Security Check
                </h1>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Please enter your 4-digit POS PIN to unlock the station.
                </p>
            </div>

            <form wire:submit="verify" class="space-y-6">
                {{ $this->form }}

                <x-filament::button type="submit" class="w-full">
                    Verify & Unlock
                </x-filament::button>
            </form>

            <div class="text-center mt-6">
                <x-filament::link 
                    href="{{ filament()->getLogoutUrl() }}" 
                    color="danger" 
                    class="text-sm cursor-pointer"
                >
                    Log out of session
                </x-filament::link>
            </div>
        </div>
    </div>
</x-filament-panels::page>