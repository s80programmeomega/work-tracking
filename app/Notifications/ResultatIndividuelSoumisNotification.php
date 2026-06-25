<?php

namespace App\Notifications;

use App\Models\Tache;
use App\Models\TacheResultat;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * 📧 Notification: Résultat individuel soumis
 */
class ResultatIndividuelSoumisNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Tache $tache,
        public User $assignee,
        public TacheResultat $resultat
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('📋 Nouveau résultat à valider')
            ->greeting("Bonjour {$notifiable->nom},")
            ->line("{$this->assignee->nom} a soumis son résultat pour la tâche **{$this->tache->titre}**.")
            ->line("**Taux de réalisation:** {$this->resultat->taux_realisation}%")
            ->line("Activité: {$this->tache->activite->nom}")
            ->action('Valider le résultat', url("/taches/{$this->tache->id}?tab=results"))
            ->line('Merci de valider ce résultat dans les meilleurs délais.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'resultat_soumis',
            'tache_id' => $this->tache->id,
            'tache_titre' => $this->tache->titre,
            'resultat_id' => $this->resultat->id,
            'assignee_id' => $this->assignee->id,
            'assignee_nom' => $this->assignee->nom,
            'taux_realisation' => $this->resultat->taux_realisation,
            'url' => "/taches/{$this->tache->id}?tab=results",
        ];
    }
}
