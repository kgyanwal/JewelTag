<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            // 1. First, create the missing dependency column if it doesn't exist
            if (!Schema::hasColumn('sale_items', 'discount_amount')) {
                $table->decimal('discount_amount', 10, 2)->default(0)->after('sold_price');
            }
        });

        Schema::table('sale_items', function (Blueprint $table) {
            // 2. Then add the is_tax_free column, safely referencing discount_amount
            if (!Schema::hasColumn('sale_items', 'is_tax_free')) {
                $table->boolean('is_tax_free')->default(false)->after('discount_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['is_tax_free']);
            // We usually keep discount_amount if it was a missing dependency
        });
    }
};