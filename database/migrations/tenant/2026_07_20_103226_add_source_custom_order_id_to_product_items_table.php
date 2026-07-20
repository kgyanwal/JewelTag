<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_items', function (Blueprint $table) {
            if (!Schema::hasColumn('product_items', 'source_custom_order_id')) {
                $table->unsignedBigInteger('source_custom_order_id')->nullable()->after('memo_vendor_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_items', function (Blueprint $table) {
            $table->dropColumn('source_custom_order_id');
        });
    }
};