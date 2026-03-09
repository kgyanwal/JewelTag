<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {

            // Fix for old migration dependency
            if (!Schema::hasColumn('sale_items', 'discount_amount')) {
                $table->decimal('discount_amount', 10, 2)->default(0)->nullable();
            }

            if (!Schema::hasColumn('sale_items', 'is_tax_free')) {
                $table->boolean('is_tax_free')->default(false)->nullable();
            }

        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {

            if (Schema::hasColumn('sale_items', 'is_tax_free')) {
                $table->dropColumn('is_tax_free');
            }

            // optional: remove discount_amount if you want
            // $table->dropColumn('discount_amount');

        });
    }
};