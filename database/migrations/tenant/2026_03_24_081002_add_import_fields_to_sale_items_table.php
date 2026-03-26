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
    Schema::table('sale_items', function (Blueprint $table) {
        $table->string('stock_no_display')->nullable()->after('is_non_stock');
        $table->string('import_source')->nullable()->after('stock_no_display');
    });
}

public function down(): void
{
    Schema::table('sale_items', function (Blueprint $table) {
        $table->dropColumn(['stock_no_display', 'import_source']);
    });
}
};
