<?php

namespace App\Notifications\Taches;

use App\Models\Tache;
use App\Models\TacheExternalLink;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TacheLinkRemovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Tache $tache, 
        public User $removedBy, 
        public TacheExternalLink $link
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'task_link_removed',
            'tache_id' => $this->tache->id,
            'title' => "Lien supprimé",
            'message' => "{$this->removedBy->nom} a supprimé le lien \"{$this->link->title}\" de la tâche \"{$this->tache->titre}\"",
            
            // Informations de la tâche
            'tache_titre' => $this->tache->titre,
            'activite_id' => $this->tache->activite_id,
            
            // Informations du lien supprimé
            'link_title' => $this->link->title,
            'link_url' => $this->link->url,
            
            // Informations de l'utilisateur
            'removed_by_id' => $this->removedBy->id,
            'removed_by_nom' => $this->removedBy->nom,
            'removed_by_avatar' => $this->removedBy->avatar,
            
            // URL d'accès à la tâche
            'url' => "/taches/{$this->tache->id}?tab=links",
            'action_url' => "/taches/{$this->tache->id}",
        ];
    }
}

 