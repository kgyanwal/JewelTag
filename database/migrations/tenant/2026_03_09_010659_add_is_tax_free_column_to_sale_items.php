<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('sale_items', 'is_tax_free')) {
            Schema::table('sale_items', function (Blueprint $table) {
                $table->boolean('is_tax_free')->default(false)->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn('is_tax_free');
        });
    }
};