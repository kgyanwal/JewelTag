<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('loyalty_holds', function (Blueprint $t) {
    $t->id();
    $t->string('draft_id', 64)->unique();
    $t->string('draft_name')->nullable();
    $t->unsignedBigInteger('customer_id')->index();
    $t->integer('points')->default(0);
    $t->decimal('discount', 10, 2)->default(0);
    $t->unsignedBigInteger('user_id')->nullable();
    $t->string('status', 20)->default('held')->index();
    $t->unsignedBigInteger('sale_id')->nullable();
    $t->timestamp('expires_at')->nullable();
    $t->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loyalty_holds');
    }
};
