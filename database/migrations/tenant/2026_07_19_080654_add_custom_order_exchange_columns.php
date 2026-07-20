<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exchanges', function (Blueprint $table) {
            if (!Schema::hasColumn('exchanges', 'from_custom_order'))
                $table->boolean('from_custom_order')->default(false)->after('returned_source');
            if (!Schema::hasColumn('exchanges', 'co_item_in_production'))
                $table->boolean('co_item_in_production')->default(true)->after('from_custom_order');
            if (!Schema::hasColumn('exchanges', 'co_item_arrived'))
                $table->boolean('co_item_arrived')->default(false)->after('co_item_in_production');
        });

        // Also add 'exchanged' to CustomOrder statuses — handled in model $fillable, no schema change needed.
        // Also add 'exchanged' status to exchanges — already handled via string column.
    }

    public function down(): void
    {
        Schema::table('exchanges', function (Blueprint $table) {
            $table->dropColumn(['from_custom_order','co_item_in_production','co_item_arrived']);
        });
    }
};