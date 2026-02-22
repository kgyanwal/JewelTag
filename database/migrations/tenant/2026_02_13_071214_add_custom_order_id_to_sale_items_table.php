<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            // Check if column exists before adding to prevent crashes
            if (!Schema::hasColumn('sale_items', 'custom_order_id')) {
                $table->foreignId('custom_order_id')
                    ->nullable()
                    ->after('repair_id')
                    ->constrained('custom_orders')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            if (Schema::hasColumn('sale_items', 'custom_order_id')) {
                $table->dropForeign(['custom_order_id']);
                $table->dropColumn('custom_order_id');
            }
        });
    }
};