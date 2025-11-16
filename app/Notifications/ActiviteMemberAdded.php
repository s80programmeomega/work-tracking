<?php

namespace App\Notifications;

use App\Models\Activite;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ActiviteMemberAdded extends Notification implements ShouldQueue
{
    use Queueable;

    protected $activite;
    protected $addedBy;
    protected $role;
    protected $permissions;

    public function __construct(Activite $activite, User $addedBy, string $role, array $permissions)
    {
        $this->activite = $activite;
        $this->addedBy = $addedBy;
        $this->role = $role;
        $this->permissions = $permissions;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $roleLabel = $this->getRoleLabel();
        $url = url("/activites/{$this->activite->id}");

        return (new MailMessage)
            ->subject("Vous avez été ajouté à l'activité: {$this->activite->nom}")
            ->greeting("Bonjour {$notifiable->nom},")
            ->line("{$this->addedBy->nom} vous a ajouté à l'activité **{$this->activite->nom}**.")
            ->line("**Projet:** {$this->activite->projet->nom}")
            ->line("**Votre rôle:** {$roleLabel}")
            ->line("**Vos permissions:**")
            ->line($this->formatPermissions())
            ->action('Voir l\'activité', $url)
            ->line('Merci de votre collaboration !');
    }

    public function toArray($notifiable)
    {
        return [
            'type' => 'activite_member_added',
            'activite_id' => $this->activite->id,
            'activite_nom' => $this->activite->nom,
            'activite_code' => $this->activite->code,
            'projet_id' => $this->activite->projet_id,
            'projet_nom' => $this->activite->projet->nom,
            'added_by_id' => $this->addedBy->id,
            'added_by_nom' => $this->addedBy->nom,
            'role' => $this->role,
            'permissions' => $this->permissions,
            'url' => "/activites/{$this->activite->id}",
            'message' => "{$this->addedBy->nom} vous a ajouté à l'activité {$this->activite->nom}"
        ];
    }

    private function formatPermissions(): string
    {
        $permissions = [];
        
        if ($this->permissions['can_create_tasks'] ?? false) {
            $permissions[] = '✓ Créer des tâches';
        }
        if ($this->permissions['can_edit_tasks'] ?? false) {
            $permissions[] = '✓ Modifier des tâches';
        }
        if ($this->permissions['can_delete_tasks'] ?? false) {
            $permissions[] = '✓ Supprimer des tâches';
        }
        if ($this->permissions['can_validate_results'] ?? false) {
            $permissions[] = '✓ Valider les résultats (N1)';
        }
        if ($this->permissions['can_assign_users'] ?? false) {
            $permissions[] = '✓ Assigner des membres';
        }

        return empty($permissions) 
            ? '- Aucune permission spéciale' 
            : implode("\n", $permissions);
    }

    private function getRoleLabel(): string
    {
        return match($this->role) {
            'responsable' => 'Responsable',
            'collaborator' => 'Collaborateur',
            'viewer' => 'Observateur',
            default => ucfirst($this->role)
        };
    }
}