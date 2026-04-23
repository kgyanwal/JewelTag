

<x-filament-panels::page>

    {{-- ── API Key Table ───────────────────────────────────────────────── --}}
    {{ $this->table }}

    {{-- ── One-Time Token Display Modal ───────────────────────────────── --}}
    <x-filament::modal
        id="show-token-modal"
        icon="heroicon-o-shield-check"
        icon-color="success"
        :close-by-clicking-away="false"
        width="xl"
    >
        <x-slot name="heading">
            Save Your New API Key
        </x-slot>

        <x-slot name="description">
            Copy this key and paste it into your CRM or integration now.
            <br>
            <strong class="text-danger-600">It will never be shown again.</strong>
        </x-slot>

        {{-- Token display input ──────────────────────────────────────── --}}
        <div class="mt-4 space-y-2">
            <x-filament::input.wrapper>
                <x-filament::input
                    type="text"
                    readonly
                    :value="$plainTextToken"
                    id="plain-token-input"
                    class="font-mono text-sm text-primary-600 bg-gray-50 cursor-pointer select-all"
                    onclick="copyToken()"
                />
            </x-filament::input.wrapper>

            <p class="text-xs text-gray-500">
                Click the field to copy automatically.
            </p>
        </div>

        {{-- Usage guide ─────────────────────────────────────────────── --}}
        <div class="mt-5 rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600 space-y-2">
            <p class="font-semibold text-gray-800">How to use this key:</p>
            <ol class="list-decimal list-inside space-y-1">
                <li>Copy the key above.</li>
                <li>In your CRM, navigate to <strong>Settings → Integrations → API</strong>.</li>
                <li>Paste it as a <strong>Bearer Token</strong> in the Authorization header.</li>
                <li>
                    Example request:
                    <code class="block mt-1 rounded bg-gray-200 px-2 py-1 font-mono text-xs text-gray-800">
                        GET /api/v1/crm/daily-export?resources=customers
                        <br>
                        Authorization: Bearer &lt;your-key&gt;
                    </code>
                </li>
            </ol>

            <p class="pt-1 text-xs text-gray-500">
                For customers-only sync, append
                <code class="rounded bg-gray-200 px-1 font-mono">?resources=customers</code>
                to any export URL.
            </p>
        </div>

        {{-- Footer actions ───────────────────────────────────────────── --}}
        <x-slot name="footerActions">
            {{--
                wire:click="clearToken" wipes $plainTextToken from Livewire
                state so it's not accessible after the modal is dismissed.
            --}}
            <x-filament::button
                color="gray"
                wire:click="clearToken"
                x-on:click="$dispatch('close-modal', { id: 'show-token-modal' })"
            >
                I've saved my key — Close
            </x-filament::button>
        </x-slot>
    </x-filament::modal>

    {{-- ── Scripts ──────────────────────────────────────────────────────── --}}
    <script>
        /**
         * Listen for the Livewire event dispatched by the PHP action
         * and relay it to Filament's Alpine modal system.
         */
        document.addEventListener('open-token-modal', () => {
            window.dispatchEvent(
                new CustomEvent('open-modal', { detail: { id: 'show-token-modal' } })
            );
        });

        /**
         * Copy the token to the clipboard and show a Filament notification.
         * Falls back to execCommand for older browsers.
         */
        function copyToken() {
            const input = document.getElementById('plain-token-input');
            if (!input) return;

            const text = input.value;

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(() => showCopied());
            } else {
                // Legacy fallback
                input.select();
                document.execCommand('copy');
                showCopied();
            }
        }

        function showCopied() {
            // Filament's JS notification helper
            if (typeof FilamentNotification !== 'undefined') {
                FilamentNotification.make()
                    .title('Copied to clipboard!')
                    .success()
                    .send();
            }
        }
    </script>

</x-filament-panels::page>