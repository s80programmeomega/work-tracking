<?php

namespace App\Notifications;

use App\Models\TacheResultat;
use App\Services\NotificationService;
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
        // G3: info pour N1 — passe par channelsFor() pour bénéficier du
        // canal broadcast (badge en temps réel) en plus de database.
        // Mail/push héritent du mapping high-signal de 'rejete_n2'.
        return app(NotificationService::class)->channelsFor($notifiable, 'rejete_n2');
    }

    public function toMail($notifiable): MailMessage
    {
        // G3: ajouté pour rester compatible avec channelsFor() qui peut
        // maintenant inclure 'mail' (high-signal). Avant G3 la classe
        // était database-only.
        return (new MailMessage)
            ->subject('ℹ️ Un résultat que vous aviez validé (N1) a été rejeté en N2')
            ->greeting("Bonjour {$notifiable->nom},")
            ->line("Le résultat de {$this->resultat->user->nom} pour la tâche **{$this->resultat->tache->titre}** — que vous aviez validé au niveau N1 — a été rejeté par {$this->resultat->validateurN2->nom} (N2).")
            ->line('**Motif:**')
            ->line($this->commentaire)
            ->action('Voir la tâche', url("/taches/{$this->resultat->tache->id}"))
            ->line('Cette notification est informative — aucune action n\'est requise.');
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
            'message' => "Le résultat que vous aviez validé (N1) a été rejeté par {$this->resultat->validateurN2->nom} (N2)",
        ];
    }
}
