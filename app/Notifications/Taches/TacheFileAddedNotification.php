<?php

namespace App\Notifications\Taches;

use App\Models\Tache;
use App\Models\TacheAttachment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TacheFileAddedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Tache $tache, 
        public User $uploadedBy, 
        public TacheAttachment $file
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'task_file_added',
            'tache_id' => $this->tache->id,
            'attachment_id' => $this->file->id,
            'title' => "Nouveau fichier ajouté",
            'message' => "{$this->uploadedBy->nom} a ajouté un fichier à la tâche \"{$this->tache->titre}\" : {$this->file->original_name}",
            
            // Informations de la tâche
            'tache_titre' => $this->tache->titre,
            'activite_id' => $this->tache->activite_id,
            
            // Informations du fichier
            'file_id' => $this->file->id,
            'file_name' => $this->file->original_name,
            'file_size' => $this->file->file_size,
            'file_mime_type' => $this->file->mime_type,
            'file_icon' => $this->getFileIcon($this->file->mime_type),
            
            // Informations de l'uploadeur
            'uploaded_by_id' => $this->uploadedBy->id,
            'uploaded_by_nom' => $this->uploadedBy->nom,
            'uploaded_by_avatar' => $this->uploadedBy->avatar,
            
            // URLs d'accès
            'url' => "/taches/{$this->tache->id}?tab=attachments",
            'download_url' => "/taches/{$this->tache->id}/attachments/{$this->file->id}/download",
            'action_url' => "/taches/{$this->tache->id}?highlight=attachment-{$this->file->id}",
        ];
    }

    /**
     * Obtenir l'icône du fichier selon son type MIME
     */
    private function getFileIcon(string $mimeType): string
    {
        $icons = [
            'application/pdf' => 'fa-file-pdf',
            'application/msword' => 'fa-file-word',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'fa-file-word',
            'application/vnd.ms-excel' => 'fa-file-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'fa-file-excel',
            'application/vnd.ms-powerpoint' => 'fa-file-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'fa-file-powerpoint',
            'application/zip' => 'fa-file-archive',
            'application/x-rar-compressed' => 'fa-file-archive',
            'text/plain' => 'fa-file-alt',
            'text/csv' => 'fa-file-csv',
        ];

        // Images
        if (str_starts_with($mimeType, 'image/')) {
            return 'fa-file-image';
        }

        // Vidéos
        if (str_starts_with($mimeType, 'video/')) {
            return 'fa-file-video';
        }

        // Audio
        if (str_starts_with($mimeType, 'audio/')) {
            return 'fa-file-audio';
        }

        return $icons[$mimeType] ?? 'fa-file';
    }
}