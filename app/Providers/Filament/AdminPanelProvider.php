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
            ->favicon(asset('jeweltaglogo.png'))

            /* ───────── LIGHT MODE ONLY ───────── */
            ->darkMode(false, false)

            /* ───────── IMPROVED BRAND COLORS ───────── */
            ->colors([
                'primary' => Color::hex('#0d9488'), // Darker Teal for better contrast
                'gray' => Color::hex('#4b5563'),    // Better contrast gray
                'info' => Color::hex('#0284c7'),
                'success' => Color::hex('#059669'),
                'warning' => Color::hex('#d97706'),
                'danger' => Color::hex('#dc2626'),
            ])
            ->font('Inter')
            
            ->maxContentWidth(MaxWidth::Full)

            /* ───────── COMPLETE UI OVERHAUL FOR TEXT VISIBILITY ───────── */
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn() => <<<'HTML'
<script>
    document.documentElement.classList.add('light');
    document.documentElement.classList.remove('dark');
    localStorage.setItem('theme', 'light');
</script>

<style>
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

/* Ensure ALL text has proper color */
* {
    color: inherit;
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

/* ───────── TOPBAR WITH VISIBLE TEXT ───────── */
.fi-topbar {
    background: var(--nav-bg) !important;
    border-bottom: 2px solid var(--border-primary) !important;
    box-shadow: var(--shadow) !important;
    height: 75px !important;
}

/* Navigation Items - FIXED TEXT VISIBILITY */
.fi-topbar-item-button {
    color: var(--text-secondary) !important;
    font-weight: 600 !important;
    font-size: 0.9375rem !important;
}

.fi-topbar-item-button:hover {
    background-color: rgba(14, 116, 144, 0.1) !important;
    color: var(--text-primary) !important;
}

.fi-topbar-item-active .fi-topbar-item-button {
    background-color: var(--content-bg) !important;
    color: var(--text-primary) !important;
    font-weight: 700 !important;
    border: 2px solid var(--border-primary) !important;
    border-radius: 10px !important;
    box-shadow: 0 2px 8px rgba(13, 148, 136, 0.2) !important;
}

/* Logo and Brand */
.fi-logo {
    filter: drop-shadow(0 2px 4px rgba(14, 116, 144, 0.2)) !important;
}

/* ───────── PAGE HEADINGS - WHITE ON DARK BG ───────── */
.fi-header-heading,
.fi-page-header-heading {
    color: var(--text-white) !important;
    font-weight: 800 !important;
    font-size: 2rem !important;
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
    margin-bottom: 1.5rem !important;
}

.fi-header-description,
.fi-page-header-subheading {
    color: rgba(255, 255, 255, 0.9) !important;
    font-weight: 500 !important;
    font-size: 1rem !important;
}

/* ───────── BUTTONS - FIXED TEXT VISIBILITY ───────── */
.fi-btn {
    font-weight: 600 !important;
    border-radius: 10px !important;
    transition: all 0.2s ease !important;
    font-family: 'Inter', sans-serif !important;
}

/* PRIMARY BUTTONS - White text on colored background */
.fi-btn-primary,
.fi-ac-action-primary {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%) !important;
    border: none !important;
    color: var(--text-button) !important; /* WHITE TEXT */
    font-weight: 700 !important;
    padding: 0.625rem 1.5rem !important;
}

.fi-btn-primary:hover,
.fi-ac-action-primary:hover {
    transform: translateY(-2px) !important;
    box-shadow: 0 4px 12px rgba(13, 148, 136, 0.3) !important;
    color: var(--text-button) !important; /* Keep white on hover */
}

/* SECONDARY BUTTONS - Dark text on light background */
.fi-btn-secondary,
.fi-btn-outline,
.fi-ac-action-secondary {
    background-color: white !important;
    border: 2px solid var(--border-color) !important;
    color: var(--text-primary) !important; /* DARK TEXT */
    font-weight: 600 !important;
}

.fi-btn-secondary:hover,
.fi-btn-outline:hover,
.fi-ac-action-secondary:hover {
    background-color: #f8fafc !important;
    border-color: var(--primary-light) !important;
    color: var(--text-primary) !important;
}

/* DANGER BUTTONS */
.fi-btn-danger {
    background-color: #dc2626 !important;
    color: white !important;
    border: none !important;
}

.fi-btn-danger:hover {
    background-color: #b91c1c !important;
    color: white !important;
}

/* ACTION BUTTONS IN TABLES */
.fi-ta-action {
    color: var(--primary-color) !important;
    font-weight: 500 !important;
}

.fi-ta-action:hover {
    color: var(--primary-dark) !important;
}

/* ───────── CARDS & TABLES - HIGH VISIBILITY TEXT ───────── */
.fi-section,
.fi-ta-ctn,
.fi-card,
.fi-form,
.fi-fo-component-ctn {
    background-color: var(--content-bg) !important;
    border: 1px solid var(--border-light) !important;
    border-radius: 16px !important;
    box-shadow: var(--shadow-lg) !important;
    overflow: hidden !important;
    margin-bottom: 1.5rem !important;
}

/* Card Headings */
.fi-section-header,
.fi-card-header,
.fi-form-header {
    background-color: #f8fafc !important;
    border-bottom: 1px solid var(--border-light) !important;
    padding: 1.25rem 1.5rem !important;
}

.fi-section-header-heading,
.fi-card-header-heading,
.fi-form-header-heading {
    color: var(--text-primary) !important;
    font-weight: 700 !important;
    font-size: 1.25rem !important;
}

/* Table Headers - HIGH VISIBILITY */
.fi-ta-header {
    background-color: #f1f5f9 !important;
}

.fi-ta-header-cell {
    color: var(--text-primary) !important;
    font-weight: 700 !important;
    font-size: 0.8125rem !important;
    text-transform: uppercase !important;
    letter-spacing: 0.05em !important;
    padding: 1rem !important;
    border-bottom: 2px solid var(--border-color) !important;
}

/* Table Cells - HIGH VISIBILITY */
.fi-ta-cell {
    color: var(--text-body) !important;
    font-weight: 500 !important;
    font-size: 0.9375rem !important;
    padding: 1rem !important;
    border-bottom: 1px solid var(--border-light) !important;
}

.fi-ta-row:hover {
    background-color: #f0fdf4 !important;
}

/* ───────── FORM ELEMENTS - VISIBLE TEXT ───────── */
.fi-input,
.fi-select,
.fi-textarea {
    background-color: white !important;
    border: 2px solid var(--border-color) !important;
    border-radius: 10px !important;
    color: var(--text-body) !important;
    font-weight: 500 !important;
    font-size: 0.9375rem !important;
}

.fi-input:focus,
.fi-select:focus,
.fi-textarea:focus {
    border-color: var(--primary-light) !important;
    box-shadow: 0 0 0 3px rgba(45, 212, 191, 0.1) !important;
    color: var(--text-body) !important;
}

.fi-input::placeholder {
    color: var(--text-muted) !important;
    font-weight: 400 !important;
}

/* Labels - HIGH VISIBILITY */
.fi-label {
    color: var(--text-primary) !important;
    font-weight: 600 !important;
    font-size: 0.875rem !important;
    margin-bottom: 0.5rem !important;
}

.fi-hint {
    color: var(--text-muted) !important;
    font-weight: 400 !important;
    font-size: 0.8125rem !important;
}

/* ───────── DASHBOARD WIDGETS ───────── */
.fi-wi-stats-overview-stat {
    background-color: var(--card-bg) !important;
    border: 1px solid var(--border-light) !important;
    border-radius: 12px !important;
    box-shadow: var(--shadow) !important;
}

.fi-wi-stats-overview-stat-value {
    color: var(--text-primary) !important;
    font-weight: 800 !important;
    font-size: 2rem !important;
}

.fi-wi-stats-overview-stat-label {
    color: var(--text-secondary) !important;
    font-weight: 600 !important;
    font-size: 0.875rem !important;
}

.fi-wi-header-heading {
    color: var(--text-primary) !important;
    font-weight: 700 !important;
    font-size: 1.125rem !important;
}

/* ───────── BADGES & TAGS ───────── */
.fi-badge {
    font-weight: 600 !important;
    font-size: 0.75rem !important;
    border-radius: 9999px !important;
    padding: 0.25rem 0.75rem !important;
}

.fi-badge-primary {
    background-color: rgba(13, 148, 136, 0.1) !important;
    color: var(--primary-dark) !important;
    border: 1px solid rgba(13, 148, 136, 0.2) !important;
}

/* ───────── PAGINATION ───────── */
.fi-pagination-item {
    color: var(--text-secondary) !important;
    font-weight: 600 !important;
    border: 1px solid var(--border-color) !important;
}

.fi-pagination-item-active {
    background-color: var(--primary-color) !important;
    color: white !important;
    border-color: var(--primary-color) !important;
}

/* ───────── SEARCH & FILTERS ───────── */
.fi-global-search-field input {
    color: var(--text-body) !important;
    font-weight: 500 !important;
}

.fi-global-search-field input::placeholder {
    color: var(--text-muted) !important;
}

/* ───────── USER MENU ───────── */
.fi-user-menu button {
    color: var(--text-secondary) !important;
    font-weight: 600 !important;
}

/* ───────── SPECIFIC PAGE FIXES ───────── */
/* Label Designer Page */
.fi-page-label-designer {
    background-color: white !important;
    border-radius: 16px !important;
    padding: 1.5rem !important;
}

/* Properties Inspector Text */
.fi-page-label-designer .fi-section:last-child {
    background-color: #f8fafc !important;
}

.fi-page-label-designer .fi-label {
    color: var(--text-primary) !important;
    font-weight: 600 !important;
}

/* ───────── RESPONSIVE FIXES ───────── */
@media (max-width: 768px) {
    .fi-main {
        padding: 1rem !important;
    }
    
    .fi-header-heading {
        font-size: 1.5rem !important;
    }
    
    .fi-topbar-item-button {
        font-size: 0.875rem !important;
        padding: 0.375rem 0.75rem !important;
    }
    
    .fi-btn {
        padding: 0.5rem 1rem !important;
        font-size: 0.875rem !important;
    }
}

/* ───────── SCROLLBAR STYLING ───────── */
::-webkit-scrollbar {
    width: 10px;
    height: 10px;
}

::-webkit-scrollbar-track {
    background: #f1f5f9;
}

::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 5px;
}

::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* ───────── UTILITY CLASSES FOR TEXT VISIBILITY ───────── */
.text-primary { color: var(--text-primary) !important; }
.text-secondary { color: var(--text-secondary) !important; }
.text-body { color: var(--text-body) !important; }
.text-white { color: var(--text-white) !important; }
.text-muted { color: var(--text-muted) !important; }

.bg-white { background-color: white !important; }
.bg-card { background-color: var(--card-bg) !important; }

/* Force white text on any element that might be invisible */
.fi-btn[class*="primary"],
.fi-badge[class*="primary"],
.fi-ac-action[class*="primary"] {
    color: white !important;
}

/* Fix for any remaining invisible text */
.fi-text-gray-600,
.fi-text-gray-700,
.fi-text-gray-800 {
    color: var(--text-body) !important;
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
                \App\Filament\Resources\TicketResource::class,
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
                \App\Http\Middleware\EnsureUserIsAdmin::class, 
                \App\Http\Middleware\VerifyPinCode::class,
            ])
            ->navigationGroups([
                NavigationGroup::make()->label('Sales'),
                NavigationGroup::make()->label('Customer'),
                NavigationGroup::make()->label('Reports'),
            ]);
    }
}