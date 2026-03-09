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
    Schema::table('product_items', function (Blueprint $table) {
        // Jewelry Specification Columns
        $table->string('shape')->nullable()->after('custom_description');
        $table->string('color')->nullable()->after('shape');
        $table->string('clarity')->nullable()->after('color');
        $table->string('cut')->nullable()->after('clarity');
        $table->string('polish')->nullable()->after('cut');
        $table->string('symmetry')->nullable()->after('polish');
        $table->string('fluorescence')->nullable()->after('symmetry');
        $table->string('measurements')->nullable()->after('fluorescence');
        $table->string('certificate_number')->nullable()->after('measurements');
        $table->string('certificate_agency')->nullable()->after('certificate_number');
        
        // Financial & Tracking Columns from OnSwim
        $table->decimal('markup', 8, 2)->nullable()->after('retail_price');
        $table->boolean('web_item')->default(false)->after('web_price');
        $table->date('date_in')->nullable()->after('status');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_items', function (Blueprint $table) {
            //
        });
    }
};
