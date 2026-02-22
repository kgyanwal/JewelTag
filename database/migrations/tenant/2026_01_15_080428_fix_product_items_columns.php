<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_items', function (Blueprint $table) {
            // 🔹 Add the missing column causing the 500 error
            if (!Schema::hasColumn('product_items', 'custom_description')) {
                $table->text('custom_description')->nullable()->after('category');
            }

            // 🔹 Add Size and Metal Weight if they don't exist
            if (!Schema::hasColumn('product_items', 'size')) {
                $table->string('size')->nullable()->after('metal_type');
            }

            if (!Schema::hasColumn('product_items', 'metal_weight')) {
                $table->decimal('metal_weight', 8, 2)->nullable()->after('size');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_items', function (Blueprint $table) {
            $table->dropColumn(['custom_description', 'size', 'metal_weight']);
        });
    }
};