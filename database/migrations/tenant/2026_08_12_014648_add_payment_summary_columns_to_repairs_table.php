<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repairs', function (Blueprint $table) {
            if (!Schema::hasColumn('repairs', 'is_split_payment')) {
                $table->boolean('is_split_payment')->default(false)->after('balance_due');
            }
            if (!Schema::hasColumn('repairs', 'payment_method')) {
                $table->string('payment_method')->nullable()->after('is_split_payment');
            }
            if (!Schema::hasColumn('repairs', 'repair_subtotal')) {
                $table->decimal('repair_subtotal', 10, 2)->default(0)->after('payment_method');
            }
            if (!Schema::hasColumn('repairs', 'repair_tax')) {
                $table->decimal('repair_tax', 10, 2)->default(0)->after('repair_subtotal');
            }
            if (!Schema::hasColumn('repairs', 'repair_total')) {
                $table->decimal('repair_total', 10, 2)->default(0)->after('repair_tax');
            }
        });
    }

    public function down(): void
    {
        Schema::table('repairs', function (Blueprint $table) {
            $table->dropColumn(array_filter([
                Schema::hasColumn('repairs', 'is_split_payment') ? 'is_split_payment' : null,
                Schema::hasColumn('repairs', 'payment_method')   ? 'payment_method'   : null,
                Schema::hasColumn('repairs', 'repair_subtotal')  ? 'repair_subtotal'  : null,
                Schema::hasColumn('repairs', 'repair_tax')       ? 'repair_tax'       : null,
                Schema::hasColumn('repairs', 'repair_total')     ? 'repair_total'     : null,
            ]));
        });
    }
};