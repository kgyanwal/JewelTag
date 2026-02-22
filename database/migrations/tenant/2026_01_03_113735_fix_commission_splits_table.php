<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commission_splits', function (Blueprint $table) {
            // 1. Drop the old User link (if it exists)
            if (Schema::hasColumn('commission_splits', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }

            // 2. Add the new Sales Assistant link
            $table->foreignId('sales_assistant_id')
                  ->after('sale_id')
                  ->constrained('sales_assistants')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('commission_splits', function (Blueprint $table) {
            $table->dropForeign(['sales_assistant_id']);
            $table->dropColumn('sales_assistant_id');
            $table->foreignId('user_id')->constrained();
        });
    }
};