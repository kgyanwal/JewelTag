<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class ReceiptController extends Controller
{
    public function show(Request $request, Sale $sale)
    {
        // Security: Ensure the link is valid (optional: you can use signed URLs)
        if (!$sale) {
            abort(404);
        }

        // Generate the PDF exactly like your Mailable does
        $pdf = Pdf::loadView('receipts.sale', [
            'sale' => $sale,
            'is_pdf' => true
        ]);

        // Stream the PDF to the browser (opens it on their phone)
        return $pdf->stream("Invoice_{$sale->invoice_number}.pdf");
    }
}