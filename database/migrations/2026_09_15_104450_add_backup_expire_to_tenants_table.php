<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'backup_codes_expires_at')) {
                $table->timestamp('backup_codes_expires_at')
                      ->nullable()
                      ->after('two_factor_backup_codes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'backup_codes_expires_at')) {
                $table->dropColumn('backup_codes_expires_at');
            }
        });
    }
};