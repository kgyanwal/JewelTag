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
            <input
                type="text"
                id="docs-search"
                placeholder="Search documentation... e.g. 'repair orders', 'RFID setup'"
                class="w-full pl-11 pr-4 py-3.5 rounded-xl border border-[rgba(33,28,22,0.12)] bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[rgba(184,134,59,0.3)] focus:border-[var(--brass)] transition"
            >
        </div>
    </div>

    <div class="grid lg:grid-cols-[260px_1fr] gap-10">

        {{-- ═══════════════ SIDEBAR ═══════════════ --}}
        <aside class="hidden lg:block">
            <div class="sticky top-28 space-y-7">
                @php
                    $nav = [
                        'Getting Started' => [
                            ['id' => 'overview', 'label' => 'Platform Overview'],
                            ['id' => 'quick-start', 'label' => 'Quick Start Guide'],
                            ['id' => 'staff-accounts', 'label' => 'Staff Accounts &amp; Roles'],
                        ],
                        'Inventory' => [
                            ['id' => 'adding-items', 'label' => 'Adding Inventory Items'],
                            ['id' => 'rfid-setup', 'label' => 'RFID &amp; Barcode Setup'],
                            ['id' => 'certifications', 'label' => 'Diamond Certifications'],
                            ['id' => 'multi-store', 'label' => 'Multi-Store Sync'],
                        ],
                        'Point of Sale' => [
                            ['id' => 'pos-basics', 'label' => 'Making a Sale'],
                            ['id' => 'layaway', 'label' => 'Layaway &amp; Financing'],
                            ['id' => 'commission', 'label' => 'Commission Tracking'],
                        ],
                        'Repairs' => [
                            ['id' => 'repair-orders', 'label' => 'Creating Repair Orders'],
                            ['id' => 'repair-status', 'label' => 'Status &amp; Notifications'],
                        ],
                        'CRM_JewelTag' => [
                            ['id' => 'customer-profiles', 'label' => 'Customer Profiles'],
                            ['id' => 'marketing', 'label' => 'Marketing Automation'],
                        ],
                        'Integrations' => [
                            ['id' => 'shopify', 'label' => 'Shopify &amp; WooCommerce'],
                            ['id' => 'quickbooks', 'label' => 'QuickBooks Sync'],
                            ['id' => 'api-link', 'label' => 'API Reference'],
                        ],
                    ];
                @endphp

                @foreach($nav as $group => $items)
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-[0.12em] text-[var(--brass-dim)] mb-2.5 px-3">{{ $group }}</div>
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

        {{-- ═══════════════ MAIN CONTENT ═══════════════ --}}
        <div class="min-w-0">

            {{-- Getting Started ════════════════════════════════ --}}
            <div class="mb-3">
                <span class="text-[10px] font-bold uppercase tracking-[0.14em] text-[var(--brass-dim)]">Getting Started</span>
            </div>

            <div id="overview" class="docs-section mb-12 scroll-mt-28">
                <h2 class="text-2xl font-bold fraunces text-[var(--onyx)] mb-3">Platform Overview</h2>
                <p class="text-[var(--ink-soft)] leading-relaxed mb-4">
                    JewelTag is built around four connected modules — Inventory, Point of Sale, Repairs, and CRM_JewelTag — that all share the same underlying item and customer records. Anything you scan at intake follows the piece through pricing, sale, and any future repair, without re-entering data.
                </p>
                <div class="grid sm:grid-cols-2 gap-3">
                    <div class="bg-[var(--case-felt)] rounded-xl p-4 border border-[rgba(33,28,22,0.07)]">
                        <i class="fas fa-tags text-[var(--brass)] mb-2 block"></i>
                        <div class="text-sm font-bold text-[var(--onyx)]">Inventory</div>
                        <div class="text-xs text-[var(--ink-soft)] mt-0.5">RFID, barcodes, certs</div>
                    </div>
                    <div class="bg-[var(--case-felt)] rounded-xl p-4 border border-[rgba(33,28,22,0.07)]">
                        <i class="fas fa-cash-register text-[var(--brass)] mb-2 block"></i>
                        <div class="text-sm font-bold text-[var(--onyx)]">Point of Sale</div>
                        <div class="text-xs text-[var(--ink-soft)] mt-0.5">Layaway, commission</div>
                    </div>
                    <div class="bg-[var(--case-felt)] rounded-xl p-4 border border-[rgba(33,28,22,0.07)]">
                        <i class="fas fa-screwdriver-wrench text-[var(--brass)] mb-2 block"></i>
                        <div class="text-sm font-bold text-[var(--onyx)]">Repairs</div>
                        <div class="text-xs text-[var(--ink-soft)] mt-0.5">Work orders, status</div>
                    </div>
                    <div class="bg-[var(--case-felt)] rounded-xl p-4 border border-[rgba(33,28,22,0.07)]">
                        <i class="fas fa-heart text-[var(--brass)] mb-2 block"></i>
                        <div class="text-sm font-bold text-[var(--onyx)]">CRM_JewelTag</div>
                        <div class="text-xs text-[var(--ink-soft)] mt-0.5">Profiles, marketing</div>
                    </div>
                </div>
            </div>

            <div id="quick-start" class="docs-section mb-12 scroll-mt-28">
                <h2 class="text-2xl font-bold fraunces text-[var(--onyx)] mb-3">Quick Start Guide</h2>
                <p class="text-[var(--ink-soft)] leading-relaxed mb-5">Most stores are taking their first sale within the first day. Here's the order we recommend:</p>
                <div class="space-y-3">
                    @foreach([
                        ['n' => '01', 't' => 'Log in &amp; set up your store', 'd' => 'Add your store name, address, and tax rate under Settings.'],
                        ['n' => '02', 't' => 'Invite your staff', 'd' => 'Create accounts and assign roles (Owner, Sales, Bench) under Staff Accounts.'],
                        ['n' => '03', 't' => 'Import or add inventory', 'd' => 'Bring in your existing spreadsheet, or start tagging items by hand.'],
                        ['n' => '04', 't' => 'Connect a payment processor', 'd' => 'Link Stripe or Square so you can take your first sale at the counter.'],
                        ['n' => '05', 't' => 'Take your first sale', 'd' => 'Scan an item, ring it up, and watch the inventory count update live.'],
                    ] as $step)
                    <div class="flex gap-4 items-start p-4 rounded-xl border border-[rgba(33,28,22,0.07)] bg-white">
                        <div class="fraunces font-bold text-sm w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0" style="background: var(--onyx); color: var(--brass-bright); border: 1.5px solid var(--brass);">{{ $step['n'] }}</div>
                        <div>
                            <div class="font-bold text-sm text-[var(--onyx)]">{!! $step['t'] !!}</div>
                            <div class="text-sm text-[var(--ink-soft)] mt-0.5">{{ $step['d'] }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <div id="staff-accounts" class="docs-section mb-12 scroll-mt-28">
                <h2 class="text-2xl font-bold fraunces text-[var(--onyx)] mb-3">Staff Accounts &amp; Roles</h2>
                <p class="text-[var(--ink-soft)] leading-relaxed mb-4">Every staff member gets their own login so sales, commissions, and repair work are always attributed correctly.</p>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-[var(--case-felt)]">
                            <tr>
                                <th class="p-3 text-left font-semibold text-[var(--onyx)] border-b border-[rgba(33,28,22,0.1)]">Role</th>
                                <th class="p-3 text-left font-semibold text-[var(--onyx)] border-b border-[rgba(33,28,22,0.1)]">Access</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[rgba(33,28,22,0.06)]">
                            <tr><td class="p-3 font-medium">Owner / Admin</td><td class="p-3 text-[var(--ink-soft)]">Full access, including settings, reporting, and user management.</td></tr>
                            <tr><td class="p-3 font-medium">Sales Associate</td><td class="p-3 text-[var(--ink-soft)]">POS, customer profiles, repair intake. No settings access.</td></tr>
                            <tr><td class="p-3 font-medium">Bench / Repair</td><td class="p-3 text-[var(--ink-soft)]">Repair queue and status updates only.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Inventory ════════════════════════════════ --}}
            <div class="mb-3 mt-16">
                <span class="text-[10px] font-bold uppercase tracking-[0.14em] text-[var(--brass-dim)]">Inventory</span>
            </div>

            <div id="adding-items" class="docs-section mb-12 scroll-mt-28">
                <h2 class="text-2xl font-bold fraunces text-[var(--onyx)] mb-3">Adding Inventory Items</h2>
                <p class="text-[var(--ink-soft)] leading-relaxed mb-4">Add items one at a time through the counter, or bulk import via CSV from the Inventory tab.</p>
                <div class="bg-[var(--onyx)] rounded-xl p-4">
                    <div class="text-xs text-[rgba(250,246,238,0.5)] font-mono mb-2">Required CSV columns</div>
                    <code class="text-[var(--brass-bright)] text-xs font-mono leading-relaxed block">sku, name, category, price, cost, supplier_id, certification</code>
                </div>
            </div>

            <div id="rfid-setup" class="docs-section mb-12 scroll-mt-28">
                <h2 class="text-2xl font-bold fraunces text-[var(--onyx)] mb-3">RFID &amp; Barcode Setup</h2>
                <p class="text-[var(--ink-soft)] leading-relaxed">Pair a compatible RFID reader under Settings → Hardware. Once paired, scanning a tagged item at any counter station instantly pulls up its full record — cost, cert, supplier, and sale history.</p>
                <div class="bg-[rgba(184,134,59,0.06)] rounded-xl p-4 border border-[rgba(184,134,59,0.2)] mt-4">
                    <p class="text-sm text-[var(--brass-dim)]"><i class="fas fa-circle-info mr-1.5"></i>RFID is included on JewelTag Pro and above. Basic plans use barcode scanning only.</p>
                </div>
            </div>

            <div id="certifications" class="docs-section mb-12 scroll-mt-28">
                <h2 class="text-2xl font-bold fraunces text-[var(--onyx)] mb-3">Diamond Certifications</h2>
                <p class="text-[var(--ink-soft)] leading-relaxed">Attach GIA, IGI, AGS, or other certification numbers directly to an inventory item. Certification details print automatically on customer-facing receipts and appraisal documents.</p>
            </div>

            <div id="multi-store" class="docs-section mb-12 scroll-mt-28">
                <h2 class="text-2xl font-bold fraunces text-[var(--onyx)] mb-3">Multi-Store Sync</h2>
                <p class="text-[var(--ink-soft)] leading-relaxed">Stock levels, transfers, and pricing sync across every connected location in real time. Transfer an item between stores from the Inventory tab — it stays linked to its full history.</p>
            </div>

            {{-- Point of Sale ════════════════════════════════ --}}
            <div class="mb-3 mt-16">
                <span class="text-[10px] font-bold uppercase tracking-[0.14em] text-[var(--brass-dim)]">Point of Sale</span>
            </div>

            <div id="pos-basics" class="docs-section mb-12 scroll-mt-28">
                <h2 class="text-2xl font-bold fraunces text-[var(--onyx)] mb-3">Making a Sale</h2>
                <p class="text-[var(--ink-soft)] leading-relaxed">Scan or search an item, attach a customer profile (optional), select a payment method, and complete the sale. Inventory updates the moment the receipt prints.</p>
            </div>

            <div id="layaway" class="docs-section mb-12 scroll-mt-28">
                <h2 class="text-2xl font-bold fraunces text-[var(--onyx)] mb-3">Layaway &amp; Financing</h2>
                <p class="text-[var(--ink-soft)] leading-relaxed">Set a deposit schedule when starting a layaway sale. JewelTag tracks remaining balance and holds the item out of available inventory until paid in full.</p>
            </div>

            <div id="commission" class="docs-section mb-12 scroll-mt-28">
                <h2 class="text-2xl font-bold fraunces text-[var(--onyx)] mb-3">Commission Tracking</h2>
                <p class="text-[var(--ink-soft)] leading-relaxed">Set commission rates per staff member or per category under Settings → Staff. Every sale automatically attributes commission to the logged-in associate.</p>
            </div>

            {{-- Repairs ════════════════════════════════ --}}
            <div class="mb-3 mt-16">
                <span class="text-[10px] font-bold uppercase tracking-[0.14em] text-[var(--brass-dim)]">Repairs</span>
            </div>

            <div id="repair-orders" class="docs-section mb-12 scroll-mt-28">
                <h2 class="text-2xl font-bold fraunces text-[var(--onyx)] mb-3">Creating Repair Orders</h2>
                <p class="text-[var(--ink-soft)] leading-relaxed">Start a repair order from the customer's profile or directly from an inventory item. Attach photos of the piece at intake — these stay attached to the order through completion.</p>
            </div>

            <div id="repair-status" class="docs-section mb-12 scroll-mt-28">
                <h2 class="text-2xl font-bold fraunces text-[var(--onyx)] mb-3">Status &amp; Notifications</h2>
                <p class="text-[var(--ink-soft)] leading-relaxed">Move a repair through its stages — Received, In Progress, Ready for Pickup — and the customer is notified automatically by SMS or email at each step (CRM_JewelTag required for automated notifications).</p>
            </div>

            {{-- CRM_JewelTag ════════════════════════════════ --}}
            <div class="mb-3 mt-16">
                <span class="text-[10px] font-bold uppercase tracking-[0.14em] text-[var(--brass-dim)]">CRM_JewelTag</span>
            </div>

            <div id="customer-profiles" class="docs-section mb-12 scroll-mt-28">
                <h2 class="text-2xl font-bold fraunces text-[var(--onyx)] mb-3">Customer Profiles</h2>
                <p class="text-[var(--ink-soft)] leading-relaxed">Every sale and repair automatically logs to the customer's profile — purchase history, preferences, and important dates like anniversaries all live in one place.</p>
            </div>

            <div id="marketing" class="docs-section mb-12 scroll-mt-28">
                <h2 class="text-2xl font-bold fraunces text-[var(--onyx)] mb-3">Marketing Automation</h2>
                <p class="text-[var(--ink-soft)] leading-relaxed">Set up automated birthday, anniversary, and wishlist-restock messages under CRM → Automation. SMS campaigns require 10DLC registration, which our team can help you complete.</p>
            </div>

            {{-- Integrations ════════════════════════════════ --}}
            <div class="mb-3 mt-16">
                <span class="text-[10px] font-bold uppercase tracking-[0.14em] text-[var(--brass-dim)]">Integrations</span>
            </div>

            <div id="shopify" class="docs-section mb-12 scroll-mt-28">
                <h2 class="text-2xl font-bold fraunces text-[var(--onyx)] mb-3">Shopify &amp; WooCommerce</h2>
                <p class="text-[var(--ink-soft)] leading-relaxed">Connect your storefront under Settings → Integrations. Inventory and pricing sync both ways — selling an item online removes it from your in-store count automatically, and vice versa.</p>
            </div>

            <div id="quickbooks" class="docs-section mb-12 scroll-mt-28">
                <h2 class="text-2xl font-bold fraunces text-[var(--onyx)] mb-3">QuickBooks Sync</h2>
                <p class="text-[var(--ink-soft)] leading-relaxed">Connect QuickBooks Online or Desktop to push daily sales summaries directly to your books — no manual journal entries required.</p>
            </div>

            <div id="api-link" class="docs-section scroll-mt-28">
                <h2 class="text-2xl font-bold fraunces text-[var(--onyx)] mb-3">API Reference</h2>
                <p class="text-[var(--ink-soft)] leading-relaxed mb-4">Building a custom integration? Our REST API covers inventory, transactions, and customers.</p>
                <a href="{{ route('api') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-bold" style="background: linear-gradient(160deg, var(--brass-bright), var(--brass-dim)); color: var(--onyx);">
                    View Full API Reference <i class="fas fa-arrow-right text-xs"></i>
                </a>
            </div>

            {{-- Still need help --}}
            <div class="mt-16 bg-[rgba(184,134,59,0.06)] rounded-2xl p-6 border border-[rgba(184,134,59,0.2)] text-center">
                <p class="text-[var(--onyx)] font-semibold mb-3">Can't find what you're looking for?</p>
                <a href="/#contact" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-bold" style="background: linear-gradient(160deg, var(--brass-bright), var(--brass-dim)); color: var(--onyx);">
                    <i class="fas fa-headset text-xs"></i> Contact Support
                </a>
            </div>

        </div>
    </div>

    {{-- Back to Top --}}
    <button id="back-to-top" class="fixed bottom-6 right-6 w-12 h-12 bg-white border border-[rgba(33,28,22,0.12)] rounded-full flex items-center justify-center shadow-lg hover:shadow-xl transition-all hidden z-40">
        <i class="fas fa-chevron-up text-[var(--ink-soft)]"></i>
    </button>
</div>

<style>
    .docs-side-link.active {
        background: rgba(184,134,59,0.1);
        color: var(--brass-dim);
        font-weight: 600;
    }
    .docs-section.dimmed { opacity: 0.25; }
</style>

<script>
    // Back to top
    const backToTopBtn = document.getElementById('back-to-top');
    window.addEventListener('scroll', function () {
        backToTopBtn.classList.toggle('hidden', window.pageYOffset <= 500);
    });
    backToTopBtn.addEventListener('click', function () {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    // Smooth scroll + active sidebar link
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (href !== '#' && href.startsWith('#')) {
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    const sideLinks = document.querySelectorAll('.docs-side-link');
    const sections = document.querySelectorAll('.docs-section');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const id = entry.target.getAttribute('id');
                sideLinks.forEach(link => {
                    link.classList.toggle('active', link.getAttribute('href') === '#' + id);
                });
            }
        });
    }, { rootMargin: '-20% 0px -70% 0px' });
    sections.forEach(s => observer.observe(s));

    // Simple client-side search — dims non-matching sections
    const searchInput = document.getElementById('docs-search');
    searchInput.addEventListener('input', function () {
        const q = this.value.trim().toLowerCase();
        sections.forEach(section => {
            if (!q) {
                section.classList.remove('dimmed');
                return;
            }
            const matches = section.innerText.toLowerCase().includes(q);
            section.classList.toggle('dimmed', !matches);
        });
    });
</script>
@endsection