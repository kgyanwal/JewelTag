<x-filament-panels::page>
    <div class="space-y-6">
        {{-- 1. Store Logo --}}
        @php
            $store = auth()->user()->store;
        @endphp
        
        <div class="flex justify-center p-6 bg-white rounded-xl shadow-sm border border-gray-200">
            @if($store && $store->logo_path)
                <img src="{{ asset('storage/' . $store->logo_path) }}" alt="Store Logo" class="h-24 object-contain">
            @else
                <div class="text-gray-400 italic">No Store Logo Uploaded</div>
            @endif
        </div>

        {{-- 🔹 DO NOT call widgets here. Filament's Page class does it automatically! --}}
    </div>
</x-filament-panels::page>