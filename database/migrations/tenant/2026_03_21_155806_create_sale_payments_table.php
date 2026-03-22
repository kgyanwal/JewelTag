<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_payments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
    $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
    $table->unsignedBigInteger('custom_order_id')->nullable()->index();
    // ↑ grouped with foreign keys — all "who does this payment belong to" columns together

    $table->decimal('amount', 10, 2);
    $table->string('payment_method');
    $table->string('original_payment_type')->nullable();

    $table->date('payment_date');
    $table->boolean('is_deposit')->default(false);
    $table->boolean('is_layby')->default(false);

    $table->string('sales_person')->nullable();
    $table->text('notes')->nullable();

    $table->timestamp('imported_at')->nullable();
    $table->timestamps();
    $table->softDeletes();
    // ↑ always last — Laravel convention is timestamps() then softDeletes()
    // so deleted_at sits after updated_at in the column order

    $table->index(['sale_id', 'payment_date']);
    // ↑ composite indexes go after all column definitions
    // Laravel builds indexes after columns, and grouping them at the bottom
    // makes them easy to spot and modify later
});
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_payments');
    }
};