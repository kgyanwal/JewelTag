<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->integer('max_laybuys')->default(100)->after('max_repairs_month');
        });

        // Set sensible defaults on existing rows
        DB::table('plans')->where('slug', 'basic')->update(['max_laybuys' => 100]);
        DB::table('plans')->where('slug', 'pro')->update(['max_laybuys' => -1]); // unlimited
        DB::table('plans')->where('slug', 'enterprise')->update(['max_laybuys' => -1]);
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('max_laybuys');
        });
    }
};