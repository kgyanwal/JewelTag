<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateLabelLayoutsBarcode extends Migration
{
    public function up()
    {
        // Update barcode field to have proper height for small labels
        DB::table('label_layouts')
            ->where('field_id', 'barcode')
            ->update([
                'height' => 15,  // Small height for 150-dot label
                'width' => 0.2,  // Narrow width
                'y_pos' => 55,   // Position below description
                'x_pos' => 60,   // Centered
                'font_size' => 1,
            ]);
        
        // Also update other fields for better layout
        $updates = [
            'stock_no' => ['y_pos' => 10, 'font_size' => 3],
            'desc' => ['y_pos' => 35, 'font_size' => 2],
            'price' => ['y_pos' => 90, 'font_size' => 4], // Below barcode
            'dwmtmk' => ['y_pos' => 10, 'font_size' => 2],
            'deptcat' => ['y_pos' => 25, 'font_size' => 2],
            'rfid' => ['y_pos' => 40, 'font_size' => 2],
        ];
        
        foreach ($updates as $fieldId => $values) {
            DB::table('label_layouts')
                ->where('field_id', $fieldId)
                ->update($values);
        }
    }

    public function down()
    {
        // Optionally revert changes (if needed)
        DB::table('label_layouts')
            ->where('field_id', 'barcode')
            ->update([
                'height' => 30,  // Original value
                'width' => 0.3,  // Original value
                'y_pos' => 50,   // Original value
                'x_pos' => 50,   // Original value
            ]);
    }
}