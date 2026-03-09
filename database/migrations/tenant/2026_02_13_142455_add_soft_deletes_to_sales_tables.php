<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {

            if (!Schema::hasColumn('sale_items', 'is_tax_free')) {
                $table->boolean('is_tax_free')->default(false)->nullable();
            }

            if (!Schema::hasColumn('sale_items', 'sale_price_override')) {
                $table->decimal('sale_price_override', 15, 2)->nullable()->after('sold_price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};