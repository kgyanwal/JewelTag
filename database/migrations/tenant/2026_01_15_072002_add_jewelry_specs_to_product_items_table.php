<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up(): void
{
    Schema::table('product_items', function (Blueprint $table) {
        if (!Schema::hasColumn('product_items', 'metal_weight')) {
            $table->decimal('metal_weight', 8, 2)->nullable()->after('metal_type');
        }
        // size and barcode are already there according to your errors, 
        // so we don't need to add them here.
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_items', function (Blueprint $table) {
            //
        });
    }
};
