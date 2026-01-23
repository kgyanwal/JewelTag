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
    Schema::create('daily_closings', function (Blueprint $table) {
        $table->id();
        $table->date('closing_date')->unique(); // Ensures only one post per day
        $table->json('expected_data');          // Stores the system totals
        $table->json('actual_data');            // Stores what you typed in
        $table->decimal('total_expected', 15, 2);
        $table->decimal('total_actual', 15, 2);
        $table->foreignId('user_id')->constrained(); // Tracks which admin posted it
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_closings');
    }
};
