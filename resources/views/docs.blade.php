{{-- resources/views/docs.blade.php --}}
@extends('layouts.legal')

@section('content')

<div class="min-h-screen">

    {{-- Hero --}}
    <div class="text-center mb-12">
        <div class="inline-flex items-center px-4 py-2 rounded-full bg-[rgba(184,134,59,0.06)] border border-[rgba(184,134,59,0.25)] mb-6">
            <i class="fas fa-book text-[var(--brass-dim)] text-sm mr-2"></i>
            <span class="text-[var(--brass-dim)] text-xs font-semibold tracking-wider">USER &amp; SETUP GUIDES</span>
        </div>
        <h1 class="text-5xl lg:text-6xl font-bold fraunces mb-4">
            <span class="gold-gradient-text">Documentation</span>
        </h1>
        <div class="h-1 w-20 gold-gradient mx-auto rounded-full mb-6"></div>
        <p class="text-[var(--ink-soft)] text-lg max-w-2xl mx-auto">
            Everything you need to set up, run, and get the most out of JewelTag — from first login to your hundredth sale.
        </p>
    </div>

    {{-- Search --}}
    <div class="max-w-xl mx-auto mb-12">
        <div class="relative">
            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-[var(--ink-soft)] text-sm"></i>
            <input type="text" id="docs-search"
                placeholder="Search documentation... e.g. 'repair orders', 'RFID setup'"
                class="w-full pl-11 pr-4 py-3.5 rounded-xl border border-[rgba(33,28,22,0.12)] bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[rgba(184,134,59,0.3)] focus:border-[var(--brass)] transition">
        </div>
    </div>

    <div class="grid lg:grid-cols-[280px_1fr] gap-10">

        {{-- ═══ SIDEBAR ═══ --}}
        <aside class="hidden lg:block">
            <div class="sticky top-28 space-y-6 max-h-[80vh] overflow-y-auto pr-2" style="scrollbar-width:thin;">
                @php
                $nav = [
                    'Getting Started' => [
                        ['id'=>'overview',        'label'=>'Platform Overview'],
                        ['id'=>'pin-login',        'label'=>'PIN Login &amp; Staff Switch'],
                        ['id'=>'quick-start',      'label'=>'Quick Start Guide'],
                        ['id'=>'staff-accounts',   'label'=>'Staff Accounts &amp; Roles'],
                        ['id'=>'store-settings',   'label'=>'Store Settings'],
                    ],
                    'Inventory' => [
                        ['id'=>'adding-items',     'label'=>'Adding Inventory Items'],
                        ['id'=>'rfid-setup',       'label'=>'RFID &amp; Barcode Setup'],
                        ['id'=>'certifications',   'label'=>'Diamond Certifications'],
                        ['id'=>'memo-inventory',   'label'=>'Memo / Consignment'],
                        ['id'=>'stock-transfer',   'label'=>'Multi-Store Transfers'],
                        ['id'=>'inventory-audit',  'label'=>'Inventory Audits'],
                        ['id'=>'restock',          'label'=>'Restocking &amp; Purchase Orders'],
                        ['id'=>'suppliers',        'label'=>'Supplier Management'],
                        ['id'=>'archived-stock',   'label'=>'Archived Stock'],
                        ['id'=>'shopify-sync',     'label'=>'Shopify Sync'],
                    ],
                    'Point of Sale' => [
                        ['id'=>'pos-basics',       'label'=>'Making a Sale'],
                        ['id'=>'split-payment',    'label'=>'Split Payments'],
                        ['id'=>'trade-in',         'label'=>'Trade-Ins'],
                        ['id'=>'trade-in-check',   'label'=>'Trade-In / Scrap Calculator'],
                        ['id'=>'special-jobs',     'label'=>'Special Jobs (Sale Repairs)'],
                        ['id'=>'layaway',          'label'=>'Layaway (Laybuy)'],
                        ['id'=>'commission',       'label'=>'Commission Tracking'],
                        ['id'=>'refunds',          'label'=>'Processing Refunds'],
                        ['id'=>'sale-edit',        'label'=>'Sale Edit Requests'],
                        ['id'=>'deposit-sales',    'label'=>'Deposit Sales'],
                    ],
                    'Custom Orders' => [
                        ['id'=>'custom-orders',    'label'=>'Creating Custom Orders'],
                        ['id'=>'custom-stages',    'label'=>'Order Stages Pipeline'],
                        ['id'=>'custom-deposit',   'label'=>'Deposit → Sale Conversion'],
                    ],
                    'Repairs' => [
                        ['id'=>'repair-orders',    'label'=>'Creating Repair Orders'],
                        ['id'=>'repair-numbering', 'label'=>'Repair Number Format'],
                        ['id'=>'repair-status',    'label'=>'Status &amp; Notifications'],
                    ],
                    'Reports' => [
                        ['id'=>'analytics-dash',   'label'=>'Analytics Dashboard'],
                        ['id'=>'sales-report',     'label'=>'Sales Report &amp; Date Logic'],
                        ['id'=>'my-sales-report',  'label'=>'My Sales Report'],
                        ['id'=>'sold-stock-report','label'=>'Sold Stock Report'],
                        ['id'=>'stock-aging',      'label'=>'Stock Aging Report'],
                        ['id'=>'inactive-stock',   'label'=>'Inactive Stock Report'],
                        ['id'=>'warranty-report',  'label'=>'Warranty Report'],
                        ['id'=>'deposit-report',   'label'=>'Deposit Sales Report'],
                        ['id'=>'restock-logs',     'label'=>'Restock Logs'],
                        ['id'=>'eod-closing',      'label'=>'End of Day Closing'],
                        ['id'=>'pipeline-report',  'label'=>'Custom Order Pipeline'],
                        ['id'=>'laybuy-health',    'label'=>'Laybuy Health Report'],
                        ['id'=>'label-designer',   'label'=>'Label Designer &amp; Printing'],
                    ],
                    'CRM_JewelTag' => [
                        ['id'=>'customer-profiles','label'=>'Customer Profiles'],
                        ['id'=>'wishlists',        'label'=>'Wishlists &amp; Follow-ups'],
                        ['id'=>'marketing',        'label'=>'Marketing Automation'],
                    ],
                    'Admin &amp; Tools' => [
                        ['id'=>'find-tools',       'label'=>'Find Stock / Sale / Customer'],
                        ['id'=>'activity-logs',    'label'=>'Activity Logs'],
                        ['id'=>'deletion-requests','label'=>'Deletion Requests'],
                        ['id'=>'support-tickets',  'label'=>'Support Tickets'],
                        ['id'=>'api-keys',         'label'=>'API Keys'],
                        ['id'=>'roles-perms',      'label'=>'Roles &amp; Permissions'],
                    ],
                    'Integrations' => [
                        ['id'=>'quickbooks',       'label'=>'QuickBooks Sync'],
                        ['id'=>'api-link',         'label'=>'API Reference'],
                    ],
                ];
                @endphp
                @foreach($nav as $group => $items)
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-[0.12em] text-[var(--brass-dim)] mb-2 px-3">{!! $group !!}</div>
                    <div class="space-y-0.5">
                        @foreach($items as $item)
                        <a href="#{{ $item['id'] }}" class="docs-side-link block px-3 py-1.5 rounded-lg text-sm text-[var(--ink-soft)] hover:text-[var(--brass-dim)] hover:bg-[rgba(184,134,59,0.06)] transition-colors">
                            {!! $item['label'] !!}
                        </a>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
        </aside>

        {{-- ═══ MAIN CONTENT ═══ --}}
        <div class="min-w-0">

            {{-- ── GETTING STARTED ── --}}
            <div class="docs-group-label">Getting Started</div>

            <div id="overview" class="docs-section mb-14 scroll-mt-28">
                <h2 class="docs-h2">Platform Overview</h2>
                <p class="docs-p">JewelTag is built around four connected modules that share the same underlying item and customer records. Anything you scan at intake follows the piece through pricing, sale, and any future repair — without re-entering data.</p>
                <div class="grid sm:grid-cols-2 gap-3 mt-5">
                    @foreach([
                        ['fas fa-tags','Inventory','RFID, barcodes, certs, Shopify sync'],
                        ['fas fa-cash-register','Point of Sale','Layaway, trade-ins, commission splits'],
                        ['fas fa-screwdriver-wrench','Repairs','Work orders, photos, status notifications'],
                        ['fas fa-heart','CRM_JewelTag','Profiles, wishlists, marketing automation'],
                    ] as [$icon,$title,$desc])
                    <div class="bg-[var(--case-felt)] rounded-xl p-4 border border-[rgba(33,28,22,0.07)]">
                        <i class="{{ $icon }} text-[var(--brass)] mb-2 block"></i>
                        <div class="text-sm font-bold text-[var(--onyx)]">{{ $title }}</div>
                        <div class="text-xs text-[var(--ink-soft)] mt-0.5">{{ $desc }}</div>
                    </div>
                    @endforeach
                </div>
            </div>

            <div id="pin-login" class="docs-section mb-14 scroll-mt-28">
                <h2 class="docs-h2">PIN Login &amp; Staff Switch</h2>
                <p class="docs-p">JewelTag uses a two-layer authentication system. The store owner logs in with their email and password once — after that, individual staff members use a 4-digit PIN to identify themselves at the counter without fully logging out.</p>
                <div class="docs-callout-warn mt-4">
                    <i class="fas fa-circle-exclamation mr-2"></i>
                    <strong>First-day confusion point:</strong> staff who try to log in with their email at <code>/admin/login</code> won't see the PIN screen. The owner must log in first, then staff tap <strong>Switch Associate</strong> from the user menu top-right.
                </div>
                <div class="docs-ss-block mt-5">
                    <img src="/pin-login-screen.png" alt="PIN login screen" class="docs-ss" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <div class="docs-ss-missing"><i class="fas fa-image"></i><span>pin-login-screen.png → public/pin-login-screen.png</span></div>
                    <div class="docs-ss-cap">PIN login screen — staff enter their 4-digit code here after the owner has authenticated</div>
                </div>
                <div class="docs-ss-block mt-4">
                    <img src="/switch-associate-modal.png" alt="Switch Associate modal" class="docs-ss" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <div class="docs-ss-missing"><i class="fas fa-image"></i><span>switch-associate-modal.png → public/switch-associate-modal.png</span></div>
                    <div class="docs-ss-cap">Switch Associate modal — accessible from the top-right user menu at any time</div>
                </div>
                <h3 class="docs-h3 mt-5">Steps to switch staff mid-shift</h3>
                <ol class="docs-ol">
                    <li>Click your name / avatar in the top-right corner of the admin panel</li>
                    <li>Select <strong>Switch Associate PIN</strong></li>
                    <li>The new associate enters their 4-digit PIN</li>
                    <li>All subsequent sales and repairs are attributed to the new associate</li>
                </ol>
            </div>

            <div id="quick-start" class="docs-section mb-14 scroll-mt-28">
                <h2 class="docs-h2">Quick Start Guide</h2>
                <p class="docs-p">Most stores take their first sale on day one. Follow this order:</p>
                <div class="space-y-3 mt-4">
                    @foreach([
                        ['01','Log in &amp; set up your store','Add store name, address, timezone, and tax rate under Settings → Store.'],
                        ['02','Create staff accounts','Add each associate under Users, assign a role, and set their 4-digit PIN.'],
                        ['03','Import or add inventory','Upload your existing spreadsheet, or start scanning items in with a barcode gun.'],
                        ['04','Set up your label printer','Connect your Zebra ZD621R to the network and enter its IP under Settings → Hardware.'],
                        ['05','Take your first sale','Scan an item, add a customer, choose a payment method, and complete the sale.'],
                    ] as [$n,$t,$d])
                    <div class="flex gap-4 items-start p-4 rounded-xl border border-[rgba(33,28,22,0.07)] bg-white">
                        <div class="fraunces font-bold text-sm w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0 border-[1.5px]" style="background:var(--onyx);color:var(--brass-bright);border-color:var(--brass);">{{ $n }}</div>
                        <div>
                            <div class="font-bold text-sm text-[var(--onyx)]">{!! $t !!}</div>
                            <div class="text-sm text-[var(--ink-soft)] mt-0.5">{{ $d }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <div id="staff-accounts" class="docs-section mb-14 scroll-mt-28">
                <h2 class="docs-h2">Staff Accounts &amp; Roles</h2>
                <p class="docs-p">Every staff member gets their own account so sales, commissions, and repairs are correctly attributed. Roles control what each person can see and do.</p>
                <div class="overflow-x-auto mt-4">
                    <table class="docs-table">
                        <thead><tr><th>Role</th><th>Access</th></tr></thead>
                        <tbody>
                            <tr><td class="font-semibold">Superadmin / Owner</td><td>Full access — settings, all reports, user management, deletion requests.</td></tr>
                            <tr><td class="font-semibold">Administration</td><td>All reports, settings, no system-level changes.</td></tr>
                            <tr><td class="font-semibold">Sales Associate</td><td>POS, customer profiles, repair intake. No settings or financial reports.</td></tr>
                            <tr><td class="font-semibold">Bench / Repair</td><td>Repair queue and status updates only.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="store-settings" class="docs-section mb-14 scroll-mt-28">
                <h2 class="docs-h2">Store Settings</h2>
                <p class="docs-p">Under <strong>Settings → Store</strong> configure the fields that affect every transaction — tax rate, default currency, store address on receipts, and logo. The timezone here controls how dates display in all reports.</p>
                <div class="docs-callout mt-4">
                    <i class="fas fa-circle-info mr-2"></i>
                    If your Sales Report shows transactions at midnight UTC instead of local time, this timezone setting is the first thing to check.
                </div>
            </div>

            {{-- ── INVENTORY ── --}}
            <div class="docs-group-label">Inventory</div>

            <div id="adding-items" class="docs-section mb-14 scroll-mt-28">
                <h2 class="docs-h2">Adding Inventory Items</h2>
                <p class="docs-p">Each inventory item holds up to 62 fields — but most are optional. At minimum you need a barcode, cost price, and supplier. Everything else (certifications, stone weights, RFID) can be added later.</p>
                <div class="docs-ss-block mt-4">
                    <img src="/new-inventory-form.png" alt="New Inventory Form" class="docs-ss" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <div class="docs-ss-missing"><i class="fas fa-image"></i><span>new-inventory-form.png → public/new-inventory-form.png</span></div>
                    <div class="docs-ss-cap">New Inventory form — field groups: Basic Info, Stone Details, Metal, Pricing, Status</div>
                </div>
                <h3 class="docs-h3 mt-5">Field groups explained</h3>
                <div class="space-y-3">
                    @foreach([
                        ['Basic Info','Department, sub-department, category, supplier code, custom description, primary image'],
                        ['Stone Details','Shape, color, clarity, cut, polish, symmetry, fluorescence, measurements, diamond weight, is_lab_grown'],
                        ['Certification','Certificate number (GIA/IGI/AGS), certificate agency — prints on customer receipts'],
                        ['Metal','Metal type, purity, gross weight, stone weight, metal weight, size'],
                        ['Pricing','Cost price (hidden from sales staff), retail price, web price, markup %, discount %'],
                        ['Status &amp; Flags','Status (in_stock/on_hold/sold/inactive), is_memo, is_trade_in, RFID code, barcode, serial number'],
                    ] as [$group, $desc])
                    <div class="p-3 rounded-lg bg-[var(--case-felt)] border border-[rgba(33,28,22,0.07)]">
                        <span class="text-xs font-bold text-[var(--brass-dim)] uppercase tracking-wide">{{ $group }}</span>
                        <p class="text-sm text-[var(--ink-soft)] mt-1">{!! $desc !!}</p>
                    </div>
                    @endforeach
                </div>
                <h3 class="docs-h3 mt-5">CSV Bulk Import</h3>
                <div class="bg-[var(--onyx)] rounded-xl p-4 mt-2">
                    <div class="text-xs text-[rgba(250,246,238,0.45)] font-mono mb-2">Minimum required CSV columns</div>
                    <code class="text-[var(--brass-bright)] text-xs font-mono leading-loose block">barcode, cost_price, supplier_id, department, category, retail_price, metal_type, status</code>
                </div>
            </div>

            <div id="rfid-setup" class="docs-section mb-14 scroll-mt-28">
                <h2 class="docs-h2">RFID &amp; Barcode Setup</h2>
                <p class="docs-p">JewelTag supports both barcode scanning and RFID. Barcodes work on all plans. RFID (the <code>rfid_code</code> field on each item) is available on Pro and above.</p>
                <div class="docs-ss-block mt-4">
                    <img src="/rfid-barcode-scan-modal.png" alt="RFID scan modal" class="docs-ss" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <div class="docs-ss-missing"><i class="fas fa-image"></i><span>rfid-barcode-scan-modal.png → public/rfid-barcode-scan-modal.png</span></div>
                    <div class="docs-ss-cap">Scan modal — click the barcode icon on any search field to open this</div>
                </div>
                <h3 class="docs-h3 mt-5">Zebra ZD621R Label Printer</h3>
                <p class="docs-p">Your Zebra printer connects over the local network at <code>192.168.1.60</code> by default. Update the IP under <strong>Settings → Hardware</strong> if your network differs.</p>
                <div class="docs-callout-warn mt-3">
                    <i class="fas fa-triangle-exclamation mr-2"></i>
                    <strong>Zebra Browser Print must be running</strong> on the printing computer. The status dot top-right shows: green = online, yellow = app running but no printer, grey = app not running.
                </div>
            </div>

            <div id="certifications" class="docs-section mb-14 scroll-mt-28">
                <h2 class="docs-h2">Diamond Certifications</h2>
                <p class="docs-p">Attach GIA, IGI, AGS, EGL, or HRD certification details to any inventory item via <strong>Certificate Number</strong> and <strong>Certificate Agency</strong> fields. These print on customer receipts and appraisal documents automatically. The cert stays linked to the item's history even after it sells.</p>
            </div>

            <div id="memo-inventory" class="docs-section mb-14 scroll-mt-28">
                <h2 class="docs-h2">Memo / Consignment Inventory</h2>
                <p class="docs-p">Items received on memo from a vendor are flagged <code>is_memo = true</code> and linked to a Memo Vendor. The <code>memo_status</code> field tracks lifecycle:</p>
                <div class="overflow-x-auto mt-4">
                    <table class="docs-table">
                        <thead><tr><th>Status</th><th>Meaning</th></tr></thead>
                        <tbody>
                            <tr><td><code>on_memo</code></td><td>In your store, not yet owned — vendor can recall it</td></tr>
                            <tr><td><code>sold</code></td><td>Sold — you owe the vendor; reconcile under Memo Inventory</td></tr>
                            <tr><td><code>returned</code></td><td>Physically returned to vendor; closes out the memo line</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="docs-callout-warn mt-4">
                    <i class="fas fa-triangle-exclamation mr-2"></i>
                    Memo items over 60 days without a status change are flagged in the Memo Aging Report — check monthly to avoid vendor disputes.
                </div>
            </div>

            <div id="stock-transfer" class="docs-section mb-14 scroll-mt-28">
                <h2 class="docs-h2">Multi-Store Stock Transfers</h2>
                <p class="docs-p">Move items between locations via <strong>Inventory → Stock Transfers</strong>. Items stay linked to full history across all stores.</p>
                <ol class="docs-ol mt-4">
                    <li>Go to <strong>Inventory → Stock Transfers → New Transfer</strong></li>
                    <li>Select source store and destination store</li>
                    <li>Scan or search items to include</li>
                    <li>Submit — items move to <em>In Transit</em> status</li>
                    <li>Destination store confirms receipt — status becomes <em>Completed</em></li>
                </ol>
            </div>

            <div id="inventory-audit" class="docs-section mb-14 scroll-mt-28">
                <h2 class="docs-h2">Inventory Audits</h2>
                <p class="docs-p">Run a physical count audit under <strong>Inventory → Inventory Audits</strong>. JewelTag compares your counted quantities against the system's expected stock and generates a variance report — showing shrinkage, overages, and unaccounted items.</p>
                <div class="docs-ss-block mt-4">
                    <img src="/inventory-audit.png" alt="Inventory Audit" class="docs-ss" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <div class="docs-ss-missing"><i class="fas fa-image"></i><span>inventory-audit.png → public/inventory-audit.png</span></div>
                    <div class="docs-ss-cap">Inventory Audit — scan or manually count items; variance column highlights discrepancies</div>
                </div>
                <h3 class="docs-h3 mt-4">How an audit works</h3>
                <ol class="docs-ol">
                    <li>Create a new audit — it snapshots expected counts from the database</li>
                    <li>Scan or enter actual counts for each item or location</li>
                    <li>Submit — the system calculates variance per item (<code>audit_items</code> table)</li>
                    <li>Review the variance report — investigate any unexplained differences</li>
                </ol>
                <div class="docs-callout mt-4">
                    <i class="fas fa-circle-info mr-2"></i>
                    RFID-enabled stores can complete a full audit 5–10× faster by scanning batches of tags simultaneously rather than one at a time.
                </div>
            </div>

            <div id="restock" class="docs-section mb-14 scroll-mt-28">
                <h2 class="docs-h2">Restocking &amp; Purchase Orders</h2>
                <p class="docs-p">When new stock arrives from a supplier, record it under <strong>Inventory → Restock</strong>. Each restock entry logs the supplier, quantity, cost, and receipt date — creating a permanent purchase history in the <code>restocks</code> table.</p>
                <div class="docs-ss-block mt-4">
                    <img src="/restock-logs.png" alt="Restock logs" class="docs-ss" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <div class="docs-ss-missing"><i class="fas fa-image"></i><span>restock-logs.png → public/restock-logs.png</span></div>
                    <div class="docs-ss-cap">Restock Logs — full purchase history by supplier, date, and item</div>
                </div>
                <div class="docs-callout mt-4">
                    <i class="fas fa-circle-info mr-2"></i>
                    Restock entries do not automatically create inventory items — they log the purchase event. You still need to add individual items to inventory via the standard item form or CSV import.
                </div>
            </div>

            <div id="suppliers" class="docs-section mb-14 scroll-mt-28">
                <h2 class="docs-h2">Supplier Management</h2>
                <p class="docs-p">Manage your vendors under <strong>Inventory → Suppliers</strong>. Every inventory item links to a supplier via <code>supplier_id</code> — so your cost reports and restock logs always show which vendor each piece came from.</p>
                <div class="docs-ss-block mt-4">
                    <img src="/supplier-management.png" alt="Supplier management" class="docs-ss" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <div class="docs-ss-missing"><i class="fas fa-image"></i><span>supplier-management.png → public/supplier-management.png</span></div>
                    <div class="docs-ss-cap">Suppliers list — name, contact, and item count per vendor</div>
                </div>
                <div class="docs-callout-warn mt-4">
                    <i class="fas fa-triangle-exclamation mr-2"></i>
                    Create all suppliers <em>before</em> importing inventory via CSV — the import requires a valid <code>supplier_id</code> for every row.
                </div>
            </div>

            <div id="archived-stock" class="docs-section mb-14 scroll-mt-28">
                <h2 class="docs-h2">Archived Stock</h2>
                <p class="docs-p">Items can be archived rather than deleted — archiving removes them from active inventory counts and reports without permanently losing their history. Access archived items under <strong>Inventory → Archived Stock</strong>.</p>
                <div class="docs-callout mt-4">
                    <i class="fas fa-circle-info mr-2"></i>
                    Common reasons to archive: item sent for repair by supplier, item lost/stolen (pending investigation), end-of-season pieces being held. Archived items can be restored to active stock at any time.
                </div>
            </div>

            <div id="shopify-sync" class="docs-section mb-14 scroll-mt-28">
                <h2 class="docs-h2">Shopify Sync</h2>
                <p class="docs-p">JewelTag pushes inventory items to Shopify and keeps stock counts in sync both ways. Synced items store <code>shopify_product_id</code> and <code>shopify_inventory_item_id</code> on the item record.</p>
                <div class="docs-ss-block mt-4">
                    <img src="/shopify-push-button.png" alt="Shopify push button" class="docs-ss" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <div class="docs-ss-missing"><i class="fas fa-image"></i><span>shopify-push-button.png → public/shopify-push-button.png</span></div>
                    <div class="docs-ss-cap">Shopify sync button on an inventory item's detail page</div>
                </div>
                <h3 class="docs-h3 mt-4">Required Shopify scopes</h3>
                <div class="bg-[var(--onyx)] rounded-xl p-4">
                    <code class="text-[var(--brass-bright)] text-xs font-mono leading-loose block">write_products · write_inventory · read_locations</code>
                </div>
                <div class="docs-callout-warn mt-4">
                    <i class="fas fa-triangle-exclamation mr-2"></i>
                    Images must be under 2MB. Synced items get the <code>jeweltag-instore</code> Shopify tag automatically. If the push fails, check the browser console — the most common cause is an oversized image.
                </div>
            </div>

            {{-- ── POINT OF SALE ── --}}
            <div class="docs-group-label">Point of Sale</div>

            <div id="pos-basics" class="docs-section mb-14 scroll-mt-28">
                <h2 class="docs-h2">Making a Sale</h2>
                <p class="docs-p">Go to <strong>Sales → New Sale</strong>. Scan or search items to add to cart, attach a customer, choose a payment method, and complete. Inventory moves from <code>in_stock</code> to <code>sold</code> the moment the receipt prints.</p>
                <div class="docs-callout mt-4">
                    <i class="fas fa-circle-info mr-2"></i>
                    <strong>Duplicate sale guard:</strong> submitting twice within 30 seconds shows a warning and blocks the second transaction. Dismiss it and check the Sales list — the first sale went through.
                </div>
            </div>

            <div id="split-payment" class="docs-section mb-14 scroll-mt-28">
                <h2 class="docs-h2">Split Payments</h2>
                <p class="docs-p">Pay with multiple methods in one sale (e.g. $500 card + $200 cash). The split section is <strong>collapsed by default</strong> — click the toggle to expand it.</p>
                <div class="docs-ss-block mt-4">
                    <img src="/split-payment-expanded.png" alt="Split payment expanded" class="docs-ss" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <div class="docs-ss-missing"><i class="fas fa-image"></i><span>split-payment-expanded.png → public/split-payment-expanded.png</span></div>
                    <div class="docs-ss-cap">Split payment expanded — add payment lines; running total updates live</div>
                </div>
                <div class="docs-callout-warn mt-4">
                    <i class="fas fa-triangle-exclamation mr-2"></i>
                    The split total must match the sale total exactly — even a cent difference keeps the submit button disabled.
                </div>
            </div>

            <div id="trade-in" class="docs-section mb-14 scroll-mt-28">
                <h2 class="docs-h2">Trade-Ins</h2>
                <p class="docs-p">Enable the <strong>Trade-In</strong> toggle on a sale to reveal trade-in fields. The value entered subtracts directly from the balance due.</p>
                <div class="docs-ss-block mt-4">
                    <img src="/trade-in-section.png" alt="Trade-in section" class="docs-ss" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <div class="docs-ss-missing"><i class="fas fa-image"></i><span>trade-in-section.png → public/trade-in-section.png</span></div>
                    <div class="docs-ss-cap">Trade-In toggle expanded — description, value, and auto-generated receipt number</div>
                </div>
                <div class="overflow-x-auto mt-4">
                    <table class="docs-table">
                        <thead><tr><th>Field</th><th>Purpose</th></tr></thead>
                        <tbody>
                            <tr><td><code>trade_in_value</code></td><td>Amount credited — reduces balance due</td></tr>
                            <tr><td><code>trade_in_description</code></td><td>Description of traded piece (e.g. "14K yellow gold ring, 4.2g")</td></tr>
                            <tr><td><code>trade_in_receipt_no</code></td><td>Auto-generated receipt number — keep for records</td></tr>
                        </tbody>
                    </table>
                </div>
                <p class="docs-p mt-3">Add the traded-in piece to inventory as a new item with <code>is_trade_in = true</code> to track its origin.</p>
            </div>

            <div id="trade-in-check" class="docs-section mb-14 scroll-mt-28">
                <h2 class="docs-h2">Trade-In / Scrap Gold Calculator</h2>
                <p class="docs-p">The <strong>Trade-In Check</strong> page is a standalone scrap gold and trade value calculator — separate from the sale form. Use it at the counter to quickly estimate what a customer's piece is worth before committing to a purchase price.</p>
                <div class="docs-ss-block mt-4">
                    <img src="/trade-in-check.png" alt="Trade-in check calculator" class="docs-ss" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <div class="docs-ss-missing"><i class="fas fa-image"></i><span>trade-in-check.png → public/trade-in-check.png</span></div>
                    <div class="docs-ss-cap">Trade-In Check — enter metal type, purity, and weight to get a live scrap value estimate</div>
                </div>
                <p class="docs-p mt-3">The calculator uses live metal spot prices. It does not create any record — it's purely for estimation. Once you agree on a price, enter the trade-in on the actual sale form.</p>
            </div>

            <div id="special-jobs" class="docs-section mb-14 scroll-mt-28">
                <h2 class="docs-h2">Special Jobs (Repairs Within a Sale)</h2>
                <p class="docs-p">A Special Job is a repair billed as part of a sale — e.g. sizing a ring at purchase. It appears as a receipt line item AND creates a repair work order automatically.</p>
                <div class="docs-ss-block mt-4">
                    <img src="/special-jobs-section.png" alt="Special Jobs section" class="docs-ss" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <div class="docs-ss-missing"><i class="fas fa-image"></i><span>special-jobs-section.png → public/special-jobs-section.png</span></div>
                    <div class="docs-ss-cap">Special Jobs — at the very bottom of the New Sale form, collapsed by default</div>
                </div>
                <div class="docs-callout-warn mt-4">
                    <i class="fas fa-triangle-exclamation mr-2"></i>
                    This is the most-missed feature by new staff. It is at the bottom of the sale form and collapsed — scroll all the way down and open it.
                </div>
            </div>

            <div id="layaway" class="docs-section mb-14 scroll-mt-28">
                <h2 class="docs-h2">Layaway (Laybuy)</h2>
                <p class="docs-p">Layaway holds an item for a customer with a deposit, paid off over time. The item immediately becomes <code>on_hold</code> via <code>held_by_sale_id</code> — it won't appear as available stock.</p>
                <div class="docs-ss-block mt-4">
                    <img src="/laybuy-creation-on-hold.png" alt="Laybuy creation" class="docs-ss" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <div class="docs-ss-missing"><i class="fas fa-image"></i><span>laybuy-creation-on-hold.png → public/laybuy-creation-on-hold.png</span></div>
                    <div class="docs-ss-cap">Laybuy creation — item status changes to ON HOLD as soon as the first deposit is recorded</div>
                </div>
                <h3 class="docs-h3 mt-4">Laybuy number format</h3>
                <p class="docs-p">Plans are numbered <code>LB-YYYYMMDD-HIS</code> (e.g. <code>LB-20260609-143022</code>). The timestamp ensures uniqueness even for same-day plans.</p>
                <h3 class="docs-h3 mt-4">Recording a payment</h3>
                <ol class="docs-ol">
                    <li>Open the plan from <strong>Sales → Layaways</strong></li>
                    <li>Click <strong>Record Payment</strong></li>
                    <li>Enter amount and method — creates a row in <code>laybuy_payments</code>, updates <code>last_paid_date</code></li>
                    <li>When <code>balance_due</code> hits zero the plan auto-closes and item moves to <code>sold</code></li>
                </ol>
                <h3 class="docs-h3 mt-4">If a customer cancels</h3>
                <p class="docs-p">Mark the plan <em>Cancelled</em> — the item returns to <code>in_stock</code> automatically. Process any deposit refund manually from <strong>Sales → Refunds</strong>.</p>
            </div>

            <div id="commission" class="docs-section mb-14 scroll-mt-28">
                <h2 class="docs-h2">Commission Tracking</h2>
                <p class="docs-p">When multiple staff share a sale, each person's split percentage is stored in the <code>sales_person_list</code> JSON field. The <strong>My Sales Report</strong> uses this to calculate per-associate earnings for any period.</p>
                <div class="docs-callout mt-4">
                    <i class="fas fa-circle-info mr-2"></i>
                    Commission splits are set at sale time. Retroactive edits require an admin-approved <strong>Sale Edit Request</strong>.
                </div>
            </div>

            <div id="refunds" class="docs-section mb-14 scroll-mt-28">
                <h2 class="docs-h2">Processing Refunds</h2>
                <p class="docs-p">Process refunds under <strong>Sales → Refunds → New Refund</strong>. Link the refund to the original sale, specify the amount and reason, and confirm. The refund is recorded in the <code>refunds</code> table and appears in your daily closing totals as a deduction.</p>
                <div class="docs-ss-block mt-4">
                    <img src="/refund-process.png" alt="Refund process" class="docs-ss" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <div class="docs-ss-missing"><i class="fas fa-image"></i><span>refund-process.png → public/refund-process.png</span></div>
                    <div class="docs-ss-cap">New Refund form — link to original sale, enter amount and reason</div>
                </div>
                <div class="docs-callout-warn mt-4">
                    <i class="fas fa-triangle-exclamation mr-2"></i>
                    Refunding a sale does <strong>not</strong> automatically return the item to inventory. If the customer returned the physical piece, go to the item record and manually set its status back to <code>in_stock</code>.
                </div>
                <h3 class="docs-h3 mt-4">Refund reasons tracked</h3>
                <p class="docs-p">Always enter a reason — refund patterns by staff member and by reason are visible to admins in the sales audit trail and help identify training gaps or policy issues.</p>
            </div>

            <div id="sale-edit" class="docs-section mb-14 scroll-mt-28">
                <h2 class="docs-h2">Sale Edit Requests</h2>
                <p class="docs-p">Once a sale is completed, it cannot be edited directly. Staff submit a <strong>Sale Edit Request</strong> explaining the change needed — an admin reviews and approves or rejects it. Every request and decision is logged permanently.</p>
                <div class="docs-ss-block mt-4">
                    <img src="/sale-edit-request.png" alt="Sale edit request" class="docs-ss" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <div class="docs-ss-missing"><i class="fas fa-image"></i><span>sale-edit-request.png → public/sale-edit-request.png</span></div>
                    <div class="docs-ss-cap">Sale Edit Request — staff describe the change needed; admin sees a queue of pending requests</div>
                </div>
                <div class="docs-callout mt-4">
                    <i class="fas fa-circle-info mr-2"></i>
                    Admins see all pending requests under <strong>Admin → Sale Edit Requests</strong>. Approved edits are applied immediately and the approval is logged in the activity trail with the approving admin's name.
                </div>
            </div>

            <div id="deposit-sales" class="docs-section mb-14 scroll-mt-28">
                <h2 class="docs-h2">Deposit Sales</h2>
                <p class="docs-p">A Deposit Sale is a partial payment collected upfront for a future purchase — different from a Layaway (which holds a specific item) and different from a Custom Order deposit (which is tied to a production order). Deposit sales are tracked in the <code>deposit_sales</code> table and appear in the <strong>Deposit Sales Report</strong>.</p>
                <div class="docs-ss-block mt-4">
                    <img src="/deposit-sales-report.png" alt="Deposit sales report" class="docs-ss" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <div class="docs-ss-missing"><i class="fas fa-image"></i><span>deposit-sales-report.png → public/deposit-sales-report.png</span></div>
                    <div class="docs-ss-cap">Deposit Sales Report — all open deposits with customer, amount held, and age</div>
                </div>
            </div>

            {{-- ── CUSTOM ORDERS ── --}}
            <div class="docs-group-label">Custom Orders</div>

            <div id="custom-orders" class="docs-section mb-14 scroll-mt-28">
                <h2 class="docs-h2">Creating Custom Orders</h2>
                <p class="docs-p">Custom Orders are for pieces that don't exist yet — commissioned from scratch or special-ordered from a vendor. They live in their own pipeline separate from regular sales.</p>
                <h3 class="docs-h3 mt-4">The three date fields — all different</h3>
                <div class="overflow-x-auto mt-3">
                    <table class="docs-table">
                        <thead><tr><th>Field</th><th>Meaning</th></tr></thead>
                        <tbody>
                            <tr><td><code>due_date</code></td><td>Date <em>promised to the customer</em></td></tr>
                            <tr><td><code>expected_delivery_date</code></td><td>When you expect the vendor to deliver <em>to you</em> — earlier than due_date</td></tr>
                            <tr><td><code>follow_up_date</code></td><td>When staff should next contact the customer with a status update</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="custom-stages" class="docs-section mb-14 scroll-mt-28">
                <h2 class="docs-h2">Order Stages — The Pipeline</h2>
                <p class="docs-p">Every custom order moves through six stages. The Pipeline Report groups all orders by stage so you can see at a glance what's stuck and what's overdue.</p>
                <div class="docs-ss-block mt-4">
                    <img src="/custom-order-stages.png" alt="Custom order stages" class="docs-ss" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <div class="docs-ss-missing"><i class="fas fa-image"></i><span>custom-order-stages.png → public/custom-order-stages.png</span></div>
                    <div class="docs-ss-cap">The six status stages — each maps to a column in the Pipeline Report</div>
                </div>
                <div class="overflow-x-auto mt-4">
                    <table class="docs-table">
                        <thead><tr><th>Stage</th><th>Meaning</th><th>Who acts next</th></tr></thead>
                        <tbody>
                            <tr><td><span class="docs-badge" style="background:#5C5346;color:#fff;">Draft</span></td><td>Created, not yet quoted</td><td>Sales staff</td></tr>
                            <tr><td><span class="docs-badge" style="background:#8A6428;color:#fff;">Quoted</span></td><td>Price given, awaiting approval</td><td>Customer</td></tr>
                            <tr><td><span class="docs-badge" style="background:#B8863B;color:#fff;">Approved</span></td><td>Approved, deposit collected</td><td>You / Vendor</td></tr>
                            <tr><td><span class="docs-badge" style="background:#E0AE5C;color:#211C16;">In Production</span></td><td>Order placed with vendor / bench</td><td>Vendor / Bench</td></tr>
                            <tr><td><span class="docs-badge" style="background:#6FCF97;color:#211C16;">Ready for Pickup</span></td><td>Piece received, customer notified</td><td>Customer</td></tr>
                            <tr><td><span class="docs-badge" style="background:#3E7C5A;color:#fff;">Completed</span></td><td>Paid in full and collected</td><td>—</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="custom-deposit" class="docs-section mb-14 scroll-mt-28">
                <h2 class="docs-h2">Deposit → Sale Conversion</h2>
                <p class="docs-p">When the customer collects and pays the balance, convert the order to a full sale. This is the most error-prone step for new staff.</p>
                <div class="docs-ss-block mt-4">
                    <img src="/custom-order-deposit-to-sale.png" alt="Custom order deposit to sale" class="docs-ss" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <div class="docs-ss-missing"><i class="fas fa-image"></i><span>custom-order-deposit-to-sale.png → public/custom-order-deposit-to-sale.png</span></div>
                    <div class="docs-ss-cap">Convert to Sale — deposit carried forward, balance auto-calculated</div>
                </div>
                <ol class="docs-ol mt-4">
                    <li>Open the order (status must be <em>Ready for Pickup</em>)</li>
                    <li>Click <strong>Convert to Sale</strong></li>
                    <li>Review: quoted price, deposits paid, balance due</li>
                    <li>Collect balance, select payment method, complete</li>
                    <li>A linked sale is created; order moves to <em>Completed</em></li>
                </ol>
                <div class="docs-callout-warn mt-4">
                    <i class="fas fa-triangle-exclamation mr-2"></i>
                    Never enter a manual sale for a custom order balance — always use <strong>Convert to Sale</strong> so deposit history and the order link are preserved.
                </div>
            </div>

            {{-- ── REPAIRS ── --}}
            <div class="docs-group-label">Repairs</div>

            <div id="repair-orders" class="docs-section mb-14 scroll-mt-28">
                <h2 class="docs-h2">Creating Repair Orders</h2>
                <p class="docs-p">Start a repair from <strong>Repairs → New Repair</strong> or from a customer's profile. Always attach photos at intake — your proof of condition if any dispute arises.</p>
                <div class="docs-callout mt-4">
                    <i class="fas fa-circle-info mr-2"></i>
                    Repairs created as <strong>Special Jobs</strong> within a sale link automatically to the sale record and appear in both the repair queue and sale line items.
                </div>
            </div>

            <div id="repair-numbering" class="docs-section mb-14 scroll-mt-28">
                <h2 class="docs-h2">Repair Number Format</h2>
                <p class="docs-p">Repairs are numbered <code>YYMMDD-N</code> where N resets to 1 each day.</p>
                <div class="docs-ss-block mt-4">
                    <img src="/repair-number-format.png" alt="Repair number format" class="docs-ss" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <div class="docs-ss-missing"><i class="fas fa-image"></i><span>repair-number-format.png → public/repair-number-format.png</span></div>
                    <div class="docs-ss-cap">Repair list — <code>260609-1</code> = first repair on June 9, 2026</div>
                </div>
                <div class="docs-callout-warn mt-4">
                    <i class="fas fa-triangle-exclamation mr-2"></i>
                    Old repair numbers from a previous system are stored in <code>legacy_repair_no</code> and are fully searchable — no need to re-number.
                </div>
            </div>

            <div id="repair-status" class="docs-section mb-14 scroll-mt-28">
                <h2 class="docs-h2">Status &amp; Notifications</h2>
                <p class="docs-p">Move repairs through stages as work progresses. Marking <em>Ready for Pickup</em> triggers an automatic SMS/email to the customer (CRM_JewelTag required).</p>
                <div class="overflow-x-auto mt-4">
                    <table class="docs-table">
                        <thead><tr><th>Status</th><th>Meaning</th></tr></thead>
                        <tbody>
                            <tr><td>Received</td><td>Dropped off, work order created</td></tr>
                            <tr><td>In Progress</td><td>Bench actively working</td></tr>
                            <tr><td>On Hold</td><td>Waiting on parts or customer decision</td></tr>
                            <tr><td>Ready for Pickup</td><td>Complete — customer notified</td></tr>
                            <tr><td>Completed</td><td>Collected and paid</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ── REPORTS ── --}}
            <div class="docs-group-label">Reports</div>

            <div id="analytics-dash" class="docs-section mb-14 scroll-mt-28">
                <h2 class="docs-h2">Analytics Dashboard</h2>
                <p class="docs-p">The main <strong>Dashboard</strong> shows today's at-a-glance metrics — revenue, items sold, repairs due, and layaway balances outstanding. Widgets update in real time as the day progresses.</p>
                <div class="docs-ss-block mt-4">
                    <img src="/analytics-dashboard.png" alt="Analytics dashboard" class="docs-ss" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <div class="docs-ss-missing"><i class="fas fa-image"></i><span>analytics-dashboard.png → public/analytics-dashboard.png</span></div>
                    <div class="docs-ss-cap">Analytics Dashboard — today's revenue, top items, pending repairs, and layaway health at a glance</div>
                </div>
                <p class="docs-p mt-3">The <strong>Analytics</strong> page (separate from Dashboard) provides deeper trend charts — sales by month, category performance, and staff rankings over a chosen date range.</p>
            </div>

            <div id="sales-report" class="docs-section mb-14 scroll-mt-28">
                <h2 class="docs-h2">Sales Report &amp; Date Logic</h2>
                <p class="docs-p">The Sales Report filters by <strong>effective sale date</strong> — the payment date (<code>payments.paid_at</code>), not the record creation date. This is critical for imported historical data.</p>
                <div class="docs-ss-block mt-4">
                    <img src="/sales-report-date-filter.png" alt="Sales report date filter" class="docs-ss" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <div class="docs-ss-missing"><i class="fas fa-image"></i><span>sales-report-date-filter.png → public/sales-report-date-filter.png</span></div>
                    <div class="docs-ss-cap">Sales Report — date filter uses payment date; the date column shows effective sale date</div>
                </div>
                <div class="docs-callout-warn mt-4">
                    <i class="fas fa-triangle-exclamation mr-2"></i>
                    <strong>Imported / OnSwim data:</strong> invoice format <code>D091225-XXXX</code> encodes the real sale date (Sep 12, 2025). The report decodes this automatically — if dates look wrong, check the invoice number format.
                </div>
            </div>

            <div id="my-sales-report" class="docs-section mb-14 scroll-mt-28">
                <h2 class="docs-h2">My Sales Report</h2>
                <p class="docs-p">The <strong>My Sales Report</strong> is a per-associate view — each staff member sees only their own sales and commission earnings. Admins can view any associate. It uses the <code>sales_person_list</code> JSON split to calculate each person's share correctly, even for multi-staff sales.</p>
                <div class="docs-ss-block mt-4">
                    <img src="/my-sales-report.png" alt="My Sales Report" class="docs-ss" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <div class="docs-ss-missing"><i class="fas fa-image"></i><span>my-sales-report.png → public/my-sales-report.png</span></div>
                    <div class="docs-ss-cap">My Sales Report — filtered to the logged-in associate; shows split commission per sale</div>
                </div>
                <div class="docs-callout mt-4">
                    <i class="fas fa-circle-info mr-2"></i>
                    <strong>OnSwim import note:</strong> imported sales decode the real date from the invoice number automatically — so historical commission figures are correct even for pre-migration sales.
                </div>
            </div>

            <div id="sold-stock-report" class="docs-section mb-14 scroll-mt-28">
                <h2 class="docs-h2">Sold Stock Report</h2>
                <p class="docs-p">The <strong>Sold Stock Report</strong> shows every sold item with its cost, sale price, margin, and the associate who sold it — filtered by date range, category, or department. Use this for buying decisions and to identify your highest-margin categories.</p>
                <div class="docs-ss-block mt-4">
                    <img src="/sold-stock-report.png" alt="Sold Stock Report" class="docs-ss" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <div class="docs-ss-missing"><i class="fas fa-image"></i><span>sold-stock-report.png → public/sold-stock-report.png</span></div>
                    <div class="docs-ss-cap">Sold Stock Report — item-level view of cost vs sale price vs margin</div>
                </div>
            </div>

            <div id="stock-aging" class="docs-section mb-14 scroll-mt-28">
                <h2 class="docs-h2">Stock Aging Report</h2>
                <p class="docs-p">Shows unsold inventory grouped by how long it has been in stock — 0–30 days, 31–60, 61–90, and 90+ days. Use this monthly to identify slow-moving pieces that need repricing or promotion.</p>
                <div class="docs-ss-block mt-4">
                    <img src="/stock-aging-report.png" alt="Stock Aging Report" class="docs-ss" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <div class="docs-ss-missing"><i class="fas fa-image"></i><span>stock-aging-report.png → public/stock-aging-report.png</span></div>
                    <div class="docs-ss-cap">Stock Aging Report — items bucketed by days in stock; 90+ column flags stale inventory</div>
                </div>
            </div>

            <div id="inactive-stock" class="docs-section mb-14 scroll-mt-28">
                <h2 class="docs-h2">Inactive Stock Report</h2>
                <p class="docs-p">Lists all items with status <code>inactive</code> — including when they were inactivated, by whom, and the stated reason (<code>inactivated_at</code>, <code>inactivated_by</code>, <code>inactivated_reason</code>). Useful for audits and for tracking items sent off-site.</p>
                <div class="docs-ss-block mt-4">
                    <img src="/inactive-stock-report.png" alt="Inactive Stock Report" class="docs-ss" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <div class="docs-ss-missing"><i class="fas fa-image"></i><span>inactive-stock-report.png → public/inactive-stock-report.png</span></div>
                    <div class="docs-ss-cap">Inactive Stock Report — who inactivated each item, when, and why</div>
                </div>
            </div>

            <div id="warranty-report" class="docs-section mb-14 scroll-mt-28">
                <h2 class="docs-h2">Warranty Report</h2>
                <p class="docs-p">Shows all items and custom orders with active warranties — including <code>warranty_period</code>, <code>warranty_charge</code>, and the original sale date. Use this when a customer returns for a warranty repair to quickly verify coverage.</p>
                <div class="docs-ss-block mt-4">
                    <img src="/warranty-report.png" alt="Warranty Report" class="docs-ss" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <div class="docs-ss-missing"><i class="fas fa-image"></i><span>warranty-report.png → public/warranty-report.png</span></div>
                    <div class="docs-ss-cap">Warranty Report — active warranties with period, charge, and expiry</div>
                </div>
            </div>

            <div id="deposit-report" class="docs-section mb-14 scroll-mt-28">
                <h2 class="docs-h2">Deposit Sales Report</h2>
                <p class="docs-p">Lists all open deposit sales — money held from customers who haven't completed their purchase yet. Shows customer, deposit amount, date collected, and how many days old the deposit is. Review this weekly to follow up on aging deposits.</p>
                <div class="docs-ss-block mt-4">
                    <img src="/deposit-sales-report.png" alt="Deposit Sales Report" class="docs-ss" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <div class="docs-ss-missing"><i class="fas fa-image"></i><span>deposit-sales-report.png → public/deposit-sales-report.png</span></div>
                    <div class="docs-ss-cap">Deposit Sales Report — open deposits with age and customer detail</div>
                </div>
            </div>

            <div id="restock-logs" class="docs-section mb-14 scroll-mt-28">
                <h2 class="docs-h2">Restock Logs</h2>
                <p class="docs-p">Full purchase history from suppliers — every restock event with date, supplier, items, quantities, and cost. Use this to track vendor delivery performance and reconcile against supplier invoices.</p>
                <div class="docs-ss-block mt-4">
                    <img src="/restock-logs.png" alt="Restock Logs" class="docs-ss" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <div class="docs-ss-missing"><i class="fas fa-image"></i><span>restock-logs.png → public/restock-logs.png</span></div>
                    <div class="docs-ss-cap">Restock Logs — chronological purchase history by supplier</div>
                </div>
            </div>

            <div id="eod-closing" class="docs-section mb-14 scroll-mt-28">
                <h2 class="docs-h2">End of Day Closing</h2>
                <p class="docs-p">EOD Closing locks the day's transactions and produces a summary for your records. After submission, edits require an admin-approved Sale Edit Request.</p>
                <div class="docs-ss-block mt-4">
                    <img src="/eod-before-after.png" alt="EOD before and after" class="docs-ss" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <div class="docs-ss-missing"><i class="fas fa-image"></i><span>eod-before-after.png → public/eod-before-after.png</span></div>
                    <div class="docs-ss-cap">EOD Closing — open day summary (before) and locked confirmation (after)</div>
                </div>
                <div class="docs-callout-warn mt-4">
                    <i class="fas fa-triangle-exclamation mr-2"></i>
                    After submitting EOD, staff often can't find it. It moves to <strong>Reports → End of Day → Closed Days</strong> — it is not deleted.
                </div>
            </div>

            <div id="pipeline-report" class="docs-section mb-14 scroll-mt-28">
                <h2 class="docs-h2">Custom Order Pipeline Report</h2>
                <p class="docs-p">Kanban-style view of every active custom order by stage — total value per stage, average days stuck, and an overdue table at the bottom.</p>
                <div class="docs-ss-block mt-4">
                    <img src="/custom-order-pipeline-report.png" alt="Custom Order Pipeline Report" class="docs-ss" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <div class="docs-ss-missing"><i class="fas fa-image"></i><span>custom-order-pipeline-report.png → public/custom-order-pipeline-report.png</span></div>
                    <div class="docs-ss-cap">Pipeline Report — six columns, overdue orders in red table below</div>
                </div>
                <p class="docs-p mt-3">Filter pills at the top narrow to last 30 or 90 days. Click any order number to jump to its edit page.</p>
            </div>

            <div id="laybuy-health" class="docs-section mb-14 scroll-mt-28">
                <h2 class="docs-h2">Laybuy Health Report</h2>
                <p class="docs-p">Classifies every active layaway into four risk tiers based on payment pace and due date proximity.</p>
                <div class="docs-ss-block mt-4">
                    <img src="/laybuy-health-report.png" alt="Laybuy Health Report" class="docs-ss" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <div class="docs-ss-missing"><i class="fas fa-image"></i><span>laybuy-health-report.png → public/laybuy-health-report.png</span></div>
                    <div class="docs-ss-cap">Laybuy Health — risk-tiered cards; urgent plans at the top</div>
                </div>
                <div class="overflow-x-auto mt-4">
                    <table class="docs-table">
                        <thead><tr><th>Tier</th><th>Criteria</th><th>Action</th></tr></thead>
                        <tbody>
                            <tr><td><span class="docs-badge" style="background:#B8463F;color:#fff;">Overdue</span></td><td>Past due_date with balance remaining</td><td>Call immediately</td></tr>
                            <tr><td><span class="docs-badge" style="background:#C9A24B;color:#211C16;">At Risk</span></td><td>Due within 7 days AND under 50% paid</td><td>Send reminder this week</td></tr>
                            <tr><td><span class="docs-badge" style="background:#B8863B;color:#fff;">Watch</span></td><td>Under 50% paid OR no payment in 30+ days</td><td>Monitor closely</td></tr>
                            <tr><td><span class="docs-badge" style="background:#3E7C5A;color:#fff;">On Track</span></td><td>Paying on schedule</td><td>No action needed</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="label-designer" class="docs-section mb-14 scroll-mt-28">
                <h2 class="docs-h2">Label Designer &amp; Printing</h2>
                <p class="docs-p">Design label layouts under <strong>Admin → Label Designer</strong>. Labels print to your Zebra ZD621R via ZPL through the Zebra Browser Print app.</p>
                <div class="docs-ss-block mt-4">
                    <img src="/label-designer-print.png" alt="Label designer" class="docs-ss" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <div class="docs-ss-missing"><i class="fas fa-image"></i><span>label-designer-print.png → public/label-designer-print.png</span></div>
                    <div class="docs-ss-cap">Label Designer — layout editor, ZPL preview, and print button</div>
                </div>
                <h3 class="docs-h3 mt-4">Troubleshooting</h3>
                <div class="overflow-x-auto mt-3">
                    <table class="docs-table">
                        <thead><tr><th>Symptom</th><th>Fix</th></tr></thead>
                        <tbody>
                            <tr><td>Grey dot top-right</td><td>Start Zebra Browser Print app on this computer</td></tr>
                            <tr><td>Yellow dot</td><td>App running but no printer — check cable/IP (<code>192.168.1.60</code>)</td></tr>
                            <tr><td>Label prints blank</td><td>Hold Feed 2s for media calibration</td></tr>
                            <tr><td>Wrong label size</td><td>Update layout dimensions in Label Designer</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ── CRM ── --}}
            <div class="docs-group-label">CRM_JewelTag</div>

            <div id="customer-profiles" class="docs-section mb-14 scroll-mt-28">
                <h2 class="docs-h2">Customer Profiles</h2>
                <p class="docs-p">Every sale and repair logs automatically to the customer's profile — purchase history, total spend, preferred categories, and key dates. Search by name, phone, or email from the top search bar.</p>
                <div class="docs-callout-warn mt-4">
                    <i class="fas fa-triangle-exclamation mr-2"></i>
                    <strong>Duplicate phone numbers:</strong> JewelTag shows a confirmation dialog if a phone already exists. Do not skip it — this prevents duplicate profiles for the same person.
                </div>
            </div>

            <div id="wishlists" class="docs-section mb-14 scroll-mt-28">
                <h2 class="docs-h2">Wishlists &amp; Follow-ups</h2>
                <p class="docs-p">Add items to a customer's wishlist from their profile or from an item page. The <strong>Upcoming Follow-Ups</strong> report surfaces customers with a <code>follow_up_date</code> due today or in the next 7 days — so no customer check-in falls through the cracks.</p>
            </div>

            <div id="marketing" class="docs-section mb-14 scroll-mt-28">
                <h2 class="docs-h2">Marketing Automation</h2>
                <p class="docs-p">Automate birthday, anniversary, and wishlist-restock messages under <strong>CRM → Automation</strong>. SMS campaigns require 10DLC registration — contact our team if you need help completing this.</p>
            </div>

            {{-- ── ADMIN & TOOLS ── --}}
            <div class="docs-group-label">Admin &amp; Tools</div>

            <div id="find-tools" class="docs-section mb-14 scroll-mt-28">
                <h2 class="docs-h2">Find Stock / Sale / Customer / Payment</h2>
                <p class="docs-p">JewelTag has four dedicated quick-search pages — faster than using the main resource lists when you know what you're looking for.</p>
                <div class="docs-ss-block mt-4">
                    <img src="/find-stock.png" alt="Find Stock page" class="docs-ss" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <div class="docs-ss-missing"><i class="fas fa-image"></i><span>find-stock.png → public/find-stock.png</span></div>
                    <div class="docs-ss-cap">Find Stock — search by barcode, RFID, description, or certificate number</div>
                </div>
                <div class="overflow-x-auto mt-4">
                    <table class="docs-table">
                        <thead><tr><th>Page</th><th>Search by</th><th>Best used for</th></tr></thead>
                        <tbody>
                            <tr><td><strong>Find Stock</strong></td><td>Barcode, RFID, description, cert number</td><td>Looking up a specific piece quickly at the counter</td></tr>
                            <tr><td><strong>Find Sale</strong></td><td>Invoice number, customer name, date</td><td>Pulling up a past sale for a customer query or reprint</td></tr>
                            <tr><td><strong>Find Customer</strong></td><td>Name, phone, email</td><td>Identifying a customer before starting a sale</td></tr>
                            <tr><td><strong>Find Payment</strong></td><td>Payment reference, amount, date</td><td>Reconciling a specific payment against bank records</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="activity-logs" class="docs-section mb-14 scroll-mt-28">
                <h2 class="docs-h2">Activity Logs</h2>
                <p class="docs-p">Every action in JewelTag is logged — who created, edited, or deleted what, and when. Access the full audit trail under <strong>Admin → Activity Logs</strong>. Useful for investigating discrepancies, staff disputes, or unexpected changes.</p>
                <div class="docs-ss-block mt-4">
                    <img src="/activity-log.png" alt="Activity log" class="docs-ss" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <div class="docs-ss-missing"><i class="fas fa-image"></i><span>activity-log.png → public/activity-log.png</span></div>
                    <div class="docs-ss-cap">Activity Log — timestamp, user, action type, and the changed record</div>
                </div>
                <div class="docs-callout mt-4">
                    <i class="fas fa-circle-info mr-2"></i>
                    Activity logs are read-only and cannot be cleared by any user — including superadmins. They are your permanent paper trail.
                </div>
            </div>

            <div id="deletion-requests" class="docs-section mb-14 scroll-mt-28">
                <h2 class="docs-h2">Deletion Requests</h2>
                <p class="docs-p">JewelTag does not allow direct deletion of sales, inventory items, or customer records by regular staff. Instead, staff submit a <strong>Deletion Request</strong> with a reason — an admin reviews and approves or rejects it.</p>
                <div class="docs-callout mt-4">
                    <i class="fas fa-circle-info mr-2"></i>
                    This two-step process prevents accidental data loss and ensures there's always an audit trail for why something was removed. All requests and decisions are logged permanently.
                </div>
                <h3 class="docs-h3 mt-4">How to submit a deletion request</h3>
                <ol class="docs-ol">
                    <li>Open the record you need deleted (sale, item, or customer)</li>
                    <li>Click the <strong>Request Deletion</strong> button (bottom of the form)</li>
                    <li>Enter a reason — be specific</li>
                    <li>Submit — an admin is notified</li>
                    <li>The record is deleted only after admin approval</li>
                </ol>
            </div>

            <div id="support-tickets" class="docs-section mb-14 scroll-mt-28">
                <h2 class="docs-h2">Support Tickets</h2>
                <p class="docs-p">Raise a support ticket with the JewelTag team directly from within the admin panel — under <strong>Help &amp; Support</strong> in the user menu. Every ticket is emailed to the JewelTag support team and tracked until resolved.</p>
                <div class="docs-ss-block mt-4">
                    <img src="/support-ticket.png" alt="Support ticket" class="docs-ss" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <div class="docs-ss-missing"><i class="fas fa-image"></i><span>support-ticket.png → public/support-ticket.png</span></div>
                    <div class="docs-ss-cap">Support Ticket form — describe the issue; replies go to your email</div>
                </div>
                <div class="docs-callout mt-4">
                    <i class="fas fa-circle-info mr-2"></i>
                    You can reply to tickets by replying to the email notification — no need to return to the admin panel. Your reply threads back to the ticket automatically.
                </div>
            </div>

            <div id="api-keys" class="docs-section mb-14 scroll-mt-28">
                <h2 class="docs-h2">API Keys</h2>
                <p class="docs-p">Generate API keys for external integrations under <strong>Settings → API Keys</strong>. Each key can be scoped to specific endpoints — create a separate key for each integration so you can revoke one without affecting others.</p>
                <div class="docs-callout-warn mt-4">
                    <i class="fas fa-triangle-exclamation mr-2"></i>
                    Store API keys in your integration's environment variables — never hard-code them in source files or share them in messages. If a key is compromised, revoke it immediately from this page.
                </div>
            </div>

            <div id="roles-perms" class="docs-section mb-14 scroll-mt-28">
                <h2 class="docs-h2">Roles &amp; Permissions</h2>
                <p class="docs-p">JewelTag ships with four default roles (Superadmin, Administration, Sales Associate, Bench). Custom roles can be created under <strong>Admin → Roles</strong> — assign exactly the permissions each role needs, no more.</p>
                <div class="docs-callout mt-4">
                    <i class="fas fa-circle-info mr-2"></i>
                    Permissions are granular — you can create a role that can view reports but not edit sales, or one that can process refunds but not access settings. Contact support if you need help designing a custom role for your workflow.
                </div>
            </div>

            {{-- ── INTEGRATIONS ── --}}
            <div class="docs-group-label">Integrations</div>

            <div id="quickbooks" class="docs-section mb-14 scroll-mt-28">
                <h2 class="docs-h2">QuickBooks Sync</h2>
                <p class="docs-p">Connect QuickBooks Online or Desktop to push daily sales summaries directly to your books — no manual journal entries. Connect under <strong>Settings → Integrations → QuickBooks</strong>.</p>
            </div>

            <div id="api-link" class="docs-section scroll-mt-28">
                <h2 class="docs-h2">API Reference</h2>
                <p class="docs-p">Building a custom integration? Our REST API covers inventory, transactions, and customers. Authentication uses API keys from <strong>Settings → API Keys</strong>.</p>
                <a href="{{ route('api') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-bold mt-4" style="background:linear-gradient(160deg,var(--brass-bright),var(--brass-dim));color:var(--onyx);">
                    View Full API Reference <i class="fas fa-arrow-right text-xs"></i>
                </a>
            </div>

            {{-- Help footer --}}
            <div class="mt-16 bg-[rgba(184,134,59,0.06)] rounded-2xl p-6 border border-[rgba(184,134,59,0.2)] text-center">
                <p class="text-[var(--onyx)] font-semibold mb-3">Can't find what you're looking for?</p>
                <a href="/#contact" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-bold" style="background:linear-gradient(160deg,var(--brass-bright),var(--brass-dim));color:var(--onyx);">
                    <i class="fas fa-headset text-xs"></i> Contact Support
                </a>
            </div>

        </div>{{-- /main --}}
    </div>{{-- /grid --}}

    <button id="back-to-top" class="fixed bottom-6 right-6 w-12 h-12 bg-white border border-[rgba(33,28,22,0.12)] rounded-full flex items-center justify-center shadow-lg hover:shadow-xl transition-all hidden z-40">
        <i class="fas fa-chevron-up text-[var(--ink-soft)]"></i>
    </button>
</div>

<style>
.docs-group-label { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.14em; color:var(--brass-dim); margin-bottom:12px; margin-top:64px; }
.docs-h2 { font-size:1.5rem; font-weight:700; font-family:'Fraunces',serif; color:var(--onyx); margin-bottom:12px; }
.docs-h3 { font-size:1rem; font-weight:700; color:var(--onyx); margin-bottom:8px; margin-top:20px; }
.docs-p { color:var(--ink-soft); line-height:1.75; }
.docs-ol { list-style:decimal; padding-left:1.5rem; color:var(--ink-soft); font-size:.9rem; line-height:2.1; margin-top:8px; }
.docs-ol li { padding-left:.25rem; }
.docs-table { width:100%; font-size:.85rem; border-collapse:collapse; }
.docs-table th { background:var(--case-felt); padding:10px 14px; text-align:left; font-weight:700; color:var(--onyx); border-bottom:1.5px solid rgba(33,28,22,0.12); }
.docs-table td { padding:10px 14px; border-bottom:1px solid rgba(33,28,22,0.06); color:var(--ink-soft); vertical-align:top; }
.docs-table tr:last-child td { border-bottom:none; }
.docs-callout { background:rgba(184,134,59,0.06); border:1px solid rgba(184,134,59,0.2); border-radius:12px; padding:14px 16px; font-size:.87rem; color:var(--brass-dim); }
.docs-callout-warn { background:rgba(184,70,63,0.05); border:1px solid rgba(184,70,63,0.2); border-radius:12px; padding:14px 16px; font-size:.87rem; color:#7A2B26; }
.docs-ss-block { background:#f1f0ee; border:1px solid rgba(33,28,22,0.1); border-radius:14px; overflow:hidden; margin-top:16px; }
.docs-ss { width:100%; display:block; }
.docs-ss-missing { display:none; width:100%; min-height:140px; align-items:center; justify-content:center; flex-direction:column; gap:8px; background:repeating-linear-gradient(45deg,#f1f0ee,#f1f0ee 8px,#ebe9e4 8px,#ebe9e4 16px); padding:24px 16px; }
.docs-ss-missing i { font-size:26px; color:rgba(33,28,22,0.2); }
.docs-ss-missing span { font-size:10.5px; font-weight:700; color:rgba(33,28,22,0.35); letter-spacing:.06em; text-transform:uppercase; text-align:center; line-height:1.6; }
.docs-ss-cap { font-size:.75rem; color:var(--ink-soft); padding:8px 14px 10px; background:rgba(33,28,22,0.03); border-top:1px solid rgba(33,28,22,0.07); }
.docs-badge { display:inline-block; padding:2px 10px; border-radius:999px; font-size:.72rem; font-weight:700; }
.docs-side-link.active { background:rgba(184,134,59,0.1); color:var(--brass-dim); font-weight:600; }
.docs-section.dimmed { opacity:.2; transition:opacity .2s; }
code { font-family:'JetBrains Mono','Courier New',monospace; font-size:.82em; background:rgba(33,28,22,0.06); padding:1px 5px; border-radius:4px; }
</style>

<script>
const backToTopBtn = document.getElementById('back-to-top');
window.addEventListener('scroll', () => backToTopBtn.classList.toggle('hidden', window.pageYOffset <= 500));
backToTopBtn.addEventListener('click', () => window.scrollTo({ top:0, behavior:'smooth' }));

document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', function(e) {
        const href = this.getAttribute('href');
        if (href !== '#' && href.startsWith('#')) {
            e.preventDefault();
            const t = document.querySelector(href);
            if (t) t.scrollIntoView({ behavior:'smooth', block:'start' });
        }
    });
});

const sideLinks = document.querySelectorAll('.docs-side-link');
const sections  = document.querySelectorAll('.docs-section');
const obs = new IntersectionObserver(entries => {
    entries.forEach(e => {
        if (e.isIntersecting) {
            const id = e.target.getAttribute('id');
            sideLinks.forEach(l => l.classList.toggle('active', l.getAttribute('href') === '#' + id));
        }
    });
}, { rootMargin:'-20% 0px -70% 0px' });
sections.forEach(s => obs.observe(s));

document.getElementById('docs-search').addEventListener('input', function() {
    const q = this.value.trim().toLowerCase();
    sections.forEach(s => s.classList.toggle('dimmed', q && !s.innerText.toLowerCase().includes(q)));
});
</script>

@endsection