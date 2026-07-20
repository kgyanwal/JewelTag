<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE exchanges MODIFY status ENUM('pending_approval','approved','completed','rejected','exchanged') NOT NULL DEFAULT 'pending_approval'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE exchanges MODIFY status ENUM('pending_approval','approved','completed','rejected') NOT NULL DEFAULT 'pending_approval'");
    }
};