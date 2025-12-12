<?php

namespace App\Notifications\Taches;

use App\Models\Tache;
use App\Models\TacheAttachment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TacheFileRemovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Tache $tache, 
        public User $removedBy, 
        public TacheAttachment $file
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'task_file_removed',
            'tache_id' => $this->tache->id,
            'title' => "Fichier supprimé",
            'message' => "{$this->removedBy->nom} a supprimé le fichier \"{$this->file->original_name}\" de la tâche \"{$this->tache->titre}\"",
            
            // Informations de la tâche
            'tache_titre' => $this->tache->titre,
            'activite_id' => $this->tache->activite_id,
            
            // Informations du fichier supprimé
            'file_name' => $this->file->original_name,
            'file_size' => $this->file->file_size,
            
            // Informations de l'utilisateur
            'removed_by_id' => $this->removedBy->id,
            'removed_by_nom' => $this->removedBy->nom,
            'removed_by_avatar' => $this->removedBy->avatar,
            
            // URL d'accès à la tâche
            'url' => "/taches/{$this->tache->id}?tab=attachments",
            'action_url' => "/taches/{$this->tache->id}",
        ];
    }
}