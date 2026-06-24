<?php

namespace App\Notifications;

use App\Models\TacheResultat;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * 📧 Notification: Résultat validé N1
 */
class ResultatValideN1Notification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public TacheResultat $resultat,
        public User $validateur,
        public ?string $commentaire
    ) {}

    public function via($notifiable): array
    {
        // G3: delegate to NotificationService so the user's notification_preferences
        // + push_subscriptions are honoured. Adds the WebPushChannel automatically
        // when the user is push-subscribed and 'valide_n1' is a high-signal event.
        return app(NotificationService::class)->channelsFor($notifiable, 'valide_n1');
    }

    public function toMail($notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('✅ Votre résultat a été validé (N1)')
            ->greeting("Bonjour {$notifiable->nom},")
            ->line("Votre résultat pour la tâche **{$this->resultat->tache->titre}** a été validé par {$this->validateur->nom}.")
            ->line("**Taux de réalisation:** {$this->resultat->tache->taux_realisation}%");

        if ($this->commentaire) {
            $mail->line("**Commentaire:** {$this->commentaire}");
        }

        // Si N2 requis
        if ($this->resultat->tache->validation_n2_required) {
            $mail->line('Votre résultat est maintenant en attente de validation finale (N2).');
        } else {
            $mail->line('Félicitations ! Votre résultat est entièrement validé.');
        }

        return $mail->action('Voir le résultat', url("/taches/{$this->resultat->tache->id}"));
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'resultat_valide_n1',
            'resultat_id' => $this->resultat->id,
            'tache_id' => $this->resultat->tache->id,
            'tache_titre' => $this->resultat->tache->titre,
            'validateur_id' => $this->validateur->id,
            'validateur_nom' => $this->validateur->nom,
            'commentaire' => $this->commentaire,
            'taux_realisation' => $this->resultat->taux_realisation,
            'url' => "/taches/{$this->resultat->tache->id}?tab=results",
            'title' => 'Résultat validé (N1)',
            'message' => "Votre résultat pour « {$this->resultat->tache->titre} » a été validé par {$this->validateur->nom}",
        ];
    }
}
