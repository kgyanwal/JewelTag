<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sale;
use App\Models\Payment;
use Carbon\Carbon;

class FixPaymentDates extends Command
{
    protected $signature   = 'payments:fix-dates {--dry-run : Preview changes without saving}';
    protected $description = 'Fix payments.paid_at across ALL tenants to match the original sale date';

    public function handle(): void
    {
        $dryRun  = $this->option('dry-run');
        $tenants = \App\Models\Tenant::all();

        if ($tenants->isEmpty()) {
            $this->error('No tenants found in central database.');
            return;
        }

        if ($dryRun) {
            $this->warn('DRY RUN — no changes will be saved.');
        }

        $this->info("Found {$tenants->count()} tenant(s). Processing...");
        $this->newLine();

        $grandFixed   = 0;
        $grandSkipped = 0;

        foreach ($tenants as $tenant) {
            try {
                tenancy()->initialize($tenant);
            } catch (\Exception $e) {
                $this->error("  Could not initialize tenant [{$tenant->id}]: " . $e->getMessage());
                continue;
            }

            $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->info("Tenant: {$tenant->id}");

            $sales   = Sale::with('payments')->get();
            $fixed   = 0;
            $skipped = 0;

            $this->line("  Sales found: {$sales->count()}");

            foreach ($sales as $sale) {
                // ✅ FIX: Use Carbon::parse() to handle both string and Carbon instances
                $rawDate = $sale->completed_at ?? $sale->created_at;

                if (!$rawDate) {
                    $skipped++;
                    continue;
                }

                $saleDateCarbon = Carbon::parse($rawDate);
                $saleDate       = $saleDateCarbon->format('Y-m-d');

                foreach ($sale->payments as $payment) {
                    $paymentDate = $payment->paid_at
                        ? Carbon::parse($payment->paid_at)->format('Y-m-d')
                        : null;

                    if ($paymentDate === $saleDate) {
                        continue;
                    }

                    $this->line(
                        "  Sale #{$sale->invoice_number} | Payment #{$payment->id} | " .
                        "paid_at: {$paymentDate} → correcting to: {$saleDate}"
                    );

                    if (!$dryRun) {
                        $payment->paid_at = $saleDateCarbon->copy()->setTimezone('UTC');
                        $payment->save();
                    }

                    $fixed++;
                }
            }

            $this->info("  ✓ {$fixed} payment(s) " . ($dryRun ? 'would be' : 'were') . " corrected. {$skipped} skipped.");

            $grandFixed   += $fixed;
            $grandSkipped += $skipped;

            tenancy()->end();
        }

        $this->newLine();
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("ALL TENANTS COMPLETE");
        $this->info("Total fixed: {$grandFixed} | Total skipped: {$grandSkipped}");
    }
}