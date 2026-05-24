<?php

namespace App\Notifications;

use App\Models\TacheResultat;
use App\Services\NotificationService;
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
        // G3: 'validation_complete' = high-signal (le N1 doit voir vite
        // que son travail est confirmé) → in-app + broadcast + mail + push
        // selon préférences. Géré par NotificationService::channelsFor().
        return app(NotificationService::class)->channelsFor($notifiable, 'validation_complete');
    }

    public function toMail($notifiable): MailMessage
    {
        // G3: ce mail est généré quand channelsFor() inclut 'mail' (cas par
        // défaut pour validation_complete, high-signal). Avant G3 la classe
        // n'avait pas de toMail() — elle ne ciblait que 'database' — ce qui
        // crashait dès qu'on essayait de passer par mail. Ajouté pour rester
        // cohérent avec ResultatValideN2Notification reçue côté auteur.
        return (new MailMessage)
            ->subject('🎉 Validation complète d\'un résultat que vous aviez validé (N1)')
            ->greeting("Bonjour {$notifiable->nom},")
            ->line("Le résultat de {$this->resultat->user->nom} pour la tâche **{$this->resultat->tache->titre}** est maintenant entièrement validé.")
            ->line('**Validation:** N1 ✓ (par vous) + N2 ✓')
            ->action('Voir la tâche', url("/taches/{$this->resultat->tache->id}"))
            ->line('Cette notification est purement informative — aucune action n\'est requise.');
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
            'message' => "Le résultat de {$this->resultat->user->nom} que vous aviez validé (N1) est maintenant entièrement validé par {$this->resultat->validateurN2->nom} (N2)",
        ];
    }
}
