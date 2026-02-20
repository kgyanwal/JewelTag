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
                            <img src="{{ asset('storage/' . $store->logo_path) }}" 
                                 alt="{{ $store->name }}" 
                                 class="diamond-logo"
                                 style="width: 100%; height: 100%; object-fit: contain;">
                        @else
                            <img src="{{ asset('images/store-placeholder.png') }}" 
                                 alt="Default" 
                                 class="diamond-logo">
                        @endif
                    </div>

                    <div class="brand-identity">
                        <h1 class="brand-name">
                            {{ strtoupper($store->name ?? 'DIAMOND SQUARE') }}
                        </h1>
                        <p class="brand-tag">
                            {{ $store->location ?? 'Premium Jewelry Management Suite' }}
                        </p>
                    </div>
                    
                    <!-- Quick Stats Bar -->
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
                    </div>
                </div>
            </div>
        </header>

        <div class="dashboard-body">
            <!-- Priority Actions Section - NOW AT THE TOP -->
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

                <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 1.25rem;">
                    <!-- Laybuys -->
                    <a href="/admin/laybuys" class="operation-card" style="background: linear-gradient(135deg, #d1fae5, #a7f3d0); color: #065f46; border-color: #10b981;">
                        <div class="card-gem" style="background: #10b981;"></div>
                        <div class="card-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="card-content">
                            <h3>Laybuys <span style="background: #065f46; color: white; padding: 0.25rem 0.5rem; border-radius: 999px; font-size: 0.75rem; margin-left: 0.5rem;">{{ $pendingLaybuys }}</span></h3>
                            <p>Active payment plans</p>
                        </div>
                        <div class="card-tag">Track</div>
                    </a>

                    <!-- Custom Orders -->
                    <a href="/admin/custom-orders" class="operation-card" style="background: linear-gradient(135deg, #f3e8ff, #e9d5ff); color: #6d28d9; border-color: #8b5cf6;">
                        <div class="card-gem" style="background: #8b5cf6;"></div>
                        <div class="card-icon" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </div>
                        <div class="card-content">
                            <h3>Custom Orders <span style="background: #6d28d9; color: white; padding: 0.25rem 0.5rem; border-radius: 999px; font-size: 0.75rem; margin-left: 0.5rem;">{{ $pendingCustomOrders }}</span></h3>
                            <p>In progress / Pending</p>
                        </div>
                        <div class="card-tag">Design</div>
                    </a>

                    <!-- Upcoming Follow-ups (Link to your page) -->
                    <a href="/admin/upcoming-follow-ups" class="operation-card" style="background: linear-gradient(135deg, #fef3c7, #fde68a); color: #92400e; border-color: #f59e0b;">
                        <div class="card-gem" style="background: #f59e0b;"></div>
                        <div class="card-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="card-content">
                            <h3>Follow-ups <span style="background: #92400e; color: white; padding: 0.25rem 0.5rem; border-radius: 999px; font-size: 0.75rem; margin-left: 0.5rem;">{{ $pendingFollowUps }}</span></h3>
                            <p>Pending customer contacts</p>
                        </div>
                        <div class="card-tag">Remind</div>
                    </a>

                    <!-- Memo Inventory -->
                    <a href="/admin/memo-inventory" class="operation-card" style="background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #1e40af; border-color: #3b82f6;">
                        <div class="card-gem" style="background: #3b82f6;"></div>
                        <div class="card-icon" style="background: linear-gradient(135deg, #3b82f6, #2563eb);">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div class="card-content">
                            <h3>Memo Inventory <span style="background: #1e40af; color: white; padding: 0.25rem 0.5rem; border-radius: 999px; font-size: 0.75rem; margin-left: 0.5rem;">{{ $memoItems }}</span></h3>
                            <p>Consignment tracking</p>
                        </div>
                        <div class="card-tag">Track</div>
                    </a>

                    <!-- Trade-in Check (Link to your page) -->
                    <a href="/admin/trade-in-check" class="operation-card" style="background: linear-gradient(135deg, #e0f2fe, #bae6fd); color: #075985; border-color: #0ea5e9;">
                        <div class="card-gem" style="background: #0ea5e9;"></div>
                        <div class="card-icon" style="background: linear-gradient(135deg, #0ea5e9, #0369a1);">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                            </svg>
                        </div>
                        <div class="card-content">
                            <h3>Trade-in Check <span style="background: #075985; color: white; padding: 0.25rem 0.5rem; border-radius: 999px; font-size: 0.75rem; margin-left: 0.5rem;">{{ $tradeInRequests }}</span></h3>
                            <p>Pending evaluations</p>
                        </div>
                        <div class="card-tag">Evaluate</div>
                    </a>
                </div>
            </div>

            <!-- Main Operations Grid (Original Layout) -->
            <div class="operations-grid">
                <!-- Core Operations Column -->
                <div class="operations-column">
                    <!-- Sales Operations -->
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
                        
                        <div class="operations-grid-inner">
                            <a href="/admin/sales/create" class="operation-card sapphire-card">
                                <div class="card-gem"></div>
                                <div class="card-icon">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/>
                                    </svg>
                                </div>
                                <div class="card-content">
                                    <h3>New Sale</h3>
                                    <p>Standard invoice processing</p>
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
                                    <p>Express checkout terminal</p>
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
                                    <p>Transaction history archive</p>
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
                                    <p>Sales report & analytics</p>
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
                                    <p>Daily closing & reconciliation</p>
                                </div>
                                <div class="card-tag">Closing</div>
                            </a>
                        </div>
                    </section>

                    <!-- Inventory & Stock Management -->
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
                        
                        <div class="operations-grid-inner">
                            <a href="/admin/product-items/create" class="operation-card gold-card">
                                <div class="card-gem"></div>
                                <div class="card-icon">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                    </svg>
                                </div>
                                <div class="card-content">
                                    <h3>Add Item</h3>
                                    <p>Create new inventory items</p>
                                </div>
                                <div class="card-tag">Create</div>
                            </a>

                            <a href="/admin/find-stock" class="operation-card onyx-card">
                                <div class="card-gem"></div>
                                <div class="card-icon">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                                    </svg>
                                </div>
                                <div class="card-content">
                                    <h3>Find Stock</h3>
                                    <p>Advanced catalog search</p>
                                </div>
                                <div class="card-tag">Search</div>
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
                                    <p>Consignment tracking</p>
                                </div>
                                <div class="card-tag">Track</div>
                            </a>

                            <a href="/admin/restocks" class="operation-card aquamarine-card">
                                <div class="card-gem"></div>
                                <div class="card-icon">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                </div>
                                <div class="card-content">
                                    <h3>Restocks</h3>
                                    <p>Inventory replenishment</p>
                                </div>
                                <div class="card-tag">Supply</div>
                            </a>

                            <a href="/admin/repairs" class="operation-card citrine-card">
                                <div class="card-gem"></div>
                                <div class="card-icon">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </div>
                                <div class="card-content">
                                    <h3>Repairs</h3>
                                    <p>Jewelry repair & restoration</p>
                                </div>
                                <div class="card-tag">Service</div>
                            </a>
                        </div>
                    </section>
                </div>

                <!-- Services & Analytics Column -->
                <div class="services-column">
                    <!-- Custom Services -->
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
                        
                        <div class="operations-grid-inner">
                            <a href="/admin/custom-orders" class="operation-card aquamarine-card">
                                <div class="card-gem"></div>
                                <div class="card-icon">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                                    </svg>
                                </div>
                                <div class="card-content">
                                    <h3>Custom Orders</h3>
                                    <p>Bespoke jewelry creation</p>
                                </div>
                                <div class="card-tag">Bespoke</div>
                            </a>

                            <a href="/admin/repairs" class="operation-card citrine-card">
                                <div class="card-gem"></div>
                                <div class="card-icon">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </div>
                                <div class="card-content">
                                    <h3>Repairs</h3>
                                    <p>Jewelry repair & restoration</p>
                                </div>
                                <div class="card-tag">Service</div>
                            </a>
                        </div>
                    </section>

                    <!-- Analytics & Reports -->
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
                        
                        <div class="operations-grid-inner">
                            <a href="/admin/analytics" class="operation-card peridot-card">
                                <div class="card-gem"></div>
                                <div class="card-icon">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                    </svg>
                                </div>
                                <div class="card-content">
                                    <h3>Analytics</h3>
                                    <p>Business performance metrics</p>
                                </div>
                                <div class="card-tag">Insights</div>
                            </a>

                            <a href="/admin/customer-details-report" class="operation-card topaz-card">
                                <div class="card-gem"></div>
                                <div class="card-icon">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                </div>
                                <div class="card-content">
                                    <h3>Customer Report</h3>
                                    <p>Customer details & analytics</p>
                                </div>
                                <div class="card-tag">Clients</div>
                            </a>

                            <a href="/admin/customers" class="operation-card pink-sapphire-card">
                                <div class="card-gem"></div>
                                <div class="card-icon">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </div>
                                <div class="card-content">
                                    <h3>Customers</h3>
                                    <p>Client database management</p>
                                </div>
                                <div class="card-tag">Database</div>
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
                                    <p>Inventory age analysis</p>
                                </div>
                                <div class="card-tag">Analysis</div>
                            </a>
                        </div>
                    </section>
                </div>

                <!-- Dashboard Sidebar -->
                <div class="dashboard-sidebar">
                    <!-- Time & Status Widget -->
                    <div class="status-widget">
                        <div class="status-header">
                            <div class="status-badge">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>MST • NEW MEXICO</span>
                            </div>
                        </div>
                        <div class="status-body">
                            <div class="date-display">
                                {{ now()->setTimezone('America/Denver')->format('l, F d, Y') }}
                            </div>
                            <div class="clock-display">
                                <div class="clock-main">
                                    <span class="clock-hour">{{ now()->setTimezone('America/Denver')->format('h') }}</span>
                                    <span class="clock-colon">:</span>
                                    <span class="clock-minute">{{ now()->setTimezone('America/Denver')->format('i') }}</span>
                                    <span class="clock-second">{{ now()->setTimezone('America/Denver')->format('s') }}</span>
                                </div>
                                <div class="clock-meridiem">
                                    {{ now()->setTimezone('America/Denver')->format('A') }}
                                </div>
                            </div>
                            <div class="system-status">
                                <div class="status-indicator active">
                                    <div class="indicator-dot"></div>
                                    <span>SYSTEM ACTIVE</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity Stream -->
                    <div class="activity-stream">
                        <div class="stream-header">
                            <h3>LIVE ACTIVITY STREAM</h3>
                            <div class="stream-indicator">
                                <div class="pulse-dot"></div>
                                <span>REALTIME</span>
                            </div>
                        </div>
                        <div class="stream-content">
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
        position: relative;
        overflow: hidden;
    }

    .header-platinum::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
    }

    .header-inner {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }

    .brand-display {
        display: flex;
        align-items: center;
        gap: 1.5rem;
    }

    .diamond-badge {
        position: relative;
        width: 72px;
        height: 72px;
        background: white;
        border-radius: 20px;
        padding: 14px;
        box-shadow: 0 0 40px rgba(37, 99, 235, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.6);
    }

    .diamond-facet {
        position: absolute;
        width: 8px;
        height: 8px;
        background: white;
        border-radius: 2px;
        transform: rotate(45deg);
        animation: facet-sparkle 4s infinite;
    }

    .facet-1 { top: 10px; left: 10px; animation-delay: 0s; }
    .facet-2 { top: 10px; right: 10px; animation-delay: 1s; }
    .facet-3 { bottom: 10px; right: 10px; animation-delay: 2s; }

    @keyframes facet-sparkle {
        0%, 100% { opacity: 0.3; }
        50% { opacity: 1; }
    }

    .diamond-logo {
        width: 100%;
        height: 100%;
        object-fit: contain;
        filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.2));
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
        letter-spacing: -0.5px;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .brand-tag {
        color: #cbd5e1;
        font-size: 0.875rem;
        font-weight: 500;
        margin-top: 0.25rem;
    }

    .user-console {
        display: flex;
        align-items: center;
    }

    .user-profile-card {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.75rem 1.5rem;
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(12px);
        border-radius: var(--radius-md);
        border: 1px solid rgba(255, 255, 255, 0.25);
    }

    .user-avatar {
        width: 36px;
        height: 36px;
        background: var(--emerald);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
    }

    .user-details {
        display: flex;
        flex-direction: column;
    }

    .user-name {
        font-weight: 600;
        color: white;
        font-size: 0.875rem;
    }

    .user-station {
        font-size: 0.75rem;
        color: #94a3b8;
    }

    /* Quick Stats Bar */
    .quick-stats-bar {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1.5rem;
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

    /* Dashboard Body */
    .dashboard-body {
        margin-top: 2rem;
    }

    .operations-grid {
        display: grid;
        grid-template-columns: 1fr 1fr 380px;
        gap: 2rem;
    }

    .operations-column, .services-column {
        display: flex;
        flex-direction: column;
        gap: 2rem;
    }

    /* Operations Modules */
    .operations-module {
        background: var(--glass-bg);
        backdrop-filter: blur(24px);
        border-radius: var(--radius-xl);
        padding: 2rem;
        box-shadow: var(--shadow-premium);
        border: 1px solid var(--glass-border);
    }

    .module-header {
        display: flex;
        align-items: center;
        gap: 1.25rem;
        margin-bottom: 1.75rem;
        padding-bottom: 1.25rem;
        border-bottom: 1px solid rgba(0, 0, 0, 0.08);
    }

    .module-icon {
        flex-shrink: 0;
    }

    .icon-frame {
        width: 52px;
        height: 52px;
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

    .module-title {
        flex: 1;
    }

    .module-title h2 {
        font-size: 1.5rem;
        font-weight: 800;
        color: #1e293b;
        margin: 0 0 0.375rem 0;
        letter-spacing: -0.25px;
    }

    .module-title p {
        color: #64748b;
        font-size: 0.875rem;
        margin: 0;
        font-weight: 500;
    }

    /* Operations Grid Inner */
    .operations-grid-inner {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1.25rem;
    }

    /* Operation Cards */
    .operation-card {
        background: white;
        border-radius: var(--radius-lg);
        padding: 1.75rem;
        text-decoration: none !important;
        position: relative;
        overflow: hidden;
        transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        border: 1px solid rgba(255, 255, 255, 0.3);
        display: flex;
        flex-direction: column;
        height: 100%;
    }

    .operation-card:hover {
        transform: translateY(-10px) scale(1.02);
        box-shadow: var(--shadow-ultra);
    }

    .card-gem {
        position: absolute;
        top: -20px;
        right: -20px;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        opacity: 0.1;
        filter: blur(10px);
        transition: all 0.4s ease;
    }

    .operation-card:hover .card-gem {
        opacity: 0.2;
        transform: scale(1.5);
    }

    .card-icon {
        position: relative;
        z-index: 2;
        margin-bottom: 1.25rem;
        width: 56px;
        height: 56px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    }

    .card-content {
        position: relative;
        z-index: 2;
        flex: 1;
    }

    .card-content h3 {
        font-size: 1.25rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0 0 0.375rem 0;
        line-height: 1.3;
    }

    .card-content p {
        color: #64748b;
        font-size: 0.875rem;
        margin: 0;
        line-height: 1.5;
    }

    .card-tag {
        position: relative;
        z-index: 2;
        display: inline-block;
        margin-top: 1rem;
        padding: 0.375rem 0.75rem;
        background: rgba(0, 0, 0, 0.08);
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.5px;
        color: inherit;
        text-transform: uppercase;
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

    /* Dashboard Sidebar */
    .dashboard-sidebar {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .status-widget {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        border-radius: var(--radius-xl);
        padding: 2rem;
        color: white;
        box-shadow: var(--shadow-premium);
    }

    .status-header {
        margin-bottom: 1.5rem;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        background: rgba(255, 255, 255, 0.15);
        border-radius: var(--radius-sm);
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.5px;
    }

    .status-body {
        text-align: center;
    }

    .date-display {
        font-size: 0.875rem;
        color: #cbd5e1;
        margin-bottom: 1rem;
        font-weight: 500;
    }

    .clock-display {
        display: flex;
        align-items: baseline;
        justify-content: center;
        gap: 0.5rem;
        margin-bottom: 1.5rem;
    }

    .clock-main {
        font-size: 3rem;
        font-weight: 900;
        line-height: 1;
        display: flex;
        align-items: baseline;
    }

    .clock-colon {
        animation: blink 1s infinite;
    }

    .clock-second {
        font-size: 1.25rem;
        opacity: 0.7;
        margin-left: 0.25rem;
    }

    .clock-meridiem {
        font-size: 1.125rem;
        font-weight: 600;
        color: #cbd5e1;
    }

    .system-status {
        margin-top: 1rem;
    }

    .status-indicator {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        background: rgba(16, 185, 129, 0.2);
        border-radius: var(--radius-sm);
        font-size: 0.75rem;
        font-weight: 600;
        color: #10b981;
    }

    .status-indicator.active .indicator-dot {
        background: #10b981;
    }

    .indicator-dot {
        width: 6px;
        height: 6px;
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

    /* Activity Stream */
    .activity-stream {
        background: var(--glass-bg);
        backdrop-filter: blur(24px);
        border-radius: var(--radius-xl);
        padding: 2rem;
        box-shadow: var(--shadow-premium);
        border: 1px solid var(--glass-border);
    }

    .stream-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }

    .stream-header h3 {
        font-size: 1.125rem;
        font-weight: 800;
        color: #1e293b;
        margin: 0;
        letter-spacing: -0.25px;
    }

    .stream-indicator {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.75rem;
        font-weight: 700;
        color: #ef4444;
        letter-spacing: 0.5px;
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

    .stream-content {
        max-height: 400px;
        overflow-y: auto;
        margin-bottom: 1.5rem;
    }

    .stream-item {
        display: flex;
        align-items: center;
        padding: 1rem;
        border-radius: var(--radius-md);
        background: #f8fafc;
        margin-bottom: 0.75rem;
        transition: all 0.3s ease;
        border: 1px solid transparent;
    }

    .stream-item:hover {
        background: white;
        border-color: #e2e8f0;
        transform: translateX(4px);
    }

    .item-icon {
        margin-right: 1rem;
    }

    .icon-pulse {
        width: 32px;
        height: 32px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
    }

    .icon-pulse.regular { background: var(--sapphire); }
    .icon-pulse.premium { background: var(--gold); }

    .item-details {
        flex: 1;
    }

    .item-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.25rem;
    }

    .item-code {
        font-weight: 700;
        color: #1e293b;
        font-size: 0.875rem;
    }

    .item-time {
        font-size: 0.75rem;
        color: #64748b;
    }

    .item-info {
        display: flex;
        gap: 0.75rem;
        font-size: 0.75rem;
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
        margin-left: 1rem;
    }

    .value-amount {
        display: block;
        font-weight: 800;
        color: #059669;
        font-size: 1.125rem;
        margin-bottom: 0.125rem;
    }

    .value-time {
        font-size: 0.75rem;
        color: #94a3b8;
    }

    .stream-empty {
        text-align: center;
        padding: 3rem 1rem;
        color: #64748b;
    }

    .empty-icon {
        margin-bottom: 1rem;
        opacity: 0.3;
    }

    .stream-empty p {
        font-weight: 600;
        margin-bottom: 0.25rem;
    }

    .stream-empty small {
        font-size: 0.875rem;
        opacity: 0.7;
    }

    .stream-view-all {
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
        font-weight: 700;
        font-size: 0.875rem;
        letter-spacing: 0.5px;
        transition: all 0.3s ease;
    }

    .stream-view-all:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3);
    }

    /* Animations */
    @keyframes blink {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.3; }
    }

    @keyframes pulse {
        0% { transform: scale(1); opacity: 0.5; }
        70% { transform: scale(1.5); opacity: 0; }
        100% { transform: scale(1.5); opacity: 0; }
    }

    /* Custom Scrollbar */
    .stream-content::-webkit-scrollbar {
        width: 6px;
    }

    .stream-content::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 3px;
    }

    .stream-content::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 3px;
    }

    .stream-content::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    /* Responsive Design */
    @media (max-width: 1600px) {
        .operations-grid {
            grid-template-columns: 1fr 380px;
        }
        
        .operations-grid-inner {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 1200px) {
        .operations-grid {
            grid-template-columns: 1fr;
        }
        
        .quick-stats-bar {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 768px) {
        .header-platinum {
            padding: 1.25rem;
        }
        
        .header-inner {
            flex-direction: column;
            gap: 1.5rem;
            text-align: center;
        }
        
        .brand-display {
            flex-direction: column;
            text-align: center;
        }
        
        .user-profile-card {
            width: 100%;
            justify-content: center;
        }
        
        .operations-module {
            padding: 1.5rem;
        }
        
        .operations-grid-inner {
            grid-template-columns: 1fr;
        }
        
        .quick-stats-bar {
            grid-template-columns: 1fr;
            gap: 1rem;
        }
        
        .clock-main {
            font-size: 2.5rem;
        }
    }
    </style>

    <script>
    // Real-time New Mexico Clock
    function updateNMDashboardClock() {
        const now = new Date();
        const nmTime = new Date(now.toLocaleString('en-US', { timeZone: 'America/Denver' }));
        
        const hours = nmTime.getHours();
        const minutes = nmTime.getMinutes().toString().padStart(2, '0');
        const seconds = nmTime.getSeconds().toString().padStart(2, '0');
        const meridiem = hours >= 12 ? 'PM' : 'AM';
        const displayHour = (hours % 12 || 12).toString().padStart(2, '0');
        
        document.querySelector('.clock-hour').textContent = displayHour;
        document.querySelector('.clock-minute').textContent = minutes;
        document.querySelector('.clock-second').textContent = seconds;
        document.querySelector('.clock-meridiem').textContent = meridiem;
        
        const dateOptions = { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' };
        const dateString = nmTime.toLocaleDateString('en-US', dateOptions);
        document.querySelector('.date-display').textContent = dateString;
    }

    // Initialize and update clock
    updateNMDashboardClock();
    setInterval(updateNMDashboardClock, 1000);

    // Interactive card effects
    document.querySelectorAll('.operation-card').forEach(card => {
        card.addEventListener('mouseenter', function(e) {
            const rect = this.getBoundingClientRect();
            const x = ((e.clientX - rect.left) / rect.width) * 100;
            const y = ((e.clientY - rect.top) / rect.height) * 100;
            
            this.style.setProperty('--mouse-x', `${x}%`);
            this.style.setProperty('--mouse-y', `${y}%`);
        });
    });

    // Smooth scroll for activity stream
    const activityStream = document.querySelector('.stream-content');
    if (activityStream) {
        activityStream.addEventListener('wheel', function(e) {
            if (this.scrollHeight > this.clientHeight) {
                e.preventDefault();
                this.scrollTop += e.deltaY * 0.5;
            }
        });
    }

    // Add parallax effect to header
    window.addEventListener('scroll', function() {
        const scrolled = window.pageYOffset;
        const header = document.querySelector('.header-platinum');
        if (header) {
            header.style.transform = `translateY(${scrolled * 0.05}px)`;
        }
    });

    // System status animation
    function updateSystemStatus() {
        const statusDot = document.querySelector('.indicator-dot');
        if (statusDot) {
            statusDot.style.animation = 'pulse 2s infinite';
        }
    }

    updateSystemStatus();
    </script>
</x-filament-widgets::widget>