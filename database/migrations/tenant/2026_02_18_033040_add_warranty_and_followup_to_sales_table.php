<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->boolean('has_warranty')->default(false)->after('shipping_charges');
            $table->string('warranty_period')->nullable()->after('has_warranty'); // e.g., "1 Year"
            $table->date('follow_up_date')->nullable()->after('warranty_period');
            $table->date('second_follow_up_date')->nullable()->after('follow_up_date');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['has_warranty', 'warranty_period', 'follow_up_date', 'second_follow_up_date']);
        });
    }
};