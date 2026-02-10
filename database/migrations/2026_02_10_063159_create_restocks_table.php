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
    Schema::create('restocks', function (Blueprint $table) {
        $table->id();
        $table->foreignId('refund_id')->constrained()->onDelete('cascade');
        $table->foreignId('product_item_id')->constrained()->onDelete('cascade');
        $table->string('stock_no');
        
        // 🔹 Ensure this name matches exactly: 'salesperson_name'
        $table->string('salesperson_name'); 
        
        $table->decimal('restock_fee', 12, 2)->default(0);
        $table->enum('quality_check', ['excellent', 'good', 'damaged', 'altered'])->default('excellent');
        $table->text('notes')->nullable();
        $table->enum('status', ['pending', 'completed'])->default('pending');
        $table->foreignId('finalized_by')->nullable()->constrained('users');
        $table->timestamp('finalized_at')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restocks');
    }
};
