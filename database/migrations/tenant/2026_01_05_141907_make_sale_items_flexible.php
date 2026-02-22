<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            // 1. Allow Product ID to be empty (for manual sales)
            $table->foreignId('product_item_id')->nullable()->change();
            
            // 2. Add a column for the custom name (e.g. "Ring Resize")
            $table->string('custom_description')->nullable()->after('product_item_id');
        });
    }

    public function down(): void
    {
        // Revert changes if needed
        Schema::table('sale_items', function (Blueprint $table) {
            $table->foreignId('product_item_id')->nullable(false)->change();
            $table->dropColumn('custom_description');
        });
    }
};