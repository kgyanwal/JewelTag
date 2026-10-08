<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sale_drafts', function (Blueprint $t) {
            $t->id();
            $t->string('draft_id', 64)->unique();
            $t->string('name')->nullable();
            $t->string('staff_name')->nullable()->index();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->unsignedBigInteger('customer_id')->nullable()->index();
            $t->unsignedInteger('item_count')->default(0);
            $t->decimal('total', 12, 2)->default(0);
            $t->longText('data');
            $t->string('status', 20)->default('open')->index();
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('sale_drafts'); }
};