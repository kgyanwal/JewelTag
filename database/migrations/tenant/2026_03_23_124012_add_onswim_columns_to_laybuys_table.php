<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('laybuys', function (Blueprint $table) {
            if (!Schema::hasColumn('laybuys', 'staff_list')) {
                $table->json('staff_list')->nullable()->after('sales_person');
            }
            if (!Schema::hasColumn('laybuys', 'last_paid_date')) {
                $table->date('last_paid_date')->nullable()->after('start_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('laybuys', function (Blueprint $table) {
            $table->dropColumn(['staff_list', 'last_paid_date']);
        });
    }
};