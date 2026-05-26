<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Tâche assignée</title></head>
<body style="font-family: sans-serif; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">

<h2 style="color: #3182ce;">Nouvelle tâche assignée</h2>

<p>Bonjour {{ $notifiable->prenom ?? $notifiable->nom }},</p>

<p><strong>{{ $assignedBy->nom }}</strong> vous a assigné la tâche suivante :</p>

<div style="background: #ebf8ff; border-left: 4px solid #3182ce; padding: 16px; margin: 20px 0; border-radius: 4px;">
    <p style="margin: 0 0 8px; font-size: 18px; font-weight: bold;">{{ $tache->titre }}</p>
    @if ($tache->description)
        <p style="margin: 0 0 8px; color: #555;">{{ $tache->description }}</p>
    @endif
    @if ($tache->echeance)
        <p style="margin: 0; font-size: 13px; color: #718096;">
            Échéance : <strong>{{ $tache->echeance->format('d/m/Y') }}</strong>
        </p>
    @endif
</div>

@if (count($resources) > 0)
<div style="margin: 20px 0;">
    <p style="font-weight: bold; margin-bottom: 8px;">Ressources attachées :</p>
    <ul style="padding-left: 20px; margin: 0;">
        @foreach ($resources as $resource)
            <li style="margin-bottom: 4px;">
                @if ($resource['url'])
                    <a href="{{ $resource['url'] }}" style="color: #3182ce;">{{ $resource['nom'] }}</a>
                @else
                    {{ $resource['nom'] }}
                @endif
            </li>
        @endforeach
    </ul>
</div>
@endif

<p style="margin-top: 32px;">
    <a href="{{ url('/taches/' . $tache->id) }}"
       style="background: #3182ce; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px;">
        Voir la tâche
    </a>
</p>

<p style="color: #718096; font-size: 12px; margin-top: 40px;">Work Tracking — Direction Générale CERD Africa</p>
</body>
</html>
