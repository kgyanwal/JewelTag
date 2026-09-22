<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // For Auth App (TOTP) — stores the base32 secret
            $table->text('two_factor_secret')->nullable()->after('pin_code');

            // For SMS OTP — stores hashed code + expiry
            $table->string('two_factor_code')->nullable()->after('two_factor_secret');
            $table->timestamp('two_factor_expires_at')->nullable()->after('two_factor_code');

            // Which method the user has set up
            // null = not set up yet, 'totp' = auth app, 'sms' = SMS
            $table->string('two_factor_method')->nullable()->after('two_factor_expires_at');

            // Whether 2FA setup is confirmed/complete
            $table->boolean('two_factor_confirmed')->default(false)->after('two_factor_method');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'two_factor_secret',
                'two_factor_code',
                'two_factor_expires_at',
                'two_factor_method',
                'two_factor_confirmed',
            ]);
        });
    }
};