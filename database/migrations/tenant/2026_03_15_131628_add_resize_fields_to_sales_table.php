<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'is_resize')) {
                $table->boolean('is_resize')->default(false)->after('status');
            }
            if (!Schema::hasColumn('sales', 'current_size')) {
                $table->string('current_size')->nullable()->after('is_resize');
            }
            if (!Schema::hasColumn('sales', 'target_size')) {
                $table->string('target_size')->nullable()->after('current_size');
            }
            // date_required already exists, so we skip it or modify it if needed
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['is_resize', 'current_size', 'target_size']);
        });
    }
};