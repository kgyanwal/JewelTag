<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class SetDefaultLabelLayoutFixed extends Migration
{
    public function up()
    {
        $defaults = [
            'stock_no' => [
                'x_pos' => 60,
                'y_pos' => 6,
                'height' => 0,
                'width' => 0,
                'font_size' => 1,
            ],
            'desc' => [
                'x_pos' => 60,
                'y_pos' => 9,
                'height' => 0,
                'width' => 0,
                'font_size' => 1,
            ],
            'barcode' => [
                'x_pos' => 60,
                'y_pos' => 11,
                'height' => 15,
                'width' => 0.2,
                'font_size' => 1,
            ],
            'price' => [
                'x_pos' => 60,
                'y_pos' => 18,
                'height' => 0,
                'width' => 0,
                'font_size' => 1,
            ],
            'dwmtmk' => [
                'x_pos' => 60,
                'y_pos' => 22,
                'height' => 0,
                'width' => 0,
                'font_size' => 1,
            ],
            'deptcat' => [
                'x_pos' => 60,
                'y_pos' => 24,
                'height' => 0,
                'width' => 0,
                'font_size' => 1,
            ],
            'rfid' => [
                'x_pos' => 60,
                'y_pos' => 26,
                'height' => 0,
                'width' => 0,
                'font_size' => 1,
            ],
        ];
        
        foreach ($defaults as $fieldId => $values) {
            DB::table('label_layouts')
                ->updateOrInsert(
                    ['field_id' => $fieldId],
                    $values
                );
        }
        
        error_log("Default label layout set successfully (height/width = 0)");
    }

    public function down()
    {
        // Optional revert
    }
}