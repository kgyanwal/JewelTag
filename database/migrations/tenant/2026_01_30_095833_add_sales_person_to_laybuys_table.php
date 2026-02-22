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
        // Add the column that the error says is missing
        if (!Schema::hasColumn('laybuys', 'sales_person')) {
            $table->string('sales_person')->nullable();
        }
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
