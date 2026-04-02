<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->string('legal_name')->nullable()->after('name'); // Finest Gem LLC
            $table->string('website')->nullable()->after('domain_url'); // thedsq.com
            $table->string('tagline')->nullable()->after('legal_name'); // e.g., "Albuquerque's Custom Jeweler"
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn(['legal_name', 'website', 'tagline']);
        });
    }
};
