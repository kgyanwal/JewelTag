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
            ->topNavigation()
            ->brandLogo(asset('jeweltaglogo.png'))
            ->brandLogoHeight('2.5rem')

            /* ───────── LIGHT MODE ONLY ───────── */
            ->darkMode(false, false)

            /* ───────── YOUR BRAND COLORS ───────── */
            ->colors([
                'primary' => Color::hex('#10b981'), // Your Emerald Green
                'gray' => Color::Slate,
                'info' => Color::hex('#0ea5e9'),
                'success' => Color::hex('#10b981'),
                'warning' => Color::hex('#f59e0b'),
                'danger' => Color::hex('#ef4444'),
            ])
            ->font('Inter')
            
            /* FIX 1: CHANGE MAX WIDTH TO FULL TO REMOVE THE HUGE GAPS */
            ->maxContentWidth(MaxWidth::Full) 

            /* ───────── PROFESSIONAL UI OVERRIDE ───────── */
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn() => <<<HTML
<script>
    document.documentElement.classList.add('light');
    document.documentElement.classList.remove('dark');
</script>

<style>
/* ───────── RESTORED SEA THEME SYSTEM ───────── */
:root {
    --sea-bg: #e0f2fe; 
    --sea-body: #0e7490; /* YOUR DEEP TEAL BODY COLOR */
    --sea-nav-gradient: linear-gradient(135deg, #e0f2fe 0%, #ccfbf1 100%);
    --sea-border: #99f6e4;
    --sea-text: #0e7490;
}

/* FIX 2: ALIGNMENT & SPACE REMOVAL */
body, .fi-layout {
    background-color: var(--sea-body) !important;
}

.fi-main {
    background-color: var(--sea-body) !important;
    padding-left: 1.5rem !important;
    padding-right: 1.5rem !important;
}

/* Ensure the container takes full width and isn't squeezed */
.fi-main > div {
    max-width: 100% !important;
}

/* 3. MODERNIZED TOPBAR (VISIBLE & ALIGNED) */
.fi-topbar nav,
.fi-topbar-with-navigation nav {
    background: var(--sea-nav-gradient) !important;
    background-color: var(--sea-bg) !important;
    border-bottom: 2px solid var(--sea-border) !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1) !important;
    height: 75px !important;
}

.fi-topbar-item-button {
    color: var(--sea-text) !important;
    font-weight: 700 !important;
}

.fi-topbar-item-active .fi-topbar-item-button {
    background-color: white !important;
    border: 1px solid var(--sea-border) !important;
    border-radius: 12px !important;
    box-shadow: 0 2px 6px rgba(16, 185, 129, 0.2) !important;
}

/* 4. LUXURY TABLE & CARD STYLING */
.fi-section, .fi-ta-ctn {
    background-color: white !important;
    border: none !important;
    border-radius: 20px !important;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.2) !important;
    overflow: hidden !important;
}

/* Table Header (Making text highly visible) */
.fi-ta-header-cell {
    background-color: #f1f5f9 !important;
    color: #1e293b !important;
    font-weight: 800 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.05em !important;
    font-size: 0.75rem !important;
}

/* Table Rows */
.fi-ta-row {
    border-bottom: 1px solid #f1f5f9 !important;
}

.fi-ta-row:hover {
    background-color: #f0fdf4 !important; /* Soft Emerald tint on hover */
}

/* 5. FIXING THE TEXT VISIBILITY */
.fi-header-heading {
    color: white !important; /* Since body is dark teal, headings should be white */
    font-weight: 800 !important;
    text-shadow: 0 1px 2px rgba(0,0,0,0.2);
}

.fi-ta-text-item-label {
    color: #334155 !important;
    font-weight: 500 !important;
}

/* 6. BUTTONS */
.fi-btn-primary, .fi-ac-action {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
    border: none !important;
    border-radius: 10px !important;
    font-weight: 700 !important;
    transition: transform 0.2s ease !important;
}

.fi-btn-primary:hover {
    transform: translateY(-2px) !important;
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
                \App\Filament\Resources\UserResource::class,
                \App\Filament\Resources\RoleResource::class,
                \App\Filament\Resources\PermissionResource::class,
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
                \App\Filament\Pages\LabelDesigner::class
            ])
            ->widgets([
                \App\Filament\Widgets\StatsOverview::class,
                
                \App\Filament\Widgets\LatestSales::class,
                \App\Filament\Widgets\FastestSellingItems::class,
                \App\Filament\Widgets\DepartmentChart::class,
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
                \App\Http\Middleware\VerifyPinCode::class,
            ])
            ->navigationGroups([
                NavigationGroup::make()->label('Sales'),
                NavigationGroup::make()->label('Customer'),
                NavigationGroup::make()->label('Reports'),
            ]);
    }
}