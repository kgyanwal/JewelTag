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
    Schema::create('repairs', function (Blueprint $table) {
        $table->id();
        $table->string('repair_no')->unique(); // RPR-20260205-001
        $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
        $table->foreignId('store_id')->constrained();
        $table->foreignId('staff_id')->constrained('users');

        // Tracking Store History
        $table->boolean('is_from_store_stock')->default(false);
        $table->foreignId('original_product_id')->nullable()->constrained('product_items')->nullOnDelete();

        // Item Details
        $table->string('item_description'); 
        $table->text('reported_issue');
        $table->text('repair_notes')->nullable();

        // Financials
        $table->decimal('estimated_cost', 12, 2)->default(0);
        $table->decimal('final_cost', 12, 2)->nullable();
        
        $table->string('status')->default('received'); // received, in_progress, ready, delivered
        $table->timestamps();
    });

    // Link sale items to repairs
    Schema::table('sale_items', function (Blueprint $table) {
        $table->foreignId('repair_id')->nullable()->constrained()->nullOnDelete();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repairs');
    }
};
