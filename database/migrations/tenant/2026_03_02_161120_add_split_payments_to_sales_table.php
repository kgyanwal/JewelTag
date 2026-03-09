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
    Schema::table('sales', function (Blueprint $table) {
        // Stores [{method: 'cash', amount: 500}, {method: 'visa', amount: 300}]
        if (!Schema::hasColumn('sales', 'split_payments')) {
            $table->json('split_payments')->nullable()->after('payment_method');
        }
        
        // Ensure final_total and other financial columns exist and are decimal
        $table->decimal('final_total', 15, 2)->change();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            //
        });
    }
};
