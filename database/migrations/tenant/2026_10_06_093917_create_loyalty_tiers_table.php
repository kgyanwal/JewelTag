<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('loyalty_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();                 // matches customers.loyalty_tier
            $table->decimal('min_spend', 12, 2)->default(0);  // rolling 12-month qualifying spend
            $table->decimal('earn_multiplier', 5, 2)->default(1.00);
            $table->decimal('discount_percent', 5, 2)->nullable(); // reserved, unused for now
            $table->json('perks')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Placeholder defaults - editable later. Change freely before Step 2.
        $now = now();
        DB::table('loyalty_tiers')->insert([
            ['name' => 'Standard', 'slug' => 'standard', 'min_spend' => 0,     'earn_multiplier' => 1.00, 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Silver',   'slug' => 'silver',   'min_spend' => 2500,  'earn_multiplier' => 1.25, 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Gold',     'slug' => 'gold',     'min_spend' => 10000, 'earn_multiplier' => 1.50, 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_tiers');
    }
};