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
        // change() requires the 'doctrine/dbal' package, 
        // but in Laravel 11/12 it is supported natively for MySQL.
        $table->text('item_description')->change();
    });
}

public function down(): void
{
    Schema::table('repairs', function (Blueprint $table) {
        $table->string('item_description', 255)->change();
    });
}
};
