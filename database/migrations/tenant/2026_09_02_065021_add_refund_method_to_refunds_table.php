<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('refunds', function (Blueprint $table) {
            $table->enum('refund_method', ['cash', 'store_credit'])->default('cash')->after('refund_amount');
        });

        // Ensure customers has a credit_balance column — skip if it already exists
        if (!Schema::hasColumn('customers', 'credit_balance')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->decimal('credit_balance', 12, 2)->default(0)->after('email');
            });
        }
    }

    public function down(): void
    {
        Schema::table('refunds', function (Blueprint $table) {
            $table->dropColumn('refund_method');
        });
    }
};