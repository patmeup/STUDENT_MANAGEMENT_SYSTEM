<?php

namespace App\Mail;

use App\Models\Student;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewUserCredentials extends Mailable
{
    use Queueable, SerializesModels;

    public $recipient;
    public string $defaultPassword;
    public string $accountType;

    /**
     * Create a new message instance.
     * 
     * @param Student|User 
     * @param string 
     * @param string 
     */
    public function __construct($recipient, string $defaultPassword, string $accountType = 'student')
    {
        $this->recipient = $recipient;
        $this->defaultPassword = $defaultPassword;
        $this->accountType = $accountType;
    }

    public function envelope(): Envelope
    {
        $subject = $this->accountType === 'employee' 
            ? 'Welcome to Arellano University - Your Faculty/Employee Account Details' 
            : 'Welcome to Arellano University - Your Student Account Details';

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.new_student_credentials',
            with: [
                'recipient' => $this->recipient,
                'defaultPassword' => $this->defaultPassword,
                'accountType' => $this->accountType,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}