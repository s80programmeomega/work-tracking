<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Your evaluation sheet is ready</title></head>
<body style="font-family: sans-serif; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">

<h2 style="color: #1e40af;">Evaluation sheet ready</h2>

<p>Hello {{ $agent->nom }},</p>

<p>
    Your evaluation sheet for the period
    <strong>{{ $periode_start }} → {{ $periode_end }}</strong>
    has just been recomputed.
</p>

<div style="background: #f9fafb; border: 1px solid #e5e7eb; padding: 16px; border-radius: 6px; text-align: center; margin: 24px 0;">
    <div style="font-size: 14px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">Global score</div>
    <div style="font-size: 48px; font-weight: bold; color: {{ $score_pct >= 75 ? '#16a34a' : ($score_pct >= 50 ? '#d97706' : '#dc2626') }}; margin-top: 8px;">
        {{ $score_pct }}%
    </div>
</div>

<p>
    Sign in to the app to see the breakdown of the 8 criteria and the
    history of your directed and assigned tasks.
</p>

<p style="margin-top: 32px;">
    <a href="{{ url('/evaluations/personnel/' . $agent->id . '/historique') }}"
       style="background: #1e40af; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px;">
        View my sheet
    </a>
</p>

<p style="color: #718096; font-size: 12px; margin-top: 40px;">Work Tracking — Direction Générale CERD Africa</p>
</body>
</html>
