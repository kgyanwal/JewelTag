<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exchanges', function (Blueprint $table) {
            // Structured "new items" cart — mirrors sale_items shape (stock / non-tag / custom order rows)
            if (!Schema::hasColumn('exchanges', 'new_items')) {
                $table->json('new_items')->nullable()->after('new_sale_amount');
            }

            // Link to a custom order created directly from this exchange (if the customer is upgrading into a custom piece)
            if (!Schema::hasColumn('exchanges', 'new_custom_order_id')) {
                $table->unsignedBigInteger('new_custom_order_id')->nullable()->after('new_sale_id');
            }

            // Link to a repair created directly from this exchange (if part of the "new" side is a repair/service job)
            if (!Schema::hasColumn('exchanges', 'new_repair_id')) {
                $table->unsignedBigInteger('new_repair_id')->nullable()->after('new_custom_order_id');
            }

            // Discount / tax bookkeeping for the new-items side (so the receipt + totals match Sale behavior)
            if (!Schema::hasColumn('exchanges', 'new_items_subtotal')) {
                $table->decimal('new_items_subtotal', 12, 2)->default(0)->after('new_items');
            }
            if (!Schema::hasColumn('exchanges', 'new_items_tax')) {
                $table->decimal('new_items_tax', 12, 2)->default(0)->after('new_items_subtotal');
            }

            // Split payment support for the difference amount, matching Sale's split_payments repeater
            if (!Schema::hasColumn('exchanges', 'is_split_payment')) {
                $table->boolean('is_split_payment')->default(false)->after('difference_payment_method');
            }
            if (!Schema::hasColumn('exchanges', 'split_payments')) {
                $table->json('split_payments')->nullable()->after('is_split_payment');
            }
        });

        Schema::table('exchanges', function (Blueprint $table) {
            if (!Schema::hasColumn('exchanges', 'new_custom_order_id_fk_added')) {
                // Add FKs in a second pass safely (skip if tables/columns already constrained elsewhere)
                try {
                    $table->foreign('new_custom_order_id')->references('id')->on('custom_orders')->nullOnDelete();
                } catch (\Throwable $e) {
                }
                try {
                    $table->foreign('new_repair_id')->references('id')->on('repairs')->nullOnDelete();
                } catch (\Throwable $e) {
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('exchanges', function (Blueprint $table) {
            foreach (['new_custom_order_id', 'new_repair_id'] as $col) {
                if (Schema::hasColumn('exchanges', $col)) {
                    try { $table->dropForeign(['exchanges_' . $col . '_foreign']); } catch (\Throwable $e) {}
                }
            }

            $drop = ['new_items', 'new_custom_order_id', 'new_repair_id', 'new_items_subtotal', 'new_items_tax', 'is_split_payment', 'split_payments'];
            foreach ($drop as $col) {
                if (Schema::hasColumn('exchanges', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};