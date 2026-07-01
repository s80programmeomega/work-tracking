<?php

namespace App\Notifications;

use App\Models\User;
use App\Models\WorkspaceInvitation;
use App\Permissions\RoleLabel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkspaceInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $invitation;

    public function __construct(WorkspaceInvitation $invitation)
    {
        $this->invitation = $invitation;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $url = url('/accept-invitation/'.$this->invitation->token);
        $workspaceName = $this->invitation->workspace->nom;
        $inviterName = $this->invitation->invitedBy->nom;
        $role = RoleLabel::label($this->invitation->role);

        // Vérifier si l'utilisateur existe déjà
        $userExists = $notifiable instanceof User;

        $mail = (new MailMessage)
            ->subject("Invitation à rejoindre le workspace {$workspaceName}")
            ->greeting('Bonjour !')
            ->line("{$inviterName} vous invite à rejoindre le workspace **{$workspaceName}**.")
            ->line("Rôle assigné : **{$role}**");

        if ($this->invitation->message) {
            $mail->line("Message de l'inviteur :")
                ->line('> '.$this->invitation->message);
        }

        if ($userExists) {
            $mail->line('Vous avez déjà un compte sur notre plateforme. Connectez-vous pour accepter cette invitation.');
        } else {
            $mail->line("Vous n'avez pas encore de compte. Vous pourrez en créer un en acceptant cette invitation.");
        }

        $mail->action('Accepter l\'invitation', $url)
            ->line("Cette invitation expire le {$this->invitation->expires_at->format('d/m/Y à H:i')}.")
            ->line('Si vous ne souhaitez pas accepter cette invitation, vous pouvez ignorer cet email.')
            ->salutation("Cordialement,\nL'équipe ".config('app.name'));

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'workspace_invitation',
            'title' => 'Nouvelle invitation workspace',
            'message' => "{$this->invitation->invitedBy->nom} vous invite à rejoindre {$this->invitation->workspace->nom}",
            'workspace_id' => $this->invitation->workspace_id,
            'workspace_name' => $this->invitation->workspace->nom,
            'workspace_logo' => $this->invitation->workspace->logo_url,
            'inviter_name' => $this->invitation->invitedBy->nom,
            'inviter_email' => $this->invitation->invitedBy->email,
            'role' => $this->invitation->role,
            'invitation_id' => $this->invitation->id,
            'invitation_token' => $this->invitation->token,
            'invitation_message' => $this->invitation->message,
            'expires_at' => $this->invitation->expires_at->toISOString(),
            'action_url' => '/accept-invitation/'.$this->invitation->token,
            'is_pending' => $this->invitation->status === 'pending',
        ];
    }
}
