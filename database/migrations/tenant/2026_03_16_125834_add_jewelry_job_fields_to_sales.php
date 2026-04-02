<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // 1. Remove the old boolean if it's there
            if (Schema::hasColumn('sales', 'is_resize')) {
                $table->dropColumn('is_resize');
            }

            // 2. Add Job Type (The core of the new system)
            if (!Schema::hasColumn('sales', 'job_type')) {
                $table->string('job_type')->nullable()->after('status');
            }

            // 3. Size columns (Skip if they already exist from your previous migration)
            if (!Schema::hasColumn('sales', 'current_size')) {
                $table->string('current_size')->nullable()->after('job_type');
            }
            if (!Schema::hasColumn('sales', 'target_size')) {
                $table->string('target_size')->nullable()->after('current_size');
            }

            // 4. New detailed fields
            if (!Schema::hasColumn('sales', 'job_instructions')) {
                $table->text('job_instructions')->nullable()->after('target_size');
            }
            if (!Schema::hasColumn('sales', 'metal_type')) {
                $table->string('metal_type')->nullable()->after('job_instructions');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn([
                'job_type', 
                'current_size', 
                'target_size', 
                'job_instructions', 
                'metal_type'
            ]);
        });
    }
};