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
    Schema::create('customers', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('phone')->unique(); // Vital for jewelry CRM
        $table->string('email')->nullable();
        $table->date('dob')->nullable(); // For Birthday Reports (CR009)
        $table->date('wedding_anniversary')->nullable(); // For Anniversary Reports (CR010)
        $table->text('address')->nullable();
        $table->string('city')->nullable();
        $table->string('loyalty_tier')->default('standard'); // Bronze, Silver, Gold
        $table->integer('loyalty_points')->default(0); 
        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
