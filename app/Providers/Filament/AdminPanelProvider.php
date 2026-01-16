<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
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
            ->topNavigation()
            ->brandLogo(asset('jeweltaglogo.png'))

            ->colors([
                'primary' => Color::Blue,
            ])

            /* ───────── FORCE DARK MODE (INLINE, NO EXTRA FILES) ───────── */
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn () => <<<HTML
<script>
    // Force dark mode always
    document.documentElement.classList.add('dark');
    document.documentElement.style.colorScheme = 'dark';
    localStorage.setItem('theme', 'dark');
</script>

<style>
    /* GLOBAL DARK BACKGROUND */
    html,
    body,
    .fi-body,
    .fi-page,
    .fi-main,
    .fi-layout {
        background-color: #0f172a !important;
        color: #e2e8f0 !important;
    }

    /* REMOVE THEME SWITCHER */
    .fi-theme-switcher,
    button[aria-label*="theme"],
    button[aria-label*="Theme"] {
        display: none !important;
    }

    /* TOP NAV BAR */
    .fi-topbar {
        background-color: #020617 !important;
        border-bottom: 1px solid #1e293b !important;
    }

    /* CARDS / SECTIONS */
    .fi-card,
    .fi-section {
        background-color: #020617 !important;
        border-color: #1e293b !important;
    }

    /* INPUTS */
    .fi-input,
    .fi-select,
    .fi-textarea {
        background-color: #020617 !important;
        color: #e2e8f0 !important;
        border-color: #334155 !important;
    }

    /* LABELS */
    .fi-fo-field-label {
        color: #cbd5e1 !important;
    }

    /* TABLES */
    .fi-ta-table {
        background-color: #020617 !important;
    }
</style>
HTML
            )

            /* ───────── RESOURCES ───────── */
            ->resources([
                \App\Filament\Resources\SupplierResource::class,
                \App\Filament\Resources\ProductItemResource::class,
                \App\Filament\Resources\CustomerResource::class,
                \App\Filament\Resources\SaleResource::class,
                \App\Filament\Resources\UserResource::class,
                \App\Filament\Resources\RoleResource::class,
                \App\Filament\Resources\PermissionResource::class,
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

            /* ───────── NAV GROUPS ───────── */
            ->navigationGroups([
                NavigationGroup::make()->label('Sales'),
                NavigationGroup::make()->label('CRM'),
                NavigationGroup::make()->label('Reports'),
            ]);
    }
}
