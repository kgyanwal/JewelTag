<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('custom_orders', function (Blueprint $table) {
            // Vendor & Scheduling (Added after reference_image for organization)
            $table->string('vendor_name')->nullable()->after('reference_image');
            $table->text('vendor_info')->nullable()->after('vendor_name'); 
            $table->date('due_date')->nullable()->after('vendor_info'); // Internal Deadline
            $table->date('expected_delivery_date')->nullable()->after('due_date'); // Customer Pickup
            $table->date('follow_up_date')->nullable()->after('expected_delivery_date'); 
            
            // Notifications (Added after status)
            $table->boolean('is_customer_notified')->default(false)->after('status');
            $table->timestamp('notified_at')->nullable()->after('is_customer_notified');
        });
    }

    public function down(): void
    {
        Schema::table('custom_orders', function (Blueprint $table) {
            $table->dropColumn([
                'vendor_name', 
                'vendor_info', 
                'due_date', 
                'expected_delivery_date', 
                'follow_up_date',
                'is_customer_notified',
                'notified_at'
            ]);
        });
    }
};