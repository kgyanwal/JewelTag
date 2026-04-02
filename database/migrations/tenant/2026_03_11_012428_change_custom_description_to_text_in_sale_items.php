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
        // Change from string(255) to text (65k limit) to support your 5000 chars
        $table->text('custom_description')->nullable()->change();
    });
}

public function down(): void
{
    Schema::table('sale_items', function (Blueprint $table) {
        $table->string('custom_description', 255)->nullable()->change();
    });
}
};
