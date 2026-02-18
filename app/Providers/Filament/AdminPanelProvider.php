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
use App\Models\Store;

class AdminPanelProvider extends PanelProvider
{
    /**
     * Configure the Filament Admin Panel.
     */
    public function panel(Panel $panel): Panel
    {
        // 1. Dynamically fetch the store logo for the navigation bar
        $store = Store::first();
        $logoUrl = ($store && $store->logo_path) 
            ? asset('storage/' . $store->logo_path) 
            : asset('jeweltaglogo.png');

        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->registration()
            ->login()
            ->topNavigation()
            // 2. Set the dynamic brand logo and height to fix the "bad gap"
            ->brandLogo($logoUrl)
            ->brandLogoHeight('2.5rem') 
            ->brandName('JEWELTAG')
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_END,
                fn (): string => view('filament.hooks.custom-logo')->render(),
            )
            ->favicon(asset('jeweltaglogo.png'))
            ->darkMode(false, false)
            ->colors([
                'primary' => Color::hex('#0d9488'), // Main Teal
                'gray' => Color::hex('#4b5563'),
                'info' => Color::hex('#0284c7'),
                'success' => Color::hex('#059669'),
                'warning' => Color::hex('#d97706'),
                'danger' => Color::hex('#dc2626'),
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
    /**
     * Force Light Mode and Persistence
     */
    document.documentElement.classList.add('light');
    document.documentElement.classList.remove('dark');
    localStorage.setItem('theme', 'light');

    /* ───────── ZEBRA STATUS INDICATOR ───────── */
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
        }, function() {
            dot.style.backgroundColor = '#dc2626';
        });
    }

    /* ───────── ZEBRA PRINT LISTENER ───────── */
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
/* ───────── ENHANCED UI SYSTEM ───────── */
:root {
    --primary-color: #0d9488;
    --primary-light: #2dd4bf;
    --primary-dark: #0f766e;
    --body-bg: #0e7490;
    --nav-bg: linear-gradient(135deg, #f0f9ff 0%, #e6fffa 100%);
    --card-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
}

/* Base Layout Adjustments */
body, .fi-body { 
    background-color: var(--body-bg) !important; 
    background-image: radial-gradient(circle at 2px 2px, rgba(255,255,255,0.05) 1px, transparent 0);
    background-size: 40px 40px;
}

.fi-main { 
    background-color: transparent !important; 
     
}

/* Topbar Styling & Logo Alignment */
.fi-topbar { 
    background: var(--nav-bg) !important; 
    border-bottom: 3px solid var(--primary-color) !important;
    backdrop-filter: blur(8px);
}
/* Ensure the logo has clean padding and doesn't look "bad" in the gap */
.fi-logo {
    padding: 0.25rem;
    filter: drop-shadow(0 1px 2px rgba(0,0,0,0.1));
}

.fi-topbar * { color: #0f172a !important; font-weight: 600 !important; }

/* ───────── RESOURCE TABLE STYLING ───────── */
.fi-ta-ctn { 
    border-radius: 16px !important;
    overflow: hidden !important;
    border: 1px solid rgba(255,255,255,0.2) !important;
    box-shadow: var(--card-shadow) !important;
    background: white !important;
}

.fi-ta-header { 
    background-color: #f8fafc !important; 
    border-bottom: 1px solid #e2e8f0 !important;
}

.fi-ta-header-cell { 
    background-color: #f1f5f9 !important; 
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.05em;
    color: #475569 !important;
}

.fi-ta-row:hover {
    background-color: #f0fdfa !important;
    transition: background-color 0.2s ease;
}

/* ───────── CREATE/EDIT FORM STYLING ───────── */
.fi-resource-create-form, 
.fi-resource-edit-form,
section.fi-section { 
    background-color: rgba(255, 255, 255, 0.98) !important; 
    border-radius: 20px !important;
    padding: 10px !important;
    border-left: 8px solid var(--primary-color) !important; 
    box-shadow: var(--card-shadow) !important;
}

.fi-fo-field-wrp-label label {
    color: #334155 !important;
    font-weight: 700 !important;
}

.fi-input-wrp {
    border-radius: 8px !important;
    transition: all 0.2s;
}

.fi-input-wrp:focus-within {
    box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.2) !important;
    border-color: var(--primary-color) !important;
}

/* Status Indicator */
#zebra-status-dot { 
    width: 14px; 
    height: 14px; 
    
    /* 🔹 FIX: Prevent it from disappearing/shrinking */
    min-width: 14px; 
    min-height: 14px;
    flex-shrink: 0; 
    
    /* 🔹 FIX: Ensure it sits on top of other layers */
    z-index: 9999;
    position: relative;

    border-radius: 50%; 
    background-color: #94a3b8; 
    border: 2px solid white; 
    display: inline-block; 
    margin-left: 70px; 
    cursor: help; 
    transition: all 0.5s ease;
    box-shadow: 0 0 8px rgba(0,0,0,0.2);
}

.fi-btn-primary { 
    background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%) !important; 
    border: none !important;
    border-radius: 8px !important;
    padding: 0.6rem 1.5rem !important;
    box-shadow: 0 4px 6px -1px rgba(13, 148, 136, 0.3) !important;
}

.fi-btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 10px 15px -3px rgba(13, 148, 136, 0.4) !important;
}

/* Scrollbar Styling */
::-webkit-scrollbar { width: 8px; }
::-webkit-scrollbar-track { background: #0e7490; }
::-webkit-scrollbar-thumb { background: #0d9488; border-radius: 10px; }
.fi-page-header {
    padding: 1.25rem 0 0.75rem 0 !important;
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
            ])
            ->pages([
                Pages\Dashboard::class,
                \App\Filament\Pages\FindStock::class,
                \App\Filament\Pages\FindCustomer::class,
                \App\Filament\Pages\FindSale::class,
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
            ])
            ->widgets([
                \App\Filament\Widgets\DashboardQuickMenu::class,
                 \App\Filament\Widgets\ScrapGoldCalculator::class,
                  \App\Filament\Widgets\UpcomingFollowUps::class,
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
            ])
            ->authMiddleware([
                Authenticate::class,
                \App\Http\Middleware\EnsureStaffSession::class,
            ])
            ->navigationGroups([
                NavigationGroup::make()->label('Sales'),
                NavigationGroup::make()->label('Vendors'),
                NavigationGroup::make()->label('Inventory'),
                NavigationGroup::make()->label('Admin'),
                NavigationGroup::make()->label('Analytics & Reports'),
            ])
            ->userMenuItems([
                'settings' => MenuItem::make()
                    ->label('Store Settings')
                    ->url(fn (): string => ManageSettings::getUrl())
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->visible(fn (): bool => \App\Helpers\Staff::user()?->hasAnyRole(['Superadmin', 'Administration']) ?? false),

                'activity_logs' => MenuItem::make()
                    ->label('Activity Logs')
                    ->icon('heroicon-o-finger-print')
                    ->url(fn (): string => ActivityLogResource::getUrl())
                    ->visible(fn (): bool => \App\Helpers\Staff::user()?->hasAnyRole(['Superadmin', 'Administration']) ?? false),
            ]);
    }
}