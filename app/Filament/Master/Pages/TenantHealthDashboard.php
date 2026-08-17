<?php

namespace App\Filament\Master\Pages;

use App\Models\Tenant;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Support\Facades\DB;

class TenantHealthDashboard extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon  = 'heroicon-o-heart';
    protected static ?string $navigationGroup = 'SaaS Management';
    protected static ?string $navigationLabel = 'Tenant Health';
    protected static ?int    $navigationSort  = 2;
    protected static ?string $title           = 'Tenant Health Dashboard';
    protected static string  $view            = 'filament.master.pages.tenant-health-dashboard';

    public string $search = '';
    public string $sortBy = 'mrr';
    public string $sortDir = 'desc';
    public string $filterStatus = 'all';

    public array $tenantStats = [];
    public array $summary = [];

    public function mount(): void
    {
        $this->loadStats();
    }

    public function updatedSearch(): void {}

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDir = 'desc';
        }
    }

    public function refreshStats(): void
    {
        $this->loadStats();
        \Filament\Notifications\Notification::make()
            ->title('Stats Refreshed')
            ->success()
            ->send();
    }

    protected function loadStats(): void
    {
        $tenants = Tenant::with('plan')->get();
        $stats   = [];
        $totalMrr = 0;
        $totalUsers = 0;

        foreach ($tenants as $tenant) {
            $plan = $tenant->plan;

            $userCount    = null;
            $laybuyCount  = null;
            $storageSize  = 0;
            $dbSizeMb     = 0;

            try {
                $tenant->run(function () use (&$userCount, &$laybuyCount) {
                    $userCount   = \App\Models\User::count();
                    $laybuyCount = \App\Models\Laybuy::count();
                });
            } catch (\Exception $e) {}

            try {
                $dbName = $tenant->tenancy_db_name;
                $result = DB::connection('mysql')->select("
                    SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size
                    FROM information_schema.tables
                    WHERE table_schema = ?
                ", [$dbName]);
                $dbSizeMb = $result[0]->size ?? 0;
            } catch (\Exception $e) {}

            try {
                $prefix = config('tenancy.filesystem.suffix_base', 'tenant') . $tenant->id;
                $paths = [
                    storage_path($prefix),
                    storage_path("app/public/{$prefix}"),
                    storage_path("app/{$prefix}"),
                ];
                foreach ($paths as $path) {
                    if (\Illuminate\Support\Facades\File::isDirectory($path)) {
                        foreach (\Illuminate\Support\Facades\File::allFiles($path) as $file) {
                            $storageSize += $file->getSize();
                        }
                    }
                }
            } catch (\Exception $e) {}

            $maxUsers   = $plan->max_users   ?? 0;
            $maxLaybuys = $plan->max_laybuys ?? 0;
            $mrr        = $plan ? floatval($plan->price_monthly) : 0;

            $daysSinceLogin = $tenant->last_login_at
                ? now()->diffInDays($tenant->last_login_at)
                : null;

            $userPct   = $maxUsers > 0 ? min(100, round(($userCount ?? 0) / $maxUsers * 100)) : ($maxUsers === -1 ? 0 : 100);
            $laybuyPct = $maxLaybuys > 0 ? min(100, round(($laybuyCount ?? 0) / $maxLaybuys * 100)) : ($maxLaybuys === -1 ? 0 : 100);

            $totalMrr   += ($tenant->plan_status === 'active' ? $mrr : 0);
            $totalUsers += ($userCount ?? 0);

            $stats[] = [
                'id'              => $tenant->id,
                'domain'          => $tenant->domains->first()?->domain ?? '—',
                'plan_name'       => $plan?->name ?? 'No Plan',
                'plan_slug'       => $plan?->slug ?? 'none',
                'plan_status'     => $tenant->plan_status,
                'user_count'      => $userCount ?? 0,
                'max_users'       => $maxUsers,
                'user_pct'        => $userPct,
                'laybuy_count'    => $laybuyCount ?? 0,
                'max_laybuys'     => $maxLaybuys,
                'laybuy_pct'      => $laybuyPct,
                'db_size_mb'      => $dbSizeMb,
                'storage_mb'      => round($storageSize / 1024 / 1024, 2),
                'mrr'             => $mrr,
                'last_login_at'   => $tenant->last_login_at,
                'days_since_login'=> $daysSinceLogin,
                'created_at'      => $tenant->created_at,
            ];
        }

        $this->tenantStats = $stats;
        $this->summary = [
            'total_tenants'   => count($stats),
            'active_tenants'  => collect($stats)->where('plan_status', 'active')->count(),
            'trial_tenants'   => collect($stats)->where('plan_status', 'trial')->count(),
            'suspended'       => collect($stats)->where('plan_status', 'suspended')->count(),
            'total_mrr'       => $totalMrr,
            'total_users'     => $totalUsers,
            'at_risk'         => collect($stats)->filter(fn($t) => $t['days_since_login'] !== null && $t['days_since_login'] > 14)->count(),
        ];
    }

    public function getFilteredStats(): array
    {
        $stats = $this->tenantStats;

        if ($this->search) {
            $search = strtolower($this->search);
            $stats = array_filter($stats, fn($t) =>
                str_contains(strtolower($t['id']), $search) ||
                str_contains(strtolower($t['domain']), $search)
            );
        }

        if ($this->filterStatus !== 'all') {
            $stats = array_filter($stats, fn($t) => $t['plan_status'] === $this->filterStatus);
        }

        $stats = collect($stats)->sortBy($this->sortBy, SORT_REGULAR, $this->sortDir === 'desc')->values()->toArray();

        return $stats;
    }
}
