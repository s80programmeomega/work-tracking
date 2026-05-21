<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Your daily digest</title></head>
<body style="font-family: sans-serif; color: #333; max-width: 640px; margin: 0 auto; padding: 24px;">

<h2 style="color: #1e40af;">Your daily digest</h2>

<p>Hello {{ $user->nom }},</p>

<p>
    Here's a recap of your
    <strong>{{ $notifications->count() }} unread notification{{ $notifications->count() > 1 ? 's' : '' }}</strong>
    since your last digest.
</p>

@foreach ($grouped as $type => $group)
<div style="margin-top: 24px; border-top: 1px solid #e5e7eb; padding-top: 16px;">
    <h3 style="color: #374151; margin-bottom: 8px;">
        @switch($type)
            @case('resultat_soumis_n0') Results submitted (N0) — {{ $group->count() }} @break
            @case('resultat_renvoye_n0') Results returned (N0) — {{ $group->count() }} @break
            @case('resultat_approuve_n0') Results approved (N0) — {{ $group->count() }} @break
            @case('resultat_transmis_auto') Auto-forwarded to N1 — {{ $group->count() }} @break
            @case('bypass_activated') Bypasses activated — {{ $group->count() }} @break
            @case('escalades_abusives') Abusive escalations — {{ $group->count() }} @break
            @case('score_updated') Score updates — {{ $group->count() }} @break
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
        <li style="color: #9ca3af; font-style: italic;">… and {{ $group->count() - 5 }} more</li>
        @endif
    </ul>
</div>
@endforeach

<p style="margin-top: 32px;">
    <a href="{{ url('/notifications') }}"
       style="background: #1e40af; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px;">
        View all your notifications
    </a>
</p>

<p style="color: #9ca3af; font-size: 12px; margin-top: 32px; border-top: 1px solid #e5e7eb; padding-top: 16px;">
    You're receiving this digest based on your notification preferences.
    <a href="{{ url('/notifications/preferences') }}" style="color: #1e40af;">Edit your preferences</a>.
</p>

<p style="color: #9ca3af; font-size: 11px;">Work Tracking — CERD Africa</p>
</body>
</html>
