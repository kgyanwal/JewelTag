<?php

namespace App\Http\Controllers;

use App\Models\CustomOrder;
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

public function customOrderReceipt(Request $request, CustomOrder $customOrder)
{
    // Force fresh load with all relationships
    $customOrder->load(['customer', 'payments', 'staff']);
    $customOrder->refresh(); // ← ensures latest data

    $pdf = Pdf::loadView('receipts.custom-order-deposit', [
        'order'  => $customOrder,
        'is_pdf' => true,
    ]);

    $pdf->setPaper('letter', 'portrait');
    $filename = "DEPOSIT_{$customOrder->order_no}.pdf";
    return $pdf->stream($filename);
}
public function printLaybuy(Request $request, \App\Models\Laybuy $laybuy)
    {
        $laybuy->load(['customer', 'sale.items.productItem', 'laybuyPayments']);
        $store = \App\Models\Store::first();

        $paymentId = $request->query('payment_id');
        $source    = $request->query('source');

        $laybuyPayments = $laybuy->laybuyPayments->map(fn($p) => (object)[
            'id' => $p->id, 'source' => 'laybuy', 'amount' => $p->amount, 
            'method' => $p->payment_method, 'date' => $p->created_at
        ]);

        $salePayments = $laybuy->sale ? $laybuy->sale->payments->map(fn($p) => (object)[
            'id' => $p->id, 'source' => 'payment', 'amount' => $p->amount, 
            'method' => $p->method, 'date' => $p->paid_at ?? $p->created_at
        ]) : collect();

        // Remove duplicates
        $filteredSalePayments = $salePayments->filter(function($sp) use ($laybuyPayments) {
            foreach ($laybuyPayments as $lp) {
                if ($sp->amount == $lp->amount && abs(\Carbon\Carbon::parse($sp->date)->diffInSeconds(\Carbon\Carbon::parse($lp->date))) < 60) {
                    return false;
                }
            }
            return true;
        });

        $allPayments = collect()
            ->concat($laybuyPayments)->concat($filteredSalePayments)
            ->sortBy('date')->values();

        $targetPayment = null;
        $runningPaid = 0;

        // 🚀 THE FIX: Calculate running balance dynamically up to the clicked receipt
        foreach ($allPayments as $p) {
            $runningPaid += $p->amount;
            if ($p->id == $paymentId && $p->source == $source) {
                $targetPayment = $p;
                break;
            }
        }

        // If no specific payment clicked, default to the latest
        if (!$targetPayment && $allPayments->count() > 0) {
            $targetPayment = $allPayments->last();
            $runningPaid = $allPayments->sum('amount');
        }

        $currentPaymentAmount = $targetPayment ? $targetPayment->amount : 0;
        $balanceBefore        = max(0, $laybuy->total_amount - ($runningPaid - $currentPaymentAmount));
        $balanceAfter         = max(0, $laybuy->total_amount - $runningPaid);

        $pdf = Pdf::loadView('receipts.laybuy', [
            'laybuy'               => $laybuy,
            'store'                => $store,
            'targetPayment'        => $targetPayment,
            'currentPaymentAmount' => $currentPaymentAmount,
            'balanceBefore'        => $balanceBefore,
            'balanceAfter'         => $balanceAfter,
            'paymentDate'          => $targetPayment ? \Carbon\Carbon::parse($targetPayment->date) : now(),
            'paymentMethod'        => $targetPayment ? strtoupper($targetPayment->method) : 'N/A',
            'is_pdf'               => true,
        ]);

        $pdf->setPaper('letter', 'portrait');
        return $pdf->stream("LAYBUY_{$laybuy->laybuy_no}.pdf");
    }
}