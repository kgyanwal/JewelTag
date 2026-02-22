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
        // 🚀 AUTOMATIC FOLDER CREATION
        // This prevents the "Failed to open stream" error for every new store.
        Event::listen(TenantCreated::class, function (TenantCreated $event) {
            $storagePath = storage_path("tenant{$event->tenant->id}");
            
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
        });

        // 🚨 LIVEWIRE MULTI-TENANCY FIX
        Livewire::setUpdateRoute(function ($handle) {
            $route = Route::post('/livewire/update', $handle)->middleware('web');
            $isCentralDomain = in_array(request()->getHost(), config('tenancy.central_domains', []));
            
            if (! $isCentralDomain) {
                $route->middleware(InitializeTenancyByDomain::class);
            }
            return $route;
        });

        // Widgets
        Livewire::component('app.filament.widgets.stats-overview', StatsOverview::class);
        Livewire::component('app.filament.widgets.latest-sales', LatestSales::class);
        Livewire::component('app.filament.widgets.fastest-selling-items', FastestSellingItems::class);
        Livewire::component('app.filament.widgets.department-chart', DepartmentChart::class);
        Livewire::component('app.filament.widgets.dashboard-quick-menu', \App\Filament\Widgets\DashboardQuickMenu::class);
        
        // Policies
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Sale::class, SalePolicy::class);
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(ProductItem::class, ProductItemPolicy::class);
        
        // 🔹 GLOBAL SUPERADMIN GATE
        Gate::before(function ($user, $ability) {
            $isCentralDomain = in_array(request()->getHost(), config('tenancy.central_domains', []));

            // On Master Panel: Access is granted for everyone in central DB for now 
            // (You can change this to $user->is_landlord later)
            if ($isCentralDomain) {
                return true; 
            }

            // Inside a Store: Only grant if Superadmin role exists
            if (function_exists('tenancy') && tenancy()->initialized && method_exists($user, 'hasRole')) {
                return $user->hasRole('Superadmin') ? true : null;
            }

            return null;
        });
    }
}