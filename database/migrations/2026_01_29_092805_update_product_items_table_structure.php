<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up(): void
{
    Schema::table('product_items', function (Blueprint $table) {
        // 🔹 STEP 1: Rename the OLD department to sub_department first.
        // This frees up the name 'department'.
        $table->renameColumn('department', 'sub_department');
    });

    Schema::table('product_items', function (Blueprint $table) {
        // 🔹 STEP 2: Now rename form_type to department.
        $table->renameColumn('form_type', 'department');

        // 🔹 STEP 3: Change metal_weight to string.
        $table->string('metal_weight')->nullable()->change();
    });
}

    public function down(): void
    {
        Schema::table('product_items', function (Blueprint $table) {
            // Reverse the changes if you roll back
            $table->renameColumn('sub_department', 'department');
            $table->renameColumn('department', 'form_type');
            
            // Note: Reverting a string back to decimal can be risky if 
            // the data contains text like "CTW", so be careful here.
            $table->decimal('metal_weight', 12, 2)->nullable()->change();
        });
    }
};