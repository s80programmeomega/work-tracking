<?php

namespace App\Notifications\Taches;

use App\Models\Tache;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TacheUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected array $changes;

    public function __construct(public Tache $tache, public User $updatedBy, array $changes = [])
    {
        $this->changes = $changes;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'task_updated',
            'tache_id' => $this->tache->id,
            'activite_id' => $this->tache->activite_id,
            'title' => 'Tâche mise à jour',
            'message' => $this->buildMessage(),

            // Informations de la tâche
            'tache_titre' => $this->tache->titre,
            'tache_statut' => $this->tache->statut,
            'tache_priorite' => $this->tache->priorite,
            'tache_echeance' => $this->tache->echeance?->format('Y-m-d H:i:s'),

            // Modifications effectuées
            'changes' => $this->formatChanges(),
            'changes_count' => count($this->changes),

            // Informations de l'utilisateur
            'updated_by_id' => $this->updatedBy->id,
            'updated_by_nom' => $this->updatedBy->nom,
            'updated_by_avatar' => $this->updatedBy->avatar,

            // Informations de l'activité/projet
            'activite_nom' => $this->tache->activite->nom ?? null,
            'projet_nom' => $this->tache->activite->projet->nom ?? null,

            // URLs d'accès
            'url' => "/taches/{$this->tache->id}",
            'action_url' => "/taches/{$this->tache->id}?tab=activity",
        ];
    }

    /**
     * Construire un message descriptif selon les modifications
     */
    private function buildMessage(): string
    {
        if (empty($this->changes)) {
            return "{$this->updatedBy->nom} a mis à jour la tâche : {$this->tache->titre}";
        }

        $changesText = $this->getMainChangesText();

        return "{$this->updatedBy->nom} a modifié {$changesText} de la tâche \"{$this->tache->titre}\"";
    }

    /**
     * Obtenir le texte des modifications principales
     */
    private function getMainChangesText(): string
    {
        $fields = array_keys($this->changes);
        $count = count($fields);

        if ($count === 0) {
            return 'des détails';
        }

        $fieldLabels = [
            'titre' => 'le titre',
            'description' => 'la description',
            'statut' => 'le statut',
            'priorite' => 'la priorité',
            'echeance' => 'l\'échéance',
            'date_debut' => 'la date de début',
            'estimated_hours' => 'la durée estimée',
            'taux_realisation' => 'le taux de réalisation',
        ];

        $mainField = $fields[0];
        $label = $fieldLabels[$mainField] ?? $mainField;

        if ($count === 1) {
            return $label;
        } elseif ($count === 2) {
            $label2 = $fieldLabels[$fields[1]] ?? $fields[1];

            return "{$label} et {$label2}";
        } else {
            return "{$label} et ".($count - 1).' autre(s) champ(s)';
        }
    }

    /**
     * Formater les modifications pour affichage
     */
    private function formatChanges(): array
    {
        $formatted = [];

        foreach ($this->changes as $field => $values) {
            $formatted[] = [
                'field' => $field,
                'old_value' => $values['old'] ?? null,
                'new_value' => $values['new'] ?? null,
                'label' => $this->getFieldLabel($field),
            ];
        }

        return $formatted;
    }

    /**
     * Obtenir le label d'un champ
     */
    private function getFieldLabel(string $field): string
    {
        $labels = [
            'titre' => 'Titre',
            'description' => 'Description',
            'statut' => 'Statut',
            'priorite' => 'Priorité',
            'echeance' => 'Échéance',
            'date_debut' => 'Date de début',
            'estimated_hours' => 'Durée estimée',
            'taux_realisation' => 'Taux de réalisation',
        ];

        return $labels[$field] ?? ucfirst($field);
    }
}
