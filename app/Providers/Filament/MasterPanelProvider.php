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
use Illuminate\Support\HtmlString;
use Jeffgreco13\FilamentBreezy\BreezyCore;
class MasterPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('master')
            ->path('master')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            /* |--------------------------------------------------------------------------
            | UI ENHANCEMENTS (REFIXED VISIBILITY)
            |--------------------------------------------------------------------------
            */
            ->renderHook(
                'panels::head.done',
                fn () => new HtmlString("
                    <style>
                        /* Set luxury variables */
                        :root {
                            --gold-primary: #d97706;
                            --gold-dark: #b45309;
                            --gold-soft: #fffbeb;
                            --text-main: #1e293b;
                        }

                        /* 1. Fix the body background - remove heavy patterns */
                        body, .fi-body { 
                            background-color: #f8fafc !important; 
                            font-family: 'Inter', sans-serif !important;
                        }

                        /* 2. Topbar - Solid and clean */
                        .fi-topbar { 
                            background: white !important;
                            border-bottom: 2px solid var(--gold-primary) !important;
                        }

                        /* 3. Sidebar active states */
                        .fi-sidebar-item-active {
                            background-color: var(--gold-soft) !important;
                            border-left: 4px solid var(--gold-primary) !important;
                        }
                        
                        .fi-sidebar-item-active * {
                            color: var(--gold-dark) !important;
                            font-weight: 700 !important;
                        }

                        /* 4. DATA TABLES & CARDS - Essential for visibility */
                        .fi-ta-ctn, .fi-card, .fi-section {
                            background: white !important;
                            border: 1px solid #e2e8f0 !important;
                            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
                            border-radius: 12px !important;
                        }

                        /* Fix Table Text */
                        .fi-ta-header-cell-label, .fi-ta-cell {
                            color: var(--text-main) !important;
                        }

                        /* 5. Gold Buttons */
                        .fi-btn-primary { 
                            background: linear-gradient(135deg, var(--gold-primary), var(--gold-dark)) !important;
                            color: white !important;
                            transition: all 0.2s ease;
                        }

                        .fi-btn-primary:hover {
                            transform: translateY(-1px);
                            box-shadow: 0 4px 12px rgba(217, 119, 6, 0.4);
                        }

                        /* 6. Fix for inputs */
                        .fi-input-wrp {
                            background: white !important;
                            border-color: #cbd5e1 !important;
                        }
                    </style>
                ")
            )
            ->discoverResources(in: app_path('Filament/Master/Resources'), for: 'App\\Filament\\Master\\Resources')
            ->discoverPages(in: app_path('Filament/Master/Pages'), for: 'App\\Filament\\Master\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->widgets([
    \App\Filament\Master\Widgets\SubscriptionOverview::class,
    \App\Filament\Master\Widgets\PlatformHealthWidget::class,
])
            ->discoverWidgets(in: app_path('Filament/Master/Widgets'), for: 'App\\Filament\\Master\\Widgets')
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
           ->plugins([
            BreezyCore::make()
                ->myProfile(shouldRegisterUserMenu: true)
                ->enableTwoFactorAuthentication(force: true), 
        ]);
    }
}