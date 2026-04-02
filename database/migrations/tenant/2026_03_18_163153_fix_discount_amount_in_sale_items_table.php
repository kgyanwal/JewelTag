<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            // 🚀 We use change() to modify the existing column safely
            // We set it to NOT NULL and provide a default of 0
            $table->decimal('discount_amount', 12, 2)->default(0.00)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            // To rollback, we'd typically make it nullable again or remove default
            $table->decimal('discount_amount', 12, 2)->nullable()->default(null)->change();
        });
    }
};