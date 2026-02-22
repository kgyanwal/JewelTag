<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_items', function (Blueprint $table) {
            // 1. Classification Fields (Matches your screenshot dropdowns)
            $table->string('supplier_code')->nullable()->after('supplier_id');
            $table->string('form_type')->nullable()->after('supplier_code'); // Ring, Pearl, Watch
            $table->string('department')->nullable()->after('form_type');    // Bridal, Gold
            $table->string('category')->nullable()->after('department');     // Pendant, Earring
            $table->string('metal_type')->nullable()->after('category');     // 14k Gold, Silver
            
            // 2. Pricing Fields
            $table->decimal('web_price', 12, 2)->nullable()->after('retail_price');
            $table->decimal('discount_percent', 5, 2)->default(0)->after('web_price');
            
            // 3. Stock Details
            $table->string('serial_number')->nullable()->after('barcode');
            $table->integer('component_qty')->default(1)->after('serial_number');
            $table->integer('qty')->default(1)->after('component_qty');

            // 4. Make template optional (since we are assembling raw stock)
            $table->foreignId('product_template_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('product_items', function (Blueprint $table) {
            $table->dropColumn([
                'supplier_code', 'form_type', 'department', 'category', 
                'metal_type', 'web_price', 'discount_percent', 
                'serial_number', 'component_qty', 'qty'
            ]);
        });
    }
};