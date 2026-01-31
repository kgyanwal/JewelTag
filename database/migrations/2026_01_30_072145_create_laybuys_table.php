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
    Schema::create('laybuys', function (Blueprint $table) {
        $table->id();
        $table->string('laybuy_no')->unique();
        $table->foreignId('customer_id')->constrained();
        // 🔹 ADD THIS: Link to the Quick Sale record to see products
        $table->foreignId('sale_id')->nullable()->constrained()->onDelete('cascade'); 
        $table->decimal('total_amount', 12, 2);
        $table->decimal('amount_paid', 12, 2)->default(0);
        $table->decimal('balance_due', 12, 2);
        // 🔹 STATUS: Use 'inprogress' to match your Quick Sale logic
        $table->string('status')->default('active'); 
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laybuys');
    }
};
