<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Résultat renvoyé</title></head>
<body style="font-family: sans-serif; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">

<h2 style="color: #e53e3e;">Votre résultat a été renvoyé</h2>

<p>Bonjour {{ $notifiable->nom }},</p>

<p>Votre résultat pour la tâche <strong>{{ $resultat->tache->titre }}</strong> a été renvoyé par <strong>{{ $n0Actor->nom }}</strong> pour révision.</p>

<div style="background: #fff5f5; border-left: 4px solid #e53e3e; padding: 16px; margin: 20px 0; border-radius: 4px;">
    <p style="margin: 0; font-weight: bold;">Commentaire :</p>
    <p style="margin: 8px 0 0;">{{ $commentaire }}</p>
</div>

<p>Veuillez prendre en compte ce retour et soumettre à nouveau votre résultat corrigé.</p>

<p style="margin-top: 32px;">
    <a href="{{ url('/taches/' . $resultat->tache_id) }}"
       style="background: #3182ce; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px;">
        Voir la tâche
    </a>
</p>

<p style="color: #718096; font-size: 12px; margin-top: 40px;">Work Tracking — Direction Générale CERD Africa</p>
</body>
</html>
