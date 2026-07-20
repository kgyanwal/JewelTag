<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exchanges', function (Blueprint $table) {
            if (!Schema::hasColumn('exchanges', 'custom_order_id')) {
                $table->unsignedBigInteger('custom_order_id')->nullable()->after('original_sale_id');
            }
            if (!Schema::hasColumn('exchanges', 'from_custom_order')) {
                $table->boolean('from_custom_order')->default(false)->after('custom_order_id');
            }
            if (!Schema::hasColumn('exchanges', 'co_item_in_production')) {
                $table->boolean('co_item_in_production')->default(true)->after('from_custom_order');
            }
            if (!Schema::hasColumn('exchanges', 'co_item_arrived')) {
                $table->boolean('co_item_arrived')->default(false)->after('co_item_in_production');
            }
        });

        // Widen status columns so any future status string works without enum errors
        Schema::table('exchanges', function (Blueprint $table) {
            $table->string('status')->default('pending_approval')->change();
        });

        Schema::table('custom_orders', function (Blueprint $table) {
            $table->string('status')->default('draft')->change();
        });
    }

    public function down(): void
    {
        Schema::table('exchanges', function (Blueprint $table) {
            $table->dropColumn(['custom_order_id', 'from_custom_order', 'co_item_in_production', 'co_item_arrived']);
        });
    }
};