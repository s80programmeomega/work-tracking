<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Anti-sabotage bypass activated</title></head>
<body style="font-family: sans-serif; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">

<h2 style="color: #d97706;">Direct escalation to N1 — Bypass activated</h2>

<p>Hello {{ $notifiable->nom }},</p>

<p>
    <strong>{{ $author->nom }}</strong> has activated the anti-sabotage bypass for task
    <strong>{{ $resultat->tache->titre }}</strong> and submitted their result directly to N1.
</p>

<h3 style="color: #374151; margin-top: 24px;">Submitted result</h3>
<div style="background: #f9fafb; border: 1px solid #e5e7eb; padding: 16px; border-radius: 6px;">
    <p><strong>Completion rate:</strong> {{ $resultat->taux_realisation }}%</p>
    <p><strong>Results obtained:</strong><br>{{ $resultat->resultats_obtenus }}</p>
    @if($resultat->difficultes_rencontrees)
    <p><strong>Difficulties encountered:</strong><br>{{ $resultat->difficultes_rencontrees }}</p>
    @endif
</div>

@if($resultat->commentaire_n0)
<h3 style="color: #374151; margin-top: 24px;">N0 comment (return)</h3>
<div style="background: #fff5f5; border-left: 4px solid #e53e3e; padding: 16px; border-radius: 4px;">
    <p style="margin: 0;">{{ $resultat->commentaire_n0 }}</p>
</div>
@endif

<h3 style="color: #374151; margin-top: 24px;">Bypass reason</h3>
<div style="background: #fffbeb; border-left: 4px solid #d97706; padding: 16px; border-radius: 4px;">
    <p style="margin: 0;">{{ $resultat->motif_bypass }}</p>
</div>

<p style="margin-top: 24px;">
    As the N1 validator, you must review this result taking the above context into account
    before making your decision.
</p>

<p style="margin-top: 32px;">
    <a href="{{ url('/taches/' . $resultat->tache_id) }}"
       style="background: #d97706; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px;">
        View result
    </a>
</p>

<p style="color: #718096; font-size: 12px; margin-top: 40px;">Work Tracking — CERD Africa</p>
</body>
</html>
