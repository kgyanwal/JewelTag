<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_items', function (Blueprint $table) {
            // Primary display image
            $table->string('primary_image')->nullable()->after('custom_description');
            // JSON array of additional images (gallery)
            $table->json('gallery_images')->nullable()->after('primary_image');
        });
    }

    public function down(): void
    {
        Schema::table('product_items', function (Blueprint $table) {
            $table->dropColumn(['primary_image', 'gallery_images']);
        });
    }
};