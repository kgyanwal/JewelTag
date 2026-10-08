<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('loyalty_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->index();
            // earn | reversal | redeem | bonus | adjust | expire
            $table->string('type', 20)->index();
            $table->integer('points');                 // signed: + adds, - removes
            $table->integer('balance_after');
            $table->string('tier_at_time', 50)->nullable();
            $table->decimal('multiplier', 5, 2)->nullable();
            $table->decimal('qualifying_amount', 12, 2)->nullable();

            $table->unsignedBigInteger('sale_id')->nullable()->index();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->unsignedBigInteger('refund_id')->nullable();
            $table->unsignedBigInteger('custom_order_id')->nullable();

            $table->string('reason')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('idempotency_key')->nullable()->unique();
            $table->timestamps();

            $table->index(['customer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_transactions');
    }
};