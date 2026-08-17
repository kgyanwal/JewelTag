<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Plans table
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');                    // Basic, Pro, Enterprise
            $table->string('slug')->unique();          // basic, pro, enterprise
            $table->text('description')->nullable();
            $table->decimal('price_monthly', 10, 2)->default(0);
            $table->decimal('price_yearly', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_popular')->default(false);
            $table->integer('sort_order')->default(0);

            // Hard limits (-1 = unlimited)
            $table->integer('max_users')->default(3);
            $table->integer('max_items')->default(1000);
            $table->integer('max_locations')->default(1);
            $table->integer('max_custom_orders_month')->default(30);
            $table->integer('max_repairs_month')->default(50);

            // Feature flags
            $table->boolean('feature_diamond_vault')->default(false);
            $table->boolean('feature_layaway')->default(false);
            $table->boolean('feature_api')->default(false);
            $table->boolean('feature_sms')->default(false);
            $table->boolean('feature_crm')->default(false);
            $table->boolean('feature_advanced_analytics')->default(false);
            $table->boolean('feature_exchange')->default(true);
            $table->boolean('feature_rfid')->default(false);
            $table->boolean('feature_multi_store')->default(false);
            $table->boolean('feature_white_label')->default(false);
            $table->boolean('feature_custom_integrations')->default(false);

            $table->json('custom_features')->nullable(); // extra bullet points for landing page
            $table->timestamps();
        });

        // Tenant plan assignments
        Schema::table('tenants', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->string('plan_status')->default('active'); // active, trial, suspended, cancelled
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('plan_expires_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->string('suspension_reason')->nullable();
        });

        // Seed default plans
        DB::table('plans')->insert([
            [
                'name'              => 'Basic',
                'slug'              => 'basic',
                'description'       => 'For small jewelry shops',
                'price_monthly'     => 299,
                'price_yearly'      => 299,
                'is_active'         => true,
                'is_popular'        => false,
                'sort_order'        => 1,
                'max_users'         => 3,
                'max_items'         => 1000,
                'max_locations'     => 1,
                'max_custom_orders_month' => 30,
                'max_repairs_month' => 50,
                'feature_diamond_vault'       => false,
                'feature_layaway'             => false,
                'feature_api'                 => false,
                'feature_sms'                 => false,
                'feature_crm'                 => false,
                'feature_advanced_analytics'  => false,
                'feature_exchange'            => true,
                'feature_rfid'                => false,
                'feature_multi_store'         => false,
                'feature_white_label'         => false,
                'feature_custom_integrations' => false,
                'custom_features'  => json_encode(['Basic POS','Customer management','Repair order tracking']),
                'created_at'       => now(), 'updated_at' => now(),
            ],
            [
                'name'              => 'Pro + CRM',
                'slug'              => 'pro',
                'description'       => 'The full counter-to-customer system',
                'price_monthly'     => 499,
                'price_yearly'      => 499,
                'is_active'         => true,
                'is_popular'        => true,
                'sort_order'        => 2,
                'max_users'         => 15,
                'max_items'         => -1,
                'max_locations'     => 2,
                'max_custom_orders_month' => -1,
                'max_repairs_month' => -1,
                'feature_diamond_vault'       => true,
                'feature_layaway'             => true,
                'feature_api'                 => true,
                'feature_sms'                 => true,
                'feature_crm'                 => true,
                'feature_advanced_analytics'  => true,
                'feature_exchange'            => true,
                'feature_rfid'                => true,
                'feature_multi_store'         => true,
                'feature_white_label'         => false,
                'feature_custom_integrations' => false,
                'custom_features'  => json_encode(['Advanced POS + Layaway + Financing','CRM — loyalty, SMS & email marketing','RFID tracking & live metal pricing','Multi-store sync & advanced analytics','API access']),
                'created_at'       => now(), 'updated_at' => now(),
            ],
            [
                'name'              => 'Enterprise',
                'slug'              => 'enterprise',
                'description'       => 'For multi-store retailers',
                'price_monthly'     => 0,
                'price_yearly'      => 0,
                'is_active'         => true,
                'is_popular'        => false,
                'sort_order'        => 3,
                'max_users'         => -1,
                'max_items'         => -1,
                'max_locations'     => -1,
                'max_custom_orders_month' => -1,
                'max_repairs_month' => -1,
                'feature_diamond_vault'       => true,
                'feature_layaway'             => true,
                'feature_api'                 => true,
                'feature_sms'                 => true,
                'feature_crm'                 => true,
                'feature_advanced_analytics'  => true,
                'feature_exchange'            => true,
                'feature_rfid'                => true,
                'feature_multi_store'         => true,
                'feature_white_label'         => true,
                'feature_custom_integrations' => true,
                'custom_features'  => json_encode(['Multi-store management','Custom integrations','Dedicated account manager','24/7 premium support']),
                'created_at'       => now(), 'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['plan_id','plan_status','trial_ends_at','plan_expires_at','suspended_at','suspension_reason']);
        });
        Schema::dropIfExists('plans');
    }
};