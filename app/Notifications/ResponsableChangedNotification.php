<?php

namespace App\Notifications;

use App\Models\Activite;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ResponsableChangedNotification extends Notification
{
    use Queueable;

    public function __construct(public Activite $activite, public $oldResponsable, public $newResponsable)
    {
    }

    public function via($notifiable)
    {
        return ['database', 'mail']; // notification + e-mail
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Changement de responsable - Activité : ' . $this->activite->nom)
            ->greeting('Bonjour ' . $notifiable->nom)
            ->line('Le responsable de l’activité "' . $this->activite->nom . '" a été modifié.')
            ->line('Ancien responsable : ' . $this->oldResponsable->nom)
            ->line('Nouveau responsable : ' . $this->newResponsable->nom)
            ->action('Voir l’activité', url('/activites/' . $this->activite->id))
            ->line('Merci d’utiliser WorkTracking.');
    }

    public function toArray($notifiable)
    {
        return [
            'type' => 'responsable_changed',
            'title' => 'Nouveau responsable d’activité',
            'message' => "{$this->oldResponsable->nom} a transféré la responsabilité de l’activité {$this->activite->nom} à {$this->newResponsable->nom}.",
            'activite_id' => $this->activite->id,
            'activite_nom' => $this->activite->nom,
            'old_responsable' => $this->oldResponsable->nom,
            'new_responsable' => $this->newResponsable->nom,
        ];
    }
}
