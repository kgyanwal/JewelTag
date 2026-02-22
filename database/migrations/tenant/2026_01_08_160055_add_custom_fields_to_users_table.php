<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 1. Employee Code
            if (!Schema::hasColumn('users', 'employee_code')) {
                $table->string('employee_code')->nullable()->unique()->after('email');
            }

            // 2. Phone (Check first)
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable()->after('email');
            }

            // 3. Pin Code
            if (!Schema::hasColumn('users', 'pin_code')) {
                $table->string('pin_code')->nullable()->after('password');
            }

            // 4. Commission Rate (This caused your error, so we check it now)
            if (!Schema::hasColumn('users', 'base_commission_rate')) {
                $table->decimal('base_commission_rate', 5, 2)->default(0.00)->after('password');
            }

            // 5. Is Active
            if (!Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('password');
            }
            
            // 6. Store ID
            if (!Schema::hasColumn('users', 'store_id')) {
                $table->unsignedBigInteger('store_id')->nullable()->after('password');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Only drop if they exist to prevent errors on rollback
            if (Schema::hasColumn('users', 'employee_code')) $table->dropColumn('employee_code');
            if (Schema::hasColumn('users', 'phone')) $table->dropColumn('phone');
            if (Schema::hasColumn('users', 'pin_code')) $table->dropColumn('pin_code');
            if (Schema::hasColumn('users', 'base_commission_rate')) $table->dropColumn('base_commission_rate');
            if (Schema::hasColumn('users', 'is_active')) $table->dropColumn('is_active');
            if (Schema::hasColumn('users', 'store_id')) $table->dropColumn('store_id');
        });
    }
};