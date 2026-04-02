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
    Schema::table('custom_orders', function (Blueprint $table) {
        if (!Schema::hasColumn('custom_orders', 'items'))
            $table->json('items')->nullable()->after('design_notes');
        
        if (!Schema::hasColumn('custom_orders', 'order_type'))
            $table->string('order_type')->default('custom')->after('order_no'); // custom | stock_modify
    });
}

public function down(): void
{
    Schema::table('custom_orders', function (Blueprint $table) {
        $table->dropColumn(array_filter([
            Schema::hasColumn('custom_orders', 'items') ? 'items' : null,
            Schema::hasColumn('custom_orders', 'order_type') ? 'order_type' : null,
        ]));
    });
}
};
