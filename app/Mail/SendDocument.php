<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class SendDocumentMail extends Mailable
{
    use Queueable, SerializesModels;

    public $studentName;
    public $documentName;
    public $filePath;

    public function __construct($studentName, $documentName, $filePath)
    {
        $this->studentName = $studentName;
        $this->documentName = $documentName;
        $this->filePath = $filePath;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Your Requested Document: " . $this->documentName,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.send_document',
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->filePath),
        ];
    }
}