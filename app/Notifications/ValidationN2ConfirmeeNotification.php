<?php

namespace App\Notifications;

use App\Models\TacheResultat;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * 📧 Notification: Confirmation de validation N1 (pour le validateur)
 */
class ValidationN2ConfirmeeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public TacheResultat $resultat
    ) {
    }

    public function via($notifiable): array
    {
        return ['database']; // Pas de mail, juste notification interne
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'validation_n1_confirmee',
            'resultat_id' => $this->resultat->id,
            'tache_id' => $this->resultat->tache->id,
            'tache_titre' => $this->resultat->tache->titre,
            'auteur_id' => $this->resultat->user->id,
            'auteur_nom' => $this->resultat->user->nom,
            'taux_realisation' => $this->resultat->taux_realisation,
            'url' => "/resultats/{$this->resultat->id}",
            'title' => 'Validation confirmée',
            'message' => "Vous avez validé le résultat de {$this->resultat->user->nom} (N2)"
        ];
    }
}