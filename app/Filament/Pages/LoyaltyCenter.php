<?php

namespace App\Filament\Pages;

use App\Models\Customer;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use App\Services\StoreFeatures;
use Illuminate\Support\Facades\DB;

/**
 * Loyalty & Store Credit - UI only. Uses ONLY existing customers columns:
 * loyalty_tier, loyalty_points, credit_balance. No new tables, no migrations.
 * 12-month spend is read live from the sales table.
 */
class LoyaltyCenter extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-gift';
    protected static ?string $navigationGroup = 'Customer';
    protected static ?string $navigationLabel = 'Loyalty & Store Credit';
    protected static ?string $title           = 'Loyalty & Store Credit';
    protected static ?int    $navigationSort  = 5;
    protected static string  $view            = 'filament.pages.loyalty-center';

    // ── SETTINGS (edit here) ──────────────────────────────────────────────
    public const POINTS_PER_DOLLAR = 100;           // 100 points = $1 store credit
    public const TIERS = [                          // slug => [label, min 12-month spend]
        'standard' => ['Standard', 0],
        'silver'   => ['Silver',   2500],
        'gold'     => ['Gold',     10000],
    ];
    private const EXCLUDED_STATUS = ['void', 'cancelled', 'refunded'];

    // ── UI STATE ──────────────────────────────────────────────────────────
    public string $tab        = 'overview';   // overview | members | credit
    #[\Livewire\Attributes\Url(as: 'q')]
    public string $search     = '';
    public string $tierFilter = '';
    public int    $limit      = 25;

    public ?int   $selectedId   = null;
    public string $pointsDelta  = '';
    public string $pointsReason = '';
    public string $creditDelta  = '';
    public string $creditReason = '';
    public string $redeemPoints = '';
    public string $tierChoice   = '';

    public static function shouldRegisterNavigation(): bool
    {
        return StoreFeatures::loyalty() || StoreFeatures::storeCredit();
    }

    public static function canAccess(): bool
    {
        return StoreFeatures::loyalty() || StoreFeatures::storeCredit();
    }

    public function loyaltyOn(): bool { return StoreFeatures::loyalty(); }
    public function creditOn(): bool  { return StoreFeatures::storeCredit(); }

    public function mount(): void
    {
        if (! $this->loyaltyOn() && $this->creditOn()) {
            $this->tab = 'credit';
        }
    }

    public function getHeading(): string { return ''; }

    public function canManage(): bool
    {
        return \App\Helpers\Staff::user()?->hasAnyRole(['Superadmin', 'Administration']) ?? false;
    }

    // ── HELPERS ───────────────────────────────────────────────────────────
    public static function fullName($c): string
    {
        return trim(($c->name ?? '') . ' ' . ($c->last_name ?? '')) ?: 'Customer #' . $c->id;
    }

    public static function tierForSpend(float $spend): string
    {
        $slug = 'standard';
        foreach (self::TIERS as $s => [, $min]) {
            if ($spend >= $min) $slug = $s;
        }
        return $slug;
    }

    /** [nextLabel|null, percent, remaining$] */
    public static function progress(float $spend): array
    {
        $prevMin = 0; 
        foreach (self::TIERS as [$label, $min]) {
            if ($min > $spend) {
                $pct = (int) round((($spend - $prevMin) / max(1, $min - $prevMin)) * 100);
                return [$label, max(0, min(100, $pct)), $min - $spend];
            }
            $prevMin = $min;
        }
        return [null, 100, 0];
    }

    /** Pre-tax spend for one customer. $since = null means lifetime. */
    public static function spendSince(int $customerId, $since = null): float
    {
        $q = DB::table('sales')->where('customer_id', $customerId)
            ->whereNotIn('status', self::EXCLUDED_STATUS);
        if ($since) {
            $q->where('effective_sale_date', '>=', $since);
        }
        return (float) $q->selectRaw('COALESCE(SUM(final_total - COALESCE(tax_amount,0)),0) AS s')->value('s');
    }

    private function log(string $msg, Customer $c): void
    {
        try {
            if (function_exists('activity')) {
                activity()->performedOn($c)->causedBy(auth()->user())->log($msg);
            }
        } catch (\Throwable $e) { /* logging must never block the action */ }
    }

    private function spendSub()
    {
        return DB::table('sales')
            ->selectRaw('customer_id, SUM(final_total - COALESCE(tax_amount,0)) AS spend')
            ->whereNotNull('customer_id')
            ->whereNotIn('status', self::EXCLUDED_STATUS)
            ->where('effective_sale_date', '>=', now()->subMonths(12))
            ->groupBy('customer_id');
    }

    private function base()
    {
        $q = Customer::query()
            ->leftJoinSub($this->spendSub(), 'sp', 'sp.customer_id', '=', 'customers.id')
            ->select('customers.*', DB::raw('COALESCE(sp.spend,0) AS spend12'));

        if (trim($this->search) !== '') {
            $s = '%' . trim($this->search) . '%';
            $q->where(fn($w) => $w->where('customers.name', 'like', $s)->orWhere('customers.last_name', 'like', $s)
                ->orWhere('customers.phone', 'like', $s)->orWhere('customers.email', 'like', $s)
                ->orWhere('customers.loyalty_card_number', 'like', $s));
        }
        return $q;
    }

    // ── DATA FOR THE VIEW ─────────────────────────────────────────────────
    public function kpis(): array
    {
        $points = (int) DB::table('customers')->sum('loyalty_points');
        return [
            'points'       => $points,
            'liability'    => round($points / self::POINTS_PER_DOLLAR, 2),
            'credit'       => (float) DB::table('customers')->sum('credit_balance'),
            'credit_count' => (int) DB::table('customers')->where('credit_balance', '>', 0)->count(),
            'members'      => (int) DB::table('customers')->where('loyalty_points', '>', 0)->count(),
            'customers'    => (int) DB::table('customers')->count(),
            'tier_counts'  => DB::table('customers')->select('loyalty_tier', DB::raw('count(*) c'))
                                ->groupBy('loyalty_tier')->pluck('c', 'loyalty_tier')->toArray(),
        ];
    }

    public function topCustomers()
    {
        return $this->base()->orderByDesc('spend12')->limit(8)->get();
    }

    public function members()
    {
        $q = $this->base();
        if ($this->tierFilter !== '') $q->where('customers.loyalty_tier', $this->tierFilter);
        return $q->orderByDesc('spend12')->orderByDesc('customers.loyalty_points')->limit($this->limit)->get();
    }

    public function creditHolders()
    {
        return $this->base()->where('customers.credit_balance', '>', 0)
            ->orderByDesc('customers.credit_balance')->limit($this->limit)->get();
    }

    public function selected(): ?Customer
    {
        if (! $this->selectedId) return null;
        return $this->base()->where('customers.id', $this->selectedId)->first();
    }

    // ── ACTIONS ───────────────────────────────────────────────────────────
    public function setTab(string $tab): void { $this->tab = $tab; $this->limit = 25; }
    public function loadMore(): void { $this->limit += 25; }
    public function updatedSearch(): void { $this->limit = 25; }

    public function openCustomer(int $id): void
    {
        $this->selectedId = $id;
        $this->pointsDelta = $this->pointsReason = $this->creditDelta = $this->creditReason = $this->redeemPoints = '';
        $this->tierChoice = (string) (Customer::find($id)?->loyalty_tier ?? 'standard');
    }

    public function closePanel(): void { $this->selectedId = null; }

    private function target(): ?Customer
    {
        if (! $this->canManage()) {
            Notification::make()->title('Not allowed')->body('Only administrators can change points or credit.')->danger()->send();
            return null;
        }
        return $this->selectedId ? Customer::find($this->selectedId) : null;
    }

    public function adjustPoints(): void
    {
        if (! $this->loyaltyOn() || ! ($c = $this->target())) return;
        $n = (int) $this->pointsDelta;
        if ($n === 0 || trim($this->pointsReason) === '') {
            Notification::make()->title('Enter points (+/-) and a reason')->warning()->send(); return;
        }
        DB::transaction(function () use ($c, $n) {
            $c = Customer::lockForUpdate()->find($c->id);
            $c->loyalty_points = max(0, (int) $c->loyalty_points + $n);
            $c->save();
        });
        $this->log(sprintf('Loyalty points %+d - %s', $n, trim($this->pointsReason)), $c);
        Notification::make()->title(($n > 0 ? '+' : '') . number_format($n) . ' points saved')->success()->send();
        $this->pointsDelta = $this->pointsReason = '';
    }

    public function adjustCredit(): void
    {
        if (! $this->creditOn() || ! ($c = $this->target())) return;
        $amt = round((float) $this->creditDelta, 2);
        if ($amt == 0 || trim($this->creditReason) === '') {
            Notification::make()->title('Enter an amount (+/-) and a reason')->warning()->send(); return;
        }
        $ok = DB::transaction(function () use ($c, $amt) {
            $c = Customer::lockForUpdate()->find($c->id);
            $new = round((float) $c->credit_balance + $amt, 2);
            if ($new < 0) return false;
            $c->credit_balance = $new;
            $c->save();
            return true;
        });
        if (! $ok) {
            Notification::make()->title('Credit cannot go below $0.00')->danger()->send(); return;
        }
        $this->log(sprintf('Store credit %s$%s - %s', $amt >= 0 ? '+' : '-', number_format(abs($amt), 2), trim($this->creditReason)), $c);
        Notification::make()->title('Store credit updated')->success()->send();
        $this->creditDelta = $this->creditReason = '';
    }

    public function redeem(): void
    {
        if (! $this->loyaltyOn() || ! $this->creditOn() || ! ($c = $this->target())) return;
        $pts = (int) $this->redeemPoints;
        $dollars = round($pts / self::POINTS_PER_DOLLAR, 2);
        if ($pts <= 0 || $dollars <= 0) {
            Notification::make()->title('Enter a valid number of points')->warning()->send(); return;
        }
        $ok = DB::transaction(function () use ($c, $pts, $dollars) {
            $c = Customer::lockForUpdate()->find($c->id);
            if ($pts > (int) $c->loyalty_points) return false;
            $c->loyalty_points = (int) $c->loyalty_points - $pts;
            $c->credit_balance = round((float) $c->credit_balance + $dollars, 2);
            $c->save();
            return true;
        });
        if (! $ok) {
            Notification::make()->title('Not enough points')->danger()->send(); return;
        }
        $this->log("Redeemed {$pts} points for $" . number_format($dollars, 2) . ' store credit', $c);
        Notification::make()->title('$' . number_format($dollars, 2) . ' added to store credit')->success()->send();
        $this->redeemPoints = '';
    }

    public function saveTier(): void
    {
        if (! $this->loyaltyOn() || ! ($c = $this->target())) return;
        if (! isset(self::TIERS[$this->tierChoice])) return;
        $c->loyalty_tier = $this->tierChoice;
        $c->save();
        $this->log('Loyalty tier set to ' . $this->tierChoice, $c);
        Notification::make()->title('Tier saved')->success()->send();
    }
}