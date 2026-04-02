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
    Schema::table('repairs', function (Blueprint $table) {
        $table->decimal('estimated_cost', 12, 2)->nullable()->default(null)->change();
    });
}

public function down(): void
{
    // 🚀 STEP 1: Update existing NULLs to 0 so the NOT NULL constraint doesn't fail
    \Illuminate\Support\Facades\DB::table('repairs')
        ->whereNull('estimated_cost')
        ->update(['estimated_cost' => 0]);

    // 🚀 STEP 2: Now change the column back
    Schema::table('repairs', function (Blueprint $table) {
        $table->decimal('estimated_cost', 12, 2)->nullable(false)->default(0)->change();
    });
}
};
