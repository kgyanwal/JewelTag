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
        $table->decimal('discount_percent', 8, 2)->default(0)->after('quoted_price');
        $table->decimal('discount_amount', 10, 2)->default(0)->after('discount_percent');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('custom_orders', function (Blueprint $table) {
            //
        });
    }
};
