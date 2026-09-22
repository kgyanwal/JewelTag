<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('msa_signer_name')->nullable()->after('msa_agreed_ip');
            $table->string('msa_signer_title')->nullable()->after('msa_signer_name');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['msa_signer_name', 'msa_signer_title']);
        });
    }
};