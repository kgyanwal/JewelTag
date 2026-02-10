<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Check if column exists before adding
        if (!Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('username')->nullable()->after('name');
            });
        }

        // 2. Make email and phone nullable safely
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('phone')->nullable()->change();
        });

        // 3. Fill empty usernames for existing users so we can set it to UNIQUE later
        $users = DB::table('users')->whereNull('username')->orWhere('username', '')->get();
        foreach ($users as $user) {
            $baseName = strtolower(str_replace(' ', '', $user->name));
            DB::table('users')
                ->where('id', $user->id)
                ->update(['username' => $baseName . $user->id]);
        }

        // 4. Finally, set the unique constraint now that data is clean
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable(false)->unique()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'username')) {
                $table->dropColumn('username');
            }
        });
    }
};