<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // This ensures the column always has a value even if Laravel misses it
            $table->string('loyalty_tier')->default('standard')->change();
            
            // While we are here, let's make sure other fields don't cause the same crash
            $table->string('last_name')->nullable()->change();
            $table->string('sales_person')->nullable()->change();
            $table->string('country')->default('Australia')->change();
        });
    }

    public function down(): void
    {
        // No need to reverse
    }
};