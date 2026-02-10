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
    Schema::create('refunds', function (Blueprint $table) {
        $table->id();
        $table->string('refund_no')->unique(); 
        $table->foreignId('sale_id')->constrained()->onDelete('cascade');
        $table->foreignId('customer_id')->constrained();
        
        // Parameters requested
        $table->enum('quality_check', ['excellent', 'good', 'damaged', 'altered'])->default('excellent');
        $table->boolean('should_restock')->default(true);
        $table->text('remarks')->nullable();
        
        $table->decimal('refund_amount', 12, 2);
        $table->decimal('restocking_fee', 12, 2)->default(0);
        $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
        
        $table->foreignId('processed_by')->constrained('users'); 
        $table->foreignId('approved_by')->nullable()->constrained('users'); 
        $table->timestamp('approved_at')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
