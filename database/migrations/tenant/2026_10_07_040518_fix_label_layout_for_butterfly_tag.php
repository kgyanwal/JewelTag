<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Services\ZebraPrinterService;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('label_layouts')) {
            (new ZebraPrinterService())->setDefaultLayout();
        }
    }

    public function down(): void
    {
        // No-op: this migration only corrects layout values.
    }
};