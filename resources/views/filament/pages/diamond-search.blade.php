<x-filament-panels::page>
<style>
/* ── DIAMOND VAULT — Design System ───────────────────────────── */
:root {
    --dv-midnight:  #0B1929;
    --dv-navy:      #0F2744;
    --dv-sapphire:  #0C4A8C;
    --dv-ice:       #A8D4F5;
    --dv-platinum:  #D8E4F0;
    --dv-gold:      #C9A24B;
    --dv-gold-lt:   #E4CD8E;
    --dv-white:     #F8F9FB;
    --dv-dim:       #6B8CAE;
    --dv-success:   #10b981;
    --dv-border:    rgba(168,212,245,0.18);
}

.dv-wrap {
    background: linear-gradient(160deg, #0B1929 0%, #0F2744 60%, #122952 100%);
    min-height: 100vh;
    border-radius: 18px;
    padding: 0 0 40px;
    position: relative;
    overflow: hidden;
}

/* floating crystal glows */
.dv-wrap::before {
    content:'';
    position:absolute;
    width:600px;height:600px;
    border-radius:50%;
    background: radial-gradient(circle, rgba(12,74,140,0.25) 0%, transparent 70%);
    top:-200px;right:-150px;
    pointer-events:none;
}
.dv-wrap::after {
    content:'';
    position:absolute;
    width:400px;height:400px;
    border-radius:50%;
    background: radial-gradient(circle, rgba(201,162,75,0.12) 0%, transparent 70%);
    bottom:50px;left:-100px;
    pointer-events:none;
}

/* ── HERO HEADER ─────────────────────────────────────────────── */
.dv-hero {
    padding: 36px 40px 28px;
    border-bottom: 1px solid var(--dv-border);
    position: relative;
    z-index: 1;
}
.dv-hero-eyebrow {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .18em;
    text-transform: uppercase;
    color: var(--dv-gold);
    margin-bottom: 6px;
}
.dv-hero-title {
    font-size: clamp(28px, 3vw, 42px);
    font-weight: 900;
    color: var(--dv-white);
    letter-spacing: -0.5px;
    line-height: 1.1;
    margin: 0;
}
.dv-hero-title span {
    background: linear-gradient(90deg, var(--dv-ice), var(--dv-gold-lt));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
.dv-hero-sub {
    font-size: 13px;
    color: var(--dv-dim);
    margin-top: 6px;
}

/* ── STOCK TYPE TABS ─────────────────────────────────────────── */
.dv-tabs {
    display: flex;
    gap: 6px;
    margin-top: 18px;
}
.dv-tab {
    padding: 6px 18px;
    border-radius: 99px;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    border: 1.5px solid var(--dv-border);
    color: var(--dv-dim);
    background: transparent;
    transition: all .2s;
    letter-spacing: .04em;
}
.dv-tab:hover { border-color: var(--dv-ice); color: var(--dv-ice); }
.dv-tab.active {
    background: var(--dv-gold);
    border-color: var(--dv-gold);
    color: var(--dv-midnight);
}

/* ── FILTER BODY ─────────────────────────────────────────────── */
.dv-filters {
    padding: 28px 40px;
    position: relative;
    z-index: 1;
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 28px;
    align-items: start;
}

/* ── SHAPES PANEL ────────────────────────────────────────────── */
.dv-section-label {
    font-size: 10px;
    font-weight: 800;
    letter-spacing: .14em;
    text-transform: uppercase;
    color: var(--dv-gold);
    margin-bottom: 12px;
}
.dv-shapes-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
}
.dv-shape-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 5px;
    padding: 10px 6px;
    border-radius: 10px;
    border: 1.5px solid var(--dv-border);
    background: rgba(255,255,255,0.03);
    cursor: pointer;
    transition: all .2s;
    color: var(--dv-dim);
    font-size: 10px;
    font-weight: 700;
    letter-spacing: .04em;
    text-transform: uppercase;
}
.dv-shape-btn:hover {
    border-color: var(--dv-ice);
    color: var(--dv-ice);
    background: rgba(168,212,245,0.06);
}
.dv-shape-btn.selected {
    border-color: var(--dv-gold);
    background: rgba(201,162,75,0.14);
    color: var(--dv-gold-lt);
}
.dv-shape-icon { font-size: 22px; line-height: 1; }

/* ── RIGHT FILTERS ───────────────────────────────────────────── */
.dv-right { display: flex; flex-direction: column; gap: 22px; }

.dv-input-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}
.dv-input-group { display: flex; flex-direction: column; gap: 5px; }
.dv-input-label { font-size: 10px; font-weight: 700; letter-spacing:.1em; text-transform:uppercase; color: var(--dv-dim); }
.dv-input {
    background: rgba(255,255,255,0.05);
    border: 1.5px solid var(--dv-border);
    border-radius: 8px;
    padding: 9px 12px;
    color: var(--dv-white);
    font-size: 13px;
    font-weight: 600;
    outline: none;
    transition: border-color .2s;
    width: 100%;
}
.dv-input:focus { border-color: var(--dv-ice); }
.dv-input::placeholder { color: rgba(107,140,174,0.5); }

/* ── SLIDERS ─────────────────────────────────────────────────── */
.dv-slider-wrap { position: relative; }
.dv-slider-track {
    position: relative;
    height: 4px;
    background: rgba(255,255,255,0.1);
    border-radius: 99px;
    margin: 20px 0 8px;
}
.dv-slider-fill {
    position: absolute;
    top: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--dv-sapphire), var(--dv-ice));
    border-radius: 99px;
    pointer-events: none;
}
.dv-range {
    position: absolute;
    top: -8px;
    width: 100%;
    height: 20px;
    background: transparent;
    -webkit-appearance: none;
    appearance: none;
    cursor: pointer;
    pointer-events: none;
}
.dv-range::-webkit-slider-thumb {
    -webkit-appearance: none;
    width: 18px; height: 18px;
    border-radius: 50%;
    background: var(--dv-gold);
    border: 2.5px solid var(--dv-midnight);
    box-shadow: 0 0 0 3px rgba(201,162,75,0.3);
    pointer-events: all;
    cursor: grab;
    transition: box-shadow .2s;
}
.dv-range::-webkit-slider-thumb:active { cursor: grabbing; box-shadow: 0 0 0 6px rgba(201,162,75,0.25); }
.dv-range::-moz-range-thumb {
    width: 18px; height: 18px;
    border-radius: 50%;
    background: var(--dv-gold);
    border: 2.5px solid var(--dv-midnight);
    pointer-events: all;
    cursor: grab;
}
.dv-slider-labels {
    display: flex;
    justify-content: space-between;
    font-size: 9px;
    color: rgba(107,140,174,0.6);
    font-weight: 600;
    letter-spacing: .06em;
    margin-top: 4px;
}
.dv-slider-values {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    color: var(--dv-ice);
    font-weight: 800;
    margin-bottom: 4px;
}

/* ── ACTIONS ─────────────────────────────────────────────────── */
.dv-actions {
    display: flex;
    gap: 10px;
    padding: 0 40px 28px;
    position: relative;
    z-index: 1;
}
.dv-btn-search {
    padding: 12px 36px;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 800;
    letter-spacing: .06em;
    background: linear-gradient(135deg, var(--dv-gold), #a07820);
    color: var(--dv-midnight);
    border: none;
    cursor: pointer;
    transition: all .2s;
    box-shadow: 0 4px 20px rgba(201,162,75,0.35);
}
.dv-btn-search:hover { transform: translateY(-1px); box-shadow: 0 6px 28px rgba(201,162,75,0.45); }
.dv-btn-clear {
    padding: 12px 24px;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 700;
    background: transparent;
    color: var(--dv-dim);
    border: 1.5px solid var(--dv-border);
    cursor: pointer;
    transition: all .2s;
}
.dv-btn-clear:hover { border-color: var(--dv-ice); color: var(--dv-white); }

/* ── RESULTS ─────────────────────────────────────────────────── */
.dv-results {
    margin: 0 40px;
    position: relative;
    z-index: 1;
}
.dv-results-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 14px;
}
.dv-results-count {
    font-size: 13px;
    color: var(--dv-dim);
    font-weight: 600;
}
.dv-results-count strong { color: var(--dv-ice); font-weight: 800; }

.dv-table-wrap {
    border: 1px solid var(--dv-border);
    border-radius: 14px;
    overflow: hidden;
}
.dv-table { width: 100%; border-collapse: collapse; }
.dv-table thead tr {
    background: rgba(12,74,140,0.4);
    border-bottom: 1px solid var(--dv-border);
}
.dv-table th {
    padding: 12px 16px;
    text-align: left;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: .12em;
    text-transform: uppercase;
    color: var(--dv-gold);
    cursor: pointer;
    white-space: nowrap;
    user-select: none;
    transition: color .15s;
}
.dv-table th:hover { color: var(--dv-gold-lt); }
.dv-table th.sorted { color: var(--dv-ice); }
.dv-table tbody tr {
    border-bottom: 1px solid rgba(168,212,245,0.07);
    transition: background .15s;
}
.dv-table tbody tr:hover { background: rgba(168,212,245,0.05); }
.dv-table tbody tr:last-child { border-bottom: none; }
.dv-table td { padding: 13px 16px; font-size: 12px; color: var(--dv-platinum); vertical-align: middle; }

.dv-stock-no {
    font-family: 'Monaco','Consolas',monospace;
    font-size: 11px;
    font-weight: 700;
    color: var(--dv-ice);
    background: rgba(12,74,140,0.3);
    padding: 3px 8px;
    border-radius: 5px;
    border: 1px solid rgba(168,212,245,0.2);
}
.dv-carat { font-weight: 800; color: var(--dv-white); font-size: 14px; }
.dv-price { font-weight: 900; color: var(--dv-success); font-size: 13px; }
.dv-price-ct { font-size: 10px; color: var(--dv-dim); display: block; margin-top: 1px; }

.dv-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 99px;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: .04em;
}
.dv-badge-cut    { background: rgba(168,212,245,0.1); color: var(--dv-ice); border: 1px solid rgba(168,212,245,0.2); }
.dv-badge-clarity{ background: rgba(255,255,255,0.06); color: var(--dv-platinum); border: 1px solid rgba(255,255,255,0.1); }
.dv-badge-colour { background: rgba(201,162,75,0.1); color: var(--dv-gold-lt); border: 1px solid rgba(201,162,75,0.2); }
.dv-badge-memo   { background: rgba(249,115,22,0.12); color: #fdba74; border: 1px solid rgba(249,115,22,0.25); }
.dv-badge-stock  { background: rgba(16,185,129,0.1); color: #6ee7b7; border: 1px solid rgba(16,185,129,0.2); }

.dv-shape-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 11px;
    font-weight: 700;
    color: var(--dv-platinum);
}

.dv-action-btn {
    padding: 5px 14px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
    background: rgba(201,162,75,0.15);
    color: var(--dv-gold-lt);
    border: 1px solid rgba(201,162,75,0.3);
    cursor: pointer;
    transition: all .2s;
    text-decoration: none;
    display: inline-block;
}
.dv-action-btn:hover { background: rgba(201,162,75,0.28); }

/* ── EMPTY STATE ─────────────────────────────────────────────── */
.dv-empty {
    text-align: center;
    padding: 60px 20px;
    color: var(--dv-dim);
}
.dv-empty-gem { font-size: 52px; margin-bottom: 14px; opacity: .4; }
.dv-empty-title { font-size: 16px; font-weight: 700; color: var(--dv-platinum); margin-bottom: 6px; }
.dv-empty-sub { font-size: 13px; }

/* ── GLASS PANEL ─────────────────────────────────────────────── */
.dv-glass {
    background: rgba(255,255,255,0.03);
    border: 1px solid var(--dv-border);
    border-radius: 14px;
    padding: 20px;
    backdrop-filter: blur(8px);
}
</style>

<div class="dv-wrap" wire:ignore.self>

    {{-- ── HERO ─────────────────────────────────────────────── --}}
    <div class="dv-hero">
        <div class="dv-hero-eyebrow">💎 JewelTag</div>
        <h1 class="dv-hero-title">Diamond <span>Vault Search</span></h1>
        <p class="dv-hero-sub">Search, filter, and discover your diamond inventory — by shape, cut, colour, clarity, and price.</p>

        <div class="dv-tabs">
            <button class="dv-tab {{ $stockType === 'in_house' ? 'active' : '' }}"
                wire:click="$set('stockType','in_house')">In-House Stock</button>
            <button class="dv-tab {{ $stockType === 'memo' ? 'active' : '' }}"
                wire:click="$set('stockType','memo')">Memo</button>
            <button class="dv-tab {{ $stockType === 'all' ? 'active' : '' }}"
                wire:click="$set('stockType','all')">All</button>
        </div>
    </div>

    {{-- ── FILTERS ──────────────────────────────────────────── --}}
    <div class="dv-filters">

        {{-- Shapes --}}
        <div class="dv-glass">
            <div class="dv-section-label">Shape</div>
            <div class="dv-shapes-grid">
                @php
                    $shapeIcons = [
                        'Round'    => '💎', 'Princess' => '◻️', 'Emerald' => '🔷',
                        'Asscher'  => '⬛', 'Marquise' => '🫧', 'Oval'    => '🥚',
                        'Radiant'  => '✦',  'Pear'     => '🍐', 'Heart'   => '♥️',
                        'Cushion'  => '🟦', 'Trillion' => '🔺', 'Other'   => '✦',
                    ];
                @endphp
                @foreach($this::$SHAPES as $shape)
                    <button
                        class="dv-shape-btn {{ in_array($shape, $selectedShapes) ? 'selected' : '' }}"
                        wire:click="toggleShape('{{ $shape }}')">
                        <span class="dv-shape-icon">{{ $shapeIcons[$shape] ?? '💠' }}</span>
                        {{ $shape }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Right filters --}}
        <div class="dv-right">

            {{-- Price & Carat --}}
            <div class="dv-glass">
                <div class="dv-section-label">Price & Carat</div>
                <div class="dv-input-row">
                    <div class="dv-input-group">
                        <label class="dv-input-label">Price Min ($)</label>
                        <input class="dv-input" type="number" placeholder="0" wire:model.blur="priceMin">
                    </div>
                    <div class="dv-input-group">
                        <label class="dv-input-label">Price Max ($)</label>
                        <input class="dv-input" type="number" placeholder="Any" wire:model.blur="priceMax">
                    </div>
                    <div class="dv-input-group">
                        <label class="dv-input-label">Carat Min</label>
                        <input class="dv-input" type="number" step="0.01" placeholder="0.00" wire:model.blur="caratMin">
                    </div>
                    <div class="dv-input-group">
                        <label class="dv-input-label">Carat Max</label>
                        <input class="dv-input" type="number" step="0.01" placeholder="Any" wire:model.blur="caratMax">
                    </div>
                </div>
            </div>

            {{-- Cut Slider --}}
            <div class="dv-glass">
                <div class="dv-section-label">Cut Quality</div>
                <div class="dv-slider-values">
                    <span>{{ $this::$CUT_LABELS[$cutMin] }}</span>
                    <span>{{ $this::$CUT_LABELS[$cutMax] }}</span>
                </div>
                <div class="dv-slider-wrap" style="position:relative;height:20px;margin:0 0 4px;">
                    <div class="dv-slider-track">
                        <div class="dv-slider-fill" style="left:{{ ($cutMin/3)*100 }}%;right:{{ ((3-$cutMax)/3)*100 }}%;"></div>
                    </div>
                    <input type="range" class="dv-range" min="0" max="3" wire:model.live="cutMin"
                        style="z-index:{{ $cutMin > $cutMax-1 ? 5 : 3 }};">
                    <input type="range" class="dv-range" min="0" max="3" wire:model.live="cutMax"
                        style="z-index:{{ $cutMax <= $cutMin+1 ? 5 : 4 }};">
                </div>
                <div class="dv-slider-labels">
                    @foreach($this::$CUT_LABELS as $l)<span>{{ $l }}</span>@endforeach
                </div>
            </div>

            {{-- Clarity Slider --}}
            <div class="dv-glass">
                <div class="dv-section-label">Clarity</div>
                <div class="dv-slider-values">
                    <span>{{ $this::$CLARITY_LABELS[$clarityMin] }}</span>
                    <span>{{ $this::$CLARITY_LABELS[$clarityMax] }}</span>
                </div>
                <div class="dv-slider-wrap" style="position:relative;height:20px;margin:0 0 4px;">
                    <div class="dv-slider-track">
                        <div class="dv-slider-fill" style="left:{{ ($clarityMin/14)*100 }}%;right:{{ ((14-$clarityMax)/14)*100 }}%;"></div>
                    </div>
                    <input type="range" class="dv-range" min="0" max="14" wire:model.live="clarityMin"
                        style="z-index:3;">
                    <input type="range" class="dv-range" min="0" max="14" wire:model.live="clarityMax"
                        style="z-index:4;">
                </div>
                <div class="dv-slider-labels">
                    @foreach($this::$CLARITY_LABELS as $l)<span>{{ $l }}</span>@endforeach
                </div>
            </div>

            {{-- Colour Slider --}}
            <div class="dv-glass">
                <div class="dv-section-label">Colour Grade</div>
                <div class="dv-slider-values">
                    <span>{{ $this::$COLOUR_LABELS[$colourMin] }}</span>
                    <span>{{ $this::$COLOUR_LABELS[$colourMax] }}</span>
                </div>
                <div class="dv-slider-wrap" style="position:relative;height:20px;margin:0 0 4px;">
                    <div class="dv-slider-track">
                        <div class="dv-slider-fill" style="left:{{ ($colourMin/22)*100 }}%;right:{{ ((22-$colourMax)/22)*100 }}%;"></div>
                    </div>
                    <input type="range" class="dv-range" min="0" max="22" wire:model.live="colourMin"
                        style="z-index:3;">
                    <input type="range" class="dv-range" min="0" max="22" wire:model.live="colourMax"
                        style="z-index:4;">
                </div>
                <div class="dv-slider-labels">
                    @foreach($this::$COLOUR_LABELS as $l)<span style="font-size:8px;">{{ $l }}</span>@endforeach
                </div>
            </div>

        </div>{{-- end dv-right --}}
    </div>{{-- end dv-filters --}}

    {{-- ── SEARCH ACTIONS ───────────────────────────────────── --}}
    <div class="dv-actions">
        <button class="dv-btn-search" wire:click="search" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="search">🔍 Search Diamonds</span>
            <span wire:loading wire:target="search">⏳ Searching...</span>
        </button>
        <button class="dv-btn-clear" wire:click="clearSearch">✕ Clear</button>
    </div>

    {{-- ── RESULTS ──────────────────────────────────────────── --}}
    @if($searched)
    <div class="dv-results">
        <div class="dv-results-header">
            <div class="dv-results-count">
                @if(count($results) > 0)
                    Found <strong>{{ count($results) }}</strong> diamond{{ count($results) !== 1 ? 's' : '' }}
                    @if(count($results) === 200) <span style="color:var(--dv-gold);"> (showing first 200)</span> @endif
                @else
                    No diamonds matched your search
                @endif
            </div>
        </div>

        @if(count($results) > 0)
        <div class="dv-table-wrap">
            <table class="dv-table">
                <thead>
                    <tr>
                        <th wire:click="sortResults('barcode')" class="{{ $sortBy==='barcode' ? 'sorted' : '' }}">
                            Stock # {{ $sortBy==='barcode' ? ($sortDir==='asc'?'↑':'↓') : '' }}
                        </th>
                        <th wire:click="sortResults('shape')">
                            Shape {{ $sortBy==='shape' ? ($sortDir==='asc'?'↑':'↓') : '' }}
                        </th>
                        <th wire:click="sortResults('diamond_weight')" class="{{ $sortBy==='diamond_weight' ? 'sorted' : '' }}">
                            Carats {{ $sortBy==='diamond_weight' ? ($sortDir==='asc'?'↑':'↓') : '' }}
                        </th>
                        <th wire:click="sortResults('cut')">
                            Cut {{ $sortBy==='cut' ? ($sortDir==='asc'?'↑':'↓') : '' }}
                        </th>
                        <th wire:click="sortResults('clarity')">
                            Clarity {{ $sortBy==='clarity' ? ($sortDir==='asc'?'↑':'↓') : '' }}
                        </th>
                        <th wire:click="sortResults('colour')">
                            Colour {{ $sortBy==='colour' ? ($sortDir==='asc'?'↑':'↓') : '' }}
                        </th>
                        <th>Location</th>
                        <th>Status</th>
                        <th wire:click="sortResults('retail_price')" class="{{ $sortBy==='retail_price' ? 'sorted' : '' }}">
                            Price {{ $sortBy==='retail_price' ? ($sortDir==='asc'?'↑':'↓') : '' }}
                        </th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($results as $row)
                    @php $r = (object)$row; @endphp
                    <tr>
                        <td><span class="dv-stock-no">{{ $r->barcode ?? '—' }}</span></td>
                        <td>
                            <span class="dv-shape-pill">
                                {{ $shapeIcons[$r->shape ?? ''] ?? '💠' }} {{ $r->shape ?? '—' }}
                            </span>
                        </td>
                        <td><span class="dv-carat">{{ number_format(floatval($r->diamond_weight ?? 0), 2) }}</span> <span style="font-size:10px;color:var(--dv-dim);">ct</span></td>
                        <td><span class="dv-badge dv-badge-cut">{{ $r->cut ?? '—' }}</span></td>
                        <td><span class="dv-badge dv-badge-clarity">{{ $r->clarity ?? '—' }}</span></td>
                        <td><span class="dv-badge dv-badge-colour">{{ $r->color ?? '—' }}</span></td>
                        <td style="color:var(--dv-dim);font-size:11px;">
                            {{ $r->sub_department ?? $r->certificate_agency ?? '—' }}
                            @if(!empty($r->certificate_number))
                                <span style="font-size:9px;display:block;color:rgba(107,140,174,0.5);">Cert: {{ $r->certificate_number }}</span>
                            @endif
                            @if(!empty($r->is_lab_grown))
                                <span class="dv-badge" style="background:rgba(16,185,129,0.1);color:#6ee7b7;border:1px solid rgba(16,185,129,0.2);font-size:9px;">Lab</span>
                            @endif
                        </td>
                        <td>
                            @if(($r->status ?? '') === 'memo')
                                <span class="dv-badge dv-badge-memo">Memo</span>
                            @else
                                <span class="dv-badge dv-badge-stock">In Stock</span>
                            @endif
                        </td>
                        <td>
                            <span class="dv-price">${{ number_format(floatval($r->retail_price ?? 0), 2) }}</span>
                            @if(floatval($r->diamond_weight ?? 0) > 0 && floatval($r->retail_price ?? 0) > 0)
                                <span class="dv-price-ct">${{ number_format(floatval($r->retail_price) / floatval($r->diamond_weight), 0) }}/ct</span>
                            @endif
                        </td>
                        <td>
                            <a class="dv-action-btn"
                               href="{{ route('filament.admin.resources.product-items.edit', ['record' => $r->id]) }}">
                                View
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="dv-table-wrap">
            <div class="dv-empty">
                <div class="dv-empty-gem">💎</div>
                <div class="dv-empty-title">No diamonds found</div>
                <div class="dv-empty-sub">Try adjusting your filters — broaden the Cut, Clarity, or Colour range.</div>
            </div>
        </div>
        @endif
    </div>
    @endif

</div>
</x-filament-panels::page>