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
    Schema::create('sales_assistants', function (Blueprint $table) {
        $table->id();
        $table->foreignId('store_id')->constrained(); // Which store do they work at?
        $table->string('name');
        $table->string('employee_code')->unique(); // e.g. SA-001
        $table->string('pin_code'); // The 4-digit PIN for authorizing sales
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_assistants');
    }
};
