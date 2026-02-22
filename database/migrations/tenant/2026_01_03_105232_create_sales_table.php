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
    Schema::create('sales', function (Blueprint $table) {
        $table->id();
        $table->string('invoice_number')->unique(); // e.g. INV-2024-001
        $table->foreignId('customer_id')->constrained();
        $table->foreignId('store_id')->constrained(); // Where did the sale happen?
        
        // Money
        $table->decimal('subtotal', 12, 2)->default(0);
        $table->decimal('tax_amount', 12, 2)->default(0);
        $table->decimal('discount_amount', 12, 2)->default(0);
        $table->decimal('final_total', 12, 2)->default(0);
        
        // Status
        $table->string('payment_method')->default('cash'); // Card, Cash, Split
        $table->string('status')->default('completed'); // completed, voided, refund
        $table->text('notes')->nullable();
        
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
