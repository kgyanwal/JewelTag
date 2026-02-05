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

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->registration()
            ->login()
            ->topNavigation()
            ->brandLogo(null)
            ->brandName('')
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_END,
                fn (): string => view('filament.hooks.custom-logo')->render(),
            )
            ->favicon(asset('jeweltaglogo.png'))
            ->darkMode(false, false)
            ->colors([
                'primary' => Color::hex('#0d9488'),
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
/* ───────── LUXURY SEA THEME SYSTEM ───────── */
:root {
    --primary-color: #0d9488;
    --primary-light: #2dd4bf;
    --primary-dark: #0f766e;
    --body-bg: #0e7490;
    --nav-bg: linear-gradient(135deg, #e0f2fe 0%, #ccfbf1 100%);
    --text-primary: #164e63;
    --text-body: #1e293b;
}

#zebra-status-dot { width: 12px; height: 12px; border-radius: 50%; background-color: #94a3b8; border: 2px solid white; display: inline-block; margin-left: 10px; cursor: help; transition: background-color 0.5s ease; }

@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

body, .fi-body, .fi-btn, .fi-input, .fi-label, .fi-heading {
    font-family: 'Inter', -apple-system, sans-serif !important;
}

body, .fi-layout { background-color: var(--body-bg) !important; color: var(--text-body) !important; }
.fi-main { background-color: var(--body-bg) !important; padding: 1.5rem !important; min-height: calc(100vh - 75px) !important; }
.fi-topbar { background: linear-gradient(135deg, #e0f2fe 0%, #ccfbf1 100%) !important; border-bottom: 2px solid #5eead4 !important; }
.fi-topbar * { color: #0f172a !important; }

.fi-topbar-content { display: flex !important; align-items: center !important; gap: 1rem !important; }
.fi-user-menu { order: -10 !important; margin-right: 0 !important; }
.fi-topbar-nav { order: -5 !important; flex: 1 !important; }
.fi-main-search { order: 1 !important; }
.fi-topbar-content > div:last-child { order: 2 !important; }

.fi-section, .fi-ta-ctn { background-color: #ffffff !important; border: none !important; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.2) !important; }
.fi-btn-primary { background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important; color: #ffffff !important; font-weight: 800 !important; }
.fi-ta-header, .fi-ta-header-cell, .fi-ta-row { background-color: #eef2f7 !important; }
.fi-ta-cell { color: #000000 !important; }
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
            ])
            ->widgets([
                \App\Filament\Widgets\DashboardQuickMenu::class,
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
            ])
            ->authMiddleware([
                Authenticate::class,
                \App\Http\Middleware\EnsureStaffSession::class,
            ])
            ->navigationGroups([
                NavigationGroup::make()->label('Sales'),
                NavigationGroup::make()->label('Customer'),
                NavigationGroup::make()->label('Reports'),
                NavigationGroup::make()->label('Inventory'),
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