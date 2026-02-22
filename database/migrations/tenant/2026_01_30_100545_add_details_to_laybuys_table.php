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
    Schema::table('laybuys', function (Blueprint $table) {
        // 🔹 Add the missing columns requested by your code
        $table->date('start_date')->nullable();
        $table->date('due_date')->nullable();
        $table->text('notes')->nullable();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laybuys', function (Blueprint $table) {
            //
        });
    }
};
