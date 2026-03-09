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
        // We use change() to modify the existing column to be nullable
        // This prevents the "Column cannot be null" error if the app sends a null value
        $table->boolean('is_tax_free')->default(false)->nullable()->change();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
