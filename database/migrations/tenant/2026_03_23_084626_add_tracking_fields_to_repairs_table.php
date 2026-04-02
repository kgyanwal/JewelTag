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
    Schema::table('repairs', function (Blueprint $table) {
        if (!Schema::hasColumn('repairs', 'dropped_by'))
            $table->string('dropped_by')->nullable()->after('staff_id');

        if (!Schema::hasColumn('repairs', 'picked_up_by'))
            $table->string('picked_up_by')->nullable()->after('dropped_by');

        if (!Schema::hasColumn('repairs', 'date_dropped'))
            $table->date('date_dropped')->nullable()->after('picked_up_by');

        if (!Schema::hasColumn('repairs', 'date_picked_up'))
            $table->date('date_picked_up')->nullable()->after('date_dropped');

        if (!Schema::hasColumn('repairs', 'customer_pickup_date'))
            $table->date('customer_pickup_date')->nullable()->after('date_picked_up');

        if (!Schema::hasColumn('repairs', 'repair_location'))
            $table->string('repair_location')->nullable()->after('customer_pickup_date');

        if (!Schema::hasColumn('repairs', 'repair_notes'))
            $table->text('repair_notes')->nullable()->after('repair_location');
    });
}

public function down(): void
{
    Schema::table('repairs', function (Blueprint $table) {
        $table->dropColumn(array_filter([
            Schema::hasColumn('repairs', 'dropped_by')        ? 'dropped_by'        : null,
            Schema::hasColumn('repairs', 'picked_up_by')      ? 'picked_up_by'      : null,
            Schema::hasColumn('repairs', 'date_dropped')      ? 'date_dropped'      : null,
            Schema::hasColumn('repairs', 'date_picked_up')    ? 'date_picked_up'    : null,
            Schema::hasColumn('repairs', 'customer_pickup_date') ? 'customer_pickup_date' : null,
            Schema::hasColumn('repairs', 'repair_location')   ? 'repair_location'   : null,
            Schema::hasColumn('repairs', 'repair_notes')      ? 'repair_notes'      : null,
        ]));
    });
}
};
