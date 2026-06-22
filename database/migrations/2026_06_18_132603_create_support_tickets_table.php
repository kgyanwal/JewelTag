<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('store_name')->nullable();
            $table->string('subject');
            $table->string('category')->default('other'); // bug, billing, feature_request, training, other
            $table->string('priority')->default('normal'); // low, normal, high, urgent
            $table->string('status')->default('open'); // open, in_progress, resolved, closed
            $table->string('created_by_name')->nullable();
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->timestamp('last_reply_at')->nullable();
            $table->timestamp('sla_due_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};