<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('custom_orders', function (Blueprint $table) {
            // Add Product Name if missing
            if (!Schema::hasColumn('custom_orders', 'product_name')) {
                $table->string('product_name')->nullable()->after('staff_id');
            }
            // Ensure staff_id exists (Sales Person)
            if (!Schema::hasColumn('custom_orders', 'staff_id')) {
                $table->foreignId('staff_id')->nullable()->constrained('users');
            }
        });
    }

    public function down(): void
    {
        Schema::table('custom_orders', function (Blueprint $table) {
            $table->dropColumn(['product_name']);
        });
    }
};