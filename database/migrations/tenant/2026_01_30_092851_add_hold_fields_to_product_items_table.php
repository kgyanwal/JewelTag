<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_items', function (Blueprint $table) {
            // Update status to include 'on_hold'
            // If you want to keep existing data, you might need to adjust
            $table->string('status')->default('in_stock')->change();
            
            $table->string('hold_reason')->nullable();
            $table->dateTime('hold_until')->nullable();
            $table->foreignId('held_by_sale_id')->nullable()->constrained('sales')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('product_items', function (Blueprint $table) {
            $table->string('status')->default('in_stock')->change();
            $table->dropColumn(['hold_reason', 'hold_until', 'held_by_sale_id']);
        });
    }
};