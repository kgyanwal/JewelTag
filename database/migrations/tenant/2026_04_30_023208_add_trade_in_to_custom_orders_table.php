<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up(): void
{
    Schema::table('custom_orders', function (Blueprint $table) {
        $table->boolean('has_trade_in')->default(false)->after('warranty_charge');
        $table->decimal('trade_in_value', 12, 2)->default(0)->after('has_trade_in');
        $table->string('trade_in_receipt_no')->nullable()->after('trade_in_value');
        $table->text('trade_in_description')->nullable()->after('trade_in_receipt_no');
    });
}

public function down(): void
{
    Schema::table('custom_orders', function (Blueprint $table) {
        $table->dropColumn([
            'has_trade_in',
            'trade_in_value', 
            'trade_in_receipt_no',
            'trade_in_description'
        ]);
    });
}
};
