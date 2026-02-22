<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->boolean('is_split_payment')->default(false)->after('payment_method');
            
            // Payment 1
            $table->string('payment_method_1')->nullable();
            $table->decimal('payment_amount_1', 10, 2)->nullable();
            
            // Payment 2
            $table->string('payment_method_2')->nullable();
            $table->decimal('payment_amount_2', 10, 2)->nullable();
            
            // Payment 3
            $table->string('payment_method_3')->nullable();
            $table->decimal('payment_amount_3', 10, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn([
                'is_split_payment',
                'payment_method_1', 'payment_amount_1',
                'payment_method_2', 'payment_amount_2',
                'payment_method_3', 'payment_amount_3',
            ]);
        });
    }
};