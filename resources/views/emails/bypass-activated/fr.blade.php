<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Bypass anti-sabotage activé</title></head>
<body style="font-family: sans-serif; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">

<h2 style="color: #d97706;">Escalade directe au N1 — Bypass activé</h2>

<p>Bonjour {{ $notifiable->nom }},</p>

<p>
    <strong>{{ $author->nom }}</strong> a activé le bypass anti-sabotage pour la tâche
    <strong>{{ $resultat->tache->titre }}</strong> et a soumis son résultat directement au N1.
</p>

<h3 style="color: #374151; margin-top: 24px;">Résultat soumis</h3>
<div style="background: #f9fafb; border: 1px solid #e5e7eb; padding: 16px; border-radius: 6px;">
    <p><strong>Taux de réalisation :</strong> {{ $resultat->taux_realisation }}%</p>
    <p><strong>Résultats obtenus :</strong><br>{{ $resultat->resultats_obtenus }}</p>
    @if($resultat->difficultes_rencontrees)
    <p><strong>Difficultés rencontrées :</strong><br>{{ $resultat->difficultes_rencontrees }}</p>
    @endif
</div>

@if($resultat->commentaire_n0)
<h3 style="color: #374151; margin-top: 24px;">Commentaire N0 (renvoi)</h3>
<div style="background: #fff5f5; border-left: 4px solid #e53e3e; padding: 16px; border-radius: 4px;">
    <p style="margin: 0;">{{ $resultat->commentaire_n0 }}</p>
</div>
@endif

<h3 style="color: #374151; margin-top: 24px;">Motif du bypass</h3>
<div style="background: #fffbeb; border-left: 4px solid #d97706; padding: 16px; border-radius: 4px;">
    <p style="margin: 0;">{{ $resultat->motif_bypass }}</p>
</div>

<p style="margin-top: 24px;">
    En tant que validateur N1, vous devez examiner ce résultat en tenant compte du contexte ci-dessus
    avant de prendre votre décision.
</p>

<p style="margin-top: 32px;">
    <a href="{{ url('/taches/' . $resultat->tache_id) }}"
       style="background: #d97706; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px;">
        Voir le résultat
    </a>
</p>

<p style="color: #718096; font-size: 12px; margin-top: 40px;">Work Tracking — Direction Générale CERD Africa</p>
</body>
</html>
