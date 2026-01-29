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
use Filament\Support\Assets\Js; // Added for Zebra Assets

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
            |--------------------------------------------------------------------------
            */
            ->assets([
                // Load the Zebra Library from public/js/zebra-lib.js
                Js::make('zebra-library', asset('js/zebra-lib.js')),
                // Load your compiled app.js logic
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
/* ───────── COMPREHENSIVE COLOR SYSTEM ───────── */
:root {
    /* Primary Colors */
    --primary-color: #0d9488;
    --primary-light: #2dd4bf;
    --primary-dark: #0f766e;
    
    /* Background Colors */
    --body-bg: #0e7490;
    --nav-bg: linear-gradient(135deg, #e0f2fe 0%, #ccfbf1 100%);
    --content-bg: #ffffff;
    --card-bg: #ffffff;
    --sidebar-bg: #f8fafc;
    
    /* Text Colors - HIGH CONTRAST */
    --text-primary: #164e63;      /* Dark Teal for Headings */
    --text-secondary: #0e7490;    /* Medium Teal */
    --text-body: #1e293b;         /* Dark Gray for Body Text - HIGH VISIBILITY */
    --text-muted: #64748b;        /* Gray for Labels */
    --text-white: #ffffff;        /* White text for dark backgrounds */
    --text-button: #ffffff;       /* White text for buttons */
    
    /* UI Elements */
    --border-color: #cbd5e1;
    --border-light: #e2e8f0;
    --border-primary: #2dd4bf;
    --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    --shadow-lg: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
}

/* ───────── LOAD INTER FONT ───────── */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

body, .fi-body, .fi-btn, .fi-input, .fi-label, .fi-heading {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
}

/* ───────── GLOBAL TEXT FIXES ───────── */
body, .fi-layout {
    background-color: var(--body-bg) !important;
    color: var(--text-body) !important;
}



/* ───────── MAIN CONTENT AREA ───────── */
.fi-main {
    background-color: var(--body-bg) !important;
    padding: 1.5rem !important;
    min-height: calc(100vh - 75px) !important;
}

.fi-main > div {
    max-width: 100% !important;
}

/* 1. LOGIN / REGISTER REFINEMENT (FIXES THE FADED LOOK) */
.fi-simple-main-ctn {
    background-color: var(--body-bg) !important;
}

.fi-simple-main {
    background-color: #ffffff !important;
    border-radius: 24px !important;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5) !important;
    padding: 2.5rem !important;
}

/* Fix Input Text & Labels in Auth Pages */
.fi-simple-main input {
    background-color: #f8fafc !important;
    color: #111827 !important;
    border: 2px solid #cbd5e1 !important;
    font-weight: 600 !important;
    opacity: 1 !important;
}

.fi-simple-main label {
    color: #1e293b !important;
    font-weight: 700 !important;
    opacity: 1 !important;
}

/* 2. TOP NAVIGATION (CLEAN GLASS LOOK) */
.fi-topbar {
    background: linear-gradient(135deg, #e0f2fe 0%, #ccfbf1 100%) !important;
    border-bottom: 2px solid #5eead4 !important;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
}

.fi-topbar * {
    color: #0f172a !important; /* Force dark text on light nav */
    opacity: 1 !important;
}

/* 3. BREADCRUMBS (STOP FADING) */
.fi-breadcrumbs-item-label {
    color: #ffffff !important; 
    font-weight: 700 !important;
    opacity: 1 !important;
}

.fi-breadcrumbs-item-link {
    color: rgba(255, 255, 255, 0.8) !important;
    opacity: 1 !important;
}

/* 4. DASHBOARD WIDGETS TEXT FIX */
.fi-wi-stats-overview-stat-value {
    color: #ffffff !important;
    font-weight: 800 !important;
    text-shadow: 0 1px 2px rgba(0,0,0,0.3) !important;
}

.fi-wi-stats-overview-stat-label, 
.fi-wi-stats-overview-stat-description {
    color: #f0f9ff !important;
    font-weight: 600 !important;
    opacity: 1 !important;
}

/* 5. TABLE & CARD CONTRAST */
.fi-section, .fi-ta-ctn {
    background-color: #ffffff !important;
    border: none !important;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.2) !important;
    opacity: 1 !important;
}

.fi-section-header-heading, .fi-ta-header-cell-label {
    color: #0f172a !important;
    font-weight: 800 !important;
}

/* 6. BUTTON VISIBILITY */
.fi-btn-primary {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
    color: #ffffff !important;
    font-weight: 800 !important;
    opacity: 1 !important;
}

/* Fix for standard inputs being invisible */
.fi-input-wrp {
    background-color: white !important;
    opacity: 1 !important;
}

/* PEARL BLUE TABLE */
.fi-ta-ctn,
.fi-ta-header,
.fi-ta-header-cell,
.fi-ta-row,
.fi-ta-empty-state,
.fi-ta-footer {
    background-color: #eef2f7 !important;
}

.fi-ta-row:hover {
    background-color: #e0e7ef !important;
}

.fi-ta-cell {
    color: #000000 !important;
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