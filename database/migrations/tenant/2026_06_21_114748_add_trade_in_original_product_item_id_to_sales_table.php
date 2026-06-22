<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->boolean('trade_in_bought_from_store')->default(false)->after('trade_in_description');
            $table->unsignedBigInteger('trade_in_original_product_item_id')->nullable()->after('trade_in_bought_from_store');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['trade_in_bought_from_store', 'trade_in_original_product_item_id']);
        });
    }
};