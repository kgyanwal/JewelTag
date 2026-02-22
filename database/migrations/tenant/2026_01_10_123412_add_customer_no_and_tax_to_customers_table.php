<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Check if customer_no exists before adding it
            if (!Schema::hasColumn('customers', 'customer_no')) {
                $table->string('customer_no')->nullable()->unique()->after('id');
            }
            
            // Check if tax_number exists before adding it
            if (!Schema::hasColumn('customers', 'tax_number')) {
                $table->string('tax_number')->nullable()->after('sales_person');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['customer_no', 'tax_number']);
        });
    }
};