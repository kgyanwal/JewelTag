<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_closings', function (Blueprint $table) {
            // We use 'json' because it looks like Laravel is trying to pass an array/JSON object
            // (e.g., {"cash": 46000, "visa": 11000})
            $table->json('sales_summary')->nullable()->after('actual_data');
        });
    }

    public function down(): void
    {
        Schema::table('daily_closings', function (Blueprint $table) {
            $table->dropColumn('sales_summary');
        });
    }
};