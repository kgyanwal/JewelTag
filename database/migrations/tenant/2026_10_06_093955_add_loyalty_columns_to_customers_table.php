<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->decimal('lifetime_spend', 12, 2)->default(0)->after('loyalty_points');
            $table->decimal('rolling_12m_spend', 12, 2)->default(0)->after('lifetime_spend');
            $table->timestamp('tier_updated_at')->nullable()->after('rolling_12m_spend');
            $table->boolean('tier_locked')->default(false)->after('tier_updated_at'); // manual override
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['lifetime_spend', 'rolling_12m_spend', 'tier_updated_at', 'tier_locked']);
        });
    }
};