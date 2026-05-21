<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

/**
 * Task 8c — Daily digest email.
 *
 * Renders a Blade template (fr or en based on user's preferred_locale) listing
 * unread notifications grouped by type. The command queue()s this mailable so
 * digest runs don't block the scheduler tick.
 */
class DailyDigestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly Collection $notifications,
    ) {}

    public function envelope(): Envelope
    {
        $count = $this->notifications->count();

        return new Envelope(
            subject: __('notifications.daily_digest.subject', ['count' => $count]),
        );
    }

    public function content(): Content
    {
        $locale = $this->user->preferred_locale ?? app()->getLocale();
        $view = "emails.daily-digest.{$locale}";

        return new Content(
            view: $view,
            with: [
                'user' => $this->user,
                'notifications' => $this->notifications,
                'grouped' => $this->groupedByType(),
            ],
        );
    }

    /**
     * Group notifications by their 'type' field for the template.
     * Returns Collection<string, Collection<DatabaseNotification>>.
     */
    private function groupedByType(): Collection
    {
        return $this->notifications->groupBy(fn ($n) => $n->data['type'] ?? 'other');
    }
}
