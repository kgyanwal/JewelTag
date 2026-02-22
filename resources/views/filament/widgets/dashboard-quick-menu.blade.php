<x-filament-widgets::widget>
    @php
        $data = $this->getViewData();
        $store = $data['store'];
        $todaySales = $data['todaySales'];
        $totalStock = $data['totalStock'] ?? 0;
        $activeOrders = $data['activeOrders'] ?? 0;
        $pendingRepairs = $data['pendingRepairs'] ?? 0;
        $pendingLaybuys = $data['pendingLaybuys'] ?? 0;
        $pendingCustomOrders = $data['pendingCustomOrders'] ?? 0;
        $pendingFollowUps = $data['pendingFollowUps'] ?? 0;
        $memoItems = $data['memoItems'] ?? 0;
        $tradeInRequests = $data['tradeInRequests'] ?? 0;
    @endphp

    <div class="ultimate-jewel-dashboard" style="margin-top: -1rem;">
        <!-- Premium Metallic Header -->
        <header class="dashboard-header">
            <div class="header-platinum">
                <div class="brand-display">
                    <div class="diamond-badge">
                       @if($store && $store->logo_path)
    <img src="{{ tenant_asset($store->logo_path) }}" 
         alt="{{ $store->name }}" 
         class="diamond-logo"
         style="width: 100%; height: 100%; object-fit: contain;">
@else
    <img src="{{ asset('jeweltaglogo.png') }}" 
         alt="Default" 
         class="diamond-logo">
@endif
                    </div>

                    <div class="brand-identity">
                        <h1 class="brand-name">
                            {{ strtoupper($store->name ?? 'StoreName') }}
                        </h1>
                        <p class="brand-tag">
                            {{ $store->location ?? 'Premium Jewelry Management Suite' }}
                        </p>
                    </div>
                    
                    <!-- Quick Stats Bar - 5 symmetrical items -->
                    <div class="quick-stats-bar">
                        <div class="stat-item">
                            <span class="stat-value">${{ number_format($todaySales ?? 0, 2) }}</span>
                            <span class="stat-label">Today's Sales</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value">{{ $totalStock ?? '0' }}</span>
                            <span class="stat-label">Total Stock</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value">{{ $activeOrders ?? '0' }}</span>
                            <span class="stat-label">Active Orders</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value">{{ $pendingRepairs ?? '0' }}</span>
                            <span class="stat-label">Pending Repairs</span>
                        </div>
                        <!-- Time Widget as 5th stat item -->
                        <div class="stat-item time-stat-item">
                            <div class="time-widget-compact">
                                <div class="time-badge">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span>MST • NM</span>
                                </div>
                                <div class="compact-time">
                                    <span class="time-display">{{ now()->setTimezone('America/Denver')->format('h:i:s A') }}</span>
                                    <span class="date-display">{{ now()->setTimezone('America/Denver')->format('M d') }}</span>
                                </div>
                                <div class="system-dot" title="System Active"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <div class="dashboard-body">
            <!-- Priority Actions Section - 5 symmetrical cards -->
            <div class="priority-section" style="margin-bottom: 2rem;">
                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
                    <div style="width: 52px; height: 52px; background: linear-gradient(135deg, #0d9488, #0f766e); border-radius: 16px; display: flex; align-items: center; justify-content: center; color: white; box-shadow: 0 8px 20px rgba(13, 148, 136, 0.3);">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 3v4M3 5h4M5 21v-4M3 17h4M17 5h4M19 3v4M17 21v-4M19 17h4" />
                        </svg>
                    </div>
                    <div>
                        <h2 style="font-size: 1.5rem; font-weight: 800; color: #1e293b; margin: 0 0 0.375rem 0;">PRIORITY ACTIONS</h2>
                        <p style="color: #64748b; font-size: 0.875rem; margin: 0; font-weight: 500;">Tasks requiring immediate attention</p>
                    </div>
                </div>

                <div class="priority-grid">
                    <!-- Laybuys -->
                    <a href="/admin/laybuys" class="operation-card priority-card" style="background: linear-gradient(135deg, #d1fae5, #a7f3d0); color: #065f46; border-color: #10b981;">
                        <div class="card-gem" style="background: #10b981;"></div>
                        <div class="card-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="card-content">
                            <h3>Laybuys <span class="badge" style="background: #065f46; color: white;">{{ $pendingLaybuys }}</span></h3>
                            <p>Active payment plans</p>
                        </div>
                        <div class="card-tag">Track</div>
                    </a>

                    <!-- Custom Orders -->
                    <a href="/admin/custom-orders" class="operation-card priority-card" style="background: linear-gradient(135deg, #f3e8ff, #e9d5ff); color: #6d28d9; border-color: #8b5cf6;">
                        <div class="card-gem" style="background: #8b5cf6;"></div>
                        <div class="card-icon" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </div>
                        <div class="card-content">
                            <h3>Custom Orders <span class="badge" style="background: #6d28d9; color: white;">{{ $pendingCustomOrders }}</span></h3>
                            <p>In progress / Pending</p>
                        </div>
                        <div class="card-tag">Design</div>
                    </a>

                    <!-- Upcoming Follow-ups -->
                    <a href="/admin/upcoming-follow-ups" class="operation-card priority-card" style="background: linear-gradient(135deg, #fef3c7, #fde68a); color: #92400e; border-color: #f59e0b;">
                        <div class="card-gem" style="background: #f59e0b;"></div>
                        <div class="card-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="card-content">
                            <h3>Follow-ups <span class="badge" style="background: #92400e; color: white;">{{ $pendingFollowUps }}</span></h3>
                            <p>Pending customer contacts</p>
                        </div>
                        <div class="card-tag">Remind</div>
                    </a>

                    <!-- Memo Inventory -->
                    <a href="/admin/memo-inventory" class="operation-card priority-card" style="background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #1e40af; border-color: #3b82f6;">
                        <div class="card-gem" style="background: #3b82f6;"></div>
                        <div class="card-icon" style="background: linear-gradient(135deg, #3b82f6, #2563eb);">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div class="card-content">
                            <h3>Memo Inventory <span class="badge" style="background: #1e40af; color: white;">{{ $memoItems }}</span></h3>
                            <p>Consignment tracking</p>
                        </div>
                        <div class="card-tag">Track</div>
                    </a>

                    <!-- Trade-in Check -->
                    <a href="/admin/trade-in-check" class="operation-card priority-card" style="background: linear-gradient(135deg, #e0f2fe, #bae6fd); color: #075985; border-color: #0ea5e9;">
                        <div class="card-gem" style="background: #0ea5e9;"></div>
                        <div class="card-icon" style="background: linear-gradient(135deg, #0ea5e9, #0369a1);">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                            </svg>
                        </div>
                        <div class="card-content">
                            <h3>Trade-in Check <span class="badge" style="background: #075985; color: white;">{{ $tradeInRequests }}</span></h3>
                            <p>Pending evaluations</p>
                        </div>
                        <div class="card-tag">Evaluate</div>
                    </a>
                </div>
            </div>

            <!-- Main Operations Grid - 2 columns -->
            <div class="operations-grid">
                <!-- Left Column - Sales & Inventory -->
                <div class="operations-column">
                    <!-- Sales Operations - 5 cards -->
                    <section class="operations-module">
                        <div class="module-header">
                            <div class="module-icon">
                                <div class="icon-frame emerald">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="module-title">
                                <h2>SALES OPERATIONS</h2>
                                <p>Transaction Processing & Management</p>
                            </div>
                        </div>
                        
                        <div class="five-card-grid">
                            <a href="/admin/sales/create" class="operation-card sapphire-card">
                                <div class="card-gem"></div>
                                <div class="card-icon">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/>
                                    </svg>
                                </div>
                                <div class="card-content">
                                    <h3>New Sale</h3>
                                    <p>Standard invoice</p>
                                </div>
                                <div class="card-tag">Primary</div>
                            </a>

                            <a href="/admin/sales/create?quick=true" class="operation-card emerald-card">
                                <div class="card-gem"></div>
                                <div class="card-icon">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    </svg>
                                </div>
                                <div class="card-content">
                                    <h3>Quick Sale</h3>
                                    <p>Express checkout</p>
                                </div>
                                <div class="card-tag">Express</div>
                            </a>

                            <a href="/admin/find-sale" class="operation-card amethyst-card">
                                <div class="card-gem"></div>
                                <div class="card-icon">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                </div>
                                <div class="card-content">
                                    <h3>Find Sale</h3>
                                    <p>Transaction history</p>
                                </div>
                                <div class="card-tag">Archive</div>
                            </a>

                            <a href="/admin/sold-items-report" class="operation-card ruby-card">
                                <div class="card-gem"></div>
                                <div class="card-icon">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </div>
                                <div class="card-content">
                                    <h3>Sold Items</h3>
                                    <p>Sales reports</p>
                                </div>
                                <div class="card-tag">Reports</div>
                            </a>

                            <a href="/admin/end-of-day-closing" class="operation-card onyx-card">
                                <div class="card-gem"></div>
                                <div class="card-icon">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                </div>
                                <div class="card-content">
                                    <h3>End of Day</h3>
                                    <p>Daily closing</p>
                                </div>
                                <div class="card-tag">Closing</div>
                            </a>
                        </div>
                    </section>

                    <!-- Inventory Management - 5 cards -->
                    <section class="operations-module">
                        <div class="module-header">
                            <div class="module-icon">
                                <div class="icon-frame gold">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="module-title">
                                <h2>INVENTORY MANAGEMENT</h2>
                                <p>Stock Control & Catalog Operations</p>
                            </div>
                        </div>
                        
                        <div class="five-card-grid">
                            <a href="/admin/product-items/create" class="operation-card gold-card">
                                <div class="card-gem"></div>
                                <div class="card-icon">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                    </svg>
                                </div>
                                <div class="card-content">
                                    <h3>Add Item</h3>
                                    <p>Create new items</p>
                                </div>
                                <div class="card-tag">Create</div>
                            </a>

                            <a href="/admin/find-stock" class="operation-card onyx-card">
                                <div class="card-gem"></div>
                                <div class="card-icon">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                </div>
                                <div class="card-content">
                                    <h3>Find Stock</h3>
                                    <p>Catalog search</p>
                                </div>
                                <div class="card-tag">Search</div>
                            </a>

                            <a href="/admin/memo-inventory" class="operation-card aquamarine-card">
                                <div class="card-gem"></div>
                                <div class="card-icon">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </div>
                                <div class="card-content">
                                    <h3>Memo</h3>
                                    <p>Consignment</p>
                                </div>
                                <div class="card-tag">Track</div>
                            </a>

                            <a href="/admin/restocks" class="operation-card pearl-card">
                                <div class="card-gem"></div>
                                <div class="card-icon">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                </div>
                                <div class="card-content">
                                    <h3>Restocks</h3>
                                    <p>Replenishment</p>
                                </div>
                                <div class="card-tag">Supply</div>
                            </a>

                            <a href="/admin/repairs" class="operation-card citrine-card">
                                <div class="card-gem"></div>
                                <div class="card-icon">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    </svg>
                                </div>
                                <div class="card-content">
                                    <h3>Repairs</h3>
                                    <p>Restoration</p>
                                </div>
                                <div class="card-tag">Service</div>
                            </a>
                        </div>
                    </section>
                </div>

                <!-- Right Column - Services & Analytics -->
                <div class="operations-column">
                    <!-- Custom Services - 5 cards -->
                    <section class="operations-module">
                        <div class="module-header">
                            <div class="module-icon">
                                <div class="icon-frame aquamarine">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="module-title">
                                <h2>CUSTOM SERVICES</h2>
                                <p>Special Orders & Repair Management</p>
                            </div>
                        </div>
                        
                        <div class="five-card-grid">
                            <a href="/admin/custom-orders" class="operation-card aquamarine-card">
                                <div class="card-gem"></div>
                                <div class="card-icon">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </div>
                                <div class="card-content">
                                    <h3>Custom Orders</h3>
                                    <p>Bespoke creations</p>
                                </div>
                                <div class="card-tag">Bespoke</div>
                            </a>

                            <a href="/admin/repairs" class="operation-card citrine-card">
                                <div class="card-gem"></div>
                                <div class="card-icon">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    </svg>
                                </div>
                                <div class="card-content">
                                    <h3>Repairs</h3>
                                    <p>Jewelry repair</p>
                                </div>
                                <div class="card-tag">Service</div>
                            </a>

                            <a href="/admin/memo-inventory" class="operation-card pearl-card">
                                <div class="card-gem"></div>
                                <div class="card-icon">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </div>
                                <div class="card-content">
                                    <h3>Memo</h3>
                                    <p>Consignment</p>
                                </div>
                                <div class="card-tag">Track</div>
                            </a>

                            <a href="/admin/trade-in-check" class="operation-card onyx-card">
                                <div class="card-gem"></div>
                                <div class="card-icon">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                    </svg>
                                </div>
                                <div class="card-content">
                                    <h3>Trade-in</h3>
                                    <p>Evaluation</p>
                                </div>
                                <div class="card-tag">Check</div>
                            </a>

                            <a href="/admin/upcoming-follow-ups" class="operation-card gold-card">
                                <div class="card-gem"></div>
                                <div class="card-icon">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="card-content">
                                    <h3>Follow-ups</h3>
                                    <p>Customer contacts</p>
                                </div>
                                <div class="card-tag">Remind</div>
                            </a>
                        </div>
                    </section>

                    <!-- Analytics & Reports - 5 cards -->
                    <section class="operations-module">
                        <div class="module-header">
                            <div class="module-icon">
                                <div class="icon-frame peridot">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="module-title">
                                <h2>ANALYTICS & REPORTS</h2>
                                <p>Business Intelligence & Insights</p>
                            </div>
                        </div>
                        
                        <div class="five-card-grid">
                            <a href="/admin/analytics" class="operation-card peridot-card">
                                <div class="card-gem"></div>
                                <div class="card-icon">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                    </svg>
                                </div>
                                <div class="card-content">
                                    <h3>Analytics</h3>
                                    <p>Performance</p>
                                </div>
                                <div class="card-tag">Insights</div>
                            </a>

                            <a href="/admin/customers" class="operation-card pink-sapphire-card">
                                <div class="card-gem"></div>
                                <div class="card-icon">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                    </svg>
                                </div>
                                <div class="card-content">
                                    <h3>Customers</h3>
                                    <p>Database</p>
                                </div>
                                <div class="card-tag">Clients</div>
                            </a>

                            <a href="/admin/customer-details-report" class="operation-card topaz-card">
                                <div class="card-gem"></div>
                                <div class="card-icon">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </div>
                                <div class="card-content">
                                    <h3>Customer Rpt</h3>
                                    <p>Details</p>
                                </div>
                                <div class="card-tag">Report</div>
                            </a>

                            <a href="/admin/stock-aging-report" class="operation-card peridot-card">
                                <div class="card-gem"></div>
                                <div class="card-icon">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="card-content">
                                    <h3>Stock Aging</h3>
                                    <p>Analysis</p>
                                </div>
                                <div class="card-tag">Age</div>
                            </a>

                            <a href="/admin/activity-logs" class="operation-card onyx-card">
                                <div class="card-gem"></div>
                                <div class="card-icon">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0zM4 4v4h4"/>
                                    </svg>
                                </div>
                                <div class="card-content">
                                    <h3>Activity</h3>
                                    <p>Audit trail</p>
                                </div>
                                <div class="card-tag">Logs</div>
                            </a>
                        </div>
                    </section>
                </div>
            </div>

            <!-- Live Activity Stream - Full Width at Bottom -->
            <div class="live-activity-section">
                <div class="activity-stream-full">
                    <div class="stream-header">
                        <h3>LIVE ACTIVITY STREAM</h3>
                        <div class="stream-indicator">
                            <div class="pulse-dot"></div>
                            <span>REALTIME</span>
                        </div>
                    </div>
                    <div class="stream-content-full">
                        @forelse($data['recentSales'] as $sale)
                        <div class="stream-item">
                            <div class="item-icon">
                                <div class="icon-pulse {{ $sale->final_total > 5000 ? 'premium' : 'regular' }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="item-details">
                                <div class="item-header">
                                    <span class="item-code">#{{ $sale->receipt_no }}</span>
                                    <span class="item-time">{{ $sale->created_at->setTimezone('America/Denver')->format('h:i A') }}</span>
                                </div>
                                <div class="item-info">
                                    <span class="item-customer">{{ $sale->customer->name ?? 'Walk-in Customer' }}</span>
                                    <span class="item-meta">{{ $sale->items_count ?? 1 }} items • {{ $sale->payment_method ?? 'Cash' }}</span>
                                </div>
                            </div>
                            <div class="item-value">
                                <span class="value-amount">${{ number_format($sale->final_total, 2) }}</span>
                                <span class="value-time">{{ $sale->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                        @empty
                        <div class="stream-empty">
                            <div class="empty-icon">
                                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <p>No recent activity</p>
                            <small>Transactions will appear here</small>
                        </div>
                        @endforelse
                    </div>
                    @if($data['recentSales']->count() > 0)
                    <a href="/admin/sales" class="stream-view-all">
                        View Complete History
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <style>
    /* ===== ULTIMATE JEWELRY DASHBOARD ===== */
    .ultimate-jewel-dashboard {
        --sapphire: linear-gradient(135deg, #2563eb, #1d4ed8);
        --emerald: linear-gradient(135deg, #10b981, #047857);
        --amethyst: linear-gradient(135deg, #8b5cf6, #7c3aed);
        --ruby: linear-gradient(135deg, #ef4444, #dc2626);
        --gold: linear-gradient(135deg, #fbbf24, #d97706);
        --onyx: linear-gradient(135deg, #1f2937, #111827);
        --pearl: linear-gradient(135deg, #f3f4f6, #d1d5db);
        --aquamarine: linear-gradient(135deg, #0ea5e9, #0369a1);
        --citrine: linear-gradient(135deg, #f59e0b, #b45309);
        --peridot: linear-gradient(135deg, #84cc16, #4d7c0f);
        --topaz: linear-gradient(135deg, #f97316, #c2410c);
        --pink-sapphire: linear-gradient(135deg, #ec4899, #be185d);
        
        --platinum: linear-gradient(135deg, #e5e7eb, #9ca3af);
        --diamond: linear-gradient(135deg, #ffffff, #e0e7ff);
        
        --glass-bg: rgba(255, 255, 255, 0.98);
        --glass-border: rgba(255, 255, 255, 0.4);
        --shadow-ultra: 0 30px 60px -15px rgba(0, 0, 0, 0.3);
        --shadow-premium: 0 20px 40px -10px rgba(0, 0, 0, 0.2);
        --radius-2xl: 32px;
        --radius-xl: 28px;
        --radius-lg: 24px;
        --radius-md: 20px;
        --radius-sm: 16px;
        
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
        min-height: 100vh;
        padding: 1rem;
    }

    /* Premium Header */
    .dashboard-header {
        margin-bottom: 2rem;
    }

    .header-platinum {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        border-radius: var(--radius-2xl);
        padding: 1.5rem 2.5rem;
        box-shadow: var(--shadow-ultra);
    }

    .brand-display {
        display: flex;
        align-items: center;
        gap: 1.5rem;
    }

    .diamond-badge {
        width: 72px;
        height: 72px;
        background: white;
        border-radius: 20px;
        padding: 14px;
        box-shadow: 0 0 40px rgba(37, 99, 235, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.6);
    }

    .diamond-logo {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }

    .brand-identity {
        display: flex;
        flex-direction: column;
    }

    .brand-name {
        font-size: 2.25rem;
        font-weight: 900;
        background: linear-gradient(135deg, #ffffff 0%, #93c5fd 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        margin: 0;
    }

    .brand-tag {
        color: #cbd5e1;
        font-size: 0.875rem;
        font-weight: 500;
        margin-top: 0.25rem;
    }

    /* Quick Stats Bar - 5 symmetrical items */
    .quick-stats-bar {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 1rem;
        padding-top: 1.5rem;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    .stat-item {
        text-align: center;
        padding: 1rem;
        background: rgba(255, 255, 255, 0.1);
        border-radius: var(--radius-md);
        border: 1px solid rgba(255, 255, 255, 0.15);
        transition: all 0.3s ease;
    }

    .stat-item:hover {
        background: rgba(255, 255, 255, 0.2);
        transform: translateY(-2px);
    }

    .stat-value {
        display: block;
        font-size: 1.75rem;
        font-weight: 800;
        color: white;
        line-height: 1;
        margin-bottom: 0.25rem;
    }

    .stat-label {
        display: block;
        font-size: 0.75rem;
        color: #cbd5e1;
        font-weight: 500;
    }

    /* Time Widget */
    .time-stat-item {
        padding: 0.75rem;
    }

    .time-widget-compact {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.25rem;
    }

    .time-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.15rem 0.5rem;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 30px;
        color: white;
        font-size: 0.65rem;
        font-weight: 600;
    }

    .compact-time {
        display: flex;
        flex-direction: column;
        line-height: 1.2;
    }

    .time-display {
        color: white;
        font-weight: 700;
        font-size: 0.9rem;
        font-family: monospace;
    }

    .date-display {
        color: #94a3b8;
        font-size: 0.65rem;
    }

    .system-dot {
        width: 6px;
        height: 6px;
        background: #10b981;
        border-radius: 50%;
        position: relative;
        margin-top: 0.1rem;
    }

    .system-dot::after {
        content: '';
        position: absolute;
        inset: -2px;
        background: #10b981;
        border-radius: 50%;
        opacity: 0.5;
        animation: pulse 2s infinite;
    }

    /* Priority Grid - 5 symmetrical cards */
    .priority-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 1.25rem;
    }

    .priority-card {
        min-height: 220px;
    }

    /* Operations Grid - 2 columns */
    .operations-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
        margin-top: 2rem;
    }

    .operations-column {
        display: flex;
        flex-direction: column;
        gap: 2rem;
    }

    /* Five Card Grid - exactly 5 cards per row */
    .five-card-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 1rem;
    }

    /* Operations Modules */
    .operations-module {
        background: var(--glass-bg);
        backdrop-filter: blur(24px);
        border-radius: var(--radius-xl);
        padding: 1.5rem;
        box-shadow: var(--shadow-premium);
        border: 1px solid var(--glass-border);
    }

    .module-header {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1.25rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid rgba(0, 0, 0, 0.08);
    }

    .icon-frame {
        width: 48px;
        height: 48px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    }

    .icon-frame.emerald { background: var(--emerald); }
    .icon-frame.gold { background: var(--gold); }
    .icon-frame.aquamarine { background: var(--aquamarine); }
    .icon-frame.peridot { background: var(--peridot); }

    .module-title h2 {
        font-size: 1.25rem;
        font-weight: 800;
        color: #1e293b;
        margin: 0 0 0.25rem 0;
    }

    .module-title p {
        color: #64748b;
        font-size: 0.75rem;
        margin: 0;
    }

    /* Operation Cards - Symmetrical sizing */
    .operation-card {
        background: white;
        border-radius: var(--radius-lg);
        padding: 1.25rem 1rem;
        text-decoration: none !important;
        position: relative;
        overflow: hidden;
        transition: all 0.3s ease;
        border: 1px solid rgba(255, 255, 255, 0.3);
        display: flex;
        flex-direction: column;
        height: 180px;
    }

    .operation-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-ultra);
    }

    .card-gem {
        position: absolute;
        top: -15px;
        right: -15px;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        opacity: 0.1;
        filter: blur(8px);
    }

    .card-icon {
        margin-bottom: 0.75rem;
        width: 42px;
        height: 42px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }

    .card-icon svg {
        width: 22px;
        height: 22px;
    }

    .card-content {
        flex: 1;
    }

    .card-content h3 {
        font-size: 1rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0 0 0.25rem 0;
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    .badge {
        display: inline-block;
        padding: 0.2rem 0.4rem;
        border-radius: 999px;
        font-size: 0.6rem;
        font-weight: 700;
        margin-left: 0.25rem;
    }

    .card-content p {
        color: #64748b;
        font-size: 0.7rem;
        margin: 0;
        line-height: 1.3;
    }

    .card-tag {
        display: inline-block;
        margin-top: 0.5rem;
        padding: 0.2rem 0.5rem;
        background: rgba(0, 0, 0, 0.05);
        border-radius: 8px;
        font-size: 0.6rem;
        font-weight: 600;
        letter-spacing: 0.3px;
        color: inherit;
        text-transform: uppercase;
        align-self: flex-start;
    }

    /* Card Color Schemes */
    .sapphire-card { background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #1e40af; }
    .sapphire-card .card-icon { background: var(--sapphire); }
    .sapphire-card .card-gem { background: #3b82f6; }

    .emerald-card { background: linear-gradient(135deg, #d1fae5, #a7f3d0); color: #065f46; }
    .emerald-card .card-icon { background: var(--emerald); }
    .emerald-card .card-gem { background: #10b981; }

    .amethyst-card { background: linear-gradient(135deg, #f3e8ff, #e9d5ff); color: #6d28d9; }
    .amethyst-card .card-icon { background: var(--amethyst); }
    .amethyst-card .card-gem { background: #8b5cf6; }

    .ruby-card { background: linear-gradient(135deg, #fee2e2, #fecaca); color: #b91c1c; }
    .ruby-card .card-icon { background: var(--ruby); }
    .ruby-card .card-gem { background: #ef4444; }

    .gold-card { background: linear-gradient(135deg, #fef3c7, #fde68a); color: #92400e; }
    .gold-card .card-icon { background: var(--gold); }
    .gold-card .card-gem { background: #fbbf24; }

    .onyx-card { background: linear-gradient(135deg, #e5e7eb, #d1d5db); color: #111827; }
    .onyx-card .card-icon { background: var(--onyx); }
    .onyx-card .card-gem { background: #374151; }

    .pearl-card { background: linear-gradient(135deg, #f9fafb, #f3f4f6); color: #374151; }
    .pearl-card .card-icon { background: var(--pearl); color: #4b5563; }
    .pearl-card .card-gem { background: #9ca3af; }

    .aquamarine-card { background: linear-gradient(135deg, #e0f2fe, #bae6fd); color: #075985; }
    .aquamarine-card .card-icon { background: var(--aquamarine); }
    .aquamarine-card .card-gem { background: #0ea5e9; }

    .citrine-card { background: linear-gradient(135deg, #fef3c7, #fde68a); color: #92400e; }
    .citrine-card .card-icon { background: var(--citrine); }
    .citrine-card .card-gem { background: #f59e0b; }

    .peridot-card { background: linear-gradient(135deg, #ecfccb, #d9f99d); color: #4d7c0f; }
    .peridot-card .card-icon { background: var(--peridot); }
    .peridot-card .card-gem { background: #84cc16; }

    .topaz-card { background: linear-gradient(135deg, #ffedd5, #fed7aa); color: #9a3412; }
    .topaz-card .card-icon { background: var(--topaz); }
    .topaz-card .card-gem { background: #f97316; }

    .pink-sapphire-card { background: linear-gradient(135deg, #fce7f3, #fbcfe8); color: #be185d; }
    .pink-sapphire-card .card-icon { background: var(--pink-sapphire); }
    .pink-sapphire-card .card-gem { background: #ec4899; }

    /* Live Activity Section - Full Width at Bottom */
    .live-activity-section {
        margin-top: 2rem;
        width: 100%;
    }

    .activity-stream-full {
        background: var(--glass-bg);
        backdrop-filter: blur(24px);
        border-radius: var(--radius-xl);
        padding: 1.5rem;
        box-shadow: var(--shadow-premium);
        border: 1px solid var(--glass-border);
    }

    .stream-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.25rem;
    }

    .stream-header h3 {
        font-size: 1.125rem;
        font-weight: 800;
        color: #1e293b;
        margin: 0;
    }

    .stream-indicator {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.75rem;
        font-weight: 700;
        color: #ef4444;
    }

    .pulse-dot {
        width: 8px;
        height: 8px;
        background: #ef4444;
        border-radius: 50%;
        position: relative;
    }

    .pulse-dot::after {
        content: '';
        position: absolute;
        inset: -2px;
        background: #ef4444;
        border-radius: 50%;
        opacity: 0.5;
        animation: pulse 1.5s infinite;
    }

    .stream-content-full {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
        max-height: none;
        overflow: visible;
        margin-bottom: 1.25rem;
    }

    .stream-item {
        display: flex;
        align-items: center;
        padding: 0.75rem;
        border-radius: var(--radius-md);
        background: #f8fafc;
        transition: all 0.2s ease;
        border: 1px solid transparent;
    }

    .stream-item:hover {
        background: white;
        border-color: #e2e8f0;
    }

    .item-icon {
        margin-right: 0.75rem;
    }

    .icon-pulse {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
    }

    .icon-pulse.regular { background: var(--sapphire); }
    .icon-pulse.premium { background: var(--gold); }

    .item-details {
        flex: 1;
        min-width: 0;
    }

    .item-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.125rem;
    }

    .item-code {
        font-weight: 700;
        color: #1e293b;
        font-size: 0.8rem;
    }

    .item-time {
        font-size: 0.65rem;
        color: #64748b;
    }

    .item-info {
        display: flex;
        gap: 0.5rem;
        font-size: 0.7rem;
    }

    .item-customer {
        font-weight: 500;
        color: #334155;
    }

    .item-meta {
        color: #64748b;
    }

    .item-value {
        text-align: right;
        margin-left: 0.75rem;
    }

    .value-amount {
        display: block;
        font-weight: 800;
        color: #059669;
        font-size: 0.9rem;
        margin-bottom: 0.125rem;
    }

    .value-time {
        font-size: 0.6rem;
        color: #94a3b8;
    }

    .stream-empty {
        grid-column: 1/-1;
        text-align: center;
        padding: 2rem;
        color: #64748b;
    }

    .empty-icon {
        margin-bottom: 0.5rem;
        opacity: 0.3;
    }

    .stream-view-all {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.75rem;
        background: var(--sapphire);
        color: white;
        border-radius: var(--radius-md);
        text-decoration: none;
        font-weight: 600;
        font-size: 0.8rem;
        transition: all 0.2s ease;
    }

    .stream-view-all:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3);
    }

    /* Animations */
    @keyframes pulse {
        0% { transform: scale(1); opacity: 0.5; }
        70% { transform: scale(1.5); opacity: 0; }
        100% { transform: scale(1.5); opacity: 0; }
    }

    /* Responsive Design */
    @media (max-width: 1600px) {
        .five-card-grid {
            grid-template-columns: repeat(3, 1fr);
        }
        .priority-grid {
            grid-template-columns: repeat(3, 1fr);
        }
        .stream-content-full {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 1200px) {
        .operations-grid {
            grid-template-columns: 1fr;
        }
        .quick-stats-bar {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    @media (max-width: 768px) {
        .header-platinum {
            padding: 1rem;
        }
        .brand-display {
            flex-direction: column;
            text-align: center;
        }
        .five-card-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        .priority-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        .stream-content-full {
            grid-template-columns: 1fr;
        }
    }
    </style>

    <script>
    // Real-time New Mexico Clock
    function updateCompactTime() {
        const now = new Date();
        const nmTime = new Date(now.toLocaleString('en-US', { timeZone: 'America/Denver' }));
        
        const timeString = nmTime.toLocaleTimeString('en-US', { 
            hour: '2-digit', 
            minute: '2-digit', 
            second: '2-digit',
            hour12: true 
        });
        
        const dateString = nmTime.toLocaleDateString('en-US', { 
            month: 'short', 
            day: 'numeric'
        });
        
        const timeDisplay = document.querySelector('.time-display');
        const dateDisplay = document.querySelector('.compact-time .date-display');
        
        if (timeDisplay) timeDisplay.textContent = timeString;
        if (dateDisplay) dateDisplay.textContent = dateString;
    }

    updateCompactTime();
    setInterval(updateCompactTime, 1000);
    </script>
</x-filament-widgets::widget>