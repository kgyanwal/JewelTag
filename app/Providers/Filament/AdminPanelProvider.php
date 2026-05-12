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
                        <div style="
                            display:flex;align-items:center;gap:6px;
                            background:rgba(13,148,136,0.10);
                            border:1.5px solid #0d9488;
                            border-radius:999px;
                            padding:5px 14px 5px 10px;
                            margin-right:8px;
                            font-size:0.75rem;
                            font-weight:700;
                            color:#0f766e;
                            white-space:nowrap;
                        ">
                            <svg xmlns="http://www.w3.org/2000/svg" style="width:14px;height:14px;flex-shrink:0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                            </svg>
                            {{ $name }}
                        </div>
                    @endif
                '),
            )
            ->favicon(asset('jeweltaglogo.png'))
            ->darkMode(false, false)
            ->colors([
                'primary' => Color::hex('#0d9488'),
                'gray'    => Color::hex('#4b5563'),
                'info'    => Color::hex('#0284c7'),
                'success' => Color::hex('#059669'),
                'warning' => Color::hex('#d97706'),
                'danger'  => Color::hex('#dc2626'),
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
            dot.style.backgroundColor = '#dc2626';
            dot.title = 'Zebra App Not Running';
            return;
        }
        BrowserPrint.getDefaultDevice("printer", function(device) {
            dot.style.backgroundColor = (device && device.name) ? '#22c55e' : '#eab308';
            dot.title = (device && device.name) ? 'Printer Online: ' + device.name : 'Zebra App Ready - No Printer Found';
        }, function() { dot.style.backgroundColor = '#dc2626'; });
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
   JEWELTAG POS — CLEAN UI
   ═══════════════════════════════════════════ */

/* ── BODY ─────────────────────────────────── */
body, .fi-body {
    background-color: #0e7490 !important;
    background-image: radial-gradient(circle at 2px 2px, rgba(255,255,255,0.04) 1px, transparent 0);
    background-size: 40px 40px;
}
.fi-main { background-color: transparent !important; }

/* ── TOPBAR — white, teal border ─────────── */
.fi-topbar {
    background: #ffffff !important;
    border-bottom: 3px solid #0d9488 !important;
    box-shadow: 0 1px 6px rgba(0,0,0,0.07) !important;
}
.fi-topbar * { color: #0f172a !important; font-weight: 600 !important; }
.fi-logo { padding: 0.25rem; }

/* ── NAV — no wrap ────────────────────────── */
/* ONLY navigation menu items */
.fi-topbar nav > ul {
    display: flex !important;
    flex-wrap: nowrap !important;
    overflow-x: auto !important;
    scrollbar-width: none !important;
}

.fi-topbar nav > ul::-webkit-scrollbar {
    display: none !important;
}

/* ONLY top menu links/buttons */
.fi-topbar nav > ul > li > a,
.fi-topbar nav > ul > li > button {
    white-space: nowrap !important;
    font-size: 0.82rem !important;
    padding: 6px 12px !important;
    border-radius: 6px !important;
    transition: background 0.15s !important;
}

.fi-topbar nav > ul > li > a:hover,
.fi-topbar nav > ul > li > button:hover {
    background: rgba(13,148,136,0.1) !important;
    color: #0d9488 !important;
}

/* ── DROPDOWN PANEL — teal, 2-col, with icons ─ */
.fi-dropdown-panel {
    background: #0e7490 !important;
    border: none !important;
    border-radius: 0 0 14px 14px !important;
    box-shadow: 0 16px 48px rgba(0,0,0,0.2) !important;
    padding: 20px !important;
    min-width: 540px !important;
}

/* 🚀 FORCE ICONS TO SHOW IN TOP NAV DROPDOWNS */
.fi-topbar-nav-dropdown .fi-dropdown-list-item-icon,
.fi-dropdown-list-item-icon {
    display: flex !important; /* Overrides Filament default hidden state */
    align-items: center !important;
    justify-content: center !important;
    color: rgba(255,255,255,0.9) !important;
    width: 20px !important;
    height: 20px !important;
    margin-right: 4px !important;
}

.fi-dropdown-list-item-icon svg {
    width: 20px !important;
    height: 20px !important;
    stroke-width: 2 !important;
}

/* 2-column grid setup */
.fi-dropdown-list {
    display: grid !important;
    grid-template-columns: 1fr 1fr !important;
    gap: 4px 10px !important;
    position: relative !important;
}

/* Center divider */
.fi-dropdown-list::after {
    content: '' !important;
    position: absolute !important;
    left: 50% !important;
    top: 10px !important;
    bottom: 10px !important;
    width: 1px !important;
    background: rgba(255,255,255,0.15) !important;
    pointer-events: none !important;
}

/* Item Styling */
.fi-dropdown-list-item {
    border-radius: 8px !important;
    transition: all 0.2s !important;
}

.fi-dropdown-list-item:hover {
    background: rgba(255,255,255,0.1) !important;
}

/* Ensure text doesn't overlap icons */
.fi-dropdown-list-item > a, 
.fi-dropdown-list-item > button {
    display: flex !important;
    align-items: center !important;
    padding: 10px 12px !important;
    gap: 12px !important;
}

.fi-dropdown-list-item-label {
    color: #ffffff !important;
    font-size: 0.88rem !important;
    font-weight: 500 !important;
}

.fi-dropdown-list-item:hover {
    background: rgba(255,255,255,0.15) !important;
}
.fi-dropdown-list-item:hover .fi-dropdown-list-item-label {
    color: #ffffff !important;
}
.fi-dropdown-list-item:hover .fi-dropdown-list-item-icon {
    color: #ffffff !important;
}

/* ── TABLES ───────────────────────────────── */
.fi-ta-ctn {
    border-radius: 14px !important;
    overflow: hidden !important;
    box-shadow: 0 4px 16px rgba(0,0,0,0.08) !important;
    background: white !important;
    border: 1px solid #e2e8f0 !important;
}
.fi-ta-header-cell {
    background: #f8fafc !important;
    font-size: 0.72rem !important;
    font-weight: 700 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.05em !important;
    color: #64748b !important;
}
.fi-ta-row { border-bottom: 1px solid #f1f5f9 !important; }
.fi-ta-row:hover { background: #f0fdfa !important; }

/* ── FORM SECTIONS ────────────────────────── */
section.fi-section {
    background: rgba(255,255,255,0.97) !important;
    border-radius: 16px !important;
    border-left: 5px solid #0d9488 !important;
    box-shadow: 0 4px 16px rgba(0,0,0,0.07) !important;
}

/* ── INPUTS ───────────────────────────────── */
.fi-input-wrp { border-radius: 8px !important; }
.fi-input-wrp:focus-within {
    box-shadow: 0 0 0 3px rgba(13,148,136,0.18) !important;
    border-color: #0d9488 !important;
}

/* ── BUTTONS ──────────────────────────────── */
.fi-btn-primary {
    background: linear-gradient(135deg, #0d9488, #0f766e) !important;
    border: none !important;
    border-radius: 8px !important;
    box-shadow: 0 2px 8px rgba(13,148,136,0.25) !important;
}
.fi-btn-primary:hover {
    transform: translateY(-1px) !important;
    box-shadow: 0 6px 16px rgba(13,148,136,0.35) !important;
}

/* ── ZEBRA DOT ────────────────────────────── */
#zebra-status-dot {
    width: 12px; height: 12px;
    position: fixed; top: 20px; right: 16px;
    z-index: 9999; border-radius: 50%;
    background-color: #94a3b8;
    border: 2px solid white;
    cursor: help;
    transition: background-color 0.4s;
    box-shadow: 0 0 6px rgba(0,0,0,0.15);
}

/* ── PAGE HEADER ──────────────────────────── */
.fi-page-header { padding: 1.25rem 0 0.75rem 0 !important; }

/* ── SCROLLBAR ────────────────────────────── */
::-webkit-scrollbar { width: 6px; height: 6px; }
::-webkit-scrollbar-track { background: #0e7490; }
::-webkit-scrollbar-thumb { background: #0d9488; border-radius: 10px; }
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
            ])
            ->widgets([
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
            fn(): string => Blade::render('
                @php
                    try {
                        $announcement = \Illuminate\Support\Facades\DB::connection("mysql")
                            ->table("announcements")
                            ->where("is_active", true)
                            ->where(function($q) {
                                $q->whereNull("expires_at")->orWhere("expires_at", ">", now());
                            })
                            ->latest()
                            ->first();
                    } catch (\Exception $e) {
                        $announcement = null;
                    }
                @endphp

                @if($announcement)
                    <div class="p-4 mb-4 text-sm rounded-lg bg-{{ $announcement->color }}-500 text-white shadow-md border-l-4 border-white/20">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                            </svg>
                            <span><strong>{{ $announcement->title }}:</strong> {{ $announcement->message }}</span>
                        </div>
                    </div>
                @endif
            '),
        );

        \Filament\Tables\Columns\TextColumn::configureUsing(function (\Filament\Tables\Columns\TextColumn $column): void {
            $column->timezone(fn() => config('app.timezone'));
        });
    }
}