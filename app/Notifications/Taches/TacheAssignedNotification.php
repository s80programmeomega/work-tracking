<?php

namespace App\Notifications\Taches;

use App\Models\Tache;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TacheAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Tache $tache, public User $assignedBy) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'task_assigned',
            'tache_id' => $this->tache->id,
            'activite_id' => $this->tache->activite_id,
            'title' => "Nouvelle tâche assignée",
            'message' => "{$this->assignedBy->nom} vous a assigné la tâche : {$this->tache->titre}",
            
            // Informations de la tâche
            'tache_titre' => $this->tache->titre,
            'tache_description' => $this->tache->description,
            'tache_priorite' => $this->tache->priorite,
            'tache_statut' => $this->tache->statut,
            'tache_echeance' => $this->tache->echeance?->format('Y-m-d H:i:s'),
            
            // Informations de l'assigneur
            'assigned_by_id' => $this->assignedBy->id,
            'assigned_by_nom' => $this->assignedBy->nom,
            'assigned_by_avatar' => $this->assignedBy->avatar,
            
            // Informations de l'activité/projet
            'activite_nom' => $this->tache->activite->nom ?? null,
            'projet_nom' => $this->tache->activite->projet->nom ?? null,
            
            // URL d'accès direct
            'url' => "/taches/{$this->tache->id}",
            'action_url' => "/activites/{$this->tache->activite_id}/kanban?tache={$this->tache->id}",
        ];
    }
}