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
    // Add trade-in tracking to Sales
    Schema::table('sales', function (Blueprint $table) {
        $table->boolean('has_trade_in')->default(false);
        $table->decimal('trade_in_value', 12, 2)->default(0);
        $table->string('trade_in_receipt_no')->nullable(); // Unique ID for the trade-in
    });

    // Add trade-in tracking to Product Items (Inventory)
    Schema::table('product_items', function (Blueprint $table) {
        $table->boolean('is_trade_in')->default(false);
        $table->string('original_trade_in_no')->nullable();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['has_trade_in', 'trade_in_value', 'trade_in_receipt_no']);
        });

        Schema::table('product_items', function (Blueprint $table) {
            $table->dropColumn(['is_trade_in', 'original_trade_in_no']);
        });
    }
};
