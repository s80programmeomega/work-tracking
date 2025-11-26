<?php

namespace App\Notifications;

use App\Models\TacheResultat;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * 📧 Notification: Résultat rejeté
 */
class ResultatRejeteNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public TacheResultat $resultat,
        public User $validateur,
        public string $commentaire,
        public string $level
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $niveau = $this->level === 'n1' ? 'N1' : 'N2';

        return (new MailMessage)
            ->subject("❌ Votre résultat nécessite des corrections")
            ->greeting("Bonjour {$notifiable->nom},")
            ->line("Votre résultat pour la tâche **{$this->resultat->tache->titre}** a été rejeté par {$this->validateur->nom} (Niveau {$niveau}).")
            ->line("**Motif du rejet:**")
            ->line($this->commentaire)
            ->action('Modifier le résultat', url("/taches/{$this->resultat->tache->id}/resultats/{$this->resultat->id}/edit"))
            ->line('Merci de corriger et soumettre à nouveau votre résultat.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'resultat_rejete',
            'resultat_id' => $this->resultat->id,
            'tache_id' => $this->resultat->tache->id,
            'tache_titre' => $this->resultat->tache->titre,
            'validateur_id' => $this->validateur->id,
            'validateur_nom' => $this->validateur->nom,
            'commentaire' => $this->commentaire,
            'level' => $this->level,
            'url' => "/taches/{$this->resultat->tache->id}/resultats/{$this->resultat->id}/edit"
        ];
    }
}
