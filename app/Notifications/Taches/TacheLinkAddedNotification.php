<?php

namespace App\Notifications\Taches;

use App\Models\Tache;
use App\Models\TacheExternalLink;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TacheLinkAddedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Tache $tache, 
        public User $addedBy, 
        public TacheExternalLink $link
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'task_link_added',
            'tache_id' => $this->tache->id,
            'link_id' => $this->link->id,
            'title' => "Nouveau lien ajouté",
            'message' => "{$this->addedBy->nom} a ajouté un lien à la tâche \"{$this->tache->titre}\" : {$this->link->title}",
            
            // Informations de la tâche
            'tache_titre' => $this->tache->titre,
            'activite_id' => $this->tache->activite_id,
            
            // Informations du lien
            'link_id' => $this->link->id,
            'link_title' => $this->link->title,
            'link_url' => $this->link->url,
            'link_domain' => $this->extractDomain($this->link->url),
            'link_icon' => $this->getLinkIcon($this->link->url),
            
            // Informations de l'utilisateur
            'added_by_id' => $this->addedBy->id,
            'added_by_nom' => $this->addedBy->nom,
            'added_by_avatar' => $this->addedBy->avatar,
            
            // URLs d'accès
            'url' => "/taches/{$this->tache->id}?tab=links",
            'external_url' => $this->link->url,
            'action_url' => "/taches/{$this->tache->id}?highlight=link-{$this->link->id}",
        ];
    }

    /**
     * Extraire le domaine d'une URL
     */
    private function extractDomain(string $url): ?string
    {
        $parsed = parse_url($url);
        return $parsed['host'] ?? null;
    }

    /**
     * Obtenir l'icône selon le domaine du lien
     */
    private function getLinkIcon(string $url): string
    {
        $domain = strtolower($this->extractDomain($url) ?? '');
        
        $icons = [
            'github.com' => 'fa-github',
            'gitlab.com' => 'fa-gitlab',
            'google.com' => 'fa-google',
            'drive.google.com' => 'fa-google-drive',
            'youtube.com' => 'fa-youtube',
            'youtu.be' => 'fa-youtube',
            'linkedin.com' => 'fa-linkedin',
            'facebook.com' => 'fa-facebook',
            'twitter.com' => 'fa-twitter',
            'x.com' => 'fa-x-twitter',
            'instagram.com' => 'fa-instagram',
            'figma.com' => 'fa-figma',
            'trello.com' => 'fa-trello',
            'slack.com' => 'fa-slack',
            'dropbox.com' => 'fa-dropbox',
        ];

        foreach ($icons as $key => $icon) {
            if (str_contains($domain, $key)) {
                return $icon;
            }
        }

        return 'fa-link';
    }
}