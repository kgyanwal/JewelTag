<div>
    @php use Illuminate\Support\Str; @endphp
    
    <style>
    /* ════════════════════════════════════════════════════════════════════════════════
       GLOBAL BREAKOUT WRAPPERS — MAX-WIDTH OVERRIDES FOR ENTERPRISE DOCKING
       ════════════════════════════════════════════════════════════════════════════════ */
    .fi-main,
    .fi-page,
    .fi-page-content,
    .fi-section-content,
    .fi-dashboard-page,
    .fi-dashboard,
    .fi-main-ctn,
    .fi-wi-stats-overview,
    [wire\:id] > .fi-wi {
        width: 100% !important;
        max-width: 100% !important;
        grid-column: 1 / -1 !important;
    }

    .fi-page-content {
        padding-left: 32px !important;
        padding-right: 32px !important;
    }

    .jt { 
        font-family: 'Inter', sans-serif; 
        width: 100%; 
        box-sizing: border-box; 
    }

    /* ── 4 EXPANDED KPI CARDS GRIDS ── */
    .jt-kpis {
        display: grid;
        grid-template-columns: repeat(4, minmax(360px, 1fr));
        gap: 24px;
        width: 100%;
        margin-bottom: 24px;
    }
    @media(max-width: 1600px) { .jt-kpis { grid-template-columns: repeat(2, 1fr); } }
    @media(max-width: 800px) { .jt-kpis { grid-template-columns: 1fr; } }

    .jt-k {
        min-height: 190px;
        padding: 32px;
        border-radius: 24px;
        color: #fff;
        position: relative;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        box-sizing: border-box;
        box-shadow: 0 10px 30px rgba(11, 61, 60, 0.05);
    }
    .jt-k::before, .jt-k::after {
        content: ''; position: absolute; border-radius: 50%;
        background: rgba(255, 255, 255, .1); pointer-events: none;
    }
    .jt-k::before { width: 140px; height: 140px; top: -40px; right: -30px; }
    .jt-k::after { width: 80px; height: 80px; bottom: -20px; right: 70px; }

    .jt-k-lbl { font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: .09em; opacity: .75; }
    .jt-k-val { font-size: 3.2rem; font-weight: 900; line-height: 1; letter-spacing: -.02em; margin: 12px 0 6px; }
    .jt-k-sub { font-size: 15px; opacity: .85; display: inline-flex; align-items: center; gap: 6px; flex-wrap: wrap; }
    .jt-k-ico { position: absolute; bottom: 20px; right: 24px; font-size: 3rem; opacity: .14; }

    .jt-pill { display: inline-flex; align-items: center; gap: 2px; padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 800; background: rgba(255, 255, 255, .2); }
    .jt-pill-warn { background: rgba(252, 165, 165, .25); color: #fecdd3; }

    .c-teal { background: linear-gradient(140deg, #0d9488, #0f766e); }
    .c-blue { background: linear-gradient(140deg, #2563eb, #1d4ed8); }
    .c-violet { background: linear-gradient(140deg, #7c3aed, #5b21b6); }
    .c-rose { background: linear-gradient(140deg, #e11d48, #9f1239); }
    .c-green { background: linear-gradient(140deg, #059669, #065f46); }

    /* ── ✅ UPDATED: ENTERPRISE WIDE 3-COLUMN STRUCTURE ── */
    .jt-bot {
        display: grid !important;
        grid-template-columns: 2fr 1.5fr 1.5fr !important;
        gap: 28px !important;
        width: 100% !important;
        align-items: start !important;
    }
    @media(max-width: 1400px) { .jt-bot { grid-template-columns: 1.5fr 1fr !important; } }
    @media(max-width: 900px) { .jt-bot { grid-template-columns: 1fr !important; } }

    /* ── ✅ UPDATED: GENEROUSE ENTERPRISE WORKSPACE SURFACES ── */
    .jt-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 24px;
        padding: 40px !important;
        box-sizing: border-box;
        width: 500px !important;
        box-shadow: 0 4px 20px rgba(0,0,0,0.01) !important;
    }
    .jt-ttl {
        font-size: 11px; font-weight: 800; text-transform: uppercase;
        letter-spacing: .09em; color: #64748b;
        margin-bottom: 20px;
        display: flex; align-items: center; gap: 8px;
    }
    .jt-ttl::before { content: ''; display: block; width: 14px; height: 3px; background: #0d9488; border-radius: 2px; flex-shrink: 0; }

    /* Sparkline Dynamic Charts */
    .jt-spark { display: flex; align-items: flex-end; gap: 8px; height: 130px; padding-bottom: 4px; }
    .jt-sc { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 6px; }
    .jt-sb { width: 100%; border-radius: 6px 6px 0 0; background: linear-gradient(180deg, #0d9488, #0f766e); min-height: 4px; }
    .jt-sd { font-size: 10px; color: #94a3b8; font-weight: 700; text-transform: uppercase; }
    .jt-sa { font-size: 10px; color: #64748b; font-weight: 700; }

    /* Today Live Tracker Block */
    .jt-dark {
        background: linear-gradient(145deg, #0f172a, #1e3a5f);
        border-radius: 24px; padding: 32px;
        margin-top: 24px; color: #fff;
        position: relative; overflow: hidden;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.15);
    }
    .jt-dark::before {
        content: ''; position: absolute; inset: 0;
        background-image: linear-gradient(rgba(255, 255, 255, .04) 1px, transparent 1px), linear-gradient(90deg, rgba(255, 255, 255, .04) 1px, transparent 1px);
        background-size: 28px 28px; opacity: .5;
    }
    .jt-di { position: relative; z-index: 1; }
    .jt-dl { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .1em; color: rgba(255, 255, 255, 0.45); margin-bottom: 8px; }
    .jt-dv { font-size: 4rem; font-weight: 900; line-height: 1; letter-spacing: -0.02em; }
    .jt-ds { font-size: 13px; color: rgba(255, 255, 255, .5); margin-top: 6px; }
    .jt-dg { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-top: 20px; }
    .jt-dm { background: rgba(255, 255, 255, .06); border-radius: 14px; padding: 16px; border: 1px solid rgba(255, 255, 255, 0.04); }
    .jt-dml { font-size: 10px; font-weight: 700; text-transform: uppercase; color: rgba(255, 255, 255, .35); margin-bottom: 4px; }
    .jt-dmv { font-size: 1.15rem; font-weight: 800; color: #fff; }

    /* Interactive Operational Table Rows */
    .jt-row { display: flex; justify-content: space-between; align-items: center; padding: 14px 0; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
    .jt-row:last-child { border-bottom: none; padding-bottom: 0; }
    .jt-rl { color: #374151; font-weight: 600; }
    .jt-badge { display: inline-flex; align-items: center; justify-content: center; min-width: 32px; padding: 4px 12px; border-radius: 999px; font-size: 12px; font-weight: 800; }

    .jt-hr { border: none; border-top: 1px solid #f1f5f9; margin: 18px 0; }

    /* Big Vault Stock Counter */
    .jt-ib { text-align: center; padding: 16px 0 24px; }
    .jt-ibn { font-size: 5rem; font-weight: 900; color: #0f172a; line-height: 1; letter-spacing: -0.03em; }
    .jt-ibs { font-size: 12px; color: #94a3b8; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-top: 6px; }

    /* Action Redirection Links */
    .jt-btn { display: block; text-align: center; border-radius: 12px; padding: 12px 16px; font-size: 13px; font-weight: 700; text-decoration: none; margin-top: 10px; transition: background 150ms ease; }
    .jt-btn-g { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
    .jt-btn-g:hover { background: #dcfce7; }
    .jt-btn-v { background: #f5f3ff; color: #7c3aed; border: 1px solid #ddd6fe; }
    .jt-btn-v:hover { background: #ede9fe; }
    .jt-btn-b { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }
    .jt-btn-b:hover { background: #dbeafe; }
    </style>

    <div class="jt" style="padding-left: 50px;">
        {{-- KPI TOPBAR GRID --}}
        <div class="jt-kpis">
            <div class="jt-k c-teal">
                <div>
                    <div class="jt-k-lbl">Monthly Revenue</div>
                    <div class="jt-k-val">${{ number_format($monthlyRevenue, 0) }}</div>
                    <div class="jt-k-sub">
                        <span class="jt-pill {{ $revenueChange < 0 ? 'jt-pill-warn' : '' }}">
                            {{ $revenueChange >= 0 ? '▲' : '▼' }} {{ abs($revenueChange) }}%
                        </span>
                        vs last month
                    </div>
                </div>
                <div class="jt-k-ico">💰</div>
            </div>

            <div class="jt-k c-blue">
                <div>
                    <div class="jt-k-lbl">Today's Revenue</div>
                    <div class="jt-k-val">${{ number_format($todayRevenue, 0) }}</div>
                    <div class="jt-k-sub">{{ $todaySales }} {{ Str::plural('sale', $todaySales) }} completed</div>
                </div>
                <div class="jt-k-ico">🛍️</div>
            </div>

            <div class="jt-k c-violet">
                <div>
                    <div class="jt-k-lbl">Inventory Value</div>
                    <div class="jt-k-val">${{ number_format($inventoryValue, 0) }}</div>
                    <div class="jt-k-sub">{{ number_format($inventoryCount) }} units on floor</div>
                </div>
                <div class="jt-k-ico">💎</div>
            </div>

            <div class="jt-k {{ $outstandingTotal > 0 ? 'c-rose' : 'c-green' }}">
                <div>
                    <div class="jt-k-lbl">Outstanding Balance</div>
                    <div class="jt-k-val">${{ number_format($outstandingTotal, 0) }}</div>
                    <div class="jt-k-sub">{{ $outstandingCount }} {{ Str::plural('sale', $outstandingCount) }} with balance</div>
                </div>
                <div class="jt-k-ico">⚖️</div>
            </div>
        </div>

        {{-- WORKSPACE MAIN GRID --}}
        <div class="jt-bot">
            {{-- COLUMN 1: ANALYTICS & REVENUE GRAPHS --}}
            <div>
                <div class="jt-card">
                    <div class="jt-ttl">Revenue — Last 7 Days</div>
                    <div class="jt-spark">
                        @foreach($sparkline as $day)
                        @php $pct = $maxSparkline > 0 ? ($day['revenue'] / $maxSparkline) : 0; @endphp
                        <div class="jt-sc">
                            <div class="jt-sa">
                                @if($day['revenue'] >= 1000)${{ number_format($day['revenue']/1000,1) }}k
                                @else${{ number_format($day['revenue'],0) }}@endif
                            </div>
                            <div class="jt-sb" style="height:{{ max(4, round($pct * 52)) }}px;"></div>
                            <div class="jt-sd">{{ $day['day'] }}</div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <div class="jt-dark">
                    <div class="jt-di">
                        <div class="jt-dl">📅 Today — {{ now()->format('F d, Y') }}</div>
                        <div class="jt-dv">${{ number_format($todayRevenue, 2) }}</div>
                        <div class="jt-ds">{{ $todaySales }} completed {{ Str::plural('sale', $todaySales) }}</div>
                        <div class="jt-dg">
                            <div class="jt-dm"><div class="jt-dml">Sold Today</div><div class="jt-dmv">{{ $soldToday }} items</div></div>
                            <div class="jt-dm"><div class="jt-dml">This Month</div><div class="jt-dmv">{{ $monthlySalesCount }} sales</div></div>
                            <div class="jt-dm"><div class="jt-dml">New Customers</div><div class="jt-dmv">{{ $newCustomers }} this mo.</div></div>
                            <div class="jt-dm"><div class="jt-dml">Total Customers</div><div class="jt-dmv">{{ number_format($totalCustomers) }}</div></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- COLUMN 2: WORKSHOP REPAIRS LOGISTICS --}}
            <div class="jt-card">
                <div class="jt-ttl">🔧 Repairs Operations</div>
                <div class="jt-row"><span class="jt-rl">In Progress</span><span class="jt-badge" style="background:#fef3c7;color:#b45309;">{{ $repairsOpen }}</span></div>
                <div class="jt-row"><span class="jt-rl">Ready for Pickup</span><span class="jt-badge" style="background:#dcfce7;color:#16a34a;">{{ $repairsReady }}</span></div>
                <div class="jt-row"><span class="jt-rl">Overdue Orders</span>
                    <span class="jt-badge" style="background:{{ $repairsOverdue > 0 ? '#fee2e2;color:#dc2626' : '#f1f5f9;color:#94a3b8' }};">{{ $repairsOverdue }}</span>
                </div>
                <hr class="jt-hr">
                <div class="jt-ttl">✨ Custom Orders</div>
                <div class="jt-row"><span class="jt-rl">In Production</span><span class="jt-badge" style="background:#f5f3ff;color:#7c3aed;">{{ $customOrdersPending }}</span></div>
                <div class="jt-row"><span class="jt-rl">Ready Vault</span><span class="jt-badge" style="background:#dcfce7;color:#16a34a;">{{ $customOrdersReady }}</span></div>
                <hr class="jt-hr">
                <a href="{{ route('filament.admin.resources.repairs.index') }}" class="jt-btn jt-btn-g">View All Repairs →</a>
                <a href="{{ route('filament.admin.resources.custom-orders.index') }}" class="jt-btn jt-btn-v">View Custom Orders →</a>
            </div>

            {{-- COLUMN 3: RETAIL INVENTORY BALANCING --}}
            <div class="jt-card">
                <div class="jt-ttl">📦 Live Showroom Stock</div>
                <div class="jt-ib">
                    <div class="jt-ibn">{{ number_format($inventoryCount) }}</div>
                    <div class="jt-ibs">Items In Stock</div>
                </div>
                <div class="jt-row"><span class="jt-rl">Retail Value</span><span style="font-weight:800;color:#0d9488;">${{ number_format($inventoryValue, 0) }}</span></div>
                <div class="jt-row"><span class="jt-rl">Sold Today</span><span style="font-weight:800;color:#7c3aed;">{{ $soldToday }}</span></div>
                <div class="jt-row"><span class="jt-rl">Avg. Item Value</span><span style="font-weight:800;color:#374151;">${{ $inventoryCount > 0 ? number_format($inventoryValue / $inventoryCount, 0) : '0' }}</span></div>
                <div class="jt-row"><span class="jt-rl">Outstanding Sales</span>
                    <span class="jt-badge" style="background:{{ $outstandingCount > 0 ? '#fee2e2;color:#dc2626' : '#f1f5f9;color:#94a3b8' }};">{{ $outstandingCount }}</span>
                </div>
                <hr class="jt-hr">
                <a href="{{ route('filament.admin.resources.product-items.index') }}" class="jt-btn jt-btn-b">Manage Inventory →</a>
            </div>
        </div>
    </div>

    <style>
        .fi-wi-stats-overview { grid-column: 1 / -1 !important; width: 100% !important; }
        [class*="fi-wi"]:has(.jt) { grid-column: 1 / -1 !important; width: 100% !important; }
    </style>
</div>