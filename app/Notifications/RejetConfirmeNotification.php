<?php

namespace App\Notifications;

use App\Models\TacheResultat;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class RejetConfirmeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public TacheResultat $resultat,
        public string $level
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        $niveau = $this->level === 'n1' ? 'N1' : 'N2';
        
        return [
            'type' => 'rejet_confirme',
            'resultat_id' => $this->resultat->id,
            'tache_id' => $this->resultat->tache->id,
            'tache_titre' => $this->resultat->tache->titre,
            'auteur_id' => $this->resultat->user->id,
            'auteur_nom' => $this->resultat->user->nom,
            'level' => $this->level,
            'taux_realisation' => $this->resultat->taux_realisation,
            'url' => "/resultats/{$this->resultat->id}",
            'title' => "Rejet confirmé ({$niveau})",
            'message' => "Vous avez rejeté le résultat de {$this->resultat->user->nom}. L'utilisateur a été notifié et devra soumettre à nouveau."
        ];
    }
}