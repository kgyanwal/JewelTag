<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->string('license_key')->unique();
            $table->string('tenant_id')->nullable();
            $table->string('plan')->default('essential'); // essential, professional, enterprise
            $table->string('status')->default('active'); // active, expired, suspended
            $table->integer('max_users')->default(5);
            $table->timestamp('expires_at')->nullable();
            $table->string('licensed_to')->nullable(); 
            $table->string('licensed_email')->nullable();
            $table->timestamps();
            
            // Link to your central tenants table
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};