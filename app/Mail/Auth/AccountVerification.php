<?php

namespace App\Mail\Auth;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountVerification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $code
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to ' . config('app.name') . ' - Verify Your Account',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.auth.account-verification',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
