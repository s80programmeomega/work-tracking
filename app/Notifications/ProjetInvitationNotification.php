<?php

namespace App\Notifications;

use App\Models\ProjetInvitation;
use App\Permissions\RoleLabel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProjetInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected ProjetInvitation $invitation;

    public function __construct(ProjetInvitation $invitation)
    {
        $this->invitation = $invitation;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $projet = $this->invitation->projet;
        $inviter = $this->invitation->invitedBy;
        $acceptUrl = url("/invitations/projet/{$this->invitation->token}");

        return (new MailMessage)
            ->subject("Invitation au projet : {$projet->nom}")
            ->greeting('Bonjour,')
            ->line("{$inviter->nom} vous invite à rejoindre le projet **{$projet->nom}**.")
            ->line('**Rôle attribué :** '.RoleLabel::label($this->invitation->role))
            ->when($this->invitation->message, function ($mail) {
                return $mail->line('**Message personnel :**')
                    ->line("_{$this->invitation->message}_");
            })
            ->action('Accepter l\'invitation', $acceptUrl)
            ->line("Cette invitation expire le {$this->invitation->expires_at->format('d/m/Y à H:i')}.")
            ->line('Si vous ne souhaitez pas rejoindre ce projet, ignorez simplement cet email.')
            ->salutation("Cordialement,\nL'équipe ".config('app.name'));
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'projet_invitation',
            'projet_id' => $this->invitation->projet_id,
            'projet_nom' => $this->invitation->projet->nom,
            'inviter_nom' => $this->invitation->invitedBy->nom,
            'role' => $this->invitation->role,
            'token' => $this->invitation->token,
            'expires_at' => $this->invitation->expires_at->toISOString(),
        ];
    }
}
