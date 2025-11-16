<?php

namespace App\Notifications;

use App\Models\Activite;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ActiviteMemberPermissionsUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    protected $activite;
    protected $updatedBy;
    protected $oldPermissions;
    protected $newPermissions;

    public function __construct(Activite $activite, User $updatedBy, array $oldPermissions, array $newPermissions)
    {
        $this->activite = $activite;
        $this->updatedBy = $updatedBy;
        $this->oldPermissions = $oldPermissions;
        $this->newPermissions = $newPermissions;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $url = url("/activites/{$this->activite->id}");
        $roleChanged = $this->oldPermissions['role'] !== $this->newPermissions['role'];

        $mail = (new MailMessage)
            ->subject("Vos permissions ont été modifiées - {$this->activite->nom}")
            ->greeting("Bonjour {$notifiable->nom},")
            ->line("{$this->updatedBy->nom} a modifié vos permissions pour l'activité **{$this->activite->nom}**.")
            ->line("**Projet:** {$this->activite->projet->nom}");

        // Afficher le changement de rôle
        if ($roleChanged) {
            $oldRoleLabel = $this->getRoleLabel($this->oldPermissions['role']);
            $newRoleLabel = $this->getRoleLabel($this->newPermissions['role']);
            $mail->line("**Nouveau rôle:** {$newRoleLabel} (était: {$oldRoleLabel})");
        } else {
            $mail->line("**Rôle:** " . $this->getRoleLabel($this->newPermissions['role']));
        }

        // Afficher les changements de permissions
        $permissionChanges = $this->getPermissionChanges();
        if (!empty($permissionChanges)) {
            $mail->line("**Changements de permissions:**")
                 ->line($this->formatPermissionChanges($permissionChanges));
        }

        $mail->action('Voir l\'activité', $url)
             ->line('Merci de votre collaboration !');

        return $mail;
    }

    public function toArray($notifiable)
    {
        return [
            'type' => 'activite_member_permissions_updated',
            'activite_id' => $this->activite->id,
            'activite_nom' => $this->activite->nom,
            'activite_code' => $this->activite->code,
            'projet_id' => $this->activite->projet_id,
            'projet_nom' => $this->activite->projet->nom,
            'updated_by_id' => $this->updatedBy->id,
            'updated_by_nom' => $this->updatedBy->nom,
            'old_permissions' => $this->oldPermissions,
            'new_permissions' => $this->newPermissions,
            'url' => "/activites/{$this->activite->id}",
            'message' => $this->getNotificationMessage()
        ];
    }

    private function getPermissionChanges(): array
    {
        $changes = [];

        $permissionFields = [
            'can_create_tasks' => 'Créer des tâches',
            'can_edit_tasks' => 'Modifier des tâches',
            'can_delete_tasks' => 'Supprimer des tâches',
            'can_validate_results' => 'Valider les résultats (N1)',
            'can_assign_users' => 'Assigner des membres',
        ];

        foreach ($permissionFields as $field => $label) {
            $oldValue = $this->oldPermissions[$field] ?? false;
            $newValue = $this->newPermissions[$field] ?? false;

            if ($oldValue !== $newValue) {
                $changes[] = [
                    'permission' => $label,
                    'old' => $oldValue,
                    'new' => $newValue
                ];
            }
        }

        return $changes;
    }

    private function formatPermissionChanges(array $changes): string
    {
        $lines = [];

        foreach ($changes as $change) {
            $status = $change['new'] ? '✓ Activée' : '✗ Désactivée';
            $lines[] = "• {$change['permission']}: {$status}";
        }

        return implode("\n", $lines);
    }

    private function getRoleLabel(string $role): string
    {
        return match($role) {
            'responsable' => 'Responsable',
            'collaborator' => 'Collaborateur',
            'viewer' => 'Observateur',
            default => ucfirst($role)
        };
    }

    private function getNotificationMessage(): string
    {
        $roleChanged = $this->oldPermissions['role'] !== $this->newPermissions['role'];
        $permissionChanges = $this->getPermissionChanges();

        if ($roleChanged && !empty($permissionChanges)) {
            return "{$this->updatedBy->nom} a modifié votre rôle et vos permissions pour l'activité {$this->activite->nom}";
        } elseif ($roleChanged) {
            return "{$this->updatedBy->nom} a modifié votre rôle pour l'activité {$this->activite->nom}";
        } elseif (!empty($permissionChanges)) {
            return "{$this->updatedBy->nom} a modifié vos permissions pour l'activité {$this->activite->nom}";
        }

        return "{$this->updatedBy->nom} a modifié vos paramètres pour l'activité {$this->activite->nom}";
    }
}