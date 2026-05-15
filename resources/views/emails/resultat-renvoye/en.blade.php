<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Result returned</title></head>
<body style="font-family: sans-serif; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">

<h2 style="color: #e53e3e;">Your result has been returned</h2>

<p>Hello {{ $notifiable->nom }},</p>

<p>Your result for the task <strong>{{ $resultat->tache->titre }}</strong> has been returned by <strong>{{ $n0Actor->nom }}</strong> for revision.</p>

<div style="background: #fff5f5; border-left: 4px solid #e53e3e; padding: 16px; margin: 20px 0; border-radius: 4px;">
    <p style="margin: 0; font-weight: bold;">Comment:</p>
    <p style="margin: 8px 0 0;">{{ $commentaire }}</p>
</div>

<p>Please take this feedback into account and resubmit your corrected result.</p>

<p style="margin-top: 32px;">
    <a href="{{ url('/taches/' . $resultat->tache_id) }}"
       style="background: #3182ce; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px;">
        View task
    </a>
</p>

<p style="color: #718096; font-size: 12px; margin-top: 40px;">Work Tracking — CERD Africa</p>
</body>
</html>
