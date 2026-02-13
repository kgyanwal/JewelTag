<x-filament-widgets::widget>
<div class="jewel-dashboard-modern">
    <!-- Clean Minimal Header -->
    <header class="dashboard-header-modern">
        <div class="header-content">
            <div class="brand-section">
                <!-- Diamond Badge Logo -->
                <div class="diamond-badge-modern">
                    @if($store && $store->logo_path)
                    <img src="{{ asset('storage/' . $store->logo_path) }}"
                        alt="{{ $store->name }}"
                        class="diamond-logo-modern">
                    @else
                    <div class="diamond-logo-placeholder">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 14l9-5-9-5-9 5 9 5z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                        </svg>
                    </div>
                    @endif
                </div>

                <!-- Brand Identity -->
                <div class="brand-identity-modern">
                    <h1 class="brand-name-modern">
                        {{ strtoupper($store->name ?? 'DIAMOND SQUARE') }}
                    </h1>
                    <p class="brand-tag-modern">
                        {{ $store->location ?? 'Premium Jewelry Management Suite' }}
                    </p>
                </div>
            </div>

            <!-- Header Right Section with System Status and Stats -->
            <div class="header-right-section">
                <!-- System Status Card in Header -->
                <div class="header-status-card">
                    <div class="status-content-horizontal">
                        <div class="status-item active">
                            <div class="status-indicator"></div>
                            <span>System Active</span>
                        </div>
                        <div class="time-display-horizontal">
                            <div class="time-main">
                                {{ now()->setTimezone('America/Denver')->format('h:i') }}
                                <span class="time-second">{{ now()->setTimezone('America/Denver')->format(':s') }}</span>
                            </div>
                            <div class="time-info">
                                <div>{{ now()->setTimezone('America/Denver')->format('A') }}</div>
                                <div>•</div>
                                <div>{{ now()->setTimezone('America/Denver')->format('M d, Y') }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats Overview -->
                <div class="stats-overview">
                    <div class="stat-card-modern">
                        <div class="stat-icon sales">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="stat-details">
                            <span class="stat-value">${{ number_format($todaySales ?? 0, 2) }}</span>
                            <span class="stat-label">Today's Sales</span>
                        </div>
                    </div>

                    <div class="stat-card-modern">
                        <div class="stat-icon inventory">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        </div>
                        <div class="stat-details">
                            <span class="stat-value">{{ $totalStock ?? '0' }}</span>
                            <span class="stat-label">Total Stock</span>
                        </div>
                    </div>

                    <div class="stat-card-modern">
                        <div class="stat-icon orders">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                        <div class="stat-details">
                            <span class="stat-value">{{ $activeOrders ?? '0' }}</span>
                            <span class="stat-label">Active Orders</span>
                        </div>
                    </div>

                    <div class="stat-card-modern">
                        <div class="stat-icon repairs">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            </svg>
                        </div>
                        <div class="stat-details">
                            <span class="stat-value">{{ $pendingRepairs ?? '0' }}</span>
                            <span class="stat-label">Pending Repairs</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="dashboard-body-modern">
        <!-- Main Grid Layout -->
        <div class="dashboard-grid">
            <!-- Left Column - Sales & Inventory -->
            <div class="dashboard-left-column">
                <!-- Sales Section -->
                <div class="dashboard-section">
                    <div class="section-header">
                        <div class="section-title">
                            <h2>Sales Operations</h2>
                            <p>Process transactions and manage sales</p>
                        </div>
                    </div>
                    <div class="actions-grid">
                        <a href="/admin/sales/create" class="action-card primary">
                            <div class="card-icon">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                            </div>
                            <div class="card-content">
                                <h3>New Sale</h3>
                                <p>Create a new invoice</p>
                            </div>
                            <div class="card-badge">Primary</div>
                        </a>

                        <a href="/admin/sales/create?quick=true" class="action-card success">
                            <div class="card-icon">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <div class="card-content">
                                <h3>Quick Sale</h3>
                                <p>Express checkout</p>
                            </div>
                            <div class="card-badge">Fast</div>
                        </a>

                        <a href="/admin/find-sale" class="action-card info">
                            <div class="card-icon">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <div class="card-content">
                                <h3>Find Sale</h3>
                                <p>Search transactions</p>
                            </div>
                            <div class="card-badge">Search</div>
                        </a>

                        <a href="/admin/sold-items-report" class="action-card warning">
                            <div class="card-icon">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div class="card-content">
                                <h3>Sold Items</h3>
                                <p>Sales reports</p>
                            </div>
                            <div class="card-badge">Reports</div>
                        </a>
                    </div>
                </div>

                <!-- Inventory Section -->
                <div class="dashboard-section">
                    <div class="section-header">
                        <div class="section-title">
                            <h2>Inventory Management</h2>
                            <p>Stock control and catalog</p>
                        </div>
                    </div>
                    <div class="actions-grid">
                        <a href="/admin/product-items/create" class="action-card success">
                            <div class="card-icon">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                            </div>
                            <div class="card-content">
                                <h3>Add Item</h3>
                                <p>Create new inventory</p>
                            </div>
                            <div class="card-badge">New</div>
                        </a>

                        <a href="/admin/find-stock" class="action-card info">
                            <div class="card-icon">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <div class="card-content">
                                <h3>Find Stock</h3>
                                <p>Search catalog</p>
                            </div>
                            <div class="card-badge">Find</div>
                        </a>

                        <a href="/admin/memo-inventory" class="action-card dark">
                            <div class="card-icon">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div class="card-content">
                                <h3>Memo Items</h3>
                                <p>Consignment tracking</p>
                            </div>
                            <div class="card-badge">Track</div>
                        </a>
                        
                        <!-- New Restocks Link -->
                        <a href="http://127.0.0.1:8001/admin/restocks" class="action-card restock">
                            <div class="card-icon">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                            </div>
                            <div class="card-content">
                                <h3>Restocks</h3>
                                <p>Manage inventory replenishment</p>
                            </div>
                            <div class="card-badge">Supply</div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Right Column - Services, Analytics & Restocks Overview -->
            <div class="dashboard-right-column">
                <!-- Services Section -->
                <div class="dashboard-section">
                    <div class="section-header">
                        <div class="section-title">
                            <h2>Services</h2>
                            <p>Custom orders and repairs</p>
                        </div>
                    </div>
                    <div class="actions-grid">
                        <a href="/admin/custom-orders" class="action-card accent">
                            <div class="card-icon">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </div>
                            <div class="card-content">
                                <h3>Custom Orders</h3>
                                <p>DSQ creations</p>
                            </div>
                            <div class="card-badge">Custom</div>
                        </a>

                        <a href="/admin/repairs" class="action-card warning">
                            <div class="card-icon">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                </svg>
                            </div>
                            <div class="card-content">
                                <h3>Repairs</h3>
                                <p>Jewelry restoration</p>
                            </div>
                            <div class="card-badge">Service</div>
                        </a>
                    </div>
                </div>

                <!-- Analytics Section -->
                <div class="dashboard-section">
                    <div class="section-header">
                        <div class="section-title">
                            <h2>Analytics & Reports</h2>
                            <p>Business insights</p>
                        </div>
                    </div>
                    <div class="actions-grid">
                        <a href="/admin/analytics" class="action-card info">
                            <div class="card-icon">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                            <div class="card-content">
                                <h3>Analytics</h3>
                                <p>Performance metrics</p>
                            </div>
                            <div class="card-badge">Insights</div>
                        </a>

                        <a href="/admin/customer-details-report" class="action-card accent">
                            <div class="card-icon">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <div class="card-content">
                                <h3>Customers Report</h3>
                                <p>Client analytics</p>
                            </div>
                            <div class="card-badge">Data</div>
                        </a>

                        <a href="/admin/customers" class="action-card dark">
                            <div class="card-icon">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <div class="card-content">
                                <h3>Customers</h3>
                                <p>Manage clients</p>
                            </div>
                            <div class="card-badge">Manage</div>
                        </a>
                    </div>
                </div>

                <!-- Restocks Overview Card -->
                <div class="restocks-card">
                    <div class="restocks-header">
                        <div class="restocks-title">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <h3>Recent Restocks</h3>
                        </div>
                        <a href="http://127.0.0.1:8001/admin/restocks" class="restocks-view-all">
                            View All
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </div>
                    <div class="restocks-list">
                        @forelse($recentRestocks ?? [] as $restock)
                        <div class="restock-item">
                            <div class="restock-icon">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <div class="restock-details">
                                <div class="restock-header">
                                    <span class="restock-sku">{{ $restock->sku ?? 'DSQ-001' }}</span>
                                    <span class="restock-quantity">+{{ $restock->quantity ?? rand(5, 25) }}</span>
                                </div>
                                <div class="restock-meta">
                                    <span class="restock-date">{{ $restock->created_at->diffForHumans() ?? '2 hours ago' }}</span>
                                    <span class="restock-supplier">{{ $restock->supplier ?? 'Direct Supply' }}</span>
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="restocks-empty">
                            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <p>No recent restocks</p>
                            <span>New inventory will appear here</span>
                        </div>
                        @endforelse
                    </div>
                    <div class="restocks-summary">
                        <div class="summary-item">
                            <span class="summary-label">Pending Orders</span>
                            <span class="summary-value">{{ $pendingRestocks ?? '3' }}</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">In Transit</span>
                            <span class="summary-value">{{ $inTransitRestocks ?? '2' }}</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Delivered</span>
                            <span class="summary-value">{{ $deliveredRestocks ?? '12' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Modern Dashboard Styles */
.jewel-dashboard-modern {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    min-height: 100vh;
}

/* Header */
.dashboard-header-modern {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    border-radius: 16px;
    margin-bottom: 24px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
    overflow: hidden;
    position: relative;
}

.dashboard-header-modern::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
}

.header-content {
    padding: 28px 32px;
}

/* Brand Section with Diamond Badge */
.brand-section {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 28px;
}

/* Modern Diamond Badge */
.diamond-badge-modern {
    position: relative;
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    border-radius: 20px;
    padding: 12px;
    box-shadow: 0 8px 32px rgba(37, 99, 235, 0.25);
    border: 1px solid rgba(255, 255, 255, 0.4);
    display: flex;
    align-items: center;
    justify-content: center;
}

.diamond-badge-modern::before {
    content: '';
    position: absolute;
    inset: -2px;
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.5), rgba(139, 92, 246, 0.5));
    border-radius: 22px;
    z-index: -1;
    opacity: 0.5;
}

.diamond-logo-modern {
    width: 100%;
    height: 100%;
    object-fit: contain;
    filter: drop-shadow(0 4px 12px rgba(0, 0, 0, 0.15));
    transition: transform 0.3s ease;
}

.diamond-logo-modern:hover {
    transform: scale(1.05);
}

.diamond-logo-placeholder {
    color: #3b82f6;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Brand Identity */
.brand-identity-modern {
    flex: 1;
}

.brand-name-modern {
    font-size: 28px;
    font-weight: 900;
    background: linear-gradient(135deg, #ffffff 0%, #93c5fd 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin: 0 0 6px 0;
    letter-spacing: -0.5px;
    line-height: 1.2;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.brand-tag-modern {
    color: #cbd5e1;
    font-size: 14px;
    font-weight: 500;
    margin: 0;
    letter-spacing: 0.3px;
}

/* Header Right Section */
.header-right-section {
    display: flex;
    align-items: center;
    gap: 24px;
    padding-top: 24px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

/* Header Status Card */
.header-status-card {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    padding: 16px 24px;
    border: 1px solid rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    flex-shrink: 0;
}

.status-content-horizontal {
    display: flex;
    align-items: center;
    gap: 24px;
}

.status-content-horizontal .status-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 0;
    background: transparent;
    border: none;
}

.status-content-horizontal .status-item span {
    color: white;
    font-size: 14px;
    font-weight: 600;
}

.time-display-horizontal {
    display: flex;
    align-items: center;
    gap: 16px;
    color: white;
}

.time-display-horizontal .time-main {
    font-size: 24px;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
    color: white;
}

.time-display-horizontal .time-second {
    font-size: 18px;
    opacity: 0.7;
}

.time-display-horizontal .time-info {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    color: #cbd5e1;
    font-weight: 500;
    padding-top: 0;
    border-top: none;
}

/* Stats Overview */
.stats-overview {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    flex: 1;
}

.stat-card-modern {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    border: 1px solid rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.stat-card-modern::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
}

.stat-card-modern:hover {
    background: rgba(255, 255, 255, 0.15);
    transform: translateY(-4px);
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.2);
    border-color: rgba(255, 255, 255, 0.25);
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.stat-icon.sales {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
}

.stat-icon.inventory {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
}

.stat-icon.orders {
    background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
    box-shadow: 0 4px 15px rgba(139, 92, 246, 0.3);
}

.stat-icon.repairs {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
}

.stat-details {
    flex: 1;
}

.stat-value {
    display: block;
    font-size: 24px;
    font-weight: 800;
    color: white;
    line-height: 1.2;
    font-variant-numeric: tabular-nums;
}

.stat-label {
    display: block;
    font-size: 13px;
    color: #cbd5e1;
    font-weight: 500;
    margin-top: 4px;
    letter-spacing: 0.3px;
}

/* Dashboard Body */
.dashboard-body-modern {
    margin-top: 24px;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: 1.2fr 1fr;
    gap: 24px;
}

/* Left Column */
.dashboard-left-column {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

/* Right Column */
.dashboard-right-column {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.dashboard-section {
    background: white;
    border-radius: 20px;
    padding: 28px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
    border: 1px solid #f1f5f9;
    transition: transform 0.3s ease;
}

.dashboard-section:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.12);
}

.section-header {
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 2px solid #f1f5f9;
}

.section-title h2 {
    font-size: 20px;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 6px;
    letter-spacing: -0.25px;
}

.section-title p {
    font-size: 14px;
    color: #64748b;
    margin: 0;
    font-weight: 500;
}

/* Actions Grid */
.actions-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

.action-card {
    background: white;
    border-radius: 16px;
    padding: 24px;
    text-decoration: none !important;
    border: 2px solid transparent;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.action-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    border-radius: 16px 16px 0 0;
}

.action-card.primary {
    border-color: #3b82f6;
}

.action-card.primary::before {
    background: #3b82f6;
}

.action-card.success {
    border-color: #10b981;
}

.action-card.success::before {
    background: #10b981;
}

.action-card.info {
    border-color: #0ea5e9;
}

.action-card.info::before {
    background: #0ea5e9;
}

.action-card.warning {
    border-color: #f59e0b;
}

.action-card.warning::before {
    background: #f59e0b;
}

.action-card.accent {
    border-color: #8b5cf6;
}

.action-card.accent::before {
    background: #8b5cf6;
}

.action-card.dark {
    border-color: #475569;
}

.action-card.dark::before {
    background: #475569;
}

.action-card.restock {
    border-color: #0ea5e9;
}

.action-card.restock::before {
    background: #0ea5e9;
}

.action-card.restock .card-icon {
    background: #0ea5e9;
}

.action-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 16px 40px rgba(0, 0, 0, 0.15);
}

.card-icon {
    width: 56px;
    height: 56px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 20px;
    color: white;
    flex-shrink: 0;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
}

.action-card.primary .card-icon {
    background: #3b82f6;
}

.action-card.success .card-icon {
    background: #10b981;
}

.action-card.info .card-icon {
    background: #0ea5e9;
}

.action-card.warning .card-icon {
    background: #f59e0b;
}

.action-card.accent .card-icon {
    background: #8b5cf6;
}

.action-card.dark .card-icon {
    background: #475569;
}

.card-content {
    flex: 1;
}

.card-content h3 {
    font-size: 18px;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 8px;
    line-height: 1.3;
}

.card-content p {
    font-size: 14px;
    color: #64748b;
    margin: 0;
    line-height: 1.5;
}

.card-badge {
    display: inline-block;
    margin-top: 16px;
    padding: 6px 12px;
    background: #f1f5f9;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 700;
    color: #475569;
    letter-spacing: 0.5px;
    align-self: flex-start;
}

/* Restocks Card */
.restocks-card {
    background: white;
    border-radius: 20px;
    padding: 28px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
    border: 1px solid #f1f5f9;
    transition: transform 0.3s ease;
}

.restocks-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.12);
}

.restocks-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 2px solid #f1f5f9;
}

.restocks-title {
    display: flex;
    align-items: center;
    gap: 12px;
}

.restocks-title svg {
    color: #0ea5e9;
}

.restocks-title h3 {
    font-size: 18px;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
}

.restocks-view-all {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #0ea5e9;
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.restocks-view-all:hover {
    color: #0284c7;
    gap: 12px;
}

.restocks-list {
    margin-bottom: 24px;
    max-height: 240px;
    overflow-y: auto;
}

.restock-item {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 12px;
    border-radius: 12px;
    margin-bottom: 8px;
    transition: all 0.3s ease;
    border: 1px solid transparent;
}

.restock-item:hover {
    background: #f8fafc;
    border-color: #e2e8f0;
    transform: translateX(4px);
}

.restock-icon {
    width: 36px;
    height: 36px;
    background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #0ea5e9;
    flex-shrink: 0;
}

.restock-details {
    flex: 1;
    min-width: 0;
}

.restock-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 4px;
}

.restock-sku {
    font-size: 14px;
    font-weight: 600;
    color: #1e293b;
}

.restock-quantity {
    font-size: 14px;
    font-weight: 700;
    color: #10b981;
    background: #d1fae5;
    padding: 2px 8px;
    border-radius: 6px;
}

.restock-meta {
    display: flex;
    gap: 12px;
    font-size: 12px;
    color: #64748b;
}

.restock-supplier {
    color: #0ea5e9;
    font-weight: 500;
}

.restocks-empty {
    text-align: center;
    padding: 32px 16px;
    color: #94a3b8;
}

.restocks-empty svg {
    margin-bottom: 12px;
    opacity: 0.3;
    color: #0ea5e9;
}

.restocks-empty p {
    font-size: 14px;
    font-weight: 600;
    margin: 0 0 4px;
    color: #475569;
}

.restocks-empty span {
    font-size: 12px;
    opacity: 0.7;
}

.restocks-summary {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    padding-top: 20px;
    border-top: 2px solid #f1f5f9;
}

.summary-item {
    text-align: center;
    padding: 12px;
    background: #f8fafc;
    border-radius: 12px;
    transition: all 0.3s ease;
}

.summary-item:hover {
    background: #f1f5f9;
    transform: translateY(-2px);
}

.summary-label {
    display: block;
    font-size: 12px;
    color: #64748b;
    margin-bottom: 4px;
    font-weight: 500;
}

.summary-value {
    display: block;
    font-size: 20px;
    font-weight: 800;
    color: #1e293b;
    line-height: 1;
}

/* Animations */
@keyframes pulse {
    0% {
        transform: scale(1);
        opacity: 0.5;
    }
    50% {
        transform: scale(1.2);
        opacity: 0.8;
    }
    100% {
        transform: scale(1);
        opacity: 0.5;
    }
}

/* Custom Scrollbar */
.restocks-list::-webkit-scrollbar {
    width: 4px;
}

.restocks-list::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 2px;
}

.restocks-list::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 2px;
}

.restocks-list::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Responsive Design */
@media (max-width: 1400px) {
    .dashboard-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 1200px) {
    .header-right-section {
        flex-direction: column;
        align-items: stretch;
    }
    
    .stats-overview {
        grid-template-columns: repeat(2, 1fr);
    }

    .brand-name-modern {
        font-size: 24px;
    }
}

@media (max-width: 1024px) {
    .actions-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 768px) {
    .stats-overview {
        grid-template-columns: 1fr;
    }

    .actions-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .header-content {
        padding: 24px;
    }

    .dashboard-section,
    .restocks-card {
        padding: 24px;
    }

    .brand-section {
        flex-direction: column;
        text-align: center;
        gap: 16px;
    }

    .brand-name-modern {
        font-size: 22px;
    }

    .diamond-badge-modern {
        width: 72px;
        height: 72px;
    }
    
    .status-content-horizontal {
        flex-direction: column;
        gap: 12px;
    }
    
    .restocks-summary {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .actions-grid {
        grid-template-columns: 1fr;
    }

    .stat-card-modern {
        padding: 16px;
    }

    .stat-value {
        font-size: 20px;
    }
}
</style>

<script>
// Live clock update
function updateClock() {
    const now = new Date();
    const nmTime = new Date(now.toLocaleString('en-US', {
        timeZone: 'America/Denver'
    }));

    const hours = nmTime.getHours();
    const minutes = nmTime.getMinutes().toString().padStart(2, '0');
    const seconds = nmTime.getSeconds().toString().padStart(2, '0');
    const meridiem = hours >= 12 ? 'PM' : 'AM';
    const displayHour = (hours % 12 || 12).toString().padStart(2, '0');

    const timeElement = document.querySelector('.time-main');
    if (timeElement) {
        timeElement.innerHTML = `${displayHour}:${minutes}<span class="time-second">:${seconds}</span>`;
    }

    const meridiemElement = document.querySelector('.time-info div:first-child');
    const dateElement = document.querySelector('.time-info div:last-child');

    if (meridiemElement && dateElement) {
        meridiemElement.textContent = meridiem;
        const dateString = nmTime.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });
        dateElement.textContent = dateString;
    }
}

updateClock();
setInterval(updateClock, 1000);

// Add hover effects to action cards
document.querySelectorAll('.action-card').forEach(card => {
    card.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-6px)';
        this.style.boxShadow = '0 16px 40px rgba(0, 0, 0, 0.15)';
    });

    card.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0)';
        this.style.boxShadow = '';
    });
});

// Add sparkle effect to diamond badge
const diamondBadge = document.querySelector('.diamond-badge-modern');
if (diamondBadge) {
    diamondBadge.addEventListener('mouseenter', function() {
        this.style.boxShadow = '0 12px 40px rgba(37, 99, 235, 0.4)';
    });

    diamondBadge.addEventListener('mouseleave', function() {
        this.style.boxShadow = '0 8px 32px rgba(37, 99, 235, 0.25)';
    });
}

// Stats cards animation
document.querySelectorAll('.stat-card-modern').forEach(card => {
    card.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-4px)';
    });

    card.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0)';
    });
});
</script>
</x-filament-widgets::widget>