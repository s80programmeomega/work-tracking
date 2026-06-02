<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewSupportTicketNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly SupportTicket $ticket,
        public readonly User $submitter,
    ) {}

    public function via(object $notifiable): array
    {
        return app(NotificationService::class)->channelsFor($notifiable, 'support_ticket_new');
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("[Support #{$this->ticket->id}] {$this->ticket->subject}")
            ->greeting('Nouveau ticket de support')
            ->line("**De :** {$this->submitter->nom_complet} ({$this->submitter->email})")
            ->line("**Catégorie :** {$this->ticket->category}")
            ->line("**Sujet :** {$this->ticket->subject}")
            ->line("**Message :** {$this->ticket->message}")
            ->action('Voir le ticket', url('/admin/support'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'support_ticket_new',
            'ticket_id' => $this->ticket->id,
            'subject' => $this->ticket->subject,
            'category' => $this->ticket->category,
            'submitter_name' => $this->submitter->nom_complet,
            'dedup_key' => "support_ticket_new:{$this->ticket->id}",
        ];
    }
}
