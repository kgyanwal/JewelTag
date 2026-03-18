<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::table('repairs', function (Blueprint $table) {
        // We use sales_person_id for the single ID (from your SQL error)
        // and we'll add sales_person_list as a json column for your multiple selection
        $table->unsignedBigInteger('sales_person_id')->nullable()->after('reported_issue');
        $table->json('sales_person_list')->nullable()->after('sales_person_id');
    });
}

public function down(): void
{
    Schema::table('repairs', function (Blueprint $table) {
        $table->dropColumn(['sales_person_id', 'sales_person_list']);
    });
}
};
