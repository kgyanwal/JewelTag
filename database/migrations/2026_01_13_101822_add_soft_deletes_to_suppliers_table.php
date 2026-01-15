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
    Schema::table('suppliers', function (Blueprint $table) {
        // 🔹 This creates the 'deleted_at' column Laravel is looking for
        $table->softDeletes(); 
    });
}

public function down(): void
{
    Schema::table('suppliers', function (Blueprint $table) {
        $table->dropSoftDeletes();
    });
}
};
