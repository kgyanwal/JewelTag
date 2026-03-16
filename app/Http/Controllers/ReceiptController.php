<?php

namespace App\Http\Controllers;

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
}