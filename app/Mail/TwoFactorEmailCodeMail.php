<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TwoFactorEmailCodeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly string $code,
    ) {}

    public function envelope(): Envelope
    {
        $locale = $this->user->language ?? app()->getLocale();
        $subject = $locale === 'fr'
            ? 'Votre code de vérification'
            : 'Your verification code';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        $locale = $this->user->language ?? app()->getLocale();

        return new Content(
            view: "emails.two-factor-email-code.{$locale}",
            with: [
                'user' => $this->user,
                'code' => $this->code,
                'expiresInMinutes' => 10,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
