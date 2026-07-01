<?php

namespace App\Notifications;

use App\Models\User;
use App\Models\Workspace;
use App\Permissions\RoleLabel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkspaceMemberAddedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $workspace;

    protected $inviter;

    protected $role;

    public function __construct(Workspace $workspace, User $inviter, string $role)
    {
        $this->workspace = $workspace;
        $this->inviter = $inviter;
        $this->role = $role;
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
        $url = url('/workspaces/'.$this->workspace->id);
        $workspaceName = $this->workspace->nom;
        $inviterName = $this->inviter->nom;
        $role = RoleLabel::label($this->role);

        return (new MailMessage)
            ->subject("Vous avez été ajouté au workspace {$workspaceName}")
            ->greeting("Bonjour {$notifiable->nom} !")
            ->line("{$inviterName} vous a ajouté au workspace **{$workspaceName}**.")
            ->line("Votre rôle : **{$role}**")
            ->action('Accéder au workspace', $url)
            ->line('Vous pouvez maintenant collaborer avec votre équipe et accéder aux projets du workspace.')
            ->salutation("Cordialement,\nL'équipe ".config('app.name'));
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'workspace_member_added',
            'title' => 'Ajouté à un workspace',
            'message' => "{$this->inviter->nom} vous a ajouté au workspace {$this->workspace->nom}",
            'workspace_id' => $this->workspace->id,
            'workspace_name' => $this->workspace->nom,
            'inviter_name' => $this->inviter->nom,
            'role' => $this->role,
            'action_url' => url('/workspaces/'.$this->workspace->id),
        ];
    }
}
