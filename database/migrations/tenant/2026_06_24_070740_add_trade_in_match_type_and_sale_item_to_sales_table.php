<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('trade_in_match_type')->nullable()->after('trade_in_bought_from_store');
            $table->unsignedBigInteger('trade_in_original_sale_item_id')->nullable()->after('trade_in_original_product_item_id');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['trade_in_match_type', 'trade_in_original_sale_item_id']);
        });
    }
};