@php
    $m = fn($n) => '$' . number_format((float) $n, 2);
    $brandColors = ['VISA' => '#1a1f71', 'MASTERCARD' => '#eb001b', 'AMEX' => '#006fcf', 'DISCOVER' => '#ff6000'];
    $maxDay = max(1, collect($byDay)->max('total') ?: 1);
@endphp

<x-filament-panels::page>
<style>
    .cp-wrap{--bg:#F5EFE1;--ink:#2b2418;--mute:#7a6f5a;--line:rgba(11,61,60,.16);--teal:#0B3D3C;--gold:#B68A2E;--good:#1f7a4d;--bad:#b4402f;
        display:flex;flex-direction:column;gap:18px;color:var(--ink);padding:22px;border-radius:22px;background:var(--bg);border:1px solid rgba(182,138,46,.35);box-shadow:0 12px 34px rgba(11,61,60,.12);color-scheme:light}
    .cp-card{border:1px solid var(--line);border-radius:16px;padding:16px;background:#FBF8F0;color:var(--ink)}
    .cp-filters{display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end}
    .cp-field{display:flex;flex-direction:column;gap:3px;min-width:140px}
    .cp-field label{font-size:10px;font-weight:800;letter-spacing:.07em;text-transform:uppercase;color:var(--mute)}
    .cp-input{border:1px solid rgba(11,61,60,.28);background-color:#fff;color:var(--ink);border-radius:10px;padding:8px 10px;font-size:13px;appearance:none;-webkit-appearance:none;background-image:none}
    select.cp-input{padding-right:30px;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%230B3D3C' stroke-width='3'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 10px center}
    .cp-input::placeholder{color:#a79c85}
    .cp-chip{border:1px solid var(--teal);color:var(--teal);background:transparent;border-radius:99px;padding:6px 12px;font-size:11px;font-weight:800;cursor:pointer}
    .cp-chip:hover{background:var(--teal);color:#F5EFE1}
    .cp-chip.gold{border-color:var(--gold);color:#8a6416}
    .cp-chip.gold:hover{background:var(--gold);color:#fff}
    .cp-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px}
    .cp-kpi{border-radius:16px;padding:14px 16px;border:1px solid var(--line);background:#FBF8F0;border-top:3px solid var(--gold)}
    .cp-kpi .l{font-size:10px;font-weight:800;letter-spacing:.07em;text-transform:uppercase;color:var(--mute)}
    .cp-kpi .v{font-size:24px;font-weight:900;margin-top:3px;color:var(--teal)}
    .cp-grid2{display:grid;grid-template-columns:1fr 1fr;gap:18px}
    @media(max-width:900px){.cp-grid2{grid-template-columns:1fr}}
    .cp-h{font-size:13px;font-weight:900;margin-bottom:10px;color:var(--teal)}
    .cp-brand{display:flex;align-items:center;gap:10px;margin-bottom:9px}
    .cp-badge{color:#fff;font-size:10px;font-weight:900;letter-spacing:.05em;padding:3px 9px;border-radius:6px;min-width:78px;text-align:center}
    .cp-bar{flex:1;height:10px;border-radius:99px;background:rgba(11,61,60,.12);overflow:hidden}
    .cp-bar>div{height:100%;border-radius:99px;background:linear-gradient(90deg,var(--teal),var(--gold))}
    .cp-days{display:flex;align-items:flex-end;justify-content:center;gap:5px;height:110px}
    .cp-days>div{flex:0 1 34px;max-width:34px;border-radius:4px 4px 0 0;background:linear-gradient(180deg,var(--gold),var(--teal));min-height:3px}
    .cp-tabs{display:flex;gap:8px}
    .cp-tab{padding:8px 16px;border-radius:10px;font-size:12px;font-weight:800;border:1px solid rgba(11,61,60,.35);cursor:pointer;background:#FBF8F0;color:var(--teal)}
    .cp-tab.on{background:var(--teal);color:#F5EFE1;border-color:var(--teal)}
    .cp-cust{border:1px solid var(--line);border-radius:14px;margin-bottom:10px;overflow:hidden;background:#FBF8F0;color:var(--ink)}
    .cp-cust>summary{list-style:none;cursor:pointer;display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:10px;align-items:center;padding:12px 14px}
    .cp-cust>summary::-webkit-details-marker{display:none}
    .cp-cust[open]>summary{background:rgba(182,138,46,.14)}
    @media(max-width:800px){.cp-cust>summary{grid-template-columns:1fr 1fr}}
    .cp-name{font-weight:900;font-size:14px;color:var(--teal)}
    .cp-sub{font-size:11px;color:var(--mute)}
    .cp-num{font-weight:900;font-size:15px}
    .cp-lbl{font-size:9px;font-weight:800;letter-spacing:.07em;text-transform:uppercase;color:var(--mute)}
    .cp-pill{display:inline-block;font-size:10px;font-weight:800;padding:2px 8px;border-radius:99px;margin:2px 4px 0 0;border:1px solid rgba(11,61,60,.28);background:#fff;color:var(--teal)}
    .cp-table{width:100%;border-collapse:collapse;font-size:12px;color:var(--ink)}
    .cp-table th{text-align:left;font-size:10px;letter-spacing:.07em;text-transform:uppercase;color:var(--mute);padding:8px 10px;border-bottom:1px solid var(--line)}
    .cp-table td{padding:9px 10px;border-bottom:1px solid rgba(11,61,60,.09);vertical-align:top}
    .cp-table a{color:var(--teal);font-weight:800;text-decoration:underline;text-decoration-color:var(--gold)}
    .cp-scroll{overflow-x:auto}
    .cp-good{color:var(--good)}.cp-bad{color:var(--bad)}
    .cp-empty{text-align:center;padding:36px;color:var(--mute);font-size:13px}
    .cp-foot{font-size:11px;color:var(--mute)}
</style>

<div class="cp-wrap">

    {{-- FILTERS --}}
    <div class="cp-card">
        <div class="cp-filters">
            <div class="cp-field"><label>From</label><input type="date" class="cp-input" wire:model.live="dateFrom"></div>
            <div class="cp-field"><label>To</label><input type="date" class="cp-input" wire:model.live="dateTo"></div>
            <div class="cp-field"><label>Card type</label>
                <select class="cp-input" wire:model.live="brand">
                    <option value="">All cards</option>
                    @foreach($brandOptions as $b)<option value="{{ $b }}">{{ strtoupper($b) }}</option>@endforeach
                </select>
            </div>
            <div class="cp-field" style="flex:1;min-width:220px"><label>Search</label>
                <input type="text" class="cp-input" placeholder="Customer, phone, invoice #, last 4, auth, txn id…" wire:model.live.debounce.400ms="search">
            </div>
            <div style="display:flex;gap:6px;flex-wrap:wrap">
                <button type="button" class="cp-chip" wire:click="setRange('today')">Today</button>
                <button type="button" class="cp-chip" wire:click="setRange('week')">This week</button>
                <button type="button" class="cp-chip" wire:click="setRange('month')">This month</button>
                <button type="button" class="cp-chip" wire:click="setRange('year')">This year</button>
                <button type="button" class="cp-chip" wire:click="setRange('all')">All time</button>
                <button type="button" class="cp-chip gold" wire:click="exportCsv">⬇ Export CSV</button>
            </div>
        </div>
    </div>

    {{-- KPIs --}}
    <div class="cp-kpis">
        <div class="cp-kpi"><div class="l">Charged on terminal</div><div class="v">{{ $m($kpi['total']) }}</div></div>
        <div class="cp-kpi"><div class="l">Transactions</div><div class="v">{{ number_format($kpi['count']) }}</div></div>
        <div class="cp-kpi"><div class="l">Average charge</div><div class="v">{{ $m($kpi['avg']) }}</div></div>
        <div class="cp-kpi"><div class="l">Customers</div><div class="v">{{ number_format($kpi['customers']) }}</div></div>
        <div class="cp-kpi"><div class="l">Sales</div><div class="v">{{ number_format($kpi['sales']) }}</div></div>
    </div>

    {{-- BREAKDOWNS --}}
    <div class="cp-grid2">
        <div class="cp-card">
            <div class="cp-h">By card type</div>
            @forelse($byBrand as $b)
                <div class="cp-brand">
                    <span class="cp-badge" style="background:{{ $brandColors[$b['brand']] ?? '#475569' }}">{{ $b['brand'] }}</span>
                    <div class="cp-bar"><div style="width:{{ $b['pct'] }}%"></div></div>
                    <div style="min-width:130px;text-align:right;font-size:12px"><strong>{{ $m($b['total']) }}</strong> <span style="color:var(--mute)">· {{ $b['count'] }} txn · {{ $b['pct'] }}%</span></div>
                </div>
            @empty
                <div class="cp-empty">No card payments in this range.</div>
            @endforelse
        </div>
        <div class="cp-card">
            <div class="cp-h">Daily terminal volume</div>
            @if(count($byDay))
                <div class="cp-days">
                    @foreach($byDay as $d)
                        <div title="{{ $d['day'] }}: {{ $m($d['total']) }}" style="height:{{ max(3, round($d['total'] / $maxDay * 100)) }}%"></div>
                    @endforeach
                </div>
                <div style="display:flex;justify-content:space-between;font-size:10px;color:var(--mute);margin-top:6px">
                    <span>{{ $byDay->first()['day'] }}</span><span>{{ $byDay->last()['day'] }}</span>
                </div>
            @else
                <div class="cp-empty">Nothing to chart yet.</div>
            @endif
        </div>
    </div>

    {{-- TABS --}}
    <div class="cp-tabs">
        <button type="button" class="cp-tab {{ $tab === 'customers' ? 'on' : '' }}" wire:click="$set('tab','customers')">👤 By customer ({{ count($customers) }})</button>
        <button type="button" class="cp-tab {{ $tab === 'transactions' ? 'on' : '' }}" wire:click="$set('tab','transactions')">🧾 All transactions ({{ count($txns) }})</button>
    </div>

    {{-- BY CUSTOMER --}}
    @if($tab === 'customers')
        <div>
            @forelse($customers as $c)
                <details class="cp-cust">
                    <summary>
                        <div>
                            <div class="cp-name">{{ $c['name'] }}</div>
                            <div class="cp-sub">{{ $c['phone'] ?: '—' }} · last card payment {{ $c['last'] }}</div>
                            <div>@foreach($c['brands'] as $brand => $amt)<span class="cp-pill">{{ $brand }} {{ $m($amt) }}</span>@endforeach</div>
                        </div>
                        <div><div class="cp-lbl">Charged on card</div><div class="cp-num cp-good">{{ $m($c['charged']) }}</div><div class="cp-sub">{{ $c['txns'] }} txn · {{ count($c['sales']) }} sale(s)</div></div>
                        <div><div class="cp-lbl">Open balance (all sales)</div><div class="cp-num {{ $c['open'] > 0.01 ? 'cp-bad' : 'cp-good' }}">{{ $c['open'] > 0.01 ? $m($c['open']) : 'Settled ✓' }}</div></div>
                        <div style="text-align:right;font-size:11px;color:var(--mute)">Click to see sales ▾</div>
                    </summary>
                    <div class="cp-scroll" style="padding:4px 10px 12px">
                        <table class="cp-table">
                            <thead><tr><th>Invoice</th><th>Date</th><th>Cards used</th><th>Sale total</th><th>Card</th><th>Total paid</th><th>Balance</th><th>Status</th></tr></thead>
                            <tbody>
                            @foreach($c['sales'] as $s)
                                <tr>
                                    <td><a href="{{ $saleUrl($s['id']) }}" target="_blank">#{{ $s['invoice'] }}</a></td>
                                    <td>{{ $s['date'] }}</td>
                                    <td>@foreach($s['brands'] as $bl)<span class="cp-pill">{{ $bl }}</span>@endforeach</td>
                                    <td>{{ $m($s['total']) }}</td>
                                    <td><strong>{{ $m($s['card']) }}</strong></td>
                                    <td>{{ $m($s['paid']) }}</td>
                                    <td class="{{ $s['balance'] > 0.01 ? 'cp-bad' : 'cp-good' }}"><strong>{{ $s['balance'] > 0.01 ? $m($s['balance']) : 'Paid ✓' }}</strong></td>
                                    <td>{{ strtoupper(str_replace('_', ' ', $s['status'])) }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </details>
            @empty
                <div class="cp-card cp-empty">No customers with terminal card payments in this range.</div>
            @endforelse
        </div>
    @endif

    {{-- TRANSACTIONS --}}
    @if($tab === 'transactions')
        <div class="cp-card cp-scroll">
            <table class="cp-table">
                <thead><tr><th>Date</th><th>Invoice</th><th>Customer</th><th>Card</th><th>Auth</th><th>Txn ID</th><th style="text-align:right">Amount</th><th style="text-align:right">Sale balance</th></tr></thead>
                <tbody>
                @forelse($txns as $t)
                    <tr>
                        <td>{{ $t['when'] }}</td>
                        <td>@if($t['sale_id'])<a href="{{ $saleUrl($t['sale_id']) }}" target="_blank">#{{ $t['invoice'] }}</a>@else — @endif</td>
                        <td>{{ $t['cust'] }}</td>
                        <td><span class="cp-badge" style="background:{{ $brandColors[$t['brand']] ?? '#475569' }};min-width:0">{{ $t['brand'] }}</span> ••{{ $t['last4'] }}</td>
                        <td>{{ $t['auth'] ?: '—' }}</td>
                        <td style="font-size:11px;color:var(--mute)">{{ $t['txn'] ?: '—' }}</td>
                        <td style="text-align:right"><strong class="cp-good">{{ $m($t['amount']) }}</strong></td>
                        <td style="text-align:right" class="{{ ($t['balance'] ?? 0) > 0.01 ? 'cp-bad' : 'cp-good' }}">{{ $t['balance'] === null ? '—' : ($t['balance'] > 0.01 ? $m($t['balance']) : 'Paid ✓') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="cp-empty">No transactions found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    @endif

    <div class="cp-foot">Only terminal (Valor) card payments are listed. Card numbers are never stored — only brand, last 4, auth code and transaction id. Showing up to 3,000 payments for the selected filters.</div>
</div>
</x-filament-panels::page>