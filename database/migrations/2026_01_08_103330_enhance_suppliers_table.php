<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            // Add new columns
            $table->string('supplier_code')->unique()->nullable()->after('id');
            $table->string('type')->default('wholesaler')->after('company_name');
            $table->string('tax_number')->nullable()->after('type');
            $table->string('website_username')->nullable()->after('tax_number');
            $table->string('website_password')->nullable()->after('website_username');
            
            // Order Conditions
            $table->integer('payment_terms_days')->default(30)->after('lead_time_days');
            $table->decimal('order_limit', 12, 2)->nullable()->after('payment_terms_days');
            $table->decimal('credit_limit', 12, 2)->nullable()->after('order_limit');
            $table->decimal('discount_percentage', 5, 2)->default(0)->after('credit_limit');
            
            // Contact Details
            $table->string('mobile')->nullable()->after('phone');
            $table->string('fax')->nullable()->after('mobile');
            $table->string('website')->nullable()->after('email');
            
            // Physical Address (split from old address field)
            $table->string('physical_street')->nullable()->after('address');
            $table->string('physical_suburb')->nullable()->after('physical_street');
            $table->string('physical_city')->nullable()->after('physical_suburb');
            $table->string('physical_state')->nullable()->after('physical_city');
            $table->string('physical_country')->default('Australia')->after('physical_state');
            $table->string('physical_postcode')->nullable()->after('physical_country');
            
            // Postal Address
            $table->string('postal_street')->nullable()->after('physical_postcode');
            $table->string('postal_suburb')->nullable()->after('postal_street');
            $table->string('postal_city')->nullable()->after('postal_suburb');
            $table->string('postal_state')->nullable()->after('postal_city');
            $table->string('postal_country')->default('Australia')->after('postal_state');
            $table->string('postal_postcode')->nullable()->after('postal_country');
            
            // Performance Metrics
            $table->integer('total_orders')->default(0);
            $table->decimal('total_spent', 12, 2)->default(0);
            $table->decimal('average_lead_time', 5, 2)->nullable();
            $table->decimal('quality_rating', 3, 2)->nullable();
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_preferred')->default(false);
            $table->text('notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            // Remove all new columns
            $table->dropColumn([
                'supplier_code',
                'type',
                'tax_number',
                'website_username',
                'website_password',
                'payment_terms_days',
                'order_limit',
                'credit_limit',
                'discount_percentage',
                'mobile',
                'fax',
                'website',
                'physical_street',
                'physical_suburb',
                'physical_city',
                'physical_state',
                'physical_country',
                'physical_postcode',
                'postal_street',
                'postal_suburb',
                'postal_city',
                'postal_state',
                'postal_country',
                'postal_postcode',
                'total_orders',
                'total_spent',
                'average_lead_time',
                'quality_rating',
                'is_active',
                'is_preferred',
                'notes',
            ]);
        });
    }
};