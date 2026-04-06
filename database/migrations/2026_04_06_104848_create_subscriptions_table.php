<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            
            // Link to the Stancl/Tenancy stores
            // Note: Stancl/Tenancy usually uses string IDs for tenants.
            $table->string('tenant_id')->index();
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();

            // Core Billing Info
            $table->string('plan_tier')->default('starter'); // starter, professional, enterprise
            $table->string('billing_cycle')->default('monthly'); // monthly, annually
            $table->string('status')->default('active'); // trialing, active, past_due, canceled, unpaid
            
            // Cycle Tracking
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('canceled_at')->nullable();

            // Legal & Compliance (Master Subscription Agreement)
            $table->string('msa_version')->default('v1.0-2026');
            $table->timestamp('msa_agreed_at')->nullable();
            $table->string('msa_agreed_ip')->nullable();
            $table->string('contract_pdf_path')->nullable();

            $table->timestamps();
            $table->softDeletes(); // Industry standard for billing records
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};