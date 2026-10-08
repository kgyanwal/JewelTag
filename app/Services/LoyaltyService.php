<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\LoyaltyHold;
use App\Models\LoyaltyTier;
use App\Models\LoyaltyTransaction;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;

/**
 * Ledger-based loyalty. Points and store credit are SEPARATE:
 * points are only ever spent as a discount on a sale (redeemForSale).
 */
class LoyaltyService
{
    /** Sale statuses that never earn. */
    private const NON_EARNING = ['void', 'cancelled', 'refunded'];

    // ── settings (site_settings, with defaults) ─────────────────────────
    public static function setting(string $key, $default)
    {
        $v = DB::table('site_settings')->where('key', $key)->value('value');
        return ($v === null || $v === '') ? $default : $v;
    }

    public static function enabled(): bool
    {
        return \App\Services\StoreFeatures::loyalty();
    }

    public static function earnRate(): float          // points per $1 qualifying spend
    {
        return (float) self::setting('loyalty_earn_rate', 1);
    }

    public static function pointsPerDollar(): float   // redemption: points needed for $1 off
    {
        return (float) self::setting('loyalty_points_per_dollar', 100);
    }

    // ── what counts ─────────────────────────────────────────────────────
    /** Pre-tax amount actually paid. Partial payments earn proportionally. */
    public static function qualifyingAmount(Sale $sale): float
    {
        if (in_array(strtolower((string) $sale->status), self::NON_EARNING, true)) {
            return 0.0;
        }
        $total = (float) $sale->final_total;
        if ($total <= 0) {
            return 0.0;
        }
        $preTax = max(0, $total - (float) $sale->tax_amount);
        $paidRatio = min(1, max(0, (float) $sale->amount_paid / $total));
        return round($preTax * $paidRatio, 2);
    }

    // ── tiers ───────────────────────────────────────────────────────────
    public static function tierForSpend(float $spend): LoyaltyTier
    {
        $tiers = LoyaltyTier::ordered();
        $match = $tiers->first();
        foreach ($tiers as $t) {
            if ($spend >= (float) $t->min_spend) {
                $match = $t;
            }
        }
        return $match;
    }

    public static function multiplierFor(?string $slug): float
    {
        $tier = LoyaltyTier::where('slug', $slug ?: 'standard')->first();
        return $tier ? (float) $tier->earn_multiplier : 1.0;
    }

    /** Refresh spend totals and tier from the sales table (source of truth). */
    public static function recalcTier(Customer $customer): Customer
    {
        $base = Sale::where('customer_id', $customer->id)
            ->whereNotIn('status', self::NON_EARNING);

        $calc = fn($q) => (float) $q->get()->sum(fn(Sale $s) => self::qualifyingAmount($s));

        $lifetime = $calc(clone $base);
        $rolling  = $calc((clone $base)->where('effective_sale_date', '>=', now()->subMonths(12)));

        $customer->lifetime_spend    = $lifetime;
        $customer->rolling_12m_spend = $rolling;

        if (! $customer->tier_locked) {
            $newTier = self::tierForSpend($rolling)->slug;
            if ($newTier !== $customer->loyalty_tier) {
                $customer->loyalty_tier    = $newTier;
                $customer->tier_updated_at = now();
            }
        }
        $customer->save();
        return $customer;
    }

    // ── earning ─────────────────────────────────────────────────────────
    /**
     * Bring the ledger in line with what this sale is entitled to.
     * Returns the signed point delta written (0 = nothing to do).
     */
    public static function syncSale(Sale $sale, ?int $userId = null): int
    {
        if (! self::enabled() || ! $sale->customer_id) {
            return 0;
        }

        return DB::transaction(function () use ($sale, $userId) {
            $customer = Customer::lockForUpdate()->find($sale->customer_id);
            if (! $customer) {
                return 0;
            }

            $qualifying = self::qualifyingAmount($sale);

            $first = LoyaltyTransaction::where('sale_id', $sale->id)
                ->where('type', 'earn')->orderBy('id')->first();
            $multiplier = $first && $first->multiplier !== null
                ? (float) $first->multiplier
                : self::multiplierFor($customer->loyalty_tier);

            $entitled = (int) floor($qualifying * self::earnRate() * $multiplier);

            $granted = (int) LoyaltyTransaction::where('sale_id', $sale->id)
                ->whereIn('type', ['earn', 'reversal'])->sum('points');

            $delta = $entitled - $granted;

            if ($delta !== 0) {
                $balance = max(0, (int) $customer->loyalty_points + $delta);
                LoyaltyTransaction::create([
                    'customer_id'       => $customer->id,
                    'type'              => $delta > 0 ? 'earn' : 'reversal',
                    'points'            => $delta,
                    'balance_after'     => $balance,
                    'tier_at_time'      => $customer->loyalty_tier,
                    'multiplier'        => $multiplier,
                    'qualifying_amount' => $qualifying,
                    'sale_id'           => $sale->id,
                    'reason'            => $delta > 0 ? 'Sale earn' : 'Sale adjustment/refund',
                    'user_id'           => $userId,
                ]);
                $customer->loyalty_points = $balance;
                $customer->save();
            }

            self::recalcTier($customer);

            return $delta;
        });
    }

    // ── NEW SALES ONLY ──────────────────────────────────────────────────
    /**
     * Called once from CreateSale. Guard: any ledger row for this sale = already processed.
     * Store-credit payments and repairs earn nothing. Returns points awarded.
     */
    public static function awardForSale(Sale $sale, ?int $userId = null): int
    {
        if (! self::enabled() || ! $sale->customer_id) {
            return 0;
        }
        $sale = $sale->fresh();
        if (! $sale || in_array(strtolower((string) $sale->status), self::NON_EARNING, true)) {
            return 0;
        }
        if (LoyaltyTransaction::where('sale_id', $sale->id)->exists()) {
            return 0;
        }

        $total = (float) $sale->final_total;
        if ($total <= 0) {
            return 0;
        }

        // Repairs never earn points.
        $repairs      = \App\Models\Repair::where('sale_id', $sale->id)->get();
        $repairGross  = (float) $repairs->sum(fn($r) => \App\Filament\Resources\RepairResource::calculateRepairTotal($r)['total']);
        $repairPreTax = (float) $repairs->sum(fn($r) => (float) $r->final_cost);

        $regularGross  = max(0, $total - $repairGross);
        $regularPreTax = max(0, $total - (float) $sale->tax_amount - $repairPreTax);

        // Real money only: no store credit, no repair payments.
        $paid = (float) \App\Models\Payment::where('sale_id', $sale->id)
            ->whereNull('repair_id')
            ->whereRaw('UPPER(method) <> ?', ['STORE_CREDIT'])
            ->sum('amount');

        $qualifying = $regularGross > 0
            ? round($regularPreTax * min(1, max(0, $paid / $regularGross)), 2)
            : 0.0;

        return DB::transaction(function () use ($sale, $qualifying, $userId) {
            $customer = Customer::lockForUpdate()->find($sale->customer_id);
            if (! $customer) {
                return 0;
            }

            $multiplier = self::multiplierFor($customer->loyalty_tier);
            $points     = (int) floor($qualifying * self::earnRate() * $multiplier);
            $balance    = (int) $customer->loyalty_points + $points;

            // Written even when 0 so the sale is marked processed.
            LoyaltyTransaction::create([
                'customer_id'       => $customer->id,
                'type'              => 'earn',
                'points'            => $points,
                'balance_after'     => $balance,
                'tier_at_time'      => $customer->loyalty_tier,
                'multiplier'        => $multiplier,
                'qualifying_amount' => $qualifying,
                'sale_id'           => $sale->id,
                'reason'            => 'Sale earn',
                'user_id'           => $userId,
            ]);

            $customer->loyalty_points = $balance;
            $customer->save();
            self::recalcTier($customer);

            return $points;
        });
    }

    /** Take back points for a refund/void. $fraction 1.0 = all points this sale earned. */
    public static function reverseForSale(Sale $sale, float $fraction = 1.0, ?int $userId = null): int
    {
        $net = (int) LoyaltyTransaction::where('sale_id', $sale->id)
            ->whereIn('type', ['earn', 'reversal'])->sum('points');
        if ($net <= 0 || ! $sale->customer_id) {
            return 0;
        }
        $take = (int) min($net, round($net * max(0, min(1, $fraction))));
        if ($take <= 0) {
            return 0;
        }

        return DB::transaction(function () use ($sale, $take, $userId) {
            $customer = Customer::lockForUpdate()->find($sale->customer_id);
            if (! $customer) {
                return 0;
            }
            $balance = max(0, (int) $customer->loyalty_points - $take);
            LoyaltyTransaction::create([
                'customer_id'   => $customer->id,
                'type'          => 'reversal',
                'points'        => -$take,
                'balance_after' => $balance,
                'tier_at_time'  => $customer->loyalty_tier,
                'sale_id'       => $sale->id,
                'reason'        => 'Refund / void',
                'user_id'       => $userId,
            ]);
            $customer->loyalty_points = $balance;
            $customer->save();
            return $take;
        });
    }

    // ── REDEEM (points → discount on a sale) ────────────────────────────
    /** Writes the redeem row and lowers the balance. Returns points actually used. */
    public static function redeemForSale(Sale $sale, int $points, ?int $userId = null): int
    {
        if ($points <= 0 || ! $sale->customer_id) {
            return 0;
        }

        return DB::transaction(function () use ($sale, $points, $userId) {
            $c = Customer::lockForUpdate()->find($sale->customer_id);
            if (! $c) {
                return 0;
            }
            $use = (int) min($points, (int) $c->loyalty_points);
            if ($use <= 0) {
                return 0;
            }
            $balance = (int) $c->loyalty_points - $use;
            $dollars = $use / max(1, self::pointsPerDollar());

            LoyaltyTransaction::create([
                'customer_id'   => $c->id,
                'type'          => 'redeem',
                'points'        => -$use,
                'balance_after' => $balance,
                'tier_at_time'  => $c->loyalty_tier,
                'sale_id'       => $sale->id,
                'reason'        => 'Redeemed on sale ($' . number_format($dollars, 2) . ' discount)',
                'user_id'       => $userId,
            ]);

            $c->loyalty_points = $balance;
            $c->save();

            return $use;
        });
    }

    // ── HOLDS (temporary ledger while a sale is a draft) ────────────────
    /** Points a customer can still use, minus holds from OTHER open drafts. */
    public static function availableFor(int $customerId, ?string $exceptDraft = null): int
    {
        $balance = (int) (Customer::find($customerId)?->loyalty_points ?? 0);

        $held = (int) LoyaltyHold::where('customer_id', $customerId)
            ->where('status', 'held')
            ->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->when($exceptDraft, fn($q) => $q->where('draft_id', '!=', $exceptDraft))
            ->sum('points');

        return max(0, $balance - $held);
    }

    public static function holdPoints(string $draftId, int $customerId, int $points, float $discount, ?string $name = null, ?int $userId = null): void
    {
        LoyaltyHold::updateOrCreate(
            ['draft_id' => $draftId],
            [
                'draft_name'  => $name,
                'customer_id' => $customerId,
                'points'      => $points,
                'discount'    => $discount,
                'user_id'     => $userId,
                'status'      => 'held',
                'sale_id'     => null,
                'expires_at'  => now()->addHours(24),
            ]
        );
    }

    public static function releaseHold(?string $draftId): void
    {
        if (! $draftId) {
            return;
        }
        LoyaltyHold::where('draft_id', $draftId)->where('status', 'held')->delete();
    }

    /** Sale completed: move the hold into the real ledger. */
    public static function commitHold(string $draftId, Sale $sale, int $points, ?int $userId = null): int
    {
        $used = self::redeemForSale($sale, $points, $userId);

        LoyaltyHold::where('draft_id', $draftId)->update([
            'status'     => 'committed',
            'sale_id'    => $sale->id,
            'points'     => $used,
            'expires_at' => null,
        ]);

        return $used;
    }

    // ── manual ──────────────────────────────────────────────────────────
    public static function adjust(Customer $customer, int $points, string $reason, ?int $userId = null, string $type = 'adjust'): LoyaltyTransaction
    {
        return DB::transaction(function () use ($customer, $points, $reason, $userId, $type) {
            $c = Customer::lockForUpdate()->find($customer->id);
            $balance = max(0, (int) $c->loyalty_points + $points);
            $tx = LoyaltyTransaction::create([
                'customer_id'   => $c->id,
                'type'          => $type,
                'points'        => $points,
                'balance_after' => $balance,
                'tier_at_time'  => $c->loyalty_tier,
                'reason'        => $reason,
                'user_id'       => $userId,
            ]);
            $c->loyalty_points = $balance;
            $c->save();
            return $tx;
        });
    }

        /**
     * Reverse loyalty activity when a refund is approved.
     * Ratio = refunded amount / sale total (capped at 1).
     * Earned points are clawed back; redeemed points are given back.
     */
    public static function reverseForRefund(\App\Models\Sale $sale, float $refundAmount, ?int $userId = null): void
    {
        $total = (float) $sale->final_total;
        if ($total <= 0 || $refundAmount <= 0 || !$sale->customer_id) return;
        $ratio = min(1, $refundAmount / $total);

        // Don't reverse the same sale twice for the same share: reverse only what's left
        $earned   = (int) \App\Models\LoyaltyTransaction::where('sale_id', $sale->id)->where('type', 'earn')->sum('points');
        $redeemed = abs((int) \App\Models\LoyaltyTransaction::where('sale_id', $sale->id)->where('type', 'redeem')->sum('points'));
        $alreadyClawed   = abs((int) \App\Models\LoyaltyTransaction::where('sale_id', $sale->id)->where('type', 'refund_reverse')->sum('points'));
        $alreadyRestored = (int) \App\Models\LoyaltyTransaction::where('sale_id', $sale->id)->where('type', 'refund_restore')->sum('points');

        $claw    = max(0, min((int) round($earned * $ratio), $earned - $alreadyClawed));
        $restore = max(0, min((int) round($redeemed * $ratio), $redeemed - $alreadyRestored));

        // Never push the customer's balance below zero
        $balance = (int) \App\Models\LoyaltyTransaction::where('customer_id', $sale->customer_id)->sum('points');
        $claw    = min($claw, max(0, $balance + $restore));

        if ($claw > 0) {
            \App\Models\LoyaltyTransaction::create([
                'customer_id' => $sale->customer_id,
                'sale_id'     => $sale->id,
                'type'        => 'refund_reverse',
                'points'      => -$claw,
                'note'        => "Points reversed for refund on #{$sale->invoice_number}",
            ]);
        }
        if ($restore > 0) {
            \App\Models\LoyaltyTransaction::create([
                'customer_id' => $sale->customer_id,
                'sale_id'     => $sale->id,
                'type'        => 'refund_restore',
                'points'      => $restore,
                'note'        => "Redeemed points restored for refund on #{$sale->invoice_number}",
            ]);
        }
    }
}