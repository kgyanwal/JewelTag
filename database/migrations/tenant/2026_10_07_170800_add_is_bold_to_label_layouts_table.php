<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('label_layouts') && !Schema::hasColumn('label_layouts', 'is_bold')) {
            Schema::table('label_layouts', function (Blueprint $table) {
                $table->boolean('is_bold')->default(false)->after('font_size');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('label_layouts', 'is_bold')) {
            Schema::table('label_layouts', function (Blueprint $table) {
                $table->dropColumn('is_bold');
            });
        }
    }
};