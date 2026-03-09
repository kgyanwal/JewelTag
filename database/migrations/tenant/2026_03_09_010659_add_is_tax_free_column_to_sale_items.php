<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Block 1: Ensure the dependency column exists first
        if (Schema::hasTable('sale_items')) {
            Schema::table('sale_items', function (Blueprint $table) {
                if (!Schema::hasColumn('sale_items', 'discount_amount')) {
                    // Create it if it's missing so the next block doesn't crash
                    $table->decimal('discount_amount', 15, 2)->default(0)->after('sold_price');
                }
            });
        }

        // Block 2: Add your new POS features
        Schema::table('sale_items', function (Blueprint $table) {
            if (!Schema::hasColumn('sale_items', 'is_tax_free')) {
                // We use nullable() and a default to ensure data integrity
                $table->boolean('is_tax_free')->default(false)->nullable();
            }
            
            if (!Schema::hasColumn('sale_items', 'sale_price_override')) {
                $table->decimal('sale_price_override', 15, 2)->nullable();
            }
        });

        // Block 3: Fix Sales table soft deletes (referenced in your filename)
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['is_tax_free', 'sale_price_override']);
        });
        
        Schema::table('sales', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};