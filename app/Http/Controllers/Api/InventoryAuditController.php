<?php

namespace App\Http\Controllers\Api;

// 🚀 FIX 1: Import the base Controller
use App\Http\Controllers\Controller; 
use App\Models\ProductItem;
use App\Models\AuditItem;
use App\Models\InventoryAudit;
use App\Mail\MissingInventoryReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

class InventoryAuditController extends Controller {

    public function recordScan(Request $request) {
        $item = ProductItem::where('rfid_code', $request->rfid)->first();
        
        if ($item) {
            // Record the scan in the database
            $scan = AuditItem::firstOrCreate([
                'audit_id' => $request->audit_id,
                'product_item_id' => $item->id,
                'rfid_code' => $request->rfid
            ]);

            // 🚀 FIX 2: Added a check for the activity helper
            if (function_exists('activity')) {
                activity()
                    ->performedOn($item)
                    ->causedBy($request->user())
                    ->log("Scanned {$item->barcode} at Kiosk");
            }

            return response()->json([
                'success' => true, 
                'item' => $item->custom_description
            ]);
        }
        
        return response()->json(['success' => false, 'message' => 'Unknown Tag'], 404);
    }

    public function completeAudit($id) {
        $audit = InventoryAudit::findOrFail($id);
        
        // Find items that are 'in_stock' but NOT in the audit_items table for this session
        $missing = ProductItem::where('status', 'in_stock')
            ->whereNotExists(function ($query) use ($id) {
                $query->select(DB::raw(1))
                      ->from('audit_items')
                      ->whereColumn('audit_items.product_item_id', 'product_items.id')
                      ->where('audit_id', $id);
            })->get();

        // Send the report to the Mac admin
        Mail::to(['admin@yourstore.com'])->send(new MissingInventoryReport($missing, $audit->session_name));
        
        $audit->update(['status' => 'completed']);
        
        return response()->json(['success' => true, 'missing_count' => $missing->count()]);
    }
}