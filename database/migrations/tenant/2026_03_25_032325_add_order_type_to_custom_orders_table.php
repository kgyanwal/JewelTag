<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('custom_orders', 'order_type')) {
            Schema::table('custom_orders', function (Blueprint $table) {
                $table->string('order_type')->default('custom')->after('id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('custom_orders', 'order_type')) {
            Schema::table('custom_orders', function (Blueprint $table) {
                $table->dropColumn('order_type');
            });
        }
    }
};
