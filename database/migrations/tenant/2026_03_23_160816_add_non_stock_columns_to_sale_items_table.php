<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            // 🚀 Tracks if item is a service/repair (Grills, Labor) rather than physical stock
            if (!Schema::hasColumn('sale_items', 'is_non_stock')) {
                $table->boolean('is_non_stock')->default(false)->after('id');
            }

            // 🚀 Tracks the cost of the custom job to calculate profit accurately
            if (!Schema::hasColumn('sale_items', 'cost_price')) {
                $table->decimal('cost_price', 15, 2)->default(0)->after('sold_price');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['is_non_stock', 'cost_price']);
        });
    }
};