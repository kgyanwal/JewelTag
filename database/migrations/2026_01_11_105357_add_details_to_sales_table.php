<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // Shipping
            $table->decimal('shipping_charges', 10, 2)->default(0);
            $table->boolean('shipping_taxed')->default(false);
            $table->string('carrier')->nullable(); // FedEx, UPS
            $table->string('tracking_number')->nullable();

            // Job / Repair Tracking
            $table->date('date_required')->nullable();
            $table->boolean('job_complete')->default(false);
            $table->string('collected_by')->nullable();
            $table->dateTime('collected_on')->nullable();
            $table->string('repair_number')->nullable();
            
            // Tax Setup
            $table->string('tax_type')->default('standard'); // 'standard', 'no_tax'
        });

        Schema::table('sale_items', function (Blueprint $table) {
            // Add Job Description for individual items
            $table->string('job_description')->nullable();
        });
    }

    public function down(): void
    {
        // Drop columns if needed
    }
};