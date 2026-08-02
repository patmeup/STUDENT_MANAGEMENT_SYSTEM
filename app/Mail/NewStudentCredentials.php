<?php

namespace App\Mail;

use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewStudentCredentials extends Mailable
{
    use Queueable, SerializesModels;

    public Student $student;
    public string $defaultPassword;

    /**
     * Create a new message instance.
     */
    public function __construct(Student $student, string $defaultPassword)
    {
        $this->student = $student;
        $this->defaultPassword = $defaultPassword;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to Arellano University - Your Student Account Details',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.new_student_credentials',
            with: [
                'student' => $this->student,
                'defaultPassword' => $this->defaultPassword,
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