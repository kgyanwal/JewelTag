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
    Schema::create('custom_orders', function (Blueprint $table) {
        $table->id();
        $table->string('order_no')->unique();
        $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
        $table->foreignId('store_id')->constrained()->cascadeOnDelete();
        $table->foreignId('staff_id')->constrained('users')->cascadeOnDelete();

        // Design Specs
        $table->string('metal_type'); // e.g., 14k Gold, Platinum
        $table->decimal('metal_weight', 8, 2)->nullable();
        $table->decimal('diamond_weight', 8, 2)->nullable();
        $table->string('size')->nullable();
        $table->text('design_notes')->nullable();
        $table->string('reference_image')->nullable();

        // Financials
        $table->decimal('budget', 12, 2)->nullable();
        $table->decimal('quoted_price', 12, 2);
        $table->string('status')->default('draft'); // draft, quoted, approved, in_production, completed

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_orders');
    }
};
