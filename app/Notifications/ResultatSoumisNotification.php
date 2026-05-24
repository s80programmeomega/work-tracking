<?php

namespace App\Notifications;

use App\Models\TacheResultat;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * 📧 Notification: Résultat soumis (générique)
 */
class ResultatSoumisNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public TacheResultat $resultat
    ) {}

    public function via($notifiable): array
    {
        // G3: 'soumis_n1' = high-signal (validateur doit voir vite). Géré
        // par NotificationService::channelsFor() + préférences user.
        return app(NotificationService::class)->channelsFor($notifiable, 'soumis_n1');
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('📋 Nouveau résultat à valider')
            ->greeting("Bonjour {$notifiable->nom},")
            ->line("{$this->resultat->user->nom} a soumis un résultat pour la tâche **{$this->resultat->tache->titre}**.")
            ->line("**Taux de réalisation:** {$this->resultat->taux_realisation}%")
            ->action('Consulter le résultat', url("/taches/{$this->resultat->tache->id}/resultats/{$this->resultat->id}"))
            ->line('Merci de valider ce résultat.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'resultat_soumis',
            'resultat_id' => $this->resultat->id,
            'tache_id' => $this->resultat->tache->id,
            'tache_titre' => $this->resultat->tache->titre,
            'auteur_id' => $this->resultat->user->id,
            'auteur_nom' => $this->resultat->user->nom,
            'taux_realisation' => $this->resultat->taux_realisation,
            'url' => "/taches/{$this->resultat->tache->id}/resultats/{$this->resultat->id}",
        ];
    }
}
