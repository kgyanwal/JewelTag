<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // We use unsignedBigInteger because foreignId()->constrained() 
            // sometimes acts up in multi-tenant environments if the stores table 
            // is in the same tenant DB (which it is in your case).
            $table->unsignedBigInteger('store_id')->nullable()->after('sale_id');
            
            // Add index for performance on the search page
            $table->index('store_id');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('store_id');
        });
    }
};