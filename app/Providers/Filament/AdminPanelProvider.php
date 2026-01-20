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
            ->topNavigation() // High-end horizontal layout
            ->brandLogo(asset('jeweltaglogo.png'))
            ->brandLogoHeight('2.5rem')
            
            /* ───────── MODERN LUXURY COLOR PALETTE ───────── */
            ->colors([
                'primary' => Color::Indigo,
                'gray' => Color::Slate,
                'info' => Color::Blue,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
                'danger' => Color::Rose,
            ])
            ->font('Inter') 
            ->maxContentWidth(MaxWidth::ScreenExtraLarge)

            /* ───────── PREMIUM UI CUSTOMIZATION (CSS & JS) ───────── */
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn () => <<<HTML
<script>
    // Force dark mode state globally
    document.documentElement.classList.add('dark');
    document.documentElement.style.colorScheme = 'dark';
    localStorage.setItem('theme', 'dark');
</script>

<style>
    /* 1. MESH GRADIENT BACKGROUND */
    :root {
        --bg-main: #020617;
        --mesh-gradient: 
            radial-gradient(at 0% 0%, rgba(79, 70, 229, 0.12) 0px, transparent 50%),
            radial-gradient(at 100% 100%, rgba(168, 85, 247, 0.12) 0px, transparent 50%);
    }

    html, body, .fi-layout {
        background-color: var(--bg-main) !important;
        background-image: var(--mesh-gradient) !important;
        background-attachment: fixed !important;
        color: #f8fafc !important;
    }

    /* 2. GLASSMORPHISM CARDS */
    .fi-card, .fi-section, .fi-ta-ctn, .fi-ta-content {
        background-color: rgba(15, 23, 42, 0.6) !important;
        backdrop-filter: blur(16px) saturate(180%) !important;
        -webkit-backdrop-filter: blur(16px) saturate(180%) !important;
        border: 1px solid rgba(255, 255, 255, 0.08) !important;
        border-radius: 1.25rem !important;
        box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.3) !important;
    }

    /* 3. MODERN GRADIENT TOPBAR */
    .fi-topbar {
        background-color: rgba(2, 6, 23, 0.8) !important;
        backdrop-filter: blur(12px) !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05) !important;
    }

    /* 4. PREMIUM GRADIENT BUTTONS */
    .fi-btn-color-primary {
        background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%) !important;
        border: none !important;
        box-shadow: 0 4px 14px 0 rgba(99, 102, 241, 0.3) !important;
        transition: all 0.3s ease !important;
    }
    
    .fi-btn-color-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(99, 102, 241, 0.4) !important;
    }

    /* 5. TYPOGRAPHY & HEADINGS */
    .fi-header-heading {
        letter-spacing: -0.02em !important;
        font-weight: 800 !important;
        background: linear-gradient(to bottom right, #fff 40%, #94a3b8 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    /* 6. HIDE THEME SWITCHER */
    .fi-theme-switcher {
        display: none !important;
    }

    /* 7. CLEANER TABLE HOVER */
    .fi-ta-row:hover {
        background-color: rgba(255, 255, 255, 0.02) !important;
    }
</style>
HTML
            )

            /* ───────── RESOURCES ───────── */
            ->resources([
                \App\Filament\Resources\StoreResource::class,
                \App\Filament\Resources\SupplierResource::class,
                \App\Filament\Resources\ProductItemResource::class,
                \App\Filament\Resources\CustomerResource::class,
                \App\Filament\Resources\SaleResource::class,
                \App\Filament\Resources\UserResource::class,
                \App\Filament\Resources\RoleResource::class,
                \App\Filament\Resources\PermissionResource::class,
                // \App\Filament\Resources\SalesAssistantResource::class,
            ])

            /* ───────── PAGES ───────── */
            ->pages([
                Pages\Dashboard::class,
                \App\Filament\Pages\FindStock::class,
                \App\Filament\Pages\FindCustomer::class,
                \App\Filament\Pages\FindSale::class,
                \App\Filament\Pages\CustomerDetailsReport::class,
                \App\Filament\Pages\SoldItemsReport::class,
                \App\Filament\Pages\InventorySettings::class,
            ])

            /* ───────── WIDGETS ───────── */
            ->widgets([
                \App\Filament\Widgets\StatsOverview::class,
                \App\Filament\Widgets\DepartmentChart::class,
                \App\Filament\Widgets\LatestSales::class,
            ])

            /* ───────── MIDDLEWARE ───────── */
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
            ])

            /* ───────── NAV GROUPS (FIXED: Removed icons to avoid crash) ───────── */
            ->navigationGroups([
                NavigationGroup::make()->label('Sales'),
                NavigationGroup::make()->label('Customer'),
                NavigationGroup::make()->label('Reports'),
            ]);
    }
}