<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add width column
        Schema::table('label_layouts', function (Blueprint $table) {
            $table->decimal('width', 5, 2)->default(0.25)->after('height');
        });
        
        // Update barcode to HALF height
        DB::table('label_layouts')
            ->where('field_id', 'barcode')
            ->update([
                'height' => 15,  // HALF of default 30
                'width' => 0.2,  // Narrow width
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        Schema::table('label_layouts', function (Blueprint $table) {
            $table->dropColumn('width');
        });
        
        // Revert barcode height
        DB::table('label_layouts')
            ->where('field_id', 'barcode')
            ->update(['height' => 30]);
    }
};




//  **IF any problem then this migration is okay**s
// use Illuminate\Database\Migrations\Migration;
// use Illuminate\Database\Schema\Blueprint;
// use Illuminate\Support\Facades\Schema;

// return new class extends Migration
// {
//     public function up(): void
//     {
//         Schema::table('label_layouts', function (Blueprint $table) {

//             if (!Schema::hasColumn('label_layouts', 'height')) {
//                 $table->decimal('height', 5, 2)->default(0);
//             }

//             if (!Schema::hasColumn('label_layouts', 'width')) {
//                 $table->decimal('width', 5, 2)->default(0);
//             }
//         });
//     }

//     public function down(): void
//     {
//         Schema::table('label_layouts', function (Blueprint $table) {
//             if (Schema::hasColumn('label_layouts', 'width')) {
//                 $table->dropColumn('width');
//             }
//             // ❗ Do NOT drop height unless you are 100% sure
//         });
//     }
// };
