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
    Schema::table('sale_items', function (Blueprint $table) {
        // Add store_id to sale_items if it's missing
        if (!Schema::hasColumn('sale_items', 'store_id')) {
            $table->foreignId('store_id')->nullable()->constrained()->after('sale_id');
        }
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            //
        });
    }
};
