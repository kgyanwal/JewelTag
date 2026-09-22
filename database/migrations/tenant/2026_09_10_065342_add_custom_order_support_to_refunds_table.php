<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('refunds', function (Blueprint $table) {
            // 🚀 sale_id must become nullable — a deposit refund on a Custom
            // Order that hasn't converted to a Sale yet has no sale_id at all.
            $table->unsignedBigInteger('sale_id')->nullable()->change();
            $table->foreignId('custom_order_id')->nullable()->after('sale_id')
                ->constrained('custom_orders')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('refunds', function (Blueprint $table) {
            $table->dropConstrainedForeignId('custom_order_id');
            $table->unsignedBigInteger('sale_id')->nullable(false)->change();
        });
    }
};