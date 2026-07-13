<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exchanges', function (Blueprint $table) {
            $table->id();

            // Core links
            $table->string('exchange_no')->unique(); // EX-260711-1
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users'); // staff who initiated
            $table->foreignId('approved_by')->nullable()->constrained('users'); // manager

            // Original sale being exchanged
            $table->foreignId('original_sale_id')->constrained('sales');
            $table->json('returned_items'); // snapshot of what's being returned [{sale_item_id, product_item_id, description, credit_amount}]
            $table->decimal('total_credit', 12, 2)->default(0); // total credited to customer

            // New sale (can be created fresh or existing)
            $table->foreignId('new_sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->decimal('new_sale_amount', 12, 2)->default(0);

            // Financial
            $table->decimal('difference_amount', 12, 2)->default(0); // new - credit (positive = customer pays, negative = store refunds)
            $table->string('difference_payment_method')->nullable(); // how difference was collected/refunded
            $table->string('exchange_type')->default('same_value'); // same_value | upgrade | downgrade

            // Status & approval
            $table->enum('status', ['pending_approval', 'approved', 'rejected', 'completed', 'cancelled'])->default('pending_approval');
            $table->text('reason')->nullable(); // why customer wants exchange
            $table->text('staff_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchanges');
    }
};