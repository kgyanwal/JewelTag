<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class FixRfidPosition extends Migration
{
    public function up()
    {
        // Fix RFID y-position to 26 (was 30)
        DB::table('label_layouts')
            ->where('field_id', 'rfid')
            ->update(['y_pos' => 26]);
            
        \Illuminate\Support\Facades\Log::info('Fixed RFID y-position to 26');
    }

    public function down()
    {
        DB::table('label_layouts')
            ->where('field_id', 'rfid')
            ->update(['y_pos' => 30]);
    }
}