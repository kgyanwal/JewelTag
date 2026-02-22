<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_items', function (Blueprint $table) {
            $table->id();

            // 1. Relationships
            $table->foreignId('product_template_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained();
            $table->foreignId('store_id')->constrained();
            $table->foreignId('memo_customer_id')->nullable()->constrained('customers'); // For consignment out

            // 2. Identity
            $table->string('barcode')->unique();
            $table->string('rfid_code')->nullable()->index(); // Index allows fast search

            // 3. Jewelry Attributes
            $table->string('purity'); // e.g., 18k, 22k
            $table->decimal('gross_weight', 10, 3)->default(0);
            $table->decimal('stone_weight', 10, 3)->default(0);
            // Net weight is calculated in Model, not stored

            // 4. Money & Logistics
            $table->decimal('cost_price', 12, 2);
            $table->decimal('retail_price', 12, 2)->nullable();
            $table->date('received_at')->nullable();

            // 5. Status Logic
            $table->string('status')->default('in_stock'); // in_stock, sold, memo, repair
            $table->boolean('is_locked')->default(false); // Locked if in cart
            $table->text('remarks')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_items');
    }
};