<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Add after existing address fields (if they exist)
            $table->string('address_line_1')->nullable()->after('company');
            $table->string('address_line_2')->nullable()->after('address_line_1');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['address_line_1', 'address_line_2']);
        });
    }
};
