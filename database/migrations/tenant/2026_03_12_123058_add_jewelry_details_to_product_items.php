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

        if (!Schema::hasColumn('product_items', 'shape')) {
            $table->string('shape')->nullable()->after('custom_description');
        }

        if (!Schema::hasColumn('product_items', 'color')) {
            $table->string('color')->nullable()->after('shape');
        }

        if (!Schema::hasColumn('product_items', 'clarity')) {
            $table->string('clarity')->nullable()->after('color');
        }

        if (!Schema::hasColumn('product_items', 'cut')) {
            $table->string('cut')->nullable()->after('clarity');
        }

        if (!Schema::hasColumn('product_items', 'polish')) {
            $table->string('polish')->nullable()->after('cut');
        }

        if (!Schema::hasColumn('product_items', 'symmetry')) {
            $table->string('symmetry')->nullable()->after('polish');
        }

        if (!Schema::hasColumn('product_items', 'fluorescence')) {
            $table->string('fluorescence')->nullable()->after('symmetry');
        }

        if (!Schema::hasColumn('product_items', 'measurements')) {
            $table->string('measurements')->nullable()->after('fluorescence');
        }

        if (!Schema::hasColumn('product_items', 'certificate_number')) {
            $table->string('certificate_number')->nullable()->after('measurements');
        }

        if (!Schema::hasColumn('product_items', 'certificate_agency')) {
            $table->string('certificate_agency')->nullable()->after('certificate_number');
        }

        if (!Schema::hasColumn('product_items', 'markup')) {
            $table->decimal('markup', 8, 2)->nullable()->after('retail_price');
        }

        if (!Schema::hasColumn('product_items', 'web_item')) {
            $table->boolean('web_item')->default(false)->after('web_price');
        }

        if (!Schema::hasColumn('product_items', 'date_in')) {
            $table->date('date_in')->nullable()->after('status');
        }

        if (!Schema::hasColumn('product_items', 'is_lab_grown')) {
            $table->boolean('is_lab_grown')->default(false);
        }
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
