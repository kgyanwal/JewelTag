<x-filament-panels::page>

<style>
.faq-root { width: 100%; font-family: 'Inter', sans-serif; padding: 0 24px; }

.faq-hero {
    background: linear-gradient(135deg, #0c4a58 0%, #0e7490 60%, #0891b2 100%);
    border-radius: 20px;
    padding: 56px 48px;
    text-align: center;
    margin-bottom: 32px;
    box-shadow: 0 12px 36px rgba(14,116,144,0.28);
    width: 100%;
}
.faq-hero h1 {
    color: #fff;
    font-size: 34px;
    font-weight: 800;
    margin: 0 0 8px;
    letter-spacing: -0.02em;
}
.faq-hero p {
    color: rgba(255,255,255,0.7);
    font-size: 14px;
    margin: 0 0 24px;
}

.faq-search-wrap {
    max-width: 720px;
    margin: 0 auto;
    position: relative;
}
.faq-search-wrap input {
    width: 100%;
    padding: 16px 16px 16px 48px;
    border-radius: 14px;
    border: none;
    font-size: 16px;
    background: rgba(255,255,255,0.95);
    box-shadow: 0 4px 14px rgba(0,0,0,0.15);
}
.faq-search-wrap input:focus { outline: 2px solid rgba(255,255,255,0.6); }
.faq-search-icon {
    position: absolute; left: 16px; top: 50%; transform: translateY(-50%);
    width: 20px; height: 20px; color: #64748b; pointer-events: none;
}

.faq-cat-row {
    display: flex; flex-wrap: wrap; gap: 10px; justify-content: flex-start;
    margin-bottom: 30px;
}
.faq-cat-pill {
    padding: 9px 18px; border-radius: 999px; font-size: 13px; font-weight: 700;
    cursor: pointer; border: 1.5px solid #e2e8f0; background: #fff; color: #475569;
    transition: all 0.15s;
}
.faq-cat-pill.active { background: #0d9488; color: #fff; border-color: #0d9488; }
.faq-cat-pill:hover:not(.active) { border-color: #0d9488; color: #0d9488; }

.faq-group-title {
    font-size: 13px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase;
    color: #94a3b8; margin: 26px 0 12px 4px;
}
.faq-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
}
@media (max-width: 900px) {
    .faq-grid { grid-template-columns: 1fr; }
}

.faq-item {
    background: #fff; border: 1px solid #e8ecf3; border-radius: 14px;
    overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,0.03);
    align-self: start;
}
.faq-q {
    padding: 18px 22px; cursor: pointer; display: flex; justify-content: space-between;
    align-items: center; gap: 12px; font-size: 15px; font-weight: 600; color: #1e293b;
}
.faq-q:hover { background: #f8fafc; }
.faq-chevron { width: 20px; height: 20px; color: #94a3b8; flex-shrink: 0; transition: transform 0.2s; }
.faq-item.open .faq-chevron { transform: rotate(180deg); }
.faq-a {
    padding: 0 22px; max-height: 0; overflow: hidden; transition: max-height 0.25s ease;
    font-size: 14px; color: #475569; line-height: 1.8;
}
.faq-item.open .faq-a { padding: 0 22px 20px; max-height: 800px; }

.faq-empty {
    text-align: center; padding: 60px 20px; color: #94a3b8;
}
.faq-empty .icon { font-size: 40px; margin-bottom: 10px; }

.faq-footer-card {
    background: #f0fdfa; border: 1.5px dashed #0d9488; border-radius: 16px;
    padding: 22px 26px; margin-top: 32px; text-align: center;
}
.faq-footer-card p { font-size: 14px; color: #0f766e; margin: 0 0 12px; font-weight: 600; }
.faq-footer-btn {
    display: inline-flex; align-items: center; gap: 6px; background: #0d9488; color: #fff;
    padding: 11px 22px; border-radius: 10px; font-size: 13px; font-weight: 700; text-decoration: none;
}
.faq-footer-btn:hover { background: #0f766e; }
</style>

@php
    $faqGroups = $this->getFaqs();
    $categoryCounts = $this->getCategoryCounts();
    $categoryLabels = \App\Models\Faq::getCategoryOptions();
@endphp

<div class="faq-root">

    {{-- HERO + SEARCH --}}
    <div class="faq-hero">
        <h1>👋 How can we help?</h1>
        <p>Search our knowledge base or browse by category below.</p>
        <div class="faq-search-wrap">
            <svg class="faq-search-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
            </svg>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search FAQs... e.g. 'shopify', 'repair number'" />
        </div>
    </div>

    {{-- CATEGORY PILLS --}}
    <div class="faq-cat-row">
        <button wire:click="$set('activeCategory', 'all')" class="faq-cat-pill {{ $activeCategory === 'all' ? 'active' : '' }}">
            All ({{ array_sum($categoryCounts) }})
        </button>
        @foreach($categoryLabels as $key => $label)
            @if(($categoryCounts[$key] ?? 0) > 0)
                <button wire:click="$set('activeCategory', '{{ $key }}')" class="faq-cat-pill {{ $activeCategory === $key ? 'active' : '' }}">
                    {{ $label }} ({{ $categoryCounts[$key] }})
                </button>
            @endif
        @endforeach
    </div>

    {{-- FAQ LIST --}}
    @if($faqGroups->isEmpty())
        <div class="faq-empty">
            <div class="icon">🔍</div>
            <div>No matching FAQs found. Try a different search term, or open a support ticket below.</div>
        </div>
    @else
        @foreach($faqGroups as $category => $items)
            <div class="faq-group-title">{{ $categoryLabels[$category] ?? ucfirst($category) }}</div>

            <div class="faq-grid" x-data="{ openId: null }">
                @foreach($items as $faq)
                    <div class="faq-item" :class="{ 'open': openId === {{ $faq->id }} }">
                        <div class="faq-q"
                             @click="openId = (openId === {{ $faq->id }} ? null : {{ $faq->id }}); $wire.trackView({{ $faq->id }})">
                            <span>{{ $faq->question }}</span>
                            <svg class="faq-chevron" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>
                        <div class="faq-a">{!! $faq->answer !!}</div>
                    </div>
                @endforeach
            </div>
        @endforeach
    @endif

    {{-- STILL NEED HELP --}}
    <div class="faq-footer-card">
        <p>Still need help? Our support team is one click away.</p>
        <a href="{{ \App\Filament\Resources\SupportTicketResource::getUrl('index') }}" class="faq-footer-btn">
            🎧 Open a Support Ticket
        </a>
    </div>

</div>

</x-filament-panels::page>