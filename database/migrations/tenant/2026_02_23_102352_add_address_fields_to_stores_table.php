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
    Schema::table('stores', function (Blueprint $table) {
        $table->after('location', function ($table) {
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip_code')->nullable();
        });
    });
}

public function down(): void
{
    Schema::table('stores', function (Blueprint $table) {
        $table->dropColumn(['address_line_1', 'address_line_2', 'city', 'state', 'zip_code']);
    });
}
};
