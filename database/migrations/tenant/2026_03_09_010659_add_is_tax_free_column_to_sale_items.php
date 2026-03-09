<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Handle sale_items table
        Schema::table('sale_items', function (Blueprint $table) {

            if (!Schema::hasColumn('sale_items', 'discount_amount')) {
                $table->decimal('discount_amount', 15, 2)->default(0);
            }

            if (!Schema::hasColumn('sale_items', 'is_tax_free')) {
                $table->boolean('is_tax_free')->default(false)->nullable();
            }

            if (!Schema::hasColumn('sale_items', 'sale_price_override')) {
                $table->decimal('sale_price_override', 15, 2)->nullable();
            }

            if (!Schema::hasColumn('sale_items', 'deleted_at')) {
                $table->softDeletes();
            }

        });

        // Handle sales table
        Schema::table('sales', function (Blueprint $table) {

            if (!Schema::hasColumn('sales', 'deleted_at')) {
                $table->softDeletes();
            }

        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {

            if (Schema::hasColumn('sale_items', 'is_tax_free')) {
                $table->dropColumn('is_tax_free');
            }

            if (Schema::hasColumn('sale_items', 'sale_price_override')) {
                $table->dropColumn('sale_price_override');
            }

            if (Schema::hasColumn('sale_items', 'deleted_at')) {
                $table->dropSoftDeletes();
            }

        });

        Schema::table('sales', function (Blueprint $table) {

            if (Schema::hasColumn('sales', 'deleted_at')) {
                $table->dropSoftDeletes();
            }

        });
    }
};