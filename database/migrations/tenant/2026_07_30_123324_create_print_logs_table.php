<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('print_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('barcode')->nullable();
            $table->string('rfid_code')->nullable();
            $table->enum('print_type', ['barcode', 'rfid', 'bulk'])->default('barcode');
            $table->string('printer_ip')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_name')->nullable();
            $table->enum('status', ['success', 'failed'])->default('success');
            $table->string('error_message')->nullable();
            $table->timestamps();

            $table->index(['product_item_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('print_logs');
    }
};