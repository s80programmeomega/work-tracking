<?php

namespace App\Notifications;

use App\Models\Activite;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ActiviteMemberRemoved extends Notification implements ShouldQueue
{
    use Queueable;

    protected $activite;
    protected $removedBy;

    public function __construct(Activite $activite, User $removedBy)
    {
        $this->activite = $activite;
        $this->removedBy = $removedBy;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $url = url("/projets/{$this->activite->projet_id}");

        return (new MailMessage)
            ->subject("Vous avez été retiré de l'activité: {$this->activite->nom}")
            ->greeting("Bonjour {$notifiable->nom},")
            ->line("{$this->removedBy->nom} vous a retiré de l'activité **{$this->activite->nom}**.")
            ->line("**Projet:** {$this->activite->projet->nom}")
            ->line("Vous n'avez plus accès à cette activité et à ses tâches.")
            ->action('Voir le projet', $url)
            ->line('Merci de votre collaboration !');
    }

    public function toArray($notifiable)
    {
        return [
            'type' => 'activite_member_removed',
            'activite_id' => $this->activite->id,
            'activite_nom' => $this->activite->nom,
            'activite_code' => $this->activite->code,
            'projet_id' => $this->activite->projet_id,
            'projet_nom' => $this->activite->projet->nom,
            'removed_by_id' => $this->removedBy->id,
            'removed_by_nom' => $this->removedBy->nom,
            'url' => "/projets/{$this->activite->projet_id}",
            'message' => "{$this->removedBy->nom} vous a retiré de l'activité {$this->activite->nom}"
        ];
    }
}