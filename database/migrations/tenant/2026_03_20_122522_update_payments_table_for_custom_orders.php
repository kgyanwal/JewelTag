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
        // Step 1: Drop the strict foreign key constraint first
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['sale_id']);
        });

        // Step 2: Now that it's unlocked, make it nullable and re-apply the constraint
        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedBigInteger('sale_id')->nullable()->change();
            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('cascade');
            
            // Step 3: Add custom_order_id (Checking if it exists just in case it partially ran earlier)
            if (!Schema::hasColumn('payments', 'custom_order_id')) {
                $table->foreignId('custom_order_id')->nullable()->after('sale_id')->constrained('custom_orders')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['custom_order_id']);
            $table->dropColumn('custom_order_id');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['sale_id']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedBigInteger('sale_id')->nullable(false)->change();
            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('cascade');
        });
    }
};
