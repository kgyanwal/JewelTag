<x-filament-panels::page>
    <form wire:submit.prevent="">
        {{ $this->form }}
    </form>

    <div class="mt-4">
        <x-filament::button color="gray" wire:click="saveCredentials" icon="heroicon-o-bookmark">
            💾 Save Credentials
        </x-filament::button>
    </div>

    <div class="flex gap-3 mt-6">
        <x-filament::button color="success" wire:click="publishTest" icon="heroicon-o-credit-card">
            1. Publish (Charge)
        </x-filament::button>

        <x-filament::button color="info" wire:click="checkStatusTest" icon="heroicon-o-arrow-path">
            2. Check Status
        </x-filament::button>

        <x-filament::button color="danger" wire:click="cancelTest" icon="heroicon-o-x-circle" outlined>
            Cancel
        </x-filament::button>
    </div>

    @if ($lastResponse)
        <div class="mt-6 p-4 bg-white border border-gray-200 rounded-lg shadow-sm overflow-auto">
            <pre class="text-gray-800 text-xs whitespace-pre-wrap">{{ $lastResponse }}</pre>
        </div>
    @endif

    <div class="mt-4 text-sm text-gray-500">
        Current REQ_TXN_ID: <strong>{{ $currentReqTxnId ?? '—' }}</strong>
    </div>
</x-filament-panels::page>