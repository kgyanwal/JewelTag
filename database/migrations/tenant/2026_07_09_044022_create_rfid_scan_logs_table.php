<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rfid_scan_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rfid_session_id')->constrained()->cascadeOnDelete();
            $table->string('epc_code');
            $table->string('rfid_code'); // normalized
            $table->integer('rssi')->nullable(); // signal strength
            $table->integer('antenna_port')->nullable();
            $table->integer('read_count')->default(1);
            $table->foreignId('product_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('match_status'); // matched, unmatched, duplicate
            $table->timestamp('scanned_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfid_scan_logs');
    }
};