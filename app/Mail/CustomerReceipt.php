<?php

namespace App\Mail;

use App\Models\Sale;
use Aws\Ses\SesClient;
use Aws\Exception\AwsException;
use Barryvdh\DomPDF\Facade\Pdf; // 🔹 PDF Generator
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
            // 🚀 1. Fetch dynamic settings from the database
            $settings = DB::table('site_settings')->pluck('value', 'key');

            // 🚀 2. Safely check for DB credentials, fallback to config
            $key = !empty($settings['aws_access_key_id']) 
                ? $settings['aws_access_key_id'] 
                : config('services.ses.key');

            $secret = !empty($settings['aws_secret_access_key']) 
                ? $settings['aws_secret_access_key'] 
                : config('services.ses.secret');

            $region = !empty($settings['aws_default_region']) 
                ? $settings['aws_default_region'] 
                : config('services.ses.region', 'us-east-1');

            // 🚀 3. Prevent 500 Crash: If keys are entirely missing, abort safely
            if (empty($key) || empty($secret)) {
                Log::error('SES PDF Email Failed: AWS Credentials are empty.');
                return false; 
            }

            // 4. Initialize the SES Client with dynamic credentials
            $sesClient = new SesClient([
                'version' => 'latest',
                'region'  => $region,
                'credentials' => [
                    'key'    => $key,
                    'secret' => $secret,
                ],
            ]);

            // 5. Generate the PDF from your exact blade template
            $pdf = Pdf::loadView('receipts.sale', [
                'sale' => $this->sale, 
                'is_pdf' => true // Pass a flag to hide the print button in the PDF
            ]);
            $pdfContent = $pdf->output();

            // 6. Build the Raw Email with PDF Attachment
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

            // 7. Send via SES Raw Message
            $sesClient->sendRawEmail([
                'RawMessage' => [
                    'Data' => $rawEmail,
                ],
            ]);

            return true;
            
        } catch (AwsException $e) {
            Log::error("SES PDF Error: " . $e->getAwsErrorMessage());
            return false;
        } catch (\Exception $e) {
            Log::error("General Email Error: " . $e->getMessage());
            return false;
        }
    }
}