<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'repair_id')) {
                $table->foreignId('repair_id')->nullable()->after('custom_order_id');
            }
        });

        Schema::table('repairs', function (Blueprint $table) {
            if (!Schema::hasColumn('repairs', 'amount_paid')) {
                $table->decimal('amount_paid', 10, 2)->default(0)->after('final_cost');
            }
            if (!Schema::hasColumn('repairs', 'balance_due')) {
                $table->decimal('balance_due', 10, 2)->default(0)->after('amount_paid');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'repair_id')) {
                $table->dropColumn('repair_id');
            }
        });

        Schema::table('repairs', function (Blueprint $table) {
            $table->dropColumn(array_filter([
                Schema::hasColumn('repairs', 'amount_paid') ? 'amount_paid' : null,
                Schema::hasColumn('repairs', 'balance_due') ? 'balance_due' : null,
            ]));
        });
    }
};