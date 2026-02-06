<?php

namespace App\Notifications;

use App\Models\TacheResultat;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * 📧 Notification: Info pour N1 quand N2 rejette
 */
class ResultatRejeteN2InfoNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public TacheResultat $resultat,
        public string $commentaire
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'resultat_rejete_n2_info',
            'resultat_id' => $this->resultat->id,
            'tache_id' => $this->resultat->tache->id,
            'tache_titre' => $this->resultat->tache->titre,
            'auteur_id' => $this->resultat->user->id,
            'auteur_nom' => $this->resultat->user->nom,
            'validateur_n2_id' => $this->resultat->validateur_n2_id,
            'validateur_n2_nom' => $this->resultat->validateurN2->nom,
            'commentaire' => $this->commentaire,
            'url' => "/taches/{$this->resultat->tache->id}",
            'message' => "Le résultat que vous aviez validé (N1) a été rejeté par {$this->resultat->validateurN2->nom} (N2)"
        ];
    }
}