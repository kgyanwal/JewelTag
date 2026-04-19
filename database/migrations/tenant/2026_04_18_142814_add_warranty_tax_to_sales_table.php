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
        // We add the column to store the specific tax amount for warranties
        $table->decimal('tax_amount_warranty', 12, 2)->default(0)->after('tax_amount');
        
        // Optional: If you want to remember if the user checked the box for later edits
        $table->boolean('is_warranty_taxed')->default(false)->after('has_warranty');
    });
}

public function down(): void
{
    Schema::table('sales', function (Blueprint $table) {
        $table->dropColumn(['tax_amount_warranty', 'is_warranty_taxed']);
    });
}
};
