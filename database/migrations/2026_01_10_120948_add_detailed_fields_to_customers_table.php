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
    Schema::table('customers', function (Blueprint $table) {
        // Details & Marketing
        $table->string('last_name')->nullable();
        $table->string('company')->nullable();
        $table->string('tax_number')->nullable();
        $table->boolean('is_active')->default(true);
        $table->string('sales_person')->nullable();
        
        // Address
        $table->string('street')->nullable();
        $table->string('suburb')->nullable();
        $table->string('state')->nullable();
        $table->string('country')->default('Australia');
        $table->string('postcode')->nullable();

        // Contact
        $table->string('home_phone')->nullable();
        $table->string('work_phone')->nullable();
        $table->string('fax')->nullable();
        $table->string('facebook')->nullable();
        $table->string('twitter')->nullable();
        $table->string('preferred_contact')->nullable();
        $table->boolean('exclude_from_mailing')->default(false);

        // Jewelry Specifics
        $table->string('age_bracket')->nullable();
        $table->string('gender')->nullable();
        $table->string('gold_preference')->nullable();
        $table->string('how_found_store')->nullable();
        $table->string('purchase_reason')->nullable();
        $table->string('purchasing_for')->nullable();
        $table->text('special_interests')->nullable();

        // Finger Sizes (Stored as JSON or individual columns - individual is easier for filtering)
        $table->string('lh_thumb')->nullable(); $table->string('rh_thumb')->nullable();
        $table->string('lh_index')->nullable(); $table->string('rh_index')->nullable();
        $table->string('lh_middle')->nullable(); $table->string('rh_middle')->nullable();
        $table->string('lh_ring')->nullable(); $table->string('rh_ring')->nullable();
        $table->string('lh_pinky')->nullable(); $table->string('rh_pinky')->nullable();

        // Spouse & Loyalty
        $table->string('spouse_name')->nullable();
        $table->string('spouse_email')->nullable();
        $table->string('loyalty_card_number')->nullable();
        $table->text('comments')->nullable();
        $table->text('customer_alerts')->nullable();
        $table->string('image')->nullable();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            //
        });
    }
};
