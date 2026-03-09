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
        // Fix the missing tax column
        if (!Schema::hasColumn('sale_items', 'is_tax_free')) {
            $table->boolean('is_tax_free')->default(false)->after('discount_amount');
        }
        // Add a column to store the "Final Line Price" (Sale Amount) for easier auditing
        if (!Schema::hasColumn('sale_items', 'sale_price_override')) {
            $table->decimal('sale_price_override', 15, 2)->nullable()->after('sold_price');
        }
    });
}
};
