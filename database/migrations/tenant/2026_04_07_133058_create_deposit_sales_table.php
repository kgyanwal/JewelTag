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
        // Check if the table exists before trying to create it
        if (!Schema::hasTable('deposit_sales')) {
            Schema::create('deposit_sales', function (Blueprint $table) {
                $table->id();
                $table->string('deposit_no')->unique();
                
                // Foreign Keys linking to customers and sales
                $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
                $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
                
                // Financials
                $table->decimal('total_amount', 12, 2);
                $table->decimal('amount_paid', 12, 2)->default(0.00);
                $table->decimal('balance_due', 12, 2);
                
                // Status & Staff
                $table->string('status')->default('active');
                $table->string('sales_person')->nullable();
                $table->json('staff_list')->nullable();
                
                // Dates
                $table->date('start_date')->nullable();
                $table->date('last_paid_date')->nullable();
                $table->date('due_date')->nullable();
                
                // Notes
                $table->text('notes')->nullable();
                
                // Timestamps
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deposit_sales');
    }
};