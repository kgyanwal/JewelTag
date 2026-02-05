<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // Add the missing link columns
            $table->foreignId('custom_order_id')->nullable()->constrained('custom_orders')->nullOnDelete();
            $table->foreignId('repair_id')->nullable()->constrained('repairs')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['custom_order_id']);
            $table->dropForeign(['repair_id']);
            $table->dropColumn(['custom_order_id', 'repair_id']);
        });
    }
};