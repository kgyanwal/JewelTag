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
                \Filament\View\PanelsRenderHook::HEAD_END,
                fn () => <<<HTML
<script>
    // Force dark mode everywhere
    document.documentElement.classList.add('dark');
    document.documentElement.style.colorScheme = 'dark';
    localStorage.setItem('theme', 'dark');
</script>

<style>
/* ───────── MODERN COLOR SYSTEM ───────── */
:root {
    /* Header Colors - Emerald Green Theme */
    --header-primary: #065f46;
    --header-secondary: #064e3b;
    --header-accent: #10b981;
    --header-text: #ffffff;
    --header-border: #059669;
    
    /* Body Colors - Dark Blue Theme */
    --body-bg: #0f172a;
    --body-card: #1e293b;
    --body-card-light: #334155;
    --body-text: #f8fafc;
    --body-text-muted: #cbd5e1;
    --body-border: #475569;
    
    /* Table Colors - High Contrast */
    --table-header: #1e293b;
    --table-header-text: #e2e8f0;
    --table-row-bg: #1e293b;
    --table-row-alt: #1a2332;
    --table-row-hover: #2d3748;
    --table-border: #334155;
    --table-accent: #10b981;
    
    /* Accent Colors */
    --accent-gold: #d4af37;
    --accent-blue: #3b82f6;
    --accent-amber: #f59e0b;
    --accent-red: #ef4444;
    
    /* Shadows */
    --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.5);
    --shadow-xl: 0 25px 50px -12px rgba(0, 0, 0, 0.6);
}

/* ───────── BASE RESET ───────── */
html, body {
    background: var(--body-bg) !important;
    color: var(--body-text);
    font-family: Inter, system-ui, -apple-system, sans-serif;
}

/* ───────── PAGE BACKGROUND ───────── */
.fi-layout {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%) !important;
    min-height: 100vh;
}

/* ───────── HEADER / TOPBAR - EMERALD GREEN ───────── */
.fi-topbar,
.fi-layout > .fixed.inset-x-0.top-0,
.fi-topbar > .fi-topbar-ctn {
    background: linear-gradient(
        135deg,
        var(--header-primary),
        var(--header-secondary)
    ) !important;
    height: 70px !important;
    border-bottom: 3px solid var(--header-border) !important;
    box-shadow: var(--shadow-lg) !important;
}

/* Brand Logo & Text */
.fi-topbar .fi-brand {
    font-size: 1.5rem !important;
    font-weight: 700 !important;
    color: var(--header-text) !important;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

/* Header Navigation Items */
.fi-topbar nav a,
.fi-topbar nav button {
    color: rgba(255, 255, 255, 0.9) !important;
    padding: 8px 16px !important;
    border-radius: 8px !important;
    font-weight: 500 !important;
    transition: all 0.2s ease !important;
    margin: 0 4px !important;
}

.fi-topbar nav a:hover,
.fi-topbar nav button:hover {
    background: rgba(255, 255, 255, 0.15) !important;
    color: white !important;
    transform: translateY(-1px);
}

.fi-topbar nav a[aria-current="page"] {
    background: rgba(255, 255, 255, 0.2) !important;
    color: white !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

/* User Menu & Notifications */
.fi-topbar .fi-dropdown-trigger {
    color: white !important;
}

/* ───────── PAGE HEADER (Inside Body) ───────── */
.fi-header,
.fi-page-header {
    background: linear-gradient(135deg, var(--body-card), var(--body-card-light)) !important;
    border-left: 4px solid var(--accent-gold) !important;
    border-radius: 16px !important;
    padding: 28px 32px !important;
    margin-bottom: 2rem !important;
    box-shadow: var(--shadow-lg) !important;
    border: 1px solid var(--body-border) !important;
}

/* Page Title */
.fi-header-heading {
    font-size: 1.8rem !important;
    font-weight: 700 !important;
    background: linear-gradient(to right, #ffffff, var(--accent-gold));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-bottom: 0.5rem !important;
}

.fi-header-description {
    color: var(--body-text-muted) !important;
    font-size: 1rem !important;
}

/* ───────── PRIMARY BUTTON ───────── */
.fi-btn-color-primary {
    background: linear-gradient(
        135deg,
        var(--accent-gold),
        #b8860b
    ) !important;
    color: #111827 !important;
    font-weight: 600 !important;
    border-radius: 10px !important;
    border: none !important;
    box-shadow: 0 8px 20px rgba(212, 175, 55, 0.3) !important;
    padding: 10px 24px !important;
    transition: all 0.2s ease !important;
}

.fi-btn-color-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 25px rgba(212, 175, 55, 0.4) !important;
}

/* ───────── TABLE CONTAINER ───────── */
.fi-ta-ctn {
    background: linear-gradient(180deg, var(--body-card), var(--table-row-bg)) !important;
    border-radius: 16px !important;
    border: 1px solid var(--body-border) !important;
    box-shadow: var(--shadow-xl) !important;
    overflow: hidden !important;
    margin-top: 1rem !important;
}

/* ───────── TABLE HEADER - DISTINCT STYLE ───────── */
.fi-ta-table thead {
    background: linear-gradient(135deg, var(--table-header), #1a2332) !important;
    border-bottom: 3px solid var(--table-accent) !important;
}

.fi-ta-table th {
    color: var(--table-header-text) !important;
    font-size: 0.85rem !important;
    font-weight: 600 !important;
    letter-spacing: 0.05em !important;
    padding: 18px 20px !important;
    text-transform: uppercase !important;
    border-right: 1px solid var(--table-border) !important;
}

.fi-ta-table th:last-child {
    border-right: none !important;
}

/* ───────── TABLE BODY - HIGH CONTRAST ROWS ───────── */
.fi-ta-table tbody {
    background: var(--table-row-bg) !important;
}

.fi-ta-table tbody tr {
    background: var(--table-row-bg) !important;
    transition: all 0.2s ease !important;
    border-bottom: 1px solid var(--table-border) !important;
}

/* Alternating row colors for better readability */
.fi-ta-table tbody tr:nth-child(even) {
    background: var(--table-row-alt) !important;
}

/* Hover effect */
.fi-ta-table tbody tr:hover {
    background: var(--table-row-hover) !important;
    transform: translateX(2px);
}

/* Table cells */
.fi-ta-table td {
    color: var(--body-text) !important;
    font-size: 0.95rem !important;
    padding: 16px 20px !important;
    border-right: 1px solid var(--table-border) !important;
    vertical-align: middle !important;
}

.fi-ta-table td:last-child {
    border-right: none !important;
}

/* ───────── TABLE ACTIONS ───────── */
.fi-ta-table a {
    color: var(--accent-blue) !important;
    font-weight: 500 !important;
    transition: color 0.2s ease !important;
}

.fi-ta-table a:hover {
    color: #60a5fa !important;
    text-decoration: underline !important;
}

/* Action buttons in table */
.fi-ta-table .fi-btn {
    padding: 6px 12px !important;
    border-radius: 6px !important;
    font-size: 0.875rem !important;
}

/* ───────── PAGINATION ───────── */
.fi-pagination {
    background: var(--body-card) !important;
    border-top: 1px solid var(--body-border) !important;
    padding: 20px !important;
    border-radius: 0 0 16px 16px !important;
}

.fi-pagination .fi-btn {
    border: 1px solid var(--body-border) !important;
    color: var(--body-text) !important;
}

.fi-pagination .fi-btn[aria-current="page"] {
    background: var(--accent-gold) !important;
    color: #111827 !important;
    border-color: var(--accent-gold) !important;
}

/* ───────── FORMS & INPUTS ───────── */
.fi-input,
.fi-select,
.fi-textarea {
    background: var(--body-card-light) !important;
    border: 2px solid var(--body-border) !important;
    color: var(--body-text) !important;
    border-radius: 10px !important;
    padding: 12px 16px !important;
    font-size: 0.95rem !important;
    transition: all 0.2s ease !important;
}

.fi-input:focus,
.fi-select:focus,
.fi-textarea:focus {
    border-color: var(--accent-gold) !important;
    box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.25) !important;
    background: var(--body-card) !important;
}

/* Labels */
.fi-label {
    color: var(--body-text-muted) !important;
    font-weight: 500 !important;
    margin-bottom: 0.5rem !important;
}

/* ───────── CARDS & WIDGETS ───────── */
.fi-card {
    background: linear-gradient(135deg, var(--body-card), var(--body-card-light)) !important;
    border: 1px solid var(--body-border) !important;
    border-radius: 16px !important;
    box-shadow: var(--shadow-lg) !important;
    padding: 24px !important;
}

/* ───────── SIDEBAR (if visible) ───────── */
.fi-sidebar {
    background: linear-gradient(180deg, var(--header-secondary), var(--header-primary)) !important;
    border-right: 3px solid var(--header-border) !important;
}

.fi-sidebar-nav {
    background: transparent !important;
}

.fi-sidebar-item {
    color: rgba(255, 255, 255, 0.85) !important;
}

.fi-sidebar-item:hover {
    background: rgba(255, 255, 255, 0.1) !important;
    color: white !important;
}

/* ───────── UTILITY CLASSES ───────── */
.bg-header-gradient {
    background: linear-gradient(135deg, var(--header-primary), var(--header-secondary)) !important;
}

.bg-body-gradient {
    background: linear-gradient(135deg, var(--body-bg), var(--body-card)) !important;
}

.text-header {
    color: var(--header-text) !important;
}

.text-body {
    color: var(--body-text) !important;
}

.border-header {
    border-color: var(--header-border) !important;
}

.border-body {
    border-color: var(--body-border) !important;
}

/* ───────── RESPONSIVE ADJUSTMENTS ───────── */
@media (max-width: 768px) {
    .fi-topbar {
        height: 60px !important;
    }
    
    .fi-header,
    .fi-page-header {
        padding: 20px !important;
        border-radius: 12px !important;
    }
    
    .fi-ta-table th,
    .fi-ta-table td {
        padding: 12px 16px !important;
        font-size: 0.9rem !important;
    }
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
                \App\Filament\Pages\EndOfDayClosing::class,
                \App\Filament\Pages\PinCodeAuth::class
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
                \App\Http\Middleware\VerifyPinCode::class,
            ])

            /* ───────── NAV GROUPS ───────── */
            ->navigationGroups([
                NavigationGroup::make()->label('Sales'),
                NavigationGroup::make()->label('Customer'),
                NavigationGroup::make()->label('Reports'),
            ]);
    }
}