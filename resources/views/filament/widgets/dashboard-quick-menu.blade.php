<x-filament-widgets::widget>
<div class="luxury-terminal-wrapper">
    @if($storeLogo)
        <div class="flex justify-center mb-10">
            <div class="dynamic-logo-pill">
                <img src="{{ asset('storage/' . $storeLogo) }}" 
                     alt="Store Logo" 
                     class="h-20 w-auto object-contain">
            </div>
        </div>
    @endif
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 p-4">
        
        <div class="lg:col-span-8 space-y-8">
            
            <section class="command-section">
                <div class="section-header">
                    <div class="indicator teal"></div>
                    <div>
                        <h2>SALES INTELLIGENCE</h2>
                        <p>Real-time transaction & checkout controls</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <a href="/admin/sales/create" class="action-card sale-primary">
                        <div class="icon-box">
                            <x-heroicon-o-shopping-bag class="w-10 h-10" />
                        </div>
                        <div class="card-content">
                            <h3>New Sale</h3>
                            <p>Standard Invoice</p>
                        </div>
                        <div class="shortcut-tag">CTRL + S</div>
                    </a>

                    <a href="/admin/sales/create" class="action-card sale-quick">
                        <div class="icon-box">
                            <x-heroicon-o-bolt class="w-10 h-10" />
                        </div>
                        <div class="card-content">
                            <h3>Quick Sale</h3>
                            <p>Express Terminal</p>
                        </div>
                    </a>

                    <a href="/admin/find-sale" class="action-card sale-search">
                        <div class="icon-box">
                            <x-heroicon-o-magnifying-glass class="w-10 h-10" />
                        </div>
                        <div class="card-content">
                            <h3>Find Sale</h3>
                            <p>History Archives</p>
                        </div>
                    </a>
                </div>
            </section>

            <section class="command-section">
                <div class="section-header">
                    <div class="indicator amber"></div>
                    <div>
                        <h2>STOCK MASTER</h2>
                        <p>Inventory lifecycle & consignment tracking</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <a href="/admin/product-items/create" class="action-card stock-add">
                        <div class="icon-box">
                            <x-heroicon-o-plus-circle class="w-10 h-10" />
                        </div>
                        <div class="card-content">
                            <h3>Assemble</h3>
                            <p>Add New Items</p>
                        </div>
                    </a>

                    <a href="/admin/find-stock" class="action-card stock-lookup">
                        <div class="icon-box">
                            <x-heroicon-o-beaker class="w-10 h-10" />
                        </div>
                        <div class="card-content">
                            <h3>Find Stock</h3>
                            <p>Catalog Search</p>
                        </div>
                    </a>

                    <a href="/admin/memo-inventory" class="action-card stock-memo">
                        <div class="icon-box">
                            <x-heroicon-o-clipboard-document-list class="w-10 h-10" />
                        </div>
                        <div class="card-content">
                            <h3>Memo</h3>
                            <p>Consignments</p>
                        </div>
                    </a>
                </div>
            </section>
        </div>

        <div class="lg:col-span-4 space-y-6">
            <div class="glass-sidebar sticky top-6">
                <div class="clock-card">
                    <div class="date-row">{{ now()->format('l, F d') }}</div>
                    <div class="time-row">
                        <span class="digits">{{ now()->format('h:i') }}</span>
                        <span class="meridiem">{{ now()->format('A') }}</span>
                    </div>
                    <div class="meta-row">STATION 01 • {{ auth()->user()->name }}</div>
                </div>

                <div class="activity-feed">
                    <div class="feed-header">
                        <h3>RECENT ACTIVITY</h3>
                        <div class="live-badge">
                            <span class="ping"></span>
                            LIVE
                        </div>
                    </div>

                    <div class="feed-list custom-scrollbar">
                        @forelse($recentSales as $sale)
                            <div class="feed-item">
                                <div class="item-icon">
                                    <x-heroicon-m-currency-dollar class="w-5 h-5 text-emerald-500" />
                                </div>
                                <div class="item-info">
                                    <span class="receipt">#{{ $sale->receipt_no }}</span>
                                    <span class="client">{{ $sale->customer_name ?: 'Walk-in Customer' }}</span>
                                </div>
                                <div class="item-value">
                                    <span class="amount">${{ number_format($sale->grand_total, 2) }}</span>
                                    <span class="time">{{ $sale->created_at->diffForHumans(null, true) }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="empty-state p-4 text-center text-xs text-slate-400">No transactions today</div>
                        @endforelse
                    </div>

                    @if($recentSales->count() > 0)
                        <a href="/admin/sales" class="view-all-btn">
                            <span>EXPLORE ALL SALES</span>
                            <x-heroicon-m-arrow-right class="w-4 h-4" />
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* ──────────────── CSS SYSTEM ──────────────── */
.luxury-terminal-wrapper {
    --glass-bg: rgba(255, 255, 255, 0.85);
    --glass-border: rgba(255, 255, 255, 0.4);
    --text-main: #0f172a;
    --text-sub: #64748b;
}
.dynamic-logo-pill {
    background: white;
    padding: 1rem 3rem;
    border-radius: 2rem;
    box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
    border: 1px solid rgba(255,255,255,0.5);
}
.command-section {
    background: var(--glass-bg);
    backdrop-filter: blur(12px);
    border: 1px solid var(--glass-border);
    border-radius: 2rem;
    padding: 2.5rem;
    margin-bottom: 2rem;
}

.section-header { display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem; }
.indicator { width: 6px; height: 40px; border-radius: 10px; }
.indicator.teal { background: linear-gradient(to bottom, #2dd4bf, #0d9488); }
.indicator.amber { background: linear-gradient(to bottom, #fbbf24, #d97706); }

.action-card {
    display: flex; flex-direction: column; align-items: center; padding: 2.5rem 1.5rem;
    border-radius: 1.5rem; color: white; transition: all 0.4s ease; text-decoration: none !important;
}
.action-card:hover { transform: translateY(-8px); box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }

.sale-primary { background: linear-gradient(135deg, #2563eb, #7c3aed); }
.sale-quick { background: linear-gradient(135deg, #10b981, #0f766e); }
.sale-search { background: linear-gradient(135deg, #7c3aed, #db2777); }
.stock-add { background: linear-gradient(135deg, #f97316, #e11d48); }
.stock-lookup { background: linear-gradient(135deg, #334155, #0f172a); }
.stock-memo { background: linear-gradient(135deg, #f59e0b, #d97706); }

.clock-card { background: #0f172a; border-radius: 2rem; padding: 2.5rem; color: white; text-align: center; }
.time-row .digits { font-size: 3rem; font-weight: 900; }

.activity-feed { background: white; border-radius: 2rem; padding: 2rem; border: 1px solid #e2e8f0; }
.live-badge { display: flex; align-items: center; gap: 6px; color: #10b981; font-weight: 900; font-size: 0.7rem; }
.ping { width: 8px; height: 8px; background: #10b981; border-radius: 50%; animation: pulse 2s infinite; }

@keyframes pulse {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
}

.view-all-btn { 
    display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; 
    margin-top: 1.5rem; padding: 1rem; background: #f1f5f9; color: #475569; border-radius: 1rem; font-size: 0.75rem; 
}
</style>
</x-filament-widgets::widget>