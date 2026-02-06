<?php

namespace App\Mail;

use App\Models\Sale;
use Aws\Ses\SesClient;
use Aws\Exception\AwsException;
use Barryvdh\DomPDF\Facade\Pdf; // 🔹 PDF Generator
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CustomerReceipt extends Mailable
{
    use Queueable, SerializesModels;

    public $sale;

    public function __construct(Sale $sale)
    {
        $this->sale = $sale;
    }

    public function sendDirectly()
    {
        try {
            $sesClient = new SesClient([
                'version' => 'latest',
                'region'  => env('AWS_DEFAULT_REGION', 'us-east-1'),
                'credentials' => [
                    'key'    => env('AWS_ACCESS_KEY_ID'),
                    'secret' => env('AWS_SECRET_ACCESS_KEY'),
                ],
            ]);

            // 1. Generate the PDF from your exact blade template
            $pdf = Pdf::loadView('receipts.sale', [
                'sale' => $this->sale, 
                'is_pdf' => true // Pass a flag to hide the print button in the PDF
            ]);
            $pdfContent = $pdf->output();

            // 2. Build the Raw Email with PDF Attachment
            $boundary = uniqid('np');
            $subject = "Tax Invoice: {$this->sale->invoice_number} | Diamond Square";
            $recipient = $this->sale->customer->email;
            
            $rawEmail = "From: Diamond Square <info@thedsq.com>\n";
            $rawEmail .= "To: {$recipient}\n";
            $rawEmail .= "Subject: {$subject}\n";
            $rawEmail .= "MIME-Version: 1.0\n";
            $rawEmail .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\n\n";
            $rawEmail .= "--{$boundary}\n";
            $rawEmail .= "Content-Type: text/plain; charset=UTF-8\n\n";
            $rawEmail .= "Please find your itemized tax invoice attached as a PDF document.\n\n";
            $rawEmail .= "--{$boundary}\n";
            $rawEmail .= "Content-Type: application/pdf; name=\"Invoice_{$this->sale->invoice_number}.pdf\"\n";
            $rawEmail .= "Content-Transfer-Encoding: base64\n";
            $rawEmail .= "Content-Disposition: attachment; filename=\"Invoice_{$this->sale->invoice_number}.pdf\"\n\n";
            $rawEmail .= chunk_split(base64_encode($pdfContent)) . "\n";
            $rawEmail .= "--{$boundary}--";

            // 3. Send via SES Raw Message
            $sesClient->sendRawEmail([
                'RawMessage' => [
                    'Data' => $rawEmail,
                ],
            ]);

            return true;
        } catch (AwsException $e) {
            \Illuminate\Support\Facades\Log::error("SES PDF Error: " . $e->getAwsErrorMessage());
            return false;
        }
    }
}