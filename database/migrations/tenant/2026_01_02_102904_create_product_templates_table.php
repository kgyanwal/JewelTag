<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. "Tiffany Style Solitaire"
            $table->string('sku_prefix'); // e.g. "RNG-SOL"
            $table->string('category'); // e.g. "Ring", "Necklace"
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_templates');
    }
};