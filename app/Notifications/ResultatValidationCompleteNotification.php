<?php

namespace App\Notifications;

use App\Models\TacheResultat;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * 📧 Notification: Validation complète (pour N1 quand N2 valide)
 * Informer le validateur N1 que le résultat est maintenant entièrement validé
 */
class ResultatValidationCompleteNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public TacheResultat $resultat
    ) {}

    public function via($notifiable): array
    {
        return ['database']; // Notification interne uniquement
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'resultat_validation_complete',
            'resultat_id' => $this->resultat->id,
            'tache_id' => $this->resultat->tache->id,
            'tache_titre' => $this->resultat->tache->titre,
            'auteur_id' => $this->resultat->user->id,
            'auteur_nom' => $this->resultat->user->nom,
            'validateur_n2_id' => $this->resultat->validateur_n2_id,
            'validateur_n2_nom' => $this->resultat->validateurN2->nom,
            'taux_realisation' => $this->resultat->taux_realisation,
            'url' => "/resultats/{$this->resultat->id}",
            'title' => 'Validation complète',
            'message' => "Le résultat de {$this->resultat->user->nom} que vous aviez validé (N1) est maintenant entièrement validé par {$this->resultat->validateurN2->nom} (N2)"
        ];
    }
}
