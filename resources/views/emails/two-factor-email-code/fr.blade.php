<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Code de vérification</title></head>
<body style="font-family: sans-serif; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">

<h2 style="color: #3182ce;">Code de vérification</h2>

<p>Bonjour {{ $user->prenom ?? $user->nom }},</p>

<p>Voici votre code de vérification à usage unique pour vous connecter à votre compte :</p>

<div style="background: #ebf8ff; border-left: 4px solid #3182ce; padding: 24px; margin: 24px 0; border-radius: 4px; text-align: center;">
    <p style="margin: 0 0 8px; font-size: 13px; color: #718096; text-transform: uppercase; letter-spacing: 1px;">Votre code</p>
    <p style="margin: 0; font-size: 36px; font-weight: bold; letter-spacing: 8px; color: #2b6cb0;">{{ $code }}</p>
</div>

<p style="color: #718096; font-size: 13px;">
    Ce code est valable <strong>{{ $expiresInMinutes }} minutes</strong> et ne peut être utilisé qu'une seule fois.
    Ne le communiquez à personne.
</p>

<p style="color: #718096; font-size: 13px;">
    Si vous n'avez pas tenté de vous connecter, ignorez cet email et sécurisez immédiatement votre compte.
</p>

<hr style="border: none; border-top: 1px solid #e2e8f0; margin: 24px 0;">
<p style="color: #a0aec0; font-size: 12px;">
    Cet email a été envoyé automatiquement — veuillez ne pas y répondre.
</p>

</body>
</html>
