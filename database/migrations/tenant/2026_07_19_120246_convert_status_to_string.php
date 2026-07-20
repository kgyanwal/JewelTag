<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Change status to string to allow any future status strings without SQL errors
        Schema::table('exchanges', function (Blueprint $table) {
            $table->string('status')->default('pending_approval')->change();
        });

        Schema::table('custom_orders', function (Blueprint $table) {
            $table->string('status')->default('draft')->change();
        });
    }

    public function down(): void
    {
        // Revert if necessary (optional)
        Schema::table('exchanges', function (Blueprint $table) {
            $table->enum('status', ['pending_approval','approved','rejected','completed','cancelled'])->change();
        });
    }
};