<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wishlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_item_id')->constrained()->cascadeOnDelete(); // Links to real inventory
            $table->foreignId('sales_person_id')->nullable()->constrained('users'); // Who added it
            
            $table->text('notes')->nullable(); // e.g., "Likes the style but waiting for discount"
            $table->date('follow_up_date')->nullable();
            $table->string('status')->default('active'); // active, purchased, cancelled, contacted
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wishlists');
    }
};