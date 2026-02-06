<x-filament-widgets::widget>
<div class="diamond-dashboard">
    <!-- Premium Header with Metallic Finish -->
    <header class="dashboard-header">
        <div class="header-gradient">
            <div class="header-content">
                <div class="brand-container">
                    <div class="jewel-badge">
                        <div class="jewel-sparkle"></div>
                        <div class="jewel-sparkle delay-1"></div>
                        <div class="jewel-sparkle delay-2"></div>
                        <img src="{{ asset('pngdiamondsquare.png') }}" alt="Diamond Square" class="jewel-logo">
                    </div>
                    <div class="brand-text">
                        <h1 class="brand-title">DIAMOND SQUARE</h1>
                        <p class="brand-tagline">Jewellery Management System</p>
                    </div>
                </div>
                
                <div class="user-console">
                    <div class="user-profile">
                        <div class="profile-avatar">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <div class="profile-info">
                            <span class="profile-name">{{ auth()->user()->name }}</span>
                            <span class="profile-role">Station {{ auth()->user()->id }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="dashboard-layout">
        <!-- Main Content -->
        <main class="main-content">
            <!-- Sales Intelligence Section -->
            <section class="dashboard-module">
                <div class="module-header">
                    <div class="module-icon">
                        <div class="icon-wrapper emerald">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="module-title">
                        <h2>SALES INTELLIGENCE</h2>
                        <p>Transaction Processing & POS Controls</p>
                    </div>
                    <div class="module-metrics">
                        <div class="metric-badge">
                            <span class="metric-value">${{ number_format($todaySales ?? 0, 2) }}</span>
                            <span class="metric-label">Today's Revenue</span>
                        </div>
                    </div>
                </div>

                <div class="action-panel">
                    <a href="/admin/sales/create" class="action-card diamond-gradient">
                        <div class="card-bg">
                            <div class="bg-pattern"></div>
                            <div class="bg-glow"></div>
                        </div>
                        <div class="card-icon">
                            <div class="icon-circle sapphire">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/>
                                </svg>
                            </div>
                        </div>
                        <div class="card-content">
                            <h3>New Sale</h3>
                            <p>Create comprehensive invoice</p>
                        </div>
                        <div class="card-action">
                            <span class="action-hint">Standard</span>
                        </div>
                    </a>

                    <a href="/admin/sales/create?quick=true" class="action-card emerald-gradient">
                        <div class="card-bg">
                            <div class="bg-pattern"></div>
                            <div class="bg-glow"></div>
                        </div>
                        <div class="card-icon">
                            <div class="icon-circle emerald">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="card-content">
                            <h3>Quick Sale</h3>
                            <p>Express checkout terminal</p>
                        </div>
                        <div class="card-action">
                            <span class="action-hint">Express</span>
                        </div>
                    </a>

                    <a href="/admin/find-sale" class="action-card amethyst-gradient">
                        <div class="card-bg">
                            <div class="bg-pattern"></div>
                            <div class="bg-glow"></div>
                        </div>
                        <div class="card-icon">
                            <div class="icon-circle amethyst">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="card-content">
                            <h3>Find Sale</h3>
                            <p>Search transaction history</p>
                        </div>
                        <div class="card-action">
                            <span class="action-hint">Archive</span>
                        </div>
                    </a>
                </div>
            </section>

            <!-- Inventory Management Section -->
            <section class="dashboard-module">
                <div class="module-header">
                    <div class="module-icon">
                        <div class="icon-wrapper gold">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </div>
                    </div>
                    <div class="module-title">
                        <h2>INVENTORY MASTER</h2>
                        <p>Stock Control & Consignment Tracking</p>
                    </div>
                    <div class="module-metrics">
                        <div class="metric-badge gold">
                            <span class="metric-value">{{ $totalStock ?? '∞' }}</span>
                            <span class="metric-label">Total Items</span>
                        </div>
                    </div>
                </div>

                <div class="action-panel">
                    <a href="/admin/product-items/create" class="action-card gold-gradient">
                        <div class="card-bg">
                            <div class="bg-pattern"></div>
                            <div class="bg-glow"></div>
                        </div>
                        <div class="card-icon">
                            <div class="icon-circle gold">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                            </div>
                        </div>
                        <div class="card-content">
                            <h3>Assemble</h3>
                            <p>Add new jewelry items</p>
                        </div>
                        <div class="card-action">
                            <span class="action-hint">Create</span>
                        </div>
                    </a>

                    <a href="/admin/find-stock" class="action-card onyx-gradient">
                        <div class="card-bg">
                            <div class="bg-pattern"></div>
                            <div class="bg-glow"></div>
                        </div>
                        <div class="card-icon">
                            <div class="icon-circle onyx">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="card-content">
                            <h3>Find Stock</h3>
                            <p>Advanced catalog search</p>
                        </div>
                        <div class="card-action">
                            <span class="action-hint">Search</span>
                        </div>
                    </a>

                    <a href="/admin/memo-inventory" class="action-card pearl-gradient">
                        <div class="card-bg">
                            <div class="bg-pattern"></div>
                            <div class="bg-glow"></div>
                        </div>
                        <div class="card-icon">
                            <div class="icon-circle pearl">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="card-content">
                            <h3>Memo</h3>
                            <p>Consignment management</p>
                        </div>
                        <div class="card-action">
                            <span class="action-hint">Track</span>
                        </div>
                    </a>
                </div>
            </section>
        </main>

        <!-- Sidebar -->
        <aside class="dashboard-sidebar">
            <!-- Time Display -->
            <div class="time-widget">
                <div class="time-header">
                    <div class="timezone-display">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>MST • Albuquerque</span>
                    </div>
                </div>
                <div class="time-body">
                    <div class="date-display">
                        {{ now()->setTimezone('America/Denver')->format('l, F d') }}
                    </div>
                    <div class="clock-display">
                        <div class="clock-digits">
                            <span class="hour">{{ now()->setTimezone('America/Denver')->format('h') }}</span>
                            <span class="colon">:</span>
                            <span class="minute">{{ now()->setTimezone('America/Denver')->format('i') }}</span>
                            <span class="second">{{ now()->setTimezone('America/Denver')->format('s') }}</span>
                        </div>
                        <div class="clock-meridiem">
                            {{ now()->setTimezone('America/Denver')->format('A') }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Activity Feed -->
            <div class="activity-widget">
                <div class="activity-header">
                    <h3>Recent Activity</h3>
                    <div class="activity-indicator">
                        <div class="indicator-dot"></div>
                        <span>LIVE</span>
                    </div>
                </div>
                <div class="activity-list">
                    @forelse($recentSales as $sale)
                    <div class="activity-item">
                        <div class="activity-icon">
                            <div class="icon-badge {{ $sale->grand_total > 5000 ? 'premium' : 'standard' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="activity-content">
                            <div class="activity-meta">
                                <span class="receipt">#{{ $sale->receipt_no }}</span>
                                <span class="time">{{ $sale->created_at->setTimezone('America/Denver')->format('h:i A') }}</span>
                            </div>
                            <div class="activity-details">
                                <span class="customer">{{ $sale->customer_name ?: 'Walk-in Customer' }}</span>
                                <span class="items">{{ $sale->items_count ?? 1 }} items</span>
                            </div>
                        </div>
                        <div class="activity-amount">
                            <span class="amount">${{ number_format($sale->grand_total, 2) }}</span>
                            <span class="ago">{{ $sale->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                    @empty
                    <div class="empty-activity">
                        <div class="empty-icon">
                            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <p>No recent transactions</p>
                        <small>Start your first sale today</small>
                    </div>
                    @endforelse
                </div>
                @if($recentSales->count() > 0)
                <a href="/admin/sales" class="view-all-btn">
                    View All Transactions
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
/* ===== LUXURY JEWELRY DASHBOARD STYLES ===== */
.diamond-dashboard {
    --sapphire: linear-gradient(135deg, #3b82f6, #1d4ed8);
    --emerald: linear-gradient(135deg, #10b981, #047857);
    --amethyst: linear-gradient(135deg, #8b5cf6, #7c3aed);
    --diamond: linear-gradient(135deg, #ffffff, #e0e7ff);
    --gold: linear-gradient(135deg, #fbbf24, #d97706);
    --onyx: linear-gradient(135deg, #1f2937, #111827);
    --pearl: linear-gradient(135deg, #f3f4f6, #d1d5db);
    --ruby: linear-gradient(135deg, #ef4444, #dc2626);
    
    --glass-bg: rgba(255, 255, 255, 0.95);
    --glass-border: rgba(255, 255, 255, 0.3);
    --shadow-luxury: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    --shadow-soft: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
    --radius-xl: 28px;
    --radius-lg: 24px;
    --radius-md: 20px;
    --radius-sm: 16px;
    
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    min-height: 100vh;
}

/* Header */
.dashboard-header {
    margin-bottom: 2rem;
}

.header-gradient {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    border-radius: var(--radius-xl);
    padding: 1.5rem 2.5rem;
    box-shadow: var(--shadow-luxury);
    position: relative;
    overflow: hidden;
}

.header-gradient::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.brand-container {
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

.jewel-badge {
    position: relative;
    width: 80px;
    height: 80px;
    background: var(--glass-bg);
    border-radius: 24px;
    padding: 16px;
    box-shadow: 0 0 40px rgba(59, 130, 246, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.5);
}

.jewel-sparkle {
    position: absolute;
    width: 12px;
    height: 12px;
    background: white;
    border-radius: 50%;
    filter: blur(2px);
    animation: sparkle 3s infinite;
}

.jewel-sparkle:nth-child(1) { top: 10px; left: 10px; }
.jewel-sparkle:nth-child(2) { top: 10px; right: 10px; animation-delay: 0.5s; }
.jewel-sparkle:nth-child(3) { bottom: 10px; right: 10px; animation-delay: 1s; }

@keyframes sparkle {
    0%, 100% { opacity: 0; transform: scale(0.5); }
    50% { opacity: 1; transform: scale(1); }
}

.jewel-logo {
    width: 100%;
    height: 100%;
    object-fit: contain;
    filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.1));
}

.brand-text {
    display: flex;
    flex-direction: column;
}

.brand-title {
    font-size: 2rem;
    font-weight: 800;
    background: linear-gradient(135deg, #ffffff 0%, #c7d2fe 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin: 0;
    letter-spacing: -0.5px;
}

.brand-tagline {
    color: #cbd5e1;
    font-size: 0.875rem;
    font-weight: 500;
    margin-top: 0.25rem;
}

.user-console {
    display: flex;
    align-items: center;
}

.user-profile {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem 1.5rem;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border-radius: var(--radius-md);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.profile-avatar {
    width: 40px;
    height: 40px;
    background: var(--emerald);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.profile-info {
    display: flex;
    flex-direction: column;
}

.profile-name {
    font-weight: 600;
    color: white;
    font-size: 0.875rem;
}

.profile-role {
    font-size: 0.75rem;
    color: #94a3b8;
}

/* Layout */
.dashboard-layout {
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 2rem;
}

/* Main Content */
.main-content {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

/* Modules */
.dashboard-module {
    background: var(--glass-bg);
    backdrop-filter: blur(20px);
    border-radius: var(--radius-xl);
    padding: 2.5rem;
    box-shadow: var(--shadow-luxury);
    border: 1px solid var(--glass-border);
}

.module-header {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    margin-bottom: 2.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.module-icon {
    flex-shrink: 0;
}

.icon-wrapper {
    width: 56px;
    height: 56px;
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.icon-wrapper.emerald { background: var(--emerald); }
.icon-wrapper.gold { background: var(--gold); }
.icon-wrapper.sapphire { background: var(--sapphire); }

.module-title {
    flex: 1;
}

.module-title h2 {
    font-size: 1.75rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 0.5rem 0;
}

.module-title p {
    color: #64748b;
    font-size: 0.875rem;
    margin: 0;
}

.module-metrics {
    flex-shrink: 0;
}

.metric-badge {
    background: var(--sapphire);
    color: white;
    padding: 1rem 1.5rem;
    border-radius: var(--radius-md);
    text-align: center;
    min-width: 120px;
}

.metric-badge.gold { background: var(--gold); }

.metric-value {
    display: block;
    font-size: 1.75rem;
    font-weight: 800;
    line-height: 1;
}

.metric-label {
    display: block;
    font-size: 0.75rem;
    opacity: 0.9;
    margin-top: 0.25rem;
}

/* Action Panel */
.action-panel {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.5rem;
}

.action-card {
    background: white;
    border-radius: var(--radius-lg);
    padding: 2rem;
    text-decoration: none !important;
    position: relative;
    overflow: hidden;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    height: 100%;
    display: flex;
    flex-direction: column;
}

.action-card:hover {
    transform: translateY(-12px);
    box-shadow: var(--shadow-luxury);
}

.card-bg {
    position: absolute;
    inset: 0;
    overflow: hidden;
}

.bg-pattern {
    position: absolute;
    inset: 0;
    opacity: 0.1;
    background-image: 
        radial-gradient(circle at 20% 80%, currentColor 1px, transparent 1px),
        radial-gradient(circle at 80% 20%, currentColor 1px, transparent 1px);
    background-size: 40px 40px;
}

.bg-glow {
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    opacity: 0;
    transition: opacity 0.4s;
}

.action-card:hover .bg-glow {
    opacity: 1;
}

.card-icon {
    position: relative;
    z-index: 2;
    margin-bottom: 1.5rem;
}

.icon-circle {
    width: 64px;
    height: 64px;
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
}

.icon-circle.sapphire { background: var(--sapphire); }
.icon-circle.emerald { background: var(--emerald); }
.icon-circle.amethyst { background: var(--amethyst); }
.icon-circle.gold { background: var(--gold); }
.icon-circle.onyx { background: var(--onyx); }
.icon-circle.pearl { background: var(--pearl); }

.card-content {
    position: relative;
    z-index: 2;
    flex: 1;
}

.card-content h3 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 0.5rem 0;
}

.card-content p {
    color: #64748b;
    font-size: 0.875rem;
    margin: 0;
    line-height: 1.5;
}

.card-action {
    position: relative;
    z-index: 2;
    margin-top: 1.5rem;
}

.action-hint {
    display: inline-block;
    padding: 0.375rem 0.75rem;
    background: rgba(0, 0, 0, 0.1);
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    color: inherit;
}

/* Gradient Backgrounds */
.diamond-gradient { background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); color: #1e293b; }
.diamond-gradient .bg-pattern { opacity: 0.05; }
.diamond-gradient:hover { background: linear-gradient(135deg, #ffffff 0%, #f1f5f9 100%); }

.emerald-gradient { background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); color: #065f46; }
.emerald-gradient .bg-pattern { opacity: 0.1; }
.emerald-gradient:hover { background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%); }

.amethyst-gradient { background: linear-gradient(135deg, #f3e8ff 0%, #e9d5ff 100%); color: #6d28d9; }
.amethyst-gradient .bg-pattern { opacity: 0.1; }
.amethyst-gradient:hover { background: linear-gradient(135deg, #f5f3ff 0%, #ede9fe 100%); }

.gold-gradient { background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); color: #92400e; }
.gold-gradient .bg-pattern { opacity: 0.1; }
.gold-gradient:hover { background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); }

.onyx-gradient { background: linear-gradient(135deg, #e5e7eb 0%, #d1d5db 100%); color: #111827; }
.onyx-gradient .bg-pattern { opacity: 0.1; }
.onyx-gradient:hover { background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%); }

.pearl-gradient { background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%); color: #374151; }
.pearl-gradient .bg-pattern { opacity: 0.1; }
.pearl-gradient:hover { background: linear-gradient(135deg, #ffffff 0%, #f9fafb 100%); }

/* Sidebar */
.dashboard-sidebar {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

/* Time Widget */
.time-widget {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    border-radius: var(--radius-xl);
    padding: 2rem;
    color: white;
    box-shadow: var(--shadow-luxury);
}

.time-header {
    margin-bottom: 1.5rem;
}

.timezone-display {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: rgba(255, 255, 255, 0.1);
    border-radius: var(--radius-sm);
    font-size: 0.75rem;
    font-weight: 500;
}

.time-body {
    text-align: center;
}

.date-display {
    font-size: 1rem;
    color: #cbd5e1;
    margin-bottom: 1rem;
}

.clock-display {
    display: flex;
    align-items: baseline;
    justify-content: center;
    gap: 0.5rem;
}

.clock-digits {
    font-size: 3.5rem;
    font-weight: 800;
    line-height: 1;
    display: flex;
    align-items: baseline;
}

.colon {
    animation: blink 1s infinite;
}

.second {
    font-size: 1.5rem;
    opacity: 0.7;
    margin-left: 0.25rem;
}

.clock-meridiem {
    font-size: 1.25rem;
    font-weight: 600;
    color: #cbd5e1;
}

/* Activity Widget */
.activity-widget {
    background: var(--glass-bg);
    backdrop-filter: blur(20px);
    border-radius: var(--radius-xl);
    padding: 2rem;
    box-shadow: var(--shadow-luxury);
    border: 1px solid var(--glass-border);
}

.activity-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.activity-header h3 {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
}

.activity-indicator {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: #10b981;
}

.indicator-dot {
    width: 8px;
    height: 8px;
    background: #10b981;
    border-radius: 50%;
    position: relative;
}

.indicator-dot::after {
    content: '';
    position: absolute;
    inset: -2px;
    background: #10b981;
    border-radius: 50%;
    opacity: 0.5;
    animation: pulse 2s infinite;
}

.activity-list {
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
    border: 1px solid transparent;
}

.activity-item:hover {
    background: white;
    border-color: #e2e8f0;
    transform: translateX(4px);
}

.activity-icon {
    margin-right: 1rem;
}

.icon-badge {
    width: 36px;
    height: 36px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.icon-badge.standard { background: var(--sapphire); }
.icon-badge.premium { background: var(--gold); }

.activity-content {
    flex: 1;
}

.activity-meta {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.25rem;
}

.receipt {
    font-weight: 600;
    color: #1e293b;
    font-size: 0.875rem;
}

.time {
    font-size: 0.75rem;
    color: #64748b;
}

.activity-details {
    display: flex;
    gap: 0.75rem;
    font-size: 0.75rem;
    color: #64748b;
}

.customer {
    font-weight: 500;
    color: #334155;
}

.activity-amount {
    text-align: right;
    margin-left: 1rem;
}

.amount {
    display: block;
    font-weight: 700;
    color: #059669;
    font-size: 1.125rem;
    margin-bottom: 0.125rem;
}

.ago {
    font-size: 0.75rem;
    color: #94a3b8;
}

/* Empty State */
.empty-activity {
    text-align: center;
    padding: 3rem 1rem;
    color: #64748b;
}

.empty-icon {
    margin-bottom: 1rem;
    opacity: 0.3;
}

.empty-activity p {
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.empty-activity small {
    font-size: 0.875rem;
    opacity: 0.7;
}

/* View All Button */
.view-all-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    width: 100%;
    padding: 1rem;
    background: var(--sapphire);
    color: white;
    border-radius: var(--radius-md);
    text-decoration: none;
    font-weight: 600;
    font-size: 0.875rem;
    transition: all 0.3s ease;
}

.view-all-btn:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-soft);
}

/* Responsive Design */
@media (max-width: 1400px) {
    .dashboard-layout {
        grid-template-columns: 1fr;
    }
    
    .action-panel {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .dashboard-layout {
        gap: 1rem;
    }
    
    .header-content {
        flex-direction: column;
        gap: 1.5rem;
        text-align: center;
    }
    
    .brand-container {
        flex-direction: column;
        text-align: center;
    }
    
    .user-profile {
        width: 100%;
        justify-content: center;
    }
    
    .dashboard-module {
        padding: 1.5rem;
    }
    
    .action-panel {
        grid-template-columns: 1fr;
    }
    
    .module-header {
        flex-direction: column;
        text-align: center;
        gap: 1rem;
    }
    
    .module-title {
        text-align: center;
    }
    
    .clock-digits {
        font-size: 2.5rem;
    }
}
</style>

<script>
// Real-time clock update for New Mexico
function updateNMClock() {
    const now = new Date();
    const nmTime = new Date(now.toLocaleString('en-US', { timeZone: 'America/Denver' }));
    
    const hours = nmTime.getHours();
    const minutes = nmTime.getMinutes().toString().padStart(2, '0');
    const seconds = nmTime.getSeconds().toString().padStart(2, '0');
    const meridiem = hours >= 12 ? 'PM' : 'AM';
    const displayHour = (hours % 12 || 12).toString().padStart(2, '0');
    
    document.querySelector('.hour').textContent = displayHour;
    document.querySelector('.minute').textContent = minutes;
    document.querySelector('.second').textContent = seconds;
    document.querySelector('.clock-meridiem').textContent = meridiem;
    
    const dateOptions = { weekday: 'long', month: 'long', day: 'numeric' };
    const dateString = nmTime.toLocaleDateString('en-US', dateOptions);
    document.querySelector('.date-display').textContent = dateString;
}

// Initialize clock
updateNMClock();
setInterval(updateNMClock, 1000);

// Add interactive effects
document.querySelectorAll('.action-card').forEach(card => {
    card.addEventListener('mouseenter', (e) => {
        const rect = e.currentTarget.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        
        card.style.setProperty('--mouse-x', `${x}px`);
        card.style.setProperty('--mouse-y', `${y}px`);
    });
});

// Smooth scroll for activity feed
const activityList = document.querySelector('.activity-list');
if (activityList) {
    activityList.addEventListener('wheel', (e) => {
        e.preventDefault();
        activityList.scrollTop += e.deltaY * 0.5;
    });
}
</script>
</x-filament-widgets::widget>