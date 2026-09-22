<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Add backup_codes_enabled after two_factor_enabled
            if (!Schema::hasColumn('tenants', 'backup_codes_enabled')) {
                $table->boolean('backup_codes_enabled')
                      ->default(true)
                      ->after('two_factor_enabled');
            }

            // Add backup codes storage after backup_codes_enabled
            if (!Schema::hasColumn('tenants', 'two_factor_backup_codes')) {
                $table->text('two_factor_backup_codes')
                      ->nullable()
                      ->after('backup_codes_enabled');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'two_factor_backup_codes')) {
                $table->dropColumn('two_factor_backup_codes');
            }
            if (Schema::hasColumn('tenants', 'backup_codes_enabled')) {
                $table->dropColumn('backup_codes_enabled');
            }
        });
    }
};