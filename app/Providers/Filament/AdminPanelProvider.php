<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Navigation\NavigationGroup;
use Filament\View\PanelsRenderHook;
use Filament\Support\Assets\Js;
use App\Filament\Pages\ManageSettings;
use Filament\Navigation\MenuItem;
use App\Filament\Resources\ActivityLogResource;
use App\Filament\Resources\CustomerResource;
use App\Filament\Resources\SaleResource;
use App\Models\Store;
use Jeffgreco13\FilamentBreezy\BreezyCore;
use Filament\Support\Facades\FilamentView;
use Illuminate\Support\Facades\Blade;
use App\Models\Announcement;
use Filament\Navigation\NavigationItem;
use App\Helpers\Staff;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->topNavigation()
            ->globalSearch(true)
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->globalSearchDebounce('500ms')
            ->brandLogo(function () {
                if (function_exists('tenancy') && tenancy()->initialized) {
                    $store = \App\Models\Store::first();
                    if ($store && $store->logo_path) {
                        return tenant_asset($store->logo_path);
                    }
                }
                return asset('jeweltaglogo.png');
            })
            ->brandLogoHeight('2.5rem')
            ->brandName('JEWELTAG')
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_END,
                fn(): string => view('filament.hooks.custom-logo')->render(),
            )
            ->renderHook(
                PanelsRenderHook::TOPBAR_END,
                fn(): string => Blade::render('
                    @php
                        $staffUser = \App\Helpers\Staff::user();
                        $name = $staffUser?->username ?? $staffUser?->name ?? null;
                    @endphp
                    @if($name)
                        <div class="jt-staff-chip">
                            <svg xmlns="http://www.w3.org/2000/svg" style="width:14px;height:14px;flex-shrink:0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                            </svg>
                            {{ $name }}
                        </div>
                    @endif
                '),
            )
            ->favicon(asset('favicon.png'))
            ->darkMode(false, false)
            ->colors([
                'primary' => Color::hex('#0B3D3C'),
                'gray'    => Color::hex('#5B6764'),
                'info'    => Color::hex('#3D6B63'),
                'success' => Color::hex('#0F7A5C'),
                'warning' => Color::hex('#C9A24B'),
                'danger'  => Color::hex('#B8463F'),
            ])
            ->font('Inter')
            ->maxContentWidth(MaxWidth::Full)
            ->assets([
                Js::make('zebra-library', asset('js/zebra-lib.js')),
                Js::make('zebra-print-logic', asset('js/custom/zebra-print.js')),
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn() => <<<'HTML'
<script>
    document.documentElement.classList.add('light');
    document.documentElement.classList.remove('dark');
    localStorage.setItem('theme', 'light');

    function updatePrinterStatus() {
        const dot = document.getElementById('zebra-status-dot');
        if (!dot) return;
        if (typeof BrowserPrint === 'undefined') {
            dot.classList.remove('jt-dot-online', 'jt-dot-idle');
            dot.classList.add('jt-dot-offline');
            dot.title = 'Zebra App Not Running';
            return;
        }
        BrowserPrint.getDefaultDevice("printer", function(device) {
            const online = !!(device && device.name);
            dot.classList.remove('jt-dot-online', 'jt-dot-idle', 'jt-dot-offline');
            dot.classList.add(online ? 'jt-dot-online' : 'jt-dot-idle');
            dot.title = online ? 'Printer Online: ' + device.name : 'Zebra App Ready - No Printer Found';
        }, function() {
            dot.classList.remove('jt-dot-online', 'jt-dot-idle');
            dot.classList.add('jt-dot-offline');
        });
    }

    window.addEventListener('zebra-print', event => {
        const zpl = event.detail.zpl;
        if (typeof BrowserPrint === 'undefined') return alert("Please start Zebra Browser Print software.");
        BrowserPrint.getLocalDevices(function(devices) {
            const printer = devices.find(d => d.name.includes('192.168.1.60'));
            if (printer) {
                printer.send(zpl, () => console.log("Sent to ZD621R"), (err) => alert("Printer Error: " + err));
            } else {
                BrowserPrint.getDefaultDevice("printer", (d) => d.send(zpl));
            }
        }, () => {}, "printer");
    });

    setInterval(updatePrinterStatus, 8000);
    window.addEventListener('load', updatePrinterStatus);
</script>

<style>
/* ═══════════════════════════════════════════
   JEWELTAG POS — JEWELRY CASE THEME
   Deep teal-pine structure · ivory surfaces · antique gold signature
   Motion budget: button lift, status-dot breathing, row hover,
   dropdown/banner slide-in. No page-load fade (caused first-paint
   glitches with the topbar) and nothing animates money figures.
   ═══════════════════════════════════════════ */

@import url('https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Inter:wght@400;500;600;700&display=swap');

:root {
    --jt-pine:      #0B3D3C;
    --jt-pine-dark: #07292A;
    --jt-gold:      #C9A24B;
    --jt-gold-soft: #E4CD8E;
    --jt-ivory:     #F8F6F1;
    --jt-ink:       #1A2E2D;
    --jt-sage:      #3D6B63;
    --jt-brick:     #B8463F;
    --jt-ease: cubic-bezier(0.16, 1, 0.3, 1);
}

@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after { animation-duration: 0.001ms !important; transition-duration: 0.001ms !important; }
}

/* ── BODY ─────────────────────────────────── */
body, .fi-body {
    background-color: var(--jt-ivory) !important;
    background-image:
        linear-gradient(rgba(11,61,60,0.025) 1px, transparent 1px),
        linear-gradient(90deg, rgba(11,61,60,0.025) 1px, transparent 1px);
    background-size: 32px 32px;
}
.fi-main { background-color: transparent !important; }
.fi-body, .fi-body * { font-family: 'Inter', sans-serif; }

/* ── TOPBAR ───────────────────────────────── */
/* Entire top navigation */
.fi-topbar,
.fi-topbar > nav,
.fi-topbar nav,
header.fi-topbar {
    background: #0B3D3C !important;
    border-bottom: 2px solid #C9A24B !important;
}

/* Topbar wrapper */
.fi-layout,
.fi-layout-header {
    background: #0B3D3C !important;
}

/* Navigation links */
.fi-topbar * {
    color: #F8F6F1 !important;
}
/* Active top navigation item */
.fi-topbar nav a[aria-current="page"],
.fi-topbar nav button[aria-current="page"],
.fi-topbar nav .fi-active,
.fi-topbar nav .fi-topbar-item-active,
.fi-topbar nav li[data-active="true"] > a,
.fi-topbar nav li[data-active="true"] > button {
    background: rgba(201, 162, 75, 0.18) !important;
    color: #E4CD8E !important;
    border: 1px solid rgba(201, 162, 75, 0.35) !important;
    box-shadow: none !important;
}

/* Remove white active background */
.fi-topbar nav a[aria-current="page"] *,
.fi-topbar nav button[aria-current="page"] *,
.fi-topbar nav .fi-active * {
    color: #E4CD8E !important;
}

/* Hover */
.fi-topbar nav > ul > li > a:hover,
.fi-topbar nav > ul > li > button:hover {
    background: rgba(201, 162, 75, 0.12) !important;
    color: #E4CD8E !important;
}
.fi-topbar * { color: var(--jt-ivory) !important; font-weight: 600 !important; }
.fi-logo { padding: 0.25rem; }

.jt-staff-chip {
    display: flex; align-items: center; gap: 6px;
    background: rgba(201,162,75,0.14);
    border: 1.5px solid var(--jt-gold);
    border-radius: 999px;
    padding: 5px 14px 5px 10px;
    margin-right: 8px;
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--jt-gold-soft) !important;
    white-space: nowrap;
    transition: background 150ms var(--jt-ease);
}
.jt-staff-chip:hover { background: rgba(201,162,75,0.22); }
/* ── GLOBAL SEARCH BAR (top nav) ──────────── */
.fi-topbar .fi-global-search-field,
.fi-global-search-field {
    background: rgba(248,246,241,0.97) !important;
    border: 1.5px solid var(--jt-gold) !important;
    border-radius: 10px !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.12) !important;
    transition: box-shadow 150ms var(--jt-ease), border-color 150ms var(--jt-ease) !important;
}
.fi-global-search-field:focus-within {
    box-shadow: 0 0 0 3px rgba(201,162,75,0.35) !important;
    border-color: var(--jt-gold-soft) !important;
}
.fi-global-search-field input {
    color: var(--jt-ink) !important;
    background: transparent !important;
}
.fi-global-search-field input::placeholder {
    color: #8A9491 !important;
    opacity: 1 !important;
}
.fi-global-search-field svg {
    color: var(--jt-sage) !important;
    stroke: var(--jt-sage) !important;
}
/* ── GLOBAL SEARCH RESULTS PANEL (appears while typing) ── */
.fi-topbar .fi-global-search-field-results,
[x-show*="search"] [class*="results"],
.fi-global-search-results-ctn,
div[id*="global-search"] {
    background: #ffffff !important;
    border: 1.5px solid var(--jt-gold) !important;
    border-radius: 10px !important;
    box-shadow: 0 12px 32px rgba(0,0,0,0.18) !important;
}

/* Force every piece of text/icon inside the results panel to be dark
   and readable, regardless of what class Filament gives it */
.fi-topbar .fi-global-search-field-results,
.fi-topbar .fi-global-search-field-results *,
.fi-global-search-results-ctn,
.fi-global-search-results-ctn *,
div[id*="global-search"],
div[id*="global-search"] * {
    color: var(--jt-ink) !important;
}

/* Result group headings (e.g. "Customers", "Sales") */
.fi-global-search-results-ctn [class*="heading"],
div[id*="global-search"] [class*="heading"] {
    color: var(--jt-sage) !important;
    font-weight: 700 !important;
    text-transform: uppercase !important;
    font-size: 0.7rem !important;
    letter-spacing: 0.05em !important;
}

/* Hover state on a result row */
.fi-global-search-results-ctn a:hover,
div[id*="global-search"] a:hover {
    background: rgba(201,162,75,0.10) !important;
    color: var(--jt-pine) !important;
}

/* "No search results found" + any empty-state text */
.fi-global-search-results-ctn p,
div[id*="global-search"] p {
    color: var(--jt-ink) !important;
    opacity: 0.65 !important;
}
/* ── NAV ──────────────────────────────────── */
.fi-topbar nav > ul {
    display: flex !important;
    flex-wrap: nowrap !important;
    overflow-x: auto !important;
    scrollbar-width: none !important;
}
.fi-topbar nav > ul::-webkit-scrollbar { display: none !important; }

.fi-topbar nav > ul > li > a,
.fi-topbar nav > ul > li > button {
    white-space: nowrap !important;
    font-size: 0.82rem !important;
    padding: 6px 12px !important;
    border-radius: 6px !important;
    transition: background 150ms var(--jt-ease), color 150ms var(--jt-ease) !important;
}
.fi-topbar nav > ul > li > a:hover,
.fi-topbar nav > ul > li > button:hover {
    background: rgba(201,162,75,0.16) !important;
    color: var(--jt-gold-soft) !important;
}

/* ── DROPDOWN PANEL ───────────────────────── */
.fi-dropdown-panel {
    background: var(--jt-pine-dark) !important;
    border: none !important;
    border-top: 2px solid var(--jt-gold) !important;
    border-radius: 0 0 14px 14px !important;
    box-shadow: 0 20px 56px rgba(0,0,0,0.35) !important;
    padding: 20px !important;
    min-width: 540px !important;
    animation: jt-dropdown-in 160ms var(--jt-ease);
    color: var(--jt-ivory) !important; /* safety net: any unstyled text defaults to ivory, never invisible */
}
.fi-dropdown-panel *:not(svg):not(path) {
    color: inherit;
}
@keyframes jt-dropdown-in {
    from { opacity: 0; transform: translateY(-4px); }
    to   { opacity: 1; transform: translateY(0); }
}
.fi-ta-actions [wire\:loading],
.fi-ac-action [wire\:loading] svg.animate-spin { display: none !important; }
.fi-dropdown-list-item svg.animate-spin { display: none !important; }
.fi-ac-action-icon-btn svg.animate-spin { display: none !important; }
.fi-topbar-nav-dropdown .fi-dropdown-list-item-icon,
.fi-dropdown-list-item-icon {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    color: var(--jt-gold-soft) !important;
    width: 20px !important;
    height: 20px !important;
    margin-right: 4px !important;
}
.fi-dropdown-list-item-icon svg { width: 20px !important; height: 20px !important; stroke-width: 2 !important; }

.fi-dropdown-list {
    display: grid !important;
    grid-template-columns: 1fr 1fr !important;
    gap: 4px 10px !important;
    position: relative !important;
}
.fi-dropdown-list::after {
    content: '' !important;
    position: absolute !important;
    left: 50% !important; top: 10px !important; bottom: 10px !important;
    width: 1px !important;
    background: rgba(201,162,75,0.25) !important;
    pointer-events: none !important;
}
.fi-dropdown-list-item { border-radius: 8px !important; transition: background 150ms var(--jt-ease) !important; }
.fi-dropdown-list-item > a,
.fi-dropdown-list-item > button {
    display: flex !important; align-items: center !important; padding: 10px 12px !important; gap: 12px !important;
}
.fi-dropdown-list-item-label { color: var(--jt-ivory) !important; font-size: 0.88rem !important; font-weight: 500 !important; transition: color 150ms var(--jt-ease); }
.fi-dropdown-list-item:hover { background: rgba(201,162,75,0.14) !important; }
.fi-dropdown-list-item:hover .fi-dropdown-list-item-label,
.fi-dropdown-list-item:hover .fi-dropdown-list-item-icon { color: var(--jt-gold-soft) !important; }

/* ── TABLES ───────────────────────────────── */
.fi-ta-ctn {
    border-radius: 14px !important;
    overflow: hidden !important;
    box-shadow: 0 6px 24px rgba(11,61,60,0.10) !important;
    background: #ffffff !important;
    border: 1px solid rgba(11,61,60,0.10) !important;
}
.fi-ta-header-cell {
    background: var(--jt-pine) !important;
    font-size: 0.7rem !important;
    font-weight: 700 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.07em !important;
    color: var(--jt-gold-soft) !important;
    border-bottom: none !important;
}
.fi-ta-header-cell * { color: var(--jt-gold-soft) !important; }
.fi-ta-row { border-bottom: 1px solid rgba(11,61,60,0.06) !important; transition: background 80ms linear !important; }
.fi-ta-row:hover { background: rgba(201,162,75,0.07) !important; }

/* ── FORM SECTIONS ────────────────────────── */
section.fi-section {
    background: #ffffff !important;
    border-radius: 16px !important;
    border: 1px solid rgba(11,61,60,0.08) !important;
    box-shadow: 0 6px 20px rgba(11,61,60,0.08) !important;
    transition: box-shadow 200ms var(--jt-ease) !important;
}
section.fi-section:has(.fi-input-wrp:focus-within) {
    box-shadow: 0 10px 28px rgba(201,162,75,0.18) !important;
}
section.fi-section > .fi-section-header {
    border-bottom: 2px solid var(--jt-gold-soft) !important;
}
.fi-section-header-heading {
    font-family: 'Fraunces', serif !important;
    font-weight: 600 !important;
    color: var(--jt-pine) !important;
    letter-spacing: 0.01em !important;
}

/* ── INPUTS ───────────────────────────────── */
.fi-input-wrp {
    border-radius: 8px !important;
    transition: box-shadow 150ms var(--jt-ease), border-color 150ms var(--jt-ease) !important;
}
.fi-input-wrp:focus-within {
    box-shadow: 0 0 0 3px rgba(201,162,75,0.30) !important;
    border-color: var(--jt-gold) !important;
}

/* ── BUTTONS — lift + gold glow on hover, the one tactile cue ── */
.fi-btn-primary {
    background: linear-gradient(135deg, var(--jt-pine), var(--jt-pine-dark)) !important;
    border: 1px solid var(--jt-gold) !important;
    border-radius: 8px !important;
    box-shadow: 0 2px 10px rgba(11,61,60,0.25) !important;
    transition: transform 150ms var(--jt-ease), box-shadow 150ms var(--jt-ease) !important;
}
.fi-btn-primary:hover {
    transform: translateY(-1px) !important;
    box-shadow: 0 8px 20px rgba(201,162,75,0.35) !important;
}
.fi-btn-primary:active { transform: translateY(0) !important; }

/* ── SIGNATURE: money/total figures — serif + gold underline, never animated ── */
.jt-total-figure {
    font-family: 'Fraunces', serif !important;
    font-weight: 700 !important;
    color: var(--jt-pine) !important;
    border-bottom: 2px solid var(--jt-gold) !important;
    padding-bottom: 2px !important;
}

/* ── ZEBRA STATUS DOT — breathing pulse when genuinely online ── */
#zebra-status-dot {
    width: 12px; height: 12px;
    position: fixed; top: 20px; right: 16px;
    z-index: 9999; border-radius: 50%;
    border: 2px solid var(--jt-ivory);
    cursor: help;
    background-color: #94a3b8;
    box-shadow: 0 0 6px rgba(0,0,0,0.15);
}
#zebra-status-dot.jt-dot-online {
    background-color: #0F7A5C;
    animation: jt-dot-breathe 2.4s ease-in-out infinite;
}
#zebra-status-dot.jt-dot-idle { background-color: var(--jt-gold); }
#zebra-status-dot.jt-dot-offline { background-color: var(--jt-brick); }
@keyframes jt-dot-breathe {
    0%, 100% { box-shadow: 0 0 0 0 rgba(15,122,92,0.45); }
    50%      { box-shadow: 0 0 0 6px rgba(15,122,92,0); }
}

/* ── PAGE HEADER ──────────────────────────── */
.fi-page-header { padding: 1.25rem 0 0.75rem 0 !important; }
.fi-header-heading {
    font-family: 'Fraunces', serif !important;
    color: var(--jt-pine) !important;
}

/* ── SCROLLBAR ────────────────────────────── */
::-webkit-scrollbar { width: 6px; height: 6px; }
::-webkit-scrollbar-track { background: var(--jt-ivory); }
::-webkit-scrollbar-thumb { background: var(--jt-gold); border-radius: 10px; }

/* ── DOCS BUTTON ──────────────────────────── */
.docs-manual-btn {
    background: rgba(201,162,75,0.14) !important;
    border: 1.5px solid var(--jt-gold) !important;
    color: var(--jt-ivory) !important;
    border-radius: 8px !important;
    transition: background 150ms var(--jt-ease), border-color 150ms var(--jt-ease) !important;
}
.docs-manual-btn:hover {
    background: rgba(201,162,75,0.24) !important;
    border-color: var(--jt-gold-soft) !important;
}
.docs-manual-btn svg, .docs-manual-btn span { color: var(--jt-ivory) !important; stroke: currentColor !important; }

#split-payments-body.is-collapsed { max-height: 220px; }

/* ── ANNOUNCEMENT BANNER — quiet slide-down, not a pop ── */
#jeweltag-announcement {
    animation: jt-banner-in 280ms var(--jt-ease);
}
@keyframes jt-banner-in {
    from { opacity: 0; transform: translateY(-8px); }
    to   { opacity: 1; transform: translateY(0); }
}


/* icons */
/******** ACTIVE NAV ITEM ONLY ********/

.fi-topbar nav li > a[aria-current="page"],
.fi-topbar nav li > button[aria-current="page"] {
    background: rgba(201,162,75,.15) !important;
    color: #E4CD8E !important;
    border: 1px solid rgba(201,162,75,.35) !important;
}

/******** KEEP DROPDOWN NORMAL ********/

.fi-dropdown-panel {
    background: #07292A !important;
}

.fi-dropdown-list-item {
    background: transparent !important;
}

.fi-dropdown-list-item:hover {
    background: rgba(201,162,75,.14) !important;
}
/* ── COLUMN TOGGLER & CHECKBOX DROPDOWNS ── */

/* Targets the "Columns" header text */
.fi-dropdown-panel .fi-dropdown-header-heading {
    color: var(--jt-gold-soft) !important;
    font-size: 0.85rem !important;
    letter-spacing: 0.05em !important;
    text-transform: uppercase !important;
}

/* Targets the checkbox labels ("DATE", "CUSTOMER", etc.) */
.fi-dropdown-panel label span,
.fi-dropdown-panel .fi-checkbox-label {
    color: var(--jt-ivory) !important;
    font-weight: 500 !important;
    transition: color 150ms var(--jt-ease);
}

/* Hover state for the labels */
.fi-dropdown-panel label:hover span {
    color: var(--jt-gold-soft) !important;
}

/* Enhances checkbox borders to match the dark theme */
.fi-dropdown-panel input[type="checkbox"] {
    border-color: rgba(201,162,75,0.5) !important;
    background-color: rgba(11,61,60,0.5) !important;
}
/* Remove ALL white pills in top nav */
.fi-topbar button,
.fi-topbar a {
    background: transparent !important;
}

/* Active/open nav item */
.fi-topbar button[aria-expanded="true"],
.fi-topbar a[aria-expanded="true"],
.fi-topbar .fi-dropdown-trigger[aria-expanded="true"] {
    background: rgba(201,162,75,.15) !important;
    border: 1px solid #C9A24B !important;
    color: #E4CD8E !important;
}

/* Text/icons */
.fi-topbar button[aria-expanded="true"] *,
.fi-topbar a[aria-expanded="true"] * {
    color: #E4CD8E !important;
    fill: #E4CD8E !important;
    stroke: #E4CD8E !important;
}

</style>
HTML
            )
            ->resources([
                \App\Filament\Resources\StoreResource::class,
                \App\Filament\Resources\SupplierResource::class,
                \App\Filament\Resources\ProductItemResource::class,
                \App\Filament\Resources\CustomerResource::class,
                \App\Filament\Resources\SaleResource::class,
                \App\Filament\Resources\LaybuyResource::class,
                \App\Filament\Resources\UserResource::class,
                \App\Filament\Resources\RoleResource::class,
                \App\Filament\Resources\PermissionResource::class,
                \App\Filament\Resources\ActivityLogResource::class,
                \App\Filament\Resources\DeletionRequestResource::class,
                \App\Filament\Resources\SaleEditRequestResource::class,
                \App\Filament\Resources\CustomOrderResource::class,
                \App\Filament\Resources\RepairResource::class,
                \App\Filament\Resources\RefundResource::class,
                \App\Filament\Resources\RestockResource::class,
                \App\Filament\Resources\ArchivedStockResource::class,
                \App\Filament\Resources\ArchivedSaleResource::class,
                \App\Filament\Resources\InventoryAuditResource::class,
                \App\Filament\Resources\WishlistResource::class,
                \App\Filament\Resources\SalePaymentResource::class,
                \App\Filament\Resources\StockTransferResource::class,
                \App\Filament\Resources\SupportTicketResource::class,
            ])
            ->pages([
                \App\Filament\Pages\Dashboard::class,
                \App\Filament\Pages\FindStock::class,
                \App\Filament\Pages\FindCustomer::class,
                \App\Filament\Pages\FindSale::class,
                \App\Filament\Pages\FindPayment::class,
                \App\Filament\Pages\CustomerDetailsReport::class,
                \App\Filament\Pages\SoldItemsReport::class,
                \App\Filament\Pages\InventorySettings::class,
                \App\Filament\Pages\EndOfDayClosing::class,
                \App\Filament\Pages\PinCodeAuth::class,
                \App\Filament\Pages\LabelDesigner::class,
                \App\Filament\Pages\ManageSettings::class,
                \App\Filament\Pages\TradeInCheck::class,
                \App\Filament\Pages\MemoInventory::class,
                \App\Filament\Pages\Analytics::class,
                \App\Filament\Pages\StockAgingReport::class,
                \App\Filament\Pages\UpcomingFollowUps::class,
                \App\Filament\Pages\InactiveStockReport::class,
                \App\Filament\Pages\MySalesReport::class,
                \App\Filament\Pages\SoldStockReport::class,
                \App\Filament\Pages\DepositSalesReport::class,
                \App\Filament\Pages\NonStockReport::class,
                \App\Filament\Pages\StockListingReport::class,
                \App\Filament\Pages\ManageApiKeys::class,
                \App\Filament\Pages\SalesReport::class,
                \App\Filament\Pages\RestockLogs::class,
                \App\Filament\Pages\WarrantyReport::class,
                \App\Filament\Pages\FaqCenter::class,
            ])
            ->widgets([
                \App\Filament\Widgets\AdminAttentionWidget::class,
                \App\Filament\Widgets\DashboardQuickMenu::class,
                \App\Filament\Widgets\ScrapGoldCalculator::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                \App\Http\Middleware\LogPageViews::class,
                \Stancl\Tenancy\Middleware\InitializeTenancyByDomain::class,
                \Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains::class,
                \App\Http\Middleware\CheckLicense::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                \App\Http\Middleware\EnsureStaffSession::class,
            ])
            ->navigationItems([
                NavigationItem::make('New Sale')
                    ->label('New Sale')
                    ->group('Sales')
                    ->icon('heroicon-o-plus-circle')
                    ->activeIcon('heroicon-s-plus-circle')
                    ->url(fn(): string => SaleResource::getUrl('create'))
                    ->sort(-100),

                NavigationItem::make('Edit Sale')
                    ->label('Edit Sales')
                    ->group('Sales')
                    ->icon('heroicon-o-pencil-square')
                    ->activeIcon('heroicon-s-pencil-square')
                    ->url(fn(): string => SaleResource::getUrl('index'))
                    ->sort(-99),

                NavigationItem::make('New Customer')
                    ->label('New Customer')
                    ->group('Customer')
                    ->icon('heroicon-o-user-plus')
                    ->activeIcon('heroicon-s-user-plus')
                    ->url(fn(): string => CustomerResource::getUrl('create'))
                    ->sort(-100),
            ])
            ->navigationGroups([
                NavigationGroup::make()->label('Sales'),
                NavigationGroup::make()->label('Vendors'),
                NavigationGroup::make()->label('Inventory'),
                NavigationGroup::make()->label('Admin'),
                NavigationGroup::make()->label('Analytics & Reports'),
            ])
            ->userMenuItems([
                'account' => MenuItem::make()
                    ->label(fn() => Staff::user()?->username ?? auth()->user()->name)
                    ->icon('heroicon-o-user-circle'),

                'switch_user' => MenuItem::make()
                    ->label('Switch Associate PIN')
                    ->icon('heroicon-o-arrows-right-left')
                    ->color('warning')
                    ->url(fn(): string => route('filament.admin.pages.pin-code-auth', ['switch' => true])),

                'settings' => MenuItem::make()
                    ->label('Store Settings')
                    ->url(fn(): string => ManageSettings::getUrl())
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->visible(fn(): bool => \App\Helpers\Staff::user()?->hasAnyRole(['Superadmin', 'Administration']) ?? false),

                'support' => MenuItem::make()
                    ->label('Help & Support')
                    ->icon('heroicon-o-lifebuoy')
                    ->color('info')
                    ->url(fn(): string => \App\Filament\Resources\SupportTicketResource::getUrl('index')),

                'faq' => MenuItem::make()
                    ->label('Help & FAQ')
                    ->icon('heroicon-o-question-mark-circle')
                    ->color('info')
                    ->url(fn(): string => \App\Filament\Pages\FaqCenter::getUrl()),

                'activity_logs' => MenuItem::make()
                    ->label('Activity Logs')
                    ->icon('heroicon-o-finger-print')
                    ->url(fn(): string => ActivityLogResource::getUrl())
                    ->visible(fn(): bool => \App\Helpers\Staff::user()?->hasAnyRole(['Superadmin', 'Administration']) ?? false),
            ])
            ->plugins([
                \Jeffgreco13\FilamentBreezy\BreezyCore::make()
                    ->myProfile(shouldRegisterUserMenu: false)
                    ->enableTwoFactorAuthentication(false),
            ]);
    }

    public function boot(): void
    {
        FilamentView::registerRenderHook(
            'panels::content.start',
            function (): string {
                try {
                    $announcement = \Illuminate\Support\Facades\DB::connection('mysql')
                        ->table('announcements')
                        ->where('is_active', true)
                        ->where(function ($q) {
                            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                        })
                        ->latest()
                        ->first();
                } catch (\Exception $e) {
                    $announcement = null;
                }

                if (!$announcement) return '';

                $styles = [
                    'info'    => ['bg' => '#EEF3F2', 'border' => '#3D6B63', 'icon' => '#3D6B63', 'text' => '#0B3D3C', 'sub' => '#264E48'],
                    'success' => ['bg' => '#EAF6EF', 'border' => '#0F7A5C', 'icon' => '#0F7A5C', 'text' => '#0B3D3C', 'sub' => '#0F7A5C'],
                    'warning' => ['bg' => '#FBF3E2', 'border' => '#C9A24B', 'icon' => '#C9A24B', 'text' => '#5A4419', 'sub' => '#8A6A22'],
                    'danger'  => ['bg' => '#FBEAE8', 'border' => '#B8463F', 'icon' => '#B8463F', 'text' => '#7A2B26', 'sub' => '#963831'],
                ];
                $s       = $styles[$announcement->color ?? 'info'] ?? $styles['info'];
                $key     = 'announcement_dismissed_' . $announcement->id;
                $expires = $announcement->expires_at
                    ? '<div style="font-size:10px;color:' . $s['text'] . ';opacity:0.5;white-space:nowrap;flex-shrink:0;margin-top:3px;font-weight:600;">Expires ' . \Carbon\Carbon::parse($announcement->expires_at)->diffForHumans() . '</div>'
                    : '';

                return '
        <div id="jeweltag-announcement" style="background:' . $s['bg'] . ';border-left:4px solid ' . $s['border'] . ';border-radius:10px;padding:14px 18px;margin-bottom:16px;display:flex;align-items:flex-start;gap:12px;box-shadow:0 1px 4px rgba(11,61,60,0.06);">
            <div style="background:' . $s['icon'] . ';border-radius:50%;width:30px;height:30px;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">
                <svg xmlns="http://www.w3.org/2000/svg" style="width:15px;height:15px;" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
            </div>
            <div style="flex:1;min-width:0;">
                <div style="font-size:13px;font-weight:800;color:' . $s['text'] . ';margin-bottom:3px;">
                    📢 ' . e($announcement->title) . '
                </div>
                <div style="font-size:13px;color:' . $s['sub'] . ';line-height:1.6;">
                    ' . e($announcement->message) . '
                </div>
            </div>
            ' . $expires . '
            <button
                onclick="
                    document.getElementById(\'jeweltag-announcement\').style.display=\'none\';
                    localStorage.setItem(\'' . $key . '\', \'1\');
                "
                title="Dismiss"
                style="background:none;border:none;cursor:pointer;padding:4px;border-radius:6px;display:flex;align-items:center;justify-content:center;flex-shrink:0;opacity:0.4;transition:opacity 0.2s;"
                onmouseover="this.style.opacity=\'1\'"
                onmouseout="this.style.opacity=\'0.4\'"
            >
                <svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="' . $s['text'] . '" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <script>
            (function() {
                var key = \'' . $key . '\';
                if (localStorage.getItem(key) === \'1\') {
                    var el = document.getElementById(\'jeweltag-announcement\');
                    if (el) el.style.display = \'none\';
                }
            })();
        </script>';
            }
        );

        \Filament\Tables\Columns\TextColumn::configureUsing(function (\Filament\Tables\Columns\TextColumn $column): void {
            $column->timezone(fn() => config('app.timezone'));
        });
    }
}