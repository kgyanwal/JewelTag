<x-filament-widgets::widget>
<div class="luxury-dashboard-wrapper">
    <!-- Enhanced Header -->
    <header class="dashboard-header">
        <div class="header-backdrop">
            <div class="logo-container">
                <div class="logo-shape">
                    <div class="logo-glow"></div>
                    <img src="{{ asset('pngdiamondsquare.png') }}" 
                         alt="Store Logo" 
                         class="logo-image">
                </div>
                <div class="brand-meta">
                    <h1 class="brand-name">DIAMOND SQUARE</h1>
                    <p class="brand-subtitle">Jewel Tag</p>
                </div>
            </div>
            <div class="user-context">
                <div class="user-badge">
                    <div class="avatar-circle">
                        {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                    </div>
                    <span class="user-name">{{ auth()->user()->name }}</span>
                </div>
                <div class="station-indicator">STATION • {{ auth()->user()->id }}</div>
            </div>
        </div>
    </header>

    <div class="dashboard-grid">
        <!-- Main Content Area -->
        <main class="main-content-area">
            <!-- Sales Intelligence Section -->
            <section class="module-section">
                <div class="module-header">
                    <div class="header-glow"></div>
                    <div class="module-title-group">
                        <h2 class="module-title">
                            <span class="title-icon">💎</span>
                            SALES INTELLIGENCE
                        </h2>
                        <p class="module-subtitle">Real-time transaction processing & checkout controls</p>
                    </div>
                    <div class="module-stats">
                        <div class="stat-badge">
                            <span class="stat-value">${{ number_format($todaySales ?? 0, 2) }}</span>
                            <span class="stat-label">Today</span>
                        </div>
                    </div>
                </div>

                <div class="action-grid">
                    <a href="/admin/sales/create" class="action-tile primary-gradient">
                        <div class="tile-shimmer"></div>
                        <div class="tile-icon">
                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                        </div>
                        <div class="tile-content">
                            <h3>New Sale</h3>
                            <p>Standard invoice processing</p>
                        </div>
                        
                    </a>

                    <a href="/admin/sales/create?quick=true" class="action-tile success-gradient">
                        <div class="tile-shimmer"></div>
                        <div class="tile-icon">
                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <div class="tile-content">
                            <h3>Quick Sale</h3>
                            <p>Express checkout terminal</p>
                        </div>
                       
                    </a>

                    <a href="/admin/find-sale" class="action-tile purple-gradient">
                        <div class="tile-shimmer"></div>
                        <div class="tile-icon">
                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <div class="tile-content">
                            <h3>Find Sale</h3>
                            <p>Transaction archives</p>
                        </div>
                       
                    </a>
                </div>
            </section>

            <!-- Stock Master Section -->
            <section class="module-section">
                <div class="module-header">
                    <div class="header-glow amber"></div>
                    <div class="module-title-group">
                        <h2 class="module-title">
                            <span class="title-icon">📦</span>
                            STOCK MASTER
                        </h2>
                        <p class="module-subtitle">Inventory lifecycle & consignment tracking</p>
                    </div>
                    <div class="module-stats">
                        <div class="stat-badge amber">
                            <span class="stat-value">{{ $totalStock ?? '∞' }}</span>
                            <span class="stat-label">Items</span>
                        </div>
                    </div>
                </div>

                <div class="action-grid">
                    <a href="/admin/product-items/create" class="action-tile orange-gradient">
                        <div class="tile-shimmer"></div>
                        <div class="tile-icon">
                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                        </div>
                        <div class="tile-content">
                            <h3>Assemble</h3>
                            <p>Add new inventory items</p>
                        </div>
                       
                    </a>

                    <a href="/admin/find-stock" class="action-tile dark-gradient">
                        <div class="tile-shimmer"></div>
                        <div class="tile-icon">
                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                            </svg>
                        </div>
                        <div class="tile-content">
                            <h3>Find Stock</h3>
                            <p>Advanced catalog search</p>
                        </div>
                       
                    </a>

                    <a href="/admin/memo-inventory" class="action-tile gold-gradient">
                        <div class="tile-shimmer"></div>
                        <div class="tile-icon">
                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <div class="tile-content">
                            <h3>Memo</h3>
                            <p>Consignment management</p>
                        </div>
                      
                    </a>
                </div>
            </section>
        </main>

        <!-- Sidebar Area -->
        <aside class="sidebar-area">
            <!-- Time Widget with New Mexico Timezone -->
            <div class="time-widget">
                <div class="timezone-badge">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>MST • NEW MEXICO</span>
                </div>
                
                <div class="time-display">
                    <div class="date-string">
                        {{ now()->setTimezone('America/Denver')->format('l, F d, Y') }}
                    </div>
                    <div class="digital-clock">
                        <span class="hours">{{ now()->setTimezone('America/Denver')->format('h') }}</span>
                        <span class="colon">:</span>
                        <span class="minutes">{{ now()->setTimezone('America/Denver')->format('i') }}</span>
                        <span class="meridiem">{{ now()->setTimezone('America/Denver')->format('A') }}</span>
                    </div>
                    <div class="time-seconds">
                        {{ now()->setTimezone('America/Denver')->format('s') }}<span class="seconds-label">sec</span>
                    </div>
                </div>
            </div>

            <!-- Enhanced Activity Feed -->
            <div class="activity-widget">
                <div class="activity-header">
                    <h3 class="activity-title">
                        <span class="live-indicator"></span>
                        RECENT ACTIVITY
                    </h3>
                    <div class="activity-stats">
                        <span class="transaction-count">{{ $recentSales->count() }}</span>
                        <span class="stat-label">Today</span>
                    </div>
                </div>

                <div class="activity-feed custom-scrollbar">
                    @forelse($recentSales as $sale)
                        <div class="activity-item">
                            <div class="item-graphic">
                                <div class="graphic-circle {{ $sale->grand_total > 1000 ? 'premium' : 'standard' }}">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <div class="graphic-line"></div>
                            </div>
                            <div class="item-content">
                                <div class="content-header">
                                    <span class="receipt-code">#{{ $sale->receipt_no }}</span>
                                    <span class="sale-time">{{ $sale->created_at->setTimezone('America/Denver')->format('h:i A') }}</span>
                                </div>
                                <div class="customer-name">
                                    {{ $sale->customer_name ?: 'Walk-in Customer' }}
                                </div>
                                <div class="item-meta">
                                    <span class="items-count">{{ $sale->items_count ?? 1 }} items</span>
                                    <span class="payment-method">{{ $sale->payment_method ?? 'Cash' }}</span>
                                </div>
                            </div>
                            <div class="item-amount">
                                <span class="amount-value">${{ number_format($sale->grand_total, 2) }}</span>
                                <span class="time-ago">{{ $sale->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="empty-activity">
                            <div class="empty-icon">📊</div>
                            <p class="empty-title">No transactions yet</p>
                            <p class="empty-subtitle">Start processing sales today</p>
                        </div>
                    @endforelse
                </div>

                @if($recentSales->count() > 0)
                    <a href="/admin/sales" class="view-all-button">
                        <span>VIEW ALL TRANSACTIONS</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </a>
                @endif
            </div>
        </aside>
    </div>
</div>

<style>
/* ===== MODERN LUXURY DASHBOARD STYLES ===== */
.luxury-dashboard-wrapper {
    --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --success-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    --purple-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    --orange-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    --gold-gradient: linear-gradient(135deg, #ff9a9e 0%, #fad0c4 100%);
    --dark-gradient: linear-gradient(135deg, #434343 0%, #000000 100%);
    --glass-bg: rgba(255, 255, 255, 0.92);
    --glass-border: rgba(255, 255, 255, 0.3);
    --shadow-elevated: 0 20px 40px rgba(0, 0, 0, 0.1);
    --radius-xl: 24px;
    --radius-lg: 20px;
    --radius-md: 16px;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
}

/* Header Styles */
.dashboard-header {
    margin-bottom: 2.5rem;
}

.header-backdrop {
    background: var(--glass-bg);
    backdrop-filter: blur(20px);
    border: 1px solid var(--glass-border);
    border-radius: var(--radius-xl);
    padding: 1.5rem 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: var(--shadow-elevated);
}

.logo-container {
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

.logo-shape {
    position: relative;
    width: 80px;
    height: 80px;
    background: white;
    border-radius: 20px;
    padding: 12px;
    box-shadow: 0 8px 32px rgba(102, 126, 234, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.8);
}

.logo-glow {
    position: absolute;
    top: -10px;
    left: -10px;
    right: -10px;
    bottom: -10px;
    background: linear-gradient(45deg, #667eea, #764ba2);
    border-radius: 28px;
    z-index: -1;
    opacity: 0.3;
    filter: blur(15px);
}

.logo-image {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.brand-name {
    font-size: 1.75rem;
    font-weight: 800;
    background: linear-gradient(135deg, #667eea, #764ba2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-bottom: 0.25rem;
}

.brand-subtitle {
    color: #64748b;
    font-size: 0.875rem;
    font-weight: 500;
}

.user-context {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.5rem;
}

.user-badge {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.avatar-circle {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 0.875rem;
}

.user-name {
    font-weight: 600;
    color: #1e293b;
}

.station-indicator {
    font-size: 0.75rem;
    color: #64748b;
    background: #f1f5f9;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-weight: 500;
}

/* Grid Layout */
.dashboard-grid {
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 2rem;
}

/* Module Sections */
.module-section {
    background: var(--glass-bg);
    backdrop-filter: blur(12px);
    border: 1px solid var(--glass-border);
    border-radius: var(--radius-xl);
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: var(--shadow-elevated);
}

.module-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    position: relative;
}

.header-glow {
    position: absolute;
    left: -1rem;
    top: 0;
    bottom: 0;
    width: 4px;
    background: linear-gradient(to bottom, #667eea, #764ba2);
    border-radius: 4px;
}

.header-glow.amber {
    background: linear-gradient(to bottom, #f59e0b, #d97706);
}

.module-title-group {
    flex: 1;
}

.module-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.5rem;
}

.title-icon {
    font-size: 1.25rem;
}

.module-subtitle {
    color: #64748b;
    font-size: 0.875rem;
}

.module-stats {
    margin-left: 1rem;
}

.stat-badge {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 12px;
    text-align: center;
    min-width: 80px;
}

.stat-badge.amber {
    background: linear-gradient(135deg, #f59e0b, #d97706);
}

.stat-value {
    font-size: 1.25rem;
    font-weight: 700;
    display: block;
}

.stat-label {
    font-size: 0.75rem;
    opacity: 0.9;
}

/* Action Grid */
.action-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.5rem;
}

.action-tile {
    background: white;
    border-radius: var(--radius-lg);
    padding: 1.75rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-decoration: none !important;
    position: relative;
    overflow: hidden;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.action-tile:hover {
    transform: translateY(-8px);
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
}

.tile-shimmer {
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
    transition: 0.6s;
}

.action-tile:hover .tile-shimmer {
    left: 100%;
}

.tile-icon {
    margin-bottom: 1rem;
    color: white;
}

.tile-content {
    text-align: center;
    z-index: 1;
}

.tile-content h3 {
    font-size: 1.125rem;
    font-weight: 700;
    color: white;
    margin-bottom: 0.25rem;
}

.tile-content p {
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.9);
}

.tile-badge {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: rgba(0, 0, 0, 0.2);
    color: white;
    font-size: 0.625rem;
    padding: 0.25rem 0.5rem;
    border-radius: 6px;
    font-weight: 600;
}

/* Gradient Backgrounds */
.primary-gradient { background: var(--primary-gradient); }
.success-gradient { background: var(--success-gradient); }
.purple-gradient { background: var(--purple-gradient); }
.orange-gradient { background: var(--orange-gradient); }
.gold-gradient { background: var(--gold-gradient); }
.dark-gradient { background: var(--dark-gradient); }

/* Sidebar Styles */
.sidebar-area {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

/* Time Widget */
.time-widget {
    background: linear-gradient(135deg, #1e293b, #0f172a);
    border-radius: var(--radius-xl);
    padding: 2rem;
    color: white;
    position: relative;
    overflow: hidden;
}

.timezone-badge {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: rgba(255, 255, 255, 0.1);
    padding: 0.375rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    backdrop-filter: blur(10px);
}

.time-display {
    text-align: center;
}

.date-string {
    font-size: 0.875rem;
    color: #94a3b8;
    margin-bottom: 1rem;
}

.digital-clock {
    font-size: 3.5rem;
    font-weight: 800;
    display: flex;
    align-items: baseline;
    justify-content: center;
    gap: 0.25rem;
    line-height: 1;
    margin-bottom: 0.5rem;
}

.colon {
    animation: blink 1s infinite;
}

@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
}

.meridiem {
    font-size: 1.5rem;
    font-weight: 600;
    margin-left: 0.5rem;
    color: #cbd5e1;
}

.time-seconds {
    font-size: 1.25rem;
    color: #94a3b8;
    font-weight: 600;
}

.seconds-label {
    font-size: 0.75rem;
    margin-left: 0.25rem;
    opacity: 0.7;
}

/* Activity Widget */
.activity-widget {
    background: white;
    border-radius: var(--radius-xl);
    padding: 2rem;
    box-shadow: var(--shadow-elevated);
}

.activity-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.activity-title {
    font-size: 1.125rem;
    font-weight: 700;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.live-indicator {
    width: 8px;
    height: 8px;
    background: #10b981;
    border-radius: 50%;
    position: relative;
}

.live-indicator::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: #10b981;
    border-radius: 50%;
    animation: pulse 2s infinite;
}

.activity-stats {
    background: #f1f5f9;
    padding: 0.375rem 0.75rem;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.transaction-count {
    font-weight: 700;
    color: #1e293b;
}

.stat-label {
    font-size: 0.75rem;
    color: #64748b;
}

/* Activity Feed */
.activity-feed {
    max-height: 400px;
    overflow-y: auto;
    margin-bottom: 1.5rem;
}

.activity-item {
    display: flex;
    align-items: center;
    padding: 1rem;
    border-radius: var(--radius-md);
    background: #f8fafc;
    margin-bottom: 0.75rem;
    transition: all 0.3s ease;
}

.activity-item:hover {
    background: #f1f5f9;
    transform: translateX(4px);
}

.item-graphic {
    position: relative;
    margin-right: 1rem;
}

.graphic-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
}

.graphic-circle.premium {
    background: linear-gradient(135deg, #f59e0b, #d97706);
}

.graphic-line {
    position: absolute;
    bottom: -20px;
    left: 50%;
    transform: translateX(-50%);
    width: 2px;
    height: 20px;
    background: linear-gradient(to bottom, #cbd5e1, transparent);
}

.activity-item:last-child .graphic-line {
    display: none;
}

.item-content {
    flex: 1;
}

.content-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.25rem;
}

.receipt-code {
    font-weight: 600;
    color: #1e293b;
    font-size: 0.875rem;
}

.sale-time {
    font-size: 0.75rem;
    color: #64748b;
}

.customer-name {
    font-weight: 500;
    color: #334155;
    font-size: 0.875rem;
    margin-bottom: 0.25rem;
}

.item-meta {
    display: flex;
    gap: 0.75rem;
    font-size: 0.75rem;
    color: #64748b;
}

.item-amount {
    text-align: right;
    margin-left: 1rem;
}

.amount-value {
    display: block;
    font-weight: 700;
    color: #059669;
    font-size: 1.125rem;
    margin-bottom: 0.125rem;
}

.time-ago {
    font-size: 0.75rem;
    color: #94a3b8;
}

/* Empty State */
.empty-activity {
    text-align: center;
    padding: 3rem 1rem;
}

.empty-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.empty-title {
    font-weight: 600;
    color: #64748b;
    margin-bottom: 0.5rem;
}

.empty-subtitle {
    font-size: 0.875rem;
    color: #94a3b8;
}

/* View All Button */
.view-all-button {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 1rem;
    border-radius: var(--radius-md);
    text-decoration: none;
    font-weight: 600;
    font-size: 0.875rem;
    transition: all 0.3s ease;
}

.view-all-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
}

/* Custom Scrollbar */
.custom-scrollbar {
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 #f1f5f9;
}

.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 3px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Responsive Design */
@media (max-width: 1200px) {
    .dashboard-grid {
        grid-template-columns: 1fr;
    }
    
    .action-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .header-backdrop {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    
    .user-context {
        align-items: center;
    }
    
    .action-grid {
        grid-template-columns: 1fr;
    }
    
    .dashboard-grid {
        gap: 1rem;
    }
    
    .module-section {
        padding: 1.5rem;
    }
}
</style>

<script>
// Live time update for New Mexico timezone
function updateNewMexicoTime() {
    const now = new Date();
    const options = { timeZone: 'America/Denver', hour12: true };
    
    const timeString = now.toLocaleTimeString('en-US', options);
    const [time, meridiem] = timeString.split(' ');
    const [hours, minutes, seconds] = time.split(':');
    
    // Update digital clock
    document.querySelector('.hours').textContent = hours.padStart(2, '0');
    document.querySelector('.minutes').textContent = minutes;
    document.querySelector('.meridiem').textContent = meridiem;
    document.querySelector('.time-seconds').innerHTML = `${seconds}<span class="seconds-label">sec</span>`;
    
    // Update date
    const dateOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', timeZone: 'America/Denver' };
    const dateString = now.toLocaleDateString('en-US', dateOptions);
    document.querySelector('.date-string').textContent = dateString;
}

// Update time every second
setInterval(updateNewMexicoTime, 1000);
updateNewMexicoTime();

// Add hover effects to action tiles
document.querySelectorAll('.action-tile').forEach(tile => {
    tile.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-8px)';
    });
    
    tile.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0)';
    });
});
</script>
</x-filament-widgets::widget>