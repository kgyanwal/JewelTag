<x-filament-panels::page>
    {{ $this->table }}

    <x-filament::modal id="show-token-modal" icon="heroicon-o-shield-check" icon-color="success">
        <x-slot name="heading">Save Your New API Key</x-slot>
        <x-slot name="description">
            Please copy this API key and paste it into your CRM. <strong>For your security, it will never be shown again.</strong>
        </x-slot>

        <div class="mt-4">
            <x-filament::input.wrapper>
                <x-filament::input
                    type="text"
                    readonly
                    value="{{ $plainTextToken }}"
                    onclick="this.select(); document.execCommand('copy'); FilamentNotification.make().title('Copied!').success().send();"
                    class="font-mono text-primary-600 bg-gray-50 cursor-pointer"
                />
            </x-filament::input.wrapper>
            <p class="mt-2 text-xs text-gray-500">Click the box to copy.</p>
        </div>

        <x-slot name="footerActions">
            <x-filament::button color="gray" x-on:click="isOpen = false">Close</x-filament::button>
        </x-slot>
    </x-filament::modal>

    <script>
        document.addEventListener('open-token-modal', () => {
            window.dispatchEvent(new CustomEvent('open-modal', { detail: { id: 'show-token-modal' } }));
        });
    </script>
</x-filament-panels::page>