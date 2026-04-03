<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->string('barcode')->nullable()->after('id');
            $table->string('from_tenant')->nullable()->after('to_store_id');
            $table->string('to_tenant')->nullable()->after('from_tenant');
            $table->json('item_snapshot')->nullable()->after('notes');
            $table->unsignedBigInteger('product_item_id')->nullable()->after('item_snapshot');
            $table->string('transferred_by')->nullable()->after('product_item_id');
            $table->string('actioned_by')->nullable()->after('transferred_by');
            $table->timestamp('actioned_at')->nullable()->after('actioned_by');
        });
    }

    public function down(): void
    {
        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->dropColumn([
                'barcode',
                'from_tenant',
                'to_tenant',
                'item_snapshot',
                'product_item_id',
                'transferred_by',
                'actioned_by',
                'actioned_at',
            ]);
        });
    }
};