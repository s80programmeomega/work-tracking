<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifie le demandeur à chaque réponse admin sur son ticket.
 * Envoyée systématiquement — indépendamment du changement de statut.
 */
class SupportTicketReplyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly SupportTicket $ticket,
        public readonly SupportTicketReply $reply,
    ) {}

    public function via(object $notifiable): array
    {
        return app(NotificationService::class)->channelsFor($notifiable, 'support_ticket_reply');
    }

    public function toMail(object $notifiable): MailMessage
    {
        $statusLabels = [
            'open' => 'Ouvert',
            'in_progress' => 'En cours',
            'resolved' => 'Résolu',
        ];
        $statusLabel = $statusLabels[$this->ticket->status] ?? $this->ticket->status;

        return (new MailMessage)
            ->subject("[Support #{$this->ticket->ticket_number}] Nouvelle réponse de notre équipe")
            ->greeting("Bonjour {$notifiable->prenom},")
            ->line("Notre équipe a répondu à votre ticket **#{$this->ticket->ticket_number}**.")
            ->line("**Sujet :** {$this->ticket->subject}")
            ->line('**Réponse :**')
            ->line($this->reply->body)
            ->line("**Statut actuel :** {$statusLabel}")
            ->action('Voir mon ticket', url('/support?ticket='.$this->ticket->id));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'support_ticket_reply',
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'subject' => $this->ticket->subject,
            'reply_preview' => mb_substr($this->reply->body, 0, 100),
            'status' => $this->ticket->status,
            'dedup_key' => "support_ticket_reply:{$this->reply->id}",
        ];
    }
}
