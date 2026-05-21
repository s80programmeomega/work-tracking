<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Votre récapitulatif quotidien</title></head>
<body style="font-family: sans-serif; color: #333; max-width: 640px; margin: 0 auto; padding: 24px;">

<h2 style="color: #1e40af;">Votre récapitulatif quotidien</h2>

<p>Bonjour {{ $user->nom }},</p>

<p>
    Voici un récapitulatif de vos
    <strong>{{ $notifications->count() }} notification{{ $notifications->count() > 1 ? 's' : '' }}</strong>
    non lue{{ $notifications->count() > 1 ? 's' : '' }} depuis votre dernier digest.
</p>

@foreach ($grouped as $type => $group)
<div style="margin-top: 24px; border-top: 1px solid #e5e7eb; padding-top: 16px;">
    <h3 style="color: #374151; margin-bottom: 8px;">
        @switch($type)
            @case('resultat_soumis_n0') Résultats soumis (N0) — {{ $group->count() }} @break
            @case('resultat_renvoye_n0') Résultats renvoyés (N0) — {{ $group->count() }} @break
            @case('resultat_approuve_n0') Résultats approuvés (N0) — {{ $group->count() }} @break
            @case('resultat_transmis_auto') Transmissions automatiques au N1 — {{ $group->count() }} @break
            @case('bypass_activated') Bypass activés — {{ $group->count() }} @break
            @case('escalades_abusives') Escalades abusives — {{ $group->count() }} @break
            @case('score_updated') Mises à jour de score — {{ $group->count() }} @break
            @default {{ ucfirst(str_replace('_', ' ', $type)) }} — {{ $group->count() }}
        @endswitch
    </h3>
    <ul style="margin: 0; padding-left: 20px;">
        @foreach ($group->take(5) as $n)
        <li style="margin-bottom: 6px; color: #4b5563;">
            {{ $n->data['tache_titre'] ?? $n->data['title'] ?? 'Notification' }}
            <span style="color: #9ca3af; font-size: 12px;">— {{ $n->created_at->diffForHumans() }}</span>
        </li>
        @endforeach
        @if ($group->count() > 5)
        <li style="color: #9ca3af; font-style: italic;">… et {{ $group->count() - 5 }} de plus</li>
        @endif
    </ul>
</div>
@endforeach

<p style="margin-top: 32px;">
    <a href="{{ url('/notifications') }}"
       style="background: #1e40af; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px;">
        Voir toutes vos notifications
    </a>
</p>

<p style="color: #9ca3af; font-size: 12px; margin-top: 32px; border-top: 1px solid #e5e7eb; padding-top: 16px;">
    Vous recevez ce digest selon vos préférences de notification.
    <a href="{{ url('/notifications/preferences') }}" style="color: #1e40af;">Modifier vos préférences</a>.
</p>

<p style="color: #9ca3af; font-size: 11px;">Work Tracking — Direction Générale CERD Africa</p>
</body>
</html>
