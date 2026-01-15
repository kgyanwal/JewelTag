<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_items', function (Blueprint $table) {
            // Make the old 'purity' column optional since we use 'metal_type' now
            if (Schema::hasColumn('product_items', 'purity')) {
                $table->string('purity')->nullable()->change();
            }
            
            // Ensure weights have defaults so they don't cause similar errors
            if (Schema::hasColumn('product_items', 'gross_weight')) {
                $table->decimal('gross_weight', 10, 3)->default(0)->change();
            }
            
            if (Schema::hasColumn('product_items', 'stone_weight')) {
                $table->decimal('stone_weight', 10, 3)->default(0)->change();
            }
        });
    }

    public function down(): void
    {
        // No need to reverse this strictly for development
    }
};