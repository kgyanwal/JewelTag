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
    Schema::create('deletion_requests', function (Blueprint $table) {
        $table->id();
        $table->foreignId('product_item_id')->constrained()->onDelete('cascade');
        $table->foreignId('user_id')->constrained(); // Requester
        $table->text('reason');
        $table->enum('status', ['pending', 'verified_by_admin', 'approved', 'rejected'])->default('pending');
        $table->foreignId('verified_by')->nullable()->constrained('users');
        $table->foreignId('approved_by')->nullable()->constrained('users');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deletion_requests');
    }
};
