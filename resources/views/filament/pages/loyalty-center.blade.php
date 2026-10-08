<x-filament-panels::page>
@php
    $k       = $this->kpis();
    $tiers   = \App\Filament\Pages\LoyaltyCenter::TIERS;
    $ppd     = \App\Filament\Pages\LoyaltyCenter::POINTS_PER_DOLLAR;
    $canEdit = $this->canManage();
    $loyaltyOn = $this->loyaltyOn();
    $creditOn  = $this->creditOn();
    $tabList = [];
    if ($loyaltyOn) { $tabList['overview'] = 'Overview'; $tabList['members'] = 'Members'; }
    if ($creditOn)  { $tabList['credit'] = 'Store Credit'; }
    $money   = fn($v) => '$' . number_format((float) $v, 2);
    $totalC  = max(1, array_sum($k['tier_counts']));
    $tierStyle = function ($slug) {
        $map = [
            'standard' => ['#94a3b8', 'rgba(148,163,184,.14)'],
            'silver'   => ['#d7dee8', 'rgba(215,222,232,.16)'],
            'gold'     => ['#E4CD8E', 'rgba(201,162,75,.20)'],
        ];
        return $map[$slug] ?? ['#7dd3c0', 'rgba(125,211,192,.16)'];
    };
@endphp

<style>
:root{
    --lv-deep:#07292A; --lv-pine:#0B3D3C; --lv-sage:#3D6B63; --lv-gold:#C9A24B; --lv-gold-lt:#E4CD8E;
    --lv-ivory:#F8F6F1; --lv-dim:#8DB0A8; --lv-line:rgba(228,205,142,.18); --lv-green:#34d399; --lv-red:#f87171;
}
.lv-wrap{background:linear-gradient(160deg,#07292A 0%,#0B3D3C 55%,#0e4a47 100%);border-radius:18px;padding:0 0 40px;position:relative;overflow:hidden;min-height:80vh;color:var(--lv-ivory)}
.lv-wrap::before{content:'';position:absolute;width:560px;height:560px;border-radius:50%;background:radial-gradient(circle,rgba(201,162,75,.16),transparent 70%);top:-220px;right:-140px;pointer-events:none}
.lv-wrap::after{content:'';position:absolute;width:420px;height:420px;border-radius:50%;background:radial-gradient(circle,rgba(61,107,99,.35),transparent 70%);bottom:40px;left:-120px;pointer-events:none}
.lv-wrap *{font-family:'Inter',sans-serif}
.lv-hero{padding:34px 40px 22px;border-bottom:1px solid var(--lv-line);position:relative;z-index:1}
.lv-eyebrow{font-size:11px;font-weight:700;letter-spacing:.18em;text-transform:uppercase;color:var(--lv-gold)}
.lv-title{font-family:'Fraunces',serif!important;font-size:clamp(28px,3vw,40px);font-weight:700;margin:6px 0 0;color:var(--lv-ivory)!important;letter-spacing:-.3px}
.lv-title span{background:linear-gradient(90deg,var(--lv-gold-lt),var(--lv-gold));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.lv-sub{font-size:13px;color:var(--lv-dim);margin-top:6px}
.lv-tabs{display:flex;gap:6px;margin-top:18px;flex-wrap:wrap}
.lv-tab{padding:7px 18px;border-radius:99px;font-size:12px;font-weight:700;cursor:pointer;border:1.5px solid var(--lv-line);color:var(--lv-dim);background:transparent;transition:all .2s;letter-spacing:.04em}
.lv-tab:hover{border-color:var(--lv-gold-lt);color:var(--lv-gold-lt)}
.lv-tab.on{background:var(--lv-gold);border-color:var(--lv-gold);color:var(--lv-deep)}
.lv-body{padding:26px 40px;position:relative;z-index:1}
.lv-kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:22px}
.lv-kpi{background:rgba(255,255,255,.04);border:1px solid var(--lv-line);border-radius:14px;padding:18px 20px;backdrop-filter:blur(8px);transition:transform .2s,border-color .2s}
.lv-kpi:hover{transform:translateY(-2px);border-color:rgba(228,205,142,.4)}
.lv-kpi-l{font-size:10px;font-weight:800;letter-spacing:.14em;text-transform:uppercase;color:var(--lv-gold)}
.lv-kpi-v{font-family:'Fraunces',serif!important;font-size:30px;font-weight:700;margin-top:8px;line-height:1;color:var(--lv-ivory)}
.lv-kpi-s{font-size:11px;color:var(--lv-dim);margin-top:8px}
.lv-grid2{display:grid;grid-template-columns:1.1fr 1fr;gap:16px}
.lv-glass{background:rgba(255,255,255,.035);border:1px solid var(--lv-line);border-radius:14px;padding:20px;backdrop-filter:blur(8px)}
.lv-sec{font-size:10px;font-weight:800;letter-spacing:.14em;text-transform:uppercase;color:var(--lv-gold);margin-bottom:14px}
.lv-tier-row{display:flex;align-items:center;gap:12px;margin-bottom:12px}
.lv-tier-row:last-child{margin-bottom:0}
.lv-bar{flex:1;height:8px;background:rgba(255,255,255,.08);border-radius:99px;overflow:hidden}
.lv-bar>i{display:block;height:100%;border-radius:99px;transition:width .5s}
.lv-badge{display:inline-flex;align-items:center;gap:5px;padding:3px 11px;border-radius:99px;font-size:10px;font-weight:800;letter-spacing:.06em;text-transform:uppercase;border:1px solid}
.lv-top{display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid rgba(228,205,142,.08);cursor:pointer;transition:background .15s}
.lv-top:last-child{border-bottom:none}.lv-top:hover{background:rgba(228,205,142,.05)}
.lv-rank{width:26px;height:26px;border-radius:50%;background:rgba(201,162,75,.16);color:var(--lv-gold-lt);font-size:11px;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.lv-toolbar{display:flex;gap:10px;margin-bottom:14px;flex-wrap:wrap;align-items:center}
.lv-input{background:rgba(255,255,255,.06);border:1.5px solid var(--lv-line);border-radius:10px;padding:10px 14px;color:var(--lv-ivory)!important;font-size:13px;font-weight:600;outline:none;min-width:280px;transition:border-color .2s}
.lv-input:focus{border-color:var(--lv-gold-lt)}.lv-input::placeholder{color:rgba(141,176,168,.6)}
.lv-chip{padding:6px 14px;border-radius:99px;font-size:11px;font-weight:700;cursor:pointer;border:1.5px solid var(--lv-line);color:var(--lv-dim);background:transparent;transition:all .2s}
.lv-chip:hover{color:var(--lv-gold-lt);border-color:var(--lv-gold-lt)}.lv-chip.on{background:rgba(201,162,75,.2);border-color:var(--lv-gold);color:var(--lv-gold-lt)}
.lv-tw{border:1px solid var(--lv-line);border-radius:14px;overflow:hidden}
.lv-table{width:100%;border-collapse:collapse}
.lv-table thead tr{background:rgba(7,41,42,.7);border-bottom:1px solid var(--lv-line)}
.lv-table th{padding:12px 16px;text-align:left;font-size:10px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:var(--lv-gold);white-space:nowrap}
.lv-table tbody tr{border-bottom:1px solid rgba(228,205,142,.07);transition:background .15s}
.lv-table tbody tr:hover{background:rgba(228,205,142,.05)}.lv-table tbody tr:last-child{border-bottom:none}
.lv-table td{padding:13px 16px;font-size:12px;color:#d9e6e2;vertical-align:middle}
.lv-name{font-weight:700;color:var(--lv-ivory);font-size:13px}.lv-mut{font-size:11px;color:var(--lv-dim)}
.lv-pts{font-weight:800;color:var(--lv-gold-lt);font-size:14px}.lv-cr{font-weight:800;color:var(--lv-green)}
.lv-prog{min-width:130px}.lv-prog .lv-bar{height:6px;margin-bottom:4px}
.lv-btn{padding:6px 14px;border-radius:8px;font-size:11px;font-weight:700;background:rgba(201,162,75,.16);color:var(--lv-gold-lt);border:1px solid rgba(201,162,75,.32);cursor:pointer;transition:all .2s}
.lv-btn:hover{background:rgba(201,162,75,.3)}
.lv-primary{padding:11px 22px;border-radius:10px;font-size:13px;font-weight:800;background:linear-gradient(135deg,var(--lv-gold),#a07820);color:var(--lv-deep);border:none;cursor:pointer;box-shadow:0 4px 18px rgba(201,162,75,.3);transition:all .2s;width:100%}
.lv-primary:hover{transform:translateY(-1px);box-shadow:0 6px 24px rgba(201,162,75,.42)}
.lv-more{margin:16px auto 0;display:block}
.lv-empty{text-align:center;padding:54px 20px;color:var(--lv-dim)}.lv-empty b{display:block;color:var(--lv-ivory);font-size:15px;margin-bottom:6px}
.pos{color:var(--lv-green);font-weight:800}.neg{color:var(--lv-red);font-weight:800}
/* drawer */
.lv-ov{position:fixed;inset:0;background:rgba(3,18,18,.65);backdrop-filter:blur(3px);z-index:60}
.lv-drawer{position:fixed;top:0;right:0;bottom:0;width:min(520px,100vw);background:linear-gradient(170deg,#07292A,#0B3D3C);border-left:2px solid var(--lv-gold);z-index:61;overflow-y:auto;padding:26px 26px 40px;box-shadow:-20px 0 60px rgba(0,0,0,.4);animation:lvin .22s cubic-bezier(.16,1,.3,1);color:var(--lv-ivory)}
@keyframes lvin{from{transform:translateX(30px);opacity:0}to{transform:none;opacity:1}}
.lv-x{float:right;background:none;border:1px solid var(--lv-line);color:var(--lv-dim);border-radius:8px;width:32px;height:32px;cursor:pointer;font-size:14px}
.lv-x:hover{color:var(--lv-ivory);border-color:var(--lv-gold-lt)}
.lv-mini{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin:16px 0}
.lv-mini>div{background:rgba(255,255,255,.05);border:1px solid var(--lv-line);border-radius:12px;padding:12px 14px}
.lv-mini b{display:block;font-family:'Fraunces',serif;font-size:21px;margin-top:4px;color:var(--lv-ivory)}
.lv-card{background:rgba(255,255,255,.04);border:1px solid var(--lv-line);border-radius:12px;padding:16px;margin-bottom:12px}
.lv-row{display:grid;grid-template-columns:110px 1fr;gap:8px;margin-bottom:8px}
.lv-hint{font-size:11px;color:var(--lv-dim);margin:2px 0 10px}
.lv-sel{background:rgba(255,255,255,.06);border:1.5px solid var(--lv-line);border-radius:10px;padding:10px 12px;color:var(--lv-ivory)!important;font-size:13px;width:100%;outline:none}
.lv-sel option{color:#111}
.lv-lock{background:rgba(248,113,113,.1);border:1px solid rgba(248,113,113,.3);color:#fca5a5;border-radius:10px;padding:10px 12px;font-size:12px;margin-bottom:12px}
.lv-hist{display:flex;justify-content:space-between;gap:10px;padding:8px 0;border-bottom:1px solid rgba(228,205,142,.08);font-size:12px}
.lv-hist:last-child{border-bottom:none}
@media(max-width:1000px){.lv-kpis{grid-template-columns:1fr 1fr}.lv-grid2{grid-template-columns:1fr}.lv-body,.lv-hero{padding-left:20px;padding-right:20px}}
</style>

<div class="lv-wrap">
    <div class="lv-hero">
        <div class="lv-eyebrow">🎁 JewelTag</div>
        <h1 class="lv-title">Loyalty <span>&amp; Store Credit</span></h1>
        <p class="lv-sub">Points, tiers and store credit for every customer — in one place.</p>
        <div class="lv-tabs">
            @foreach($tabList as $key => $label)
                <button class="lv-tab {{ $tab === $key ? 'on' : '' }}" wire:click="setTab('{{ $key }}')">{{ $label }}</button>
            @endforeach
        </div>
    </div>

    <div class="lv-body">

    @if($loyaltyOn && $tab === 'overview')
        <div class="lv-kpis">
            <div class="lv-kpi"><div class="lv-kpi-l">Points Outstanding</div>
                <div class="lv-kpi-v">{{ number_format($k['points']) }}</div>
                <div class="lv-kpi-s">≈ {{ $money($k['liability']) }} if all redeemed</div></div>
            @if($creditOn)
            <div class="lv-kpi"><div class="lv-kpi-l">Store Credit Held</div>
                <div class="lv-kpi-v">{{ $money($k['credit']) }}</div>
                <div class="lv-kpi-s">across {{ number_format($k['credit_count']) }} customers</div></div>
            @else
            <div class="lv-kpi"><div class="lv-kpi-l">Customers</div><div class="lv-kpi-v">{{ number_format($k['customers']) }}</div><div class="lv-kpi-s">in your database</div></div>
            @endif
            <div class="lv-kpi"><div class="lv-kpi-l">Members With Points</div>
                <div class="lv-kpi-v">{{ number_format($k['members']) }}</div>
                <div class="lv-kpi-s">of {{ number_format($k['customers']) }} customers</div></div>
            <div class="lv-kpi"><div class="lv-kpi-l">Redeem Rate</div>
                <div class="lv-kpi-v">{{ number_format($ppd) }}<span style="font-size:14px"> pts</span></div>
                <div class="lv-kpi-s">= $1.00 store credit</div></div>
        </div>

        <div class="lv-grid2">
            <div class="lv-glass">
                <div class="lv-sec">Tier Distribution</div>
                @foreach($tiers as $slug => [$label, $min])
                    @php $cnt = $k['tier_counts'][$slug] ?? 0; [$fg,$bg] = $tierStyle($slug); @endphp
                    <div class="lv-tier-row">
                        <span class="lv-badge" style="color:{{ $fg }};background:{{ $bg }};border-color:{{ $fg }}55;min-width:86px;justify-content:center">{{ $label }}</span>
                        <div class="lv-bar"><i style="width:{{ round(($cnt / $totalC) * 100) }}%;background:{{ $fg }}"></i></div>
                        <span style="font-weight:800;font-size:13px;min-width:44px;text-align:right">{{ number_format($cnt) }}</span>
                    </div>
                    <div class="lv-mut" style="margin:-6px 0 12px 98px">from {{ $money($min) }} spend in 12 months</div>
                @endforeach
            </div>

            <div class="lv-glass">
                <div class="lv-sec">Top Customers · Last 12 Months</div>
                @forelse($this->topCustomers() as $i => $c)
                    @php [$fg,$bg] = $tierStyle($c->loyalty_tier); @endphp
                    <div class="lv-top" wire:click="openCustomer({{ $c->id }})">
                        <div class="lv-rank">{{ $i + 1 }}</div>
                        <div style="flex:1;min-width:0">
                            <div class="lv-name">{{ $this::fullName($c) }}</div>
                            <div class="lv-mut">{{ number_format((int) $c->loyalty_points) }} pts</div>
                        </div>
                        <span class="lv-badge" style="color:{{ $fg }};background:{{ $bg }};border-color:{{ $fg }}55">{{ $c->loyalty_tier ?: 'standard' }}</span>
                        <div style="font-weight:800;min-width:84px;text-align:right">{{ $money($c->spend12) }}</div>
                    </div>
                @empty
                    <div class="lv-empty"><b>No sales in the last 12 months</b></div>
                @endforelse
            </div>
        </div>
    @endif

    @if($loyaltyOn && $tab === 'members')
        <div class="lv-toolbar">
            <input class="lv-input" type="search" placeholder="Search name, phone, email or card #…" wire:model.live.debounce.400ms="search">
            <button class="lv-chip {{ $tierFilter === '' ? 'on' : '' }}" wire:click="$set('tierFilter','')">All</button>
            @foreach($tiers as $slug => [$label])
                <button class="lv-chip {{ $tierFilter === $slug ? 'on' : '' }}" wire:click="$set('tierFilter','{{ $slug }}')">{{ $label }}</button>
            @endforeach
        </div>
        @php $rows = $this->members(); @endphp
        @if($rows->isEmpty())
            <div class="lv-tw"><div class="lv-empty"><b>No customers found</b>Try a different search or tier.</div></div>
        @else
        <div class="lv-tw"><table class="lv-table">
            <thead><tr><th>Customer</th><th>Tier</th><th>Points</th><th>12-Mo Spend</th><th>Next Tier</th>@if($creditOn)<th>Store Credit</th>@endif<th></th></tr></thead>
            <tbody>
            @foreach($rows as $c)
                @php [$fg,$bg] = $tierStyle($c->loyalty_tier); [$nextLabel,$pct,$left] = $this->progress((float) $c->spend12); @endphp
                <tr wire:key="m{{ $c->id }}">
                    <td><div class="lv-name">{{ $this::fullName($c) }}</div><div class="lv-mut">{{ $c->phone ?: $c->email ?: '—' }}</div></td>
                    <td><span class="lv-badge" style="color:{{ $fg }};background:{{ $bg }};border-color:{{ $fg }}55">{{ $c->loyalty_tier ?: 'standard' }}</span></td>
                    <td class="lv-pts">{{ number_format((int) $c->loyalty_points) }}</td>
                    <td style="font-weight:700">{{ $money($c->spend12) }}</td>
                    <td class="lv-prog">
                        <div class="lv-bar"><i style="width:{{ $pct }}%;background:linear-gradient(90deg,var(--lv-sage),var(--lv-gold-lt))"></i></div>
                        <div class="lv-mut">{{ $nextLabel ? $money($left).' to '.$nextLabel : 'Top tier ✦' }}</div>
                    </td>
                    @if($creditOn)
                    <td class="{{ (float) $c->credit_balance > 0 ? 'lv-cr' : 'lv-mut' }}">{{ $money($c->credit_balance) }}</td>
                    @endif
                    <td><button class="lv-btn" wire:click="openCustomer({{ $c->id }})">Manage</button></td>
                </tr>
            @endforeach
            </tbody></table></div>
            @if($rows->count() >= $limit)<button class="lv-btn lv-more" wire:click="loadMore">Load more</button>@endif
        @endif
    @endif

    @if($creditOn && $tab === 'credit')
        <div class="lv-kpis" style="grid-template-columns:repeat(2,1fr)">
            <div class="lv-kpi"><div class="lv-kpi-l">Total Credit Held</div><div class="lv-kpi-v">{{ $money($k['credit']) }}</div><div class="lv-kpi-s">outstanding liability</div></div>
            <div class="lv-kpi"><div class="lv-kpi-l">Customers With Credit</div><div class="lv-kpi-v">{{ number_format($k['credit_count']) }}</div><div class="lv-kpi-s">credit belongs to the customer, not a sale</div></div>
        </div>
        <div class="lv-toolbar">
            <input class="lv-input" type="search" placeholder="Search customer…" wire:model.live.debounce.400ms="search">
        </div>
        @php $holders = $this->creditHolders(); @endphp
        <div class="lv-tw">
            @if($holders->isEmpty())
                <div class="lv-empty"><b>No store credit</b>Nobody currently holds credit.</div>
            @else
            <table class="lv-table"><thead><tr><th>Customer</th><th>Contact</th><th>Balance</th><th></th></tr></thead><tbody>
            @foreach($holders as $c)
                <tr wire:key="c{{ $c->id }}">
                    <td class="lv-name">{{ $this::fullName($c) }}</td>
                    <td class="lv-mut">{{ $c->phone ?: $c->email ?: '—' }}</td>
                    <td class="lv-cr" style="font-size:14px">{{ $money($c->credit_balance) }}</td>
                    <td><button class="lv-btn" wire:click="openCustomer({{ $c->id }})">Manage</button></td>
                </tr>
            @endforeach
            </tbody></table>
            @endif
        </div>
        @if($holders->count() >= $limit)<button class="lv-btn lv-more" wire:click="loadMore">Load more</button>@endif
    @endif

    </div>

    @if($selectedId && ($sc = $this->selected()))
        @php [$fg,$bg] = $tierStyle($sc->loyalty_tier); [$nextLabel,$pct,$left] = $this->progress((float) $sc->spend12);
             $suggested = \App\Filament\Pages\LoyaltyCenter::tierForSpend((float) $sc->spend12); @endphp
        <div class="lv-ov" wire:click="closePanel"></div>
        <aside class="lv-drawer" wire:key="drawer{{ $sc->id }}">
            <button class="lv-x" wire:click="closePanel">✕</button>
            <div class="lv-eyebrow">Customer</div>
            <div class="lv-title" style="font-size:26px">{{ $this::fullName($sc) }}</div>
            <div class="lv-mut" style="margin:4px 0 10px">{{ $sc->phone ?: '' }} {{ $sc->email ? '· '.$sc->email : '' }}</div>
            @if($loyaltyOn)<span class="lv-badge" style="color:{{ $fg }};background:{{ $bg }};border-color:{{ $fg }}55">{{ $sc->loyalty_tier ?: 'standard' }}</span>@endif

            <div class="lv-mini">
                @if($loyaltyOn)<div><span class="lv-kpi-l">Points</span><b>{{ number_format((int) $sc->loyalty_points) }}</b></div>@endif
                @if($creditOn)<div><span class="lv-kpi-l">Store Credit</span><b style="color:var(--lv-green)">{{ $money($sc->credit_balance) }}</b></div>@endif
                <div><span class="lv-kpi-l">12-Mo Spend</span><b>{{ $money($sc->spend12) }}</b></div>
                @if($loyaltyOn)<div><span class="lv-kpi-l">Suggested Tier</span><b style="text-transform:capitalize">{{ $suggested }}</b></div>@endif
            </div>
            @if($loyaltyOn)
            <div class="lv-bar" style="margin-bottom:6px"><i style="width:{{ $pct }}%;background:linear-gradient(90deg,var(--lv-sage),var(--lv-gold-lt))"></i></div>
            <div class="lv-mut" style="margin-bottom:18px">{{ $nextLabel ? $money($left).' more in 12 months to reach '.$nextLabel : 'Top tier reached ✦' }}</div>
            @endif

            @if(! $canEdit)
                <div class="lv-lock">🔒 View only — points and credit can be changed by administrators.</div>
            @else
                @if($loyaltyOn && $creditOn)
                <div class="lv-card">
                    <div class="lv-sec" style="margin-bottom:6px">Redeem Points → Store Credit</div>
                    <div class="lv-hint">{{ number_format($ppd) }} points = $1.00. Max {{ number_format((int) $sc->loyalty_points) }} pts ≈ {{ $money($sc->loyalty_points / $ppd) }}</div>
                    <div class="lv-row"><input class="lv-input" style="min-width:0" type="number" min="1" placeholder="Points" wire:model="redeemPoints">
                        <button class="lv-primary" wire:click="redeem" wire:loading.attr="disabled">Convert to credit</button></div>
                </div>
                @endif
                @if($loyaltyOn)
                <div class="lv-card">
                    <div class="lv-sec" style="margin-bottom:6px">Adjust Points</div>
                    <div class="lv-hint">Use + to add, − to remove. A reason is required.</div>
                    <div class="lv-row"><input class="lv-input" style="min-width:0" type="number" placeholder="+/- pts" wire:model="pointsDelta">
                        <input class="lv-input" style="min-width:0" type="text" placeholder="Reason (e.g. birthday bonus)" wire:model="pointsReason"></div>
                    <button class="lv-primary" wire:click="adjustPoints">Save points</button>
                </div>
                @endif
                @if($creditOn)
                <div class="lv-card">
                    <div class="lv-sec" style="margin-bottom:6px">Adjust Store Credit</div>
                    <div class="lv-hint">Balance can’t go below $0.</div>
                    <div class="lv-row"><input class="lv-input" style="min-width:0" type="number" step="0.01" placeholder="+/- $" wire:model="creditDelta">
                        <input class="lv-input" style="min-width:0" type="text" placeholder="Reason" wire:model="creditReason"></div>
                    <button class="lv-primary" wire:click="adjustCredit">Save credit</button>
                </div>
                @endif
                @if($loyaltyOn)
                <div class="lv-card">
                    <div class="lv-sec" style="margin-bottom:6px">Tier</div>
                    <div class="lv-hint">Based on spend this customer should be <b style="text-transform:capitalize">{{ $suggested }}</b>.</div>
                    <div class="lv-row" style="grid-template-columns:1fr 130px">
                        <select class="lv-sel" wire:model="tierChoice">
                            @foreach($tiers as $slug => [$label])<option value="{{ $slug }}">{{ $label }}</option>@endforeach
                        </select>
                        <button class="lv-btn" style="padding:10px" wire:click="saveTier">Save tier</button>
                    </div>
                </div>
                @endif
            @endif
        </aside>
    @endif
</div>
</x-filament-panels::page>