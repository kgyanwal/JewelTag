<?php

namespace App\Providers;

use App\Filament\Widgets\DepartmentChart;
use App\Filament\Widgets\FastestSellingItems;
use App\Filament\Widgets\LatestSales;
use App\Filament\Widgets\StatsOverview;
use Illuminate\Support\ServiceProvider;
use App\Models\Sale;
use App\Models\User;
use App\Models\Customer;
use App\Models\ProductItem;
use App\Policies\SalePolicy;
use App\Policies\UserPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\ProductItemPolicy;
use Illuminate\Support\Facades\Gate;

// 🚨 ADD THESE THREE IMPORTS
use Livewire\Livewire;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 🚨 THE FIX: Force Livewire to use the Tenancy database when updating forms
        Livewire::setUpdateRoute(function ($handle) {
            $route = Route::post('/livewire/update', $handle)->middleware('web');
            
            // Only apply Tenancy if we are on a Store's URL (Not the Master Landlord URL)
            $isCentralDomain = in_array(request()->getHost(), config('tenancy.central_domains', []));
            
            if (! $isCentralDomain) {
                $route->middleware(InitializeTenancyByDomain::class);
            }
            
            return $route;
        });

        Livewire::component('app.filament.widgets.stats-overview', StatsOverview::class);
        Livewire::component('app.filament.widgets.latest-sales', LatestSales::class);
        Livewire::component('app.filament.widgets.fastest-selling-items', FastestSellingItems::class);
        Livewire::component('app.filament.widgets.department-chart', DepartmentChart::class);
        Livewire::component('app.filament.widgets.dashboard-quick-menu', \App\Filament\Widgets\DashboardQuickMenu::class);
        
        // Register your policies here
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Sale::class, SalePolicy::class);
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(ProductItem::class, ProductItemPolicy::class);
        
        // This part is the "Superpower" for Superadmins
        // It tells Laravel: "If the user is a Superadmin, ignore all policies and let them in"
        Gate::before(function ($user, $ability) {
            // Determine if we are on the Central (Master) domain
            $isCentralDomain = in_array(request()->getHost(), config('tenancy.central_domains', []));

            // 🚨 SECURITY FIX: On Central, check if user is a landlord
            if ($isCentralDomain) {
                // If you have a column 'is_landlord', use it. Otherwise, return true for your admin emails.
                return $user->is_landlord ? true : null; 
            }

            // 🚀 TENANCY FIX: Check roles ONLY if tenancy is initialized.
            // This prevents the "Role Not Found" crash during the boot process.
            if (function_exists('tenancy') && tenancy()->initialized && method_exists($user, 'hasRole')) {
                return $user->hasRole('Superadmin') ? true : null;
            }

            return null;
        });
    }
}