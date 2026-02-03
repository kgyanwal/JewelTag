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
use Livewire\Livewire;
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
    Livewire::component('app.filament.widgets.stats-overview', StatsOverview::class);
    Livewire::component('app.filament.widgets.latest-sales', LatestSales::class);
    Livewire::component('app.filament.widgets.fastest-selling-items', FastestSellingItems::class);
        Livewire::component('app.filament.widgets.department-chart', DepartmentChart::class);
        \Livewire\Livewire::component('app.filament.widgets.dashboard-quick-menu', \App\Filament\Widgets\DashboardQuickMenu::class);
    // Register your policies here
    Gate::policy(User::class, UserPolicy::class);
    Gate::policy(Sale::class, SalePolicy::class);
    Gate::policy(Customer::class, CustomerPolicy::class);
    Gate::policy(ProductItem::class, ProductItemPolicy::class);
    
    // This part is the "Superpower" for Superadmins
    // It tells Laravel: "If the user is a Superadmin, ignore all policies and let them in"
    Gate::before(function ($user, $ability) {
        return $user->hasRole('Superadmin') ? true : null;
    });
}
}
