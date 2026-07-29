<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DocumentRequestSubmitted extends Mailable
{
    use Queueable, SerializesModels;

    public $student;
    public $requestedDocuments;

    public function __construct($student, $requestedDocuments)
    {
        $this->student = $student;
        $this->requestedDocuments = $requestedDocuments;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Document Request Received - Registrar Office',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.document_request_submitted',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}