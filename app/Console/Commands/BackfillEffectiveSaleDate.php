<?php

namespace App\Console\Commands;

use App\Models\Sale;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillEffectiveSaleDate extends Command
{
    protected $signature = 'sales:backfill-effective-date {--chunk=500}';
    protected $description = 'One-time backfill of sales.effective_sale_date from payments.paid_at, sale_payments.payment_date, or fallback timestamps';

    public function handle(): int
    {
        $chunkSize = (int) $this->option('chunk');
        $total = Sale::whereNull('effective_sale_date')->count();

        if ($total === 0) {
            $this->info('Nothing to backfill — all sales already have effective_sale_date set.');
            return self::SUCCESS;
        }

        $this->info("Backfilling effective_sale_date for {$total} sales (chunk size {$chunkSize})...");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        Sale::whereNull('effective_sale_date')
            ->orderBy('id')
            ->chunkById($chunkSize, function ($sales) use ($bar) {
                $updates = [];

                foreach ($sales as $sale) {
                    // 1. Earliest payment in `payments` table
                    $fromPayments = DB::table('payments')
                        ->where('sale_id', $sale->id)
                        ->min('paid_at');

                    // 2. Earliest payment in `sale_payments` table (older import format)
                    $fromSalePayments = DB::table('sale_payments')
                        ->where('sale_id', $sale->id)
                        ->min('payment_date');

                    // Pick the earliest non-null of the two sources
                    $candidates = array_filter([$fromPayments, $fromSalePayments]);
                    $effectiveDate = !empty($candidates)
                        ? min($candidates)
                        : ($sale->completed_at ?? $sale->updated_at ?? $sale->created_at);

                    $updates[] = ['id' => $sale->id, 'date' => $effectiveDate];
                    $bar->advance();
                }

                // Batch update this chunk in a single query using CASE/WHEN
                if (!empty($updates)) {
                    $cases = '';
                    $ids = [];
                    foreach ($updates as $u) {
                        $cases .= "WHEN {$u['id']} THEN " . (
                            $u['date'] ? "'" . addslashes($u['date']) . "'" : 'NULL'
                        ) . " ";
                        $ids[] = $u['id'];
                    }
                    $idList = implode(',', $ids);

                    DB::statement("
                        UPDATE sales
                        SET effective_sale_date = CASE id {$cases} END
                        WHERE id IN ({$idList})
                    ");
                }
            });

        $bar->finish();
        $this->newLine(2);
        $this->info('Backfill complete.');

        return self::SUCCESS;
    }
}