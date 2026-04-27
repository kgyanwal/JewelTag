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
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Stancl\Tenancy\Events\TenantCreated;
use Stancl\Tenancy\Events\TenancyInitialized;
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
        /**
         * ---------------------------------------------------------
         * 1. CENTRAL DOMAIN PROTECTION & STATE MANAGEMENT
         * ---------------------------------------------------------
         */
        $currentHost = request()->getHost();
        $centralDomains = config('tenancy.central_domains', []);
        $isCentralDomain = in_array($currentHost, $centralDomains);

        if ($isCentralDomain) {
            // Prevent the identification middleware from throwing 500 errors on landlord domains
            config(['tenancy.identification_exception_handler' => null]);
            
            // Ensure no tenant database connection is active on central domains
            if (function_exists('tenancy') && tenancy()->initialized) {
                tenancy()->end();
            }
        }

        /**
         * ---------------------------------------------------------
         * 2. STORAGE AUTOMATION (Self-Healing Storage)
         * ---------------------------------------------------------
         */
        $createTenantFolders = function ($tenantId) {
            $storagePath = storage_path("tenant{$tenantId}");
            $folders = [
                "$storagePath/framework/cache",
                "$storagePath/framework/views",
                "$storagePath/framework/sessions",
                "$storagePath/app/public",
            ];
            foreach ($folders as $folder) {
                if (!File::exists($folder)) {
                    File::makeDirectory($folder, 0775, true, true);
                }
            }
        };

        // Create folders when tenancy initializes or when a new store is created
       Event::listen(TenancyInitialized::class, function (TenancyInitialized $event) use ($createTenantFolders) {
    $createTenantFolders($event->tenancy->tenant->id);
    try {
        $tz = \App\Models\Store::first()?->timezone;
        if ($tz) {
            config(['app.timezone' => $tz]);
            date_default_timezone_set($tz);
        }
    } catch (\Exception $e) {}
});
        Event::listen(TenantCreated::class, fn (TenantCreated $event) => $createTenantFolders($event->tenant->id));

        /**
         * ---------------------------------------------------------
         * 3. LIVEWIRE + TENANCY ROUTE FIX
         * ---------------------------------------------------------
         */
        Livewire::setUpdateRoute(function ($handle) use ($isCentralDomain) {
            $route = Route::post('/livewire/update', $handle)->middleware(['web']);
            
            if (!$isCentralDomain) {
                // Apply tenancy identification ONLY for store domains
                $route->middleware(InitializeTenancyByDomain::class);
            } else {
                // Ensure landlord domains skip tenant identification
                $route->withoutMiddleware([InitializeTenancyByDomain::class]);
            }
            
            return $route;
        });

        /**
         * ---------------------------------------------------------
         * 4. FILAMENT WIDGETS & POLICIES
         * ---------------------------------------------------------
         */
        Livewire::component('app.filament.widgets.stats-overview', StatsOverview::class);
        Livewire::component('app.filament.widgets.latest-sales', LatestSales::class);
        Livewire::component('app.filament.widgets.fastest-selling-items', FastestSellingItems::class);
        Livewire::component('app.filament.widgets.department-chart', DepartmentChart::class);
        Livewire::component('app.filament.widgets.dashboard-quick-menu', \App\Filament\Widgets\DashboardQuickMenu::class);
        
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Sale::class, SalePolicy::class);
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(ProductItem::class, ProductItemPolicy::class);

        /**
         * ---------------------------------------------------------
         * 5. GLOBAL SECURITY GATE (ACL)
         * ---------------------------------------------------------
         */
        Gate::before(function ($user, $ability) use ($isCentralDomain) {
            // Master/Landlord panel access
            if ($isCentralDomain) {
                return (bool) $user->is_landlord; 
            }

            // Tenant Store access (check for Superadmin role within the tenant DB)
            if (function_exists('tenancy') && tenancy()->initialized && method_exists($user, 'hasRole')) {
                return $user->hasRole('Superadmin') ? true : null;
            }

            return null;
        });
    }
}