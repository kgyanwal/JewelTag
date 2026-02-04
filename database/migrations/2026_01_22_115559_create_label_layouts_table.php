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
    Schema::create('label_layouts', function (Blueprint $table) {
        $table->id();
        $table->string('field_id')->unique(); // 'stock_no', 'price', etc.
        $table->integer('x_pos')->default(65);
        $table->integer('y_pos')->default(5);
        $table->integer('font_size')->default(3);
        $table->integer('height')->default(30); // Used for barcode height
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('label_layouts');
    }
};
