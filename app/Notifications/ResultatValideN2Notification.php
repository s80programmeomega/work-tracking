<?php

namespace App\Notifications;

use App\Models\TacheResultat;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * 📧 Notification: Résultat validé N2 (final)
 */
class ResultatValideN2Notification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public TacheResultat $resultat,
        public User $validateur,
        public ?string $commentaire
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("🎉 Votre résultat est entièrement validé !")
            ->greeting("Félicitations {$notifiable->nom} !")
            ->line("Votre résultat pour la tâche **{$this->resultat->tache->titre}** a été validé par {$this->validateur->nom}.")
            ->line("**Validation complète:** N1 ✓ + N2 ✓")
            ->line("**Taux de réalisation:** {$this->resultat->taux_realisation}%");

        if ($this->commentaire) {
            $mail->line("**Commentaire:** {$this->commentaire}");
        }

        return $mail
            ->action('Voir le résultat', url("/taches/{$this->resultat->tache->id}"))
            ->line('Excellent travail ! Continuez comme ça.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'resultat_valide_n2',
            'resultat_id' => $this->resultat->id,
            'tache_id' => $this->resultat->tache->id,
            'tache_titre' => $this->resultat->tache->titre,
            'validateur_id' => $this->validateur->id,
            'validateur_nom' => $this->validateur->nom,
            'commentaire' => $this->commentaire,
            'url' => "/taches/{$this->resultat->tache->id}"
        ];
    }
}
