<?php

namespace App\Notifications;

use App\Models\Projet;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProjetMemberAddedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Projet $projet,
        public User $addedBy,
        public string $role,
        public array $permissions = []
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $roleLabels = [
            'admin' => 'Administrateur',
            'member' => 'Membre',
            'viewer' => 'Observateur',
        ];

        return (new MailMessage)
            ->subject("Vous avez été ajouté au projet : {$this->projet->nom}")
            ->greeting("Bonjour {$notifiable->prenom},")
            ->line("{$this->addedBy->nom} vous a ajouté au projet **{$this->projet->nom}**.")
            ->line("**Rôle attribué** : " . ($roleLabels[$this->role] ?? $this->role))
            ->when($this->projet->description, function ($mail) {
                return $mail->line("**Description** : {$this->projet->description}");
            })
            ->line("**Vos permissions** :")
            ->line($this->formatPermissions())
            ->action('Voir le projet', url("/projets/{$this->projet->id}"))
            ->line("Vous pouvez maintenant accéder au projet et commencer à collaborer avec l'équipe.");
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'projet_member_added',
            'projet_id' => $this->projet->id,
            'projet_nom' => $this->projet->nom,
            'added_by_id' => $this->addedBy->id,
            'added_by_name' => $this->addedBy->nom,
            'role' => $this->role,
            'permissions' => $this->permissions,
            'message' => "{$this->addedBy->nom} vous a ajouté au projet {$this->projet->nom}",
            'action_url' => "/projets/{$this->projet->id}",
        ];
    }

    private function formatPermissions(): string
    {
        $permissionLabels = [
            'can_edit' => '✓ Modifier le projet',
            'can_delete' => '✓ Supprimer le projet',
            'can_invite' => '✓ Inviter des membres',
            'can_delete_member' => '✓ Retirer des membres',
            'can_create_activity' => '✓ Créer des activités',
            'can_edit_activity' => '✓ Modifier des activités',
            'can_delete_activity' => '✓ Supprimer des activités',
        ];

        $activePermissions = [];
        foreach ($this->permissions as $key => $value) {
            if ($value && isset($permissionLabels[$key])) {
                $activePermissions[] = $permissionLabels[$key];
            }
        }

        return $activePermissions 
            ? '  - ' . implode("\n  - ", $activePermissions)
            : '  - Lecture seule';
    }
}