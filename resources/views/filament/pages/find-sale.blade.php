<x-filament-panels::page>

{{-- ── AI SMART SEARCH ─────────────────────────────────────────── --}}
<style>
.ai-search-wrap {
    background: linear-gradient(135deg, #0B1929 0%, #0F2744 100%);
    border: 1.5px solid rgba(201,162,75,0.35);
    border-radius: 16px;
    padding: 20px 24px;
    margin-bottom: 20px;
    position: relative;
    overflow: hidden;
}
.ai-label {
    font-size: 11px; font-weight: 800; letter-spacing: .14em;
    text-transform: uppercase; color: #C9A24B;
    margin-bottom: 10px; display: flex; align-items: center; gap: 8px;
}
.ai-input-row { display: flex; gap: 10px; align-items: center; }
.ai-input {
    flex: 1;
    background: rgba(255,255,255,0.06);
    border: 1.5px solid rgba(168,212,245,0.2);
    border-radius: 10px; padding: 12px 16px;
    color: #F8F9FB; font-size: 14px; font-weight: 500;
    outline: none; transition: border-color .2s, box-shadow .2s; width: 100%;
}
.ai-input:focus { border-color: #C9A24B; box-shadow: 0 0 0 3px rgba(201,162,75,0.2); }
.ai-input::placeholder { color: rgba(107,140,174,0.5); }
.ai-btn {
    padding: 12px 24px; border-radius: 10px; font-size: 13px; font-weight: 800;
    background: linear-gradient(135deg, #C9A24B, #a07820);
    color: #0B1929; border: none; cursor: pointer; white-space: nowrap;
    transition: all .2s; box-shadow: 0 4px 14px rgba(201,162,75,0.3);
}
.ai-btn:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(201,162,75,0.4); }
.ai-btn:disabled { opacity: .6; cursor: not-allowed; transform: none; }
.ai-clear-btn {
    padding: 12px 16px; border-radius: 10px; font-size: 13px; font-weight: 700;
    background: transparent; color: rgba(107,140,174,0.7);
    border: 1.5px solid rgba(107,140,174,0.2); cursor: pointer; transition: all .2s;
}
.ai-clear-btn:hover { border-color: rgba(168,212,245,0.4); color: #A8D4F5; }
.ai-result { margin-top: 12px; padding: 10px 14px; border-radius: 8px; font-size: 12px; font-weight: 600; line-height: 1.6; }
.ai-result.done { background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.25); color: #6ee7b7; }
.ai-result.error { background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.25); color: #fca5a5; }
.ai-examples { margin-top: 10px; display: flex; flex-wrap: wrap; gap: 6px; }
.ai-example-chip {
    padding: 4px 12px; border-radius: 99px; font-size: 11px; font-weight: 600;
    background: rgba(255,255,255,0.04); border: 1px solid rgba(168,212,245,0.15);
    color: rgba(168,212,245,0.6); cursor: pointer; transition: all .15s;
}
.ai-example-chip:hover { background: rgba(201,162,75,0.12); border-color: rgba(201,162,75,0.35); color: #E4CD8E; }
.ai-dots span {
    display: inline-block; width: 6px; height: 6px; border-radius: 50%;
    background: #C9A24B; animation: ai-bounce 1.2s ease-in-out infinite; margin: 0 2px;
}
.ai-dots span:nth-child(2) { animation-delay: .2s; }
.ai-dots span:nth-child(3) { animation-delay: .4s; }
@keyframes ai-bounce {
    0%, 80%, 100% { transform: translateY(0); opacity: .4; }
    40% { transform: translateY(-6px); opacity: 1; }
}
</style>

<div class="ai-search-wrap">
    <div class="ai-label">
        ✦ AI Smart Search  
    </div>
    <div class="ai-input-row">
        <input
            class="ai-input"
            type="text"
            placeholder='Try: "Anthony sales last week over $1000" or "unpaid laybuy this month"'
            wire:model="aiQuery"
            wire:keydown.enter="runAiSearch"
            @if($aiStatus === 'thinking') disabled @endif
        >
        <button class="ai-btn" wire:click="runAiSearch" wire:loading.attr="disabled" wire:target="runAiSearch">
            <span wire:loading.remove wire:target="runAiSearch">✦ Search</span>
            <span wire:loading wire:target="runAiSearch">Working...</span>
        </button>
        @if($aiSearchActive)
            <button class="ai-clear-btn" wire:click="clearAiSearch">✕ Clear</button>
        @endif
    </div>

    @if($aiStatus === 'thinking')
    <div style="display:flex;align-items:center;gap:10px;margin-top:12px;font-size:13px;color:#A8D4F5;font-weight:600;">
        <div class="ai-dots"><span></span><span></span><span></span></div>
        Gemini is analysing your query...
    </div>
    @endif

    @if($aiInterpretation && $aiStatus !== 'thinking')
    <div class="ai-result {{ $aiStatus }}">{!! $aiInterpretation !!}</div>
    @endif

    @if(!$aiSearchActive && $aiStatus !== 'thinking')
    <div class="ai-examples">
        @foreach(["Anthony's sales this week", "Unpaid laybuy last 30 days", "Cash sales over $2000 this month", "Diamond ring repairs by Javier", "Unpaid balances this month", "All refunds last month"] as $example)
            <button class="ai-example-chip" wire:click="$set('aiQuery', '{{ $example }}')">{{ $example }}</button>
        @endforeach
    </div>
    @endif
</div>

{{-- ── DIVIDER ──────────────────────────────────────────────────── --}}
<div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
    <div style="flex:1;height:1px;background:rgba(11,61,60,0.1);"></div>
    <span style="font-size:11px;font-weight:700;color:#94a3b8;letter-spacing:.08em;text-transform:uppercase;">or use manual filters below</span>
    <div style="flex:1;height:1px;background:rgba(11,61,60,0.1);"></div>
</div>

{{-- ── YOUR EXISTING CONTENT — untouched ───────────────────────── --}}
    <div class="space-y-6">
        <div class="p-6 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm">
            <div>
                {{ $this->form }}
                <div class="flex justify-end mt-4 gap-x-3">
                    <x-filament::button color="gray" wire:click="resetFilters" variant="outline" icon="heroicon-m-arrow-path">
                        Reset Filters
                    </x-filament::button>
                    <x-filament::button wire:click="$refresh" size="lg" icon="heroicon-m-magnifying-glass" class="px-8">
                        SEARCH SALES
                    </x-filament::button>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden shadow-sm">
            {{ $this->table }}
        </div>
    </div>

</x-filament-panels::page>