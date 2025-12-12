<?php

namespace App\Notifications\Taches;

use App\Models\Tache;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TacheUnassignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Tache $tache, public User $removedBy) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'task_unassigned',
            'tache_id' => $this->tache->id,
            'activite_id' => $this->tache->activite_id,
            'title' => "Tâche retirée",
            'message' => "{$this->removedBy->nom} vous a retiré de la tâche : {$this->tache->titre}",
            
            // Informations de la tâche
            'tache_titre' => $this->tache->titre,
            'tache_description' => $this->tache->description,
            'tache_statut' => $this->tache->statut,
            
            // Informations de l'utilisateur qui a retiré
            'removed_by_id' => $this->removedBy->id,
            'removed_by_nom' => $this->removedBy->nom,
            'removed_by_avatar' => $this->removedBy->avatar,
            
            // Informations de l'activité/projet
            'activite_nom' => $this->tache->activite->nom ?? null,
            'projet_nom' => $this->tache->activite->projet->nom ?? null,
            
            // URL d'accès (même si retiré, peut consulter)
            'url' => "/taches/{$this->tache->id}",
            'action_url' => "/activites/{$this->tache->activite_id}",
        ];
    }
}