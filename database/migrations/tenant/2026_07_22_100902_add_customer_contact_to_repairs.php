<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repairs', function (Blueprint $table) {
            if (!Schema::hasColumn('repairs', 'customer_called'))
                $table->boolean('customer_called')->default(false)->after('notified_at');
            if (!Schema::hasColumn('repairs', 'customer_texted'))
                $table->boolean('customer_texted')->default(false)->after('customer_called');
        });
    }

    public function down(): void
    {
        Schema::table('repairs', function (Blueprint $table) {
            $table->dropColumn(['customer_called', 'customer_texted']);
        });
    }
};