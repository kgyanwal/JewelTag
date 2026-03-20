<?php

namespace App\Http\Controllers;

use App\Models\Repair;
use App\Models\Sale;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    // Change $sale to $record to match the Route parameter {record}
    public function show(Request $request, Sale $record)
    {
        $record->load(['customer', 'items.productItem', 'store']);
        
        $type = $request->query('type', 'standard'); // standard, gift, job

        $view = match($type) {
            'gift' => 'receipts.gift',
            'job'  => 'receipts.job',
            default => 'receipts.sale',
        };

        $pdf = Pdf::loadView($view, [
            'sale' => $record, // We still pass it as 'sale' to the Blade template
            'is_pdf' => true
        ]);

        $filename = strtoupper($type) . "_{$record->invoice_number}.pdf";
        return $pdf->stream($filename);
    }


public function printRepair(Repair $repair)
{
    // 1. Load necessary relationships for the packet
    $repair->load(['customer', 'salesPerson', 'originalProduct', 'store']);

    // 2. Load the specific repair-job-packet view
    $pdf = Pdf::loadView('receipts.repair-job-packet', [
        'repair' => $repair,
    ]);

    // 3. Set paper size to Letter (common for workshop packets)
    $pdf->setPaper('letter', 'portrait');

    // 4. Stream the PDF to the browser with a clean filename
    $filename = "REPAIR_{$repair->repair_no}.pdf";
    return $pdf->stream($filename);
}
}