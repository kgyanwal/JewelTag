<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rfid_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_name');
            $table->string('session_type'); // inventory_scan, checkout, receiving, audit
            $table->string('device_type');  // fx9600, fx7500, rfd40, rfd8500, atr7000, mc9300
            $table->string('device_ip')->nullable();
            $table->integer('device_port')->default(5084);
            $table->string('status')->default('idle'); // idle, connecting, scanning, completed, error
            $table->integer('total_scanned')->default(0);
            $table->integer('matched')->default(0);
            $table->integer('unmatched')->default(0);
            $table->json('scan_results')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfid_sessions');
    }
};