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
        // Add the percentage column to store the discount per line item
        $table->decimal('discount_percent', 5, 2)->default(0)->after('sold_price');
    });
}

public function down(): void
{
    Schema::table('sale_items', function (Blueprint $table) {
        $table->dropColumn('discount_percent');
    });
}
};
