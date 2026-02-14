<?php

// app/Http/Controllers/Api/InventoryAuditController.php
namespace App\Http\Controllers\Api;

use App\Models\{ProductItem, AuditItem, InventoryAudit};
use App\Mail\MissingInventoryReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Mail, DB};

class InventoryAuditController extends Controller {

    public function recordScan(Request $request) {
        $item = ProductItem::where('rfid_code', $request->rfid)->first();
        
        if ($item) {
            $scan = AuditItem::firstOrCreate([
                'audit_id' => $request->audit_id,
                'product_item_id' => $item->id,
                'rfid_code' => $request->rfid
            ]);

            // Log activity for the "Expandable Table"
            activity()
                ->performedOn($item)
                ->causedBy($request->user())
                ->log("Scanned {$item->barcode} at Kiosk");

            return response()->json(['success' => true, 'item' => $item->custom_description]);
        }
        return response()->json(['success' => false, 'message' => 'Unknown Tag']);
    }

    public function completeAudit($id) {
        $audit = InventoryAudit::findOrFail($id);
        
        // Find items that should be here but weren't scanned
        $missing = ProductItem::where('status', 'in_stock')
            ->whereNotExists(function ($query) use ($id) {
                $query->select(DB::raw(1))->from('audit_items')
                      ->whereColumn('audit_items.product_item_id', 'product_items.id')
                      ->where('audit_id', $id);
            })->get();

        Mail::to(['admin@yourstore.com'])->send(new MissingInventoryReport($missing, $audit->session_name));
        $audit->update(['status' => 'completed']);
        
        return response()->json(['success' => true, 'missing_count' => $missing->count()]);
    }
}