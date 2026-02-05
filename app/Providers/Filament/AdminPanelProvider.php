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

        ->topNavigation()
        // 🔹 Inject the logo to the right of the User Menu
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
            /* |--------------------------------------------------------------------------
            | Zebra Browser Print Assets
            |-------------------------------------------------------------------------- */
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
</script>

<style>
/* ───────── LUXURY SEA THEME SYSTEM ───────── */
:root {
    --primary-color: #0d9488;
    --primary-light: #2dd4bf;
    --primary-dark: #0f766e;
    --body-bg: #0e7490;
    --nav-bg: linear-gradient(135deg, #e0f2fe 0%, #ccfbf1 100%);
    --content-bg: #ffffff;
    --card-bg: #ffffff;
    --sidebar-bg: #f8fafc;
    --text-primary: #164e63;
    --text-secondary: #0e7490;
    --text-body: #1e293b;
    --text-muted: #64748b;
    --text-white: #ffffff;
    --text-button: #ffffff;
    --border-color: #cbd5e1;
    --border-light: #e2e8f0;
    --border-primary: #2dd4bf;
    --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    --shadow-lg: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
}

@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

body, .fi-body, .fi-btn, .fi-input, .fi-label, .fi-heading {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
}

body, .fi-layout { background-color: var(--body-bg) !important; color: var(--text-body) !important; }
.fi-main { background-color: var(--body-bg) !important; padding: 1.5rem !important; min-height: calc(100vh - 75px) !important; }
.fi-simple-main-ctn { background-color: var(--body-bg) !important; }
.fi-simple-main { background-color: #ffffff !important; border-radius: 24px !important; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5) !important; padding: 2.5rem !important; }
.fi-topbar { background: linear-gradient(135deg, #e0f2fe 0%, #ccfbf1 100%) !important; border-bottom: 2px solid #5eead4 !important; }
.fi-topbar * { color: #0f172a !important; }
.fi-breadcrumbs-item-label { color: #ffffff !important; font-weight: 700 !important; }
.fi-wi-stats-overview-stat-label {
    color: #0f172a !important;   /* dark black/gray */
    font-weight: 700 !important;
    opacity: 1 !important;
}
/* 1. Make the topbar content container full width */
/* 1. Make the topbar container a controllable flexbox */
.fi-topbar-content {
    display: flex !important;
    align-items: center !important;
    gap: 1rem !important;
}

/* 2. FORCE THE PROFILE (NS) TO THE ABSOLUTE LEFT */
.fi-user-menu {
    order: -10 !important; /* 🔹 Negative order pulls it to the start */
    margin-right: 0 !important;
}

/* 3. Ensure Navigation (Dashboard, Sales, etc.) comes after the Profile */
.fi-topbar-nav {
    order: -5 !important;
    flex: 1 !important; /* 🔹 Pushes everything else to the right */
}

/* 4. Keep Search and Logo on the far right */
.fi-main-search {
    order: 1 !important;
}

.fi-topbar-content > div:last-child {
    order: 2 !important; /* 🔹 Your JewelTag Logo */
}
.fi-wi-stats-overview-stat-description,
.fi-wi-stats-overview-stat-icon {
    opacity: 1 !important;
    color: #ffffff !important;   /* white works best on gradients */
    fill: #ffffff !important;
    stroke: #ffffff !important;
}
.fi-section, .fi-ta-ctn { background-color: #ffffff !important; border: none !important; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.2) !important; }
.fi-btn-primary { background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important; color: #ffffff !important; font-weight: 800 !important; }
.fi-input-wrp { background-color: white !important; }
.fi-ta-ctn, .fi-ta-header, .fi-ta-header-cell, .fi-ta-row { background-color: #eef2f7 !important; }
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
        // 🔹 USE STAFF HELPER: Checks the PIN-authenticated user's role
        ->visible(fn (): bool => \App\Helpers\Staff::user()?->hasAnyRole(['Superadmin', 'Administration']) ?? false),
]);
            
    }
}