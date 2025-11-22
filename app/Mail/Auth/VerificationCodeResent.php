<?php

namespace App\Mail\Auth;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerificationCodeResent extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $code
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your New Verification Code - ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.auth.verification-code-resent',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
