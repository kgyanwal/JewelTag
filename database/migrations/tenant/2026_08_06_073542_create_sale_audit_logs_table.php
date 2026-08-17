<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name')->nullable();
            $table->string('field_label');      // e.g. "Item Sale Price (STOCK #G1234)"
            $table->string('old_value')->nullable();
            $table->string('new_value')->nullable();
            $table->string('severity')->default('info'); // info | warning | critical
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_audit_logs');
    }
};