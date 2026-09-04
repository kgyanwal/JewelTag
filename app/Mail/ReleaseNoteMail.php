<?php

namespace App\Mail;

use App\Models\ReleaseNote;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReleaseNoteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ReleaseNote $note, public string $recipientName)
    {
    }

    public function envelope(): Envelope
    {
        $prefix = $this->note->version ? "[{$this->note->version}] " : '';
        return new Envelope(
            subject: "JewelTag Update: {$prefix}{$this->note->title}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.release-note',
            with: [
                'note'          => $this->note,
                'recipientName' => $this->recipientName,
            ],
        );
    }
}