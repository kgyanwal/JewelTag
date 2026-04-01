<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('custom_orders', function (Blueprint $table) {
            // Add the sale_id column to link Custom Orders to the final Sale
            $table->unsignedBigInteger('sale_id')->nullable()->after('customer_id');
            
            // Optional: Add a foreign key constraint
            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('custom_orders', function (Blueprint $table) {
            $table->dropForeign(['sale_id']);
            $table->dropColumn('sale_id');
        });
    }
};