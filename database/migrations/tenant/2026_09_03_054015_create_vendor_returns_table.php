<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('refund_id')->nullable()->constrained('refunds')->nullOnDelete();
            $table->foreignId('product_item_id')->constrained('product_items')->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('stock_no');
            $table->decimal('cost_price', 12, 2)->default(0);
            $table->decimal('return_credit_expected', 12, 2)->default(0);
            $table->enum('status', ['pending', 'shipped', 'credited', 'rejected'])->default('pending');
            $table->string('rma_number')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('credited_at')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        if (!Schema::hasColumn('product_items', 'returned_to_vendor_at')) {
            Schema::table('product_items', function (Blueprint $table) {
                $table->timestamp('returned_to_vendor_at')->nullable()->after('status');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_returns');
        Schema::table('product_items', function (Blueprint $table) {
            $table->dropColumn('returned_to_vendor_at');
        });
    }
};