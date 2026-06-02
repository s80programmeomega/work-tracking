<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SupportTicketStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly SupportTicket $ticket,
        public readonly string $oldStatus,
    ) {}

    public function via(object $notifiable): array
    {
        return app(NotificationService::class)->channelsFor($notifiable, 'support_ticket_status_changed');
    }

    public function toMail(object $notifiable): MailMessage
    {
        $statusLabels = [
            'open' => 'Ouvert',
            'in_progress' => 'En cours',
            'resolved' => 'Résolu',
        ];
        $newLabel = $statusLabels[$this->ticket->status] ?? $this->ticket->status;

        return (new MailMessage)
            ->subject("[Support #{$this->ticket->id}] Statut mis à jour : {$newLabel}")
            ->greeting("Bonjour {$notifiable->prenom},")
            ->line("Le statut de votre ticket **#{$this->ticket->id}** a été mis à jour.")
            ->line("**Sujet :** {$this->ticket->subject}")
            ->line("**Nouveau statut :** {$newLabel}")
            ->action('Voir mon ticket', url('/support'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'support_ticket_status_changed',
            'ticket_id' => $this->ticket->id,
            'subject' => $this->ticket->subject,
            'old_status' => $this->oldStatus,
            'new_status' => $this->ticket->status,
            'dedup_key' => "support_ticket_status:{$this->ticket->id}:{$this->ticket->status}",
        ];
    }
}
