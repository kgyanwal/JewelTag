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

            // 1. Get Store Data
        $store = $this->sale->store;
        
        // 2. Set dynamic "From" identity
        // Use the store's email, or fallback to the system default if missing
        $store_email = !empty($store->email) ? $store->email : config('mail.from.address');
        $store_name  = !empty($store->name)  ? $store->name  : 'Our Store';
            // 🚀 2. Safely check for DB credentials, fallback to config
            $key = !empty($settings['aws_access_key_id']) 
                ? $settings['aws_access_key_id'] 
                : config('services.ses.key');

            $secret = !empty($settings['aws_secret_access_key']) 
                ? $settings['aws_secret_access_key'] 
                : config('services.ses.secret');

            $region = !empty($settings['aws_default_region']) 
                ? $settings['aws_default_region'] 
                : config('services.ses.region');

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

            // 🚀 6. DYNAMIC STORE & CUSTOMER DATA
            $store = $this->sale->store;
            $store_name = $store->name ?? 'Our Store';
            $store_phone = $store->phone ?? '';
            $store_email = $store->email ?? config('mail.from.address');
            $store_address = trim(($store->street ?? '') . ' ' . ($store->city ?? '') . ', ' . ($store->state ?? '') . ' ' . ($store->postcode ?? ''));
            
            $customer_name = $this->sale->customer->name ?? 'Valued Customer';
            $invoice_number = $this->sale->invoice_number;

            // 7. DYNAMIC HEADERS & SUBJECT
            $boundary = uniqid('np');
            $subject = "Your Receipt from {$store_name} (Invoice #{$invoice_number})";
            $recipient = $this->sale->customer->email;
            
            $rawEmail = "From: {$store_name} <{$store_email}>\n";
        $rawEmail .= "To: {$recipient}\n";
            $rawEmail .= "Subject: {$subject}\n";
            $rawEmail .= "MIME-Version: 1.0\n";
            $rawEmail .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\n\n";
            
            $rawEmail .= "--{$boundary}\n";
            $rawEmail .= "Content-Type: text/plain; charset=UTF-8\n\n";
            
            // 8. DYNAMIC MESSAGE BODY
            $rawEmail .= "Dear {$customer_name},\n\n" .
                "Thank you for shopping with us! {$store_name} greatly appreciates your purchase.\n" .
                "We hope you absolutely love your new jewelry. We are so happy to be a part of your Jewelry Joy!\n" .
                "Please find your receipt attached to this email.\n" .
                "Thank you for choosing {$store_name} for your jewelry needs. We look forward to serving you again soon!" .
                "Warm regards,\n" .
                "The {$store_name} Family\n" .
                "{$store_address}\n" .
                "{$store_phone}\n" .
                "{$store_email}\n\n";
            
            // 9. ATTACH THE PDF
            $rawEmail .= "--{$boundary}\n";
            $rawEmail .= "Content-Type: application/pdf; name=\"Invoice_{$invoice_number}.pdf\"\n";
            $rawEmail .= "Content-Transfer-Encoding: base64\n";
            $rawEmail .= "Content-Disposition: attachment; filename=\"Invoice_{$invoice_number}.pdf\"\n\n";
            $rawEmail .= chunk_split(base64_encode($pdfContent)) . "\n";
            $rawEmail .= "--{$boundary}--";

            // 10. Send via SES Raw Message
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