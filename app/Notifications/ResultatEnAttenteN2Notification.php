<?php

namespace App\Notifications;

use App\Models\TacheResultat;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * 📧 Notification: Résultat en attente de validation N2
 */
class ResultatEnAttenteN2Notification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public TacheResultat $resultat
    ) {}

    public function via($notifiable): array
    {
        // G3: 'en_validation_n2' = high-signal (le responsable projet doit
        // savoir tout de suite qu'un résultat l'attend).
        return app(NotificationService::class)->channelsFor($notifiable, 'en_validation_n2');
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('📋 Résultat en attente de validation finale')
            ->greeting("Bonjour {$notifiable->nom},")
            ->line('Un résultat est en attente de votre validation finale (N2).')
            ->line("**Tâche:** {$this->resultat->tache->titre}")
            ->line("**Auteur:** {$this->resultat->user->nom}")
            ->line("**Validé N1 par:** {$this->resultat->validateurN1->nom}")
            ->action('Valider le résultat', url("/taches/{$this->resultat->tache->id}/resultats/{$this->resultat->id}"))
            ->line('Ce résultat a déjà été validé au niveau 1.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'resultat_attente_n2',
            'resultat_id' => $this->resultat->id,
            'tache_id' => $this->resultat->tache->id,
            'tache_titre' => $this->resultat->tache->titre,
            'auteur_id' => $this->resultat->user->id,
            'auteur_nom' => $this->resultat->user->nom,
            'taux_realisation' => $this->resultat->taux_realisation,
            'url' => "/resultats/{$this->resultat->id}",
            'title' => 'Résultat en attente de validation',
            'message' => "Un résultat de {$this->resultat->user->nom} pour « {$this->resultat->tache->titre} » attend votre validation (N2)",
        ];
    }
}
