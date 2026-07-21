<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('custom_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('custom_orders', 'sale_id_for_deposit_credit')) {
                $table->unsignedBigInteger('sale_id_for_deposit_credit')->nullable()->after('sale_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('custom_orders', function (Blueprint $table) {
            $table->dropColumn('sale_id_for_deposit_credit');
        });
    }
};