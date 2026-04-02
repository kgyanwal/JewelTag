<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   // In the new migration file:
public function up()
{
    Schema::table('stores', function (Blueprint $table) {
        $table->string('street')->nullable()->after('domain_url');
        $table->string('postcode')->nullable()->after('state');
        $table->string('country')->default('United States')->after('postcode');
    });
}

public function down()
{
    Schema::table('stores', function (Blueprint $table) {
        $table->dropColumn(['street', 'postcode', 'country']);
    });
}

};
