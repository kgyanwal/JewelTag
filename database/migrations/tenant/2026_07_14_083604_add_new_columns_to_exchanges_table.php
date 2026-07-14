<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exchanges', function (Blueprint $table) {
            if (!Schema::hasColumn('exchanges', 'new_items'))
                $table->json('new_items')->nullable()->after('returned_items');
            if (!Schema::hasColumn('exchanges', 'new_items_subtotal'))
                $table->decimal('new_items_subtotal', 12, 2)->default(0)->after('new_items');
            if (!Schema::hasColumn('exchanges', 'new_items_tax'))
                $table->decimal('new_items_tax', 12, 2)->default(0)->after('new_items_subtotal');
            if (!Schema::hasColumn('exchanges', 'is_split_payment'))
                $table->boolean('is_split_payment')->default(false)->after('difference_payment_method');
            if (!Schema::hasColumn('exchanges', 'split_payments'))
                $table->json('split_payments')->nullable()->after('is_split_payment');
            if (!Schema::hasColumn('exchanges', 'amount_received'))
                $table->decimal('amount_received', 12, 2)->default(0)->after('split_payments');
            if (!Schema::hasColumn('exchanges', 'sales_person_list'))
                $table->string('sales_person_list')->nullable()->after('amount_received');
            if (!Schema::hasColumn('exchanges', 'returned_source'))
                $table->string('returned_source')->default('our_store')->after('sales_person_list');
        });
    }

    public function down(): void
    {
        Schema::table('exchanges', function (Blueprint $table) {
            $table->dropColumn(['new_items','new_items_subtotal','new_items_tax','is_split_payment','split_payments','amount_received','sales_person_list','returned_source']);
        });
    }
};