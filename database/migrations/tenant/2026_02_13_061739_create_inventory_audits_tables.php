<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    // database/migrations/xxxx_create_inventory_audits_tables.php
    public function up() {
        // Tracks the overall audit session
        Schema::create('inventory_audits', function (Blueprint $table) {
            $table->id();
            $table->string('session_name'); 
            $table->foreignId('user_id')->constrained(); // Who is scanning
            $table->enum('status', ['open', 'completed'])->default('open');
            $table->timestamps();
        });

        // Tracks every individual scan event
        Schema::create('audit_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_id')->constrained('inventory_audits')->onDelete('cascade');
            $table->foreignId('product_item_id')->constrained();
            $table->string('rfid_code')->index();
            $table->timestamp('scanned_at')->useCurrent(); // The requested timestamp
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_audits_tables');
    }
};
