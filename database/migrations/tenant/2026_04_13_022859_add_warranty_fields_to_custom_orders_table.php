<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('custom_orders', function (Blueprint $table) {
            $table->boolean('has_warranty')->default(false)->after('is_tax_free');
            $table->string('warranty_period')->nullable()->after('has_warranty');
            $table->decimal('warranty_charge', 10, 2)->default(0)->after('warranty_period');
        });
    }

    public function down(): void
    {
        Schema::table('custom_orders', function (Blueprint $table) {
            $table->dropColumn(['has_warranty', 'warranty_period', 'warranty_charge']);
        });
    }
};