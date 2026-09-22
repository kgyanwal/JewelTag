<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('gateway')->nullable()->after('method');
            $table->string('gateway_txn_id')->nullable()->index()->after('gateway');
            $table->string('auth_code')->nullable()->after('gateway_txn_id');
            $table->string('card_last4', 4)->nullable()->after('auth_code');
            $table->string('card_brand')->nullable()->after('card_last4');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['gateway', 'gateway_txn_id', 'auth_code', 'card_last4', 'card_brand']);
        });
    }
};