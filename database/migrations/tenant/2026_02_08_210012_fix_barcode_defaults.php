<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class FixBarcodeDefaults extends Migration
{
    public function up()
    {
        // Set all fields to proper defaults with barcode height=4
        $defaults = [
            'stock_no' => [
                'x_pos' => 60,
                'y_pos' => 6,
                'font_size' => 1,
                'height' => 0,
                'width' => 0,
            ],
            'desc' => [
                'x_pos' => 60,
                'y_pos' => 9,
                'font_size' => 1,
                'height' => 0,
                'width' => 0,
            ],
            'barcode' => [
                'x_pos' => 60,
                'y_pos' => 12,
                'font_size' => 1,
                'height' => 4,      // **FIXED: Height = 4 (not 8, not 2)**
                'width' => 0.2,     // Decimal value works
            ],
            'price' => [
                'x_pos' => 60,
                'y_pos' => 19,
                'font_size' => 1,
                'height' => 0,
                'width' => 0,
            ],
            'dwmtmk' => [
                'x_pos' => 60,
                'y_pos' => 22,
                'font_size' => 1,
                'height' => 0,
                'width' => 0,
            ],
            'deptcat' => [
                'x_pos' => 60,
                'y_pos' => 24,
                'font_size' => 1,
                'height' => 0,
                'width' => 0,
            ],
            'rfid' => [
                'x_pos' => 60,
                'y_pos' => 30,
                'font_size' => 1,
                'height' => 0,
                'width' => 0,
            ],
        ];
        
        foreach ($defaults as $fieldId => $values) {
            DB::table('label_layouts')->updateOrInsert(
                ['field_id' => $fieldId],
                $values
            );
        }
        
        // Log to Laravel log
        \Illuminate\Support\Facades\Log::info('Database barcode defaults set: height=4, width=0.2');
    }

    public function down()
    {
        // Optional: Revert to old values if needed
        DB::table('label_layouts')->where('field_id', 'barcode')->update([
            'height' => 8,
            'width' => 0.3,
        ]);
    }
}