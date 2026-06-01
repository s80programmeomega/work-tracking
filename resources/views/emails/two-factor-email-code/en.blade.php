<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Verification Code</title></head>
<body style="font-family: sans-serif; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">

<h2 style="color: #3182ce;">Verification Code</h2>

<p>Hello {{ $user->prenom ?? $user->nom }},</p>

<p>Here is your one-time verification code to sign in to your account:</p>

<div style="background: #ebf8ff; border-left: 4px solid #3182ce; padding: 24px; margin: 24px 0; border-radius: 4px; text-align: center;">
    <p style="margin: 0 0 8px; font-size: 13px; color: #718096; text-transform: uppercase; letter-spacing: 1px;">Your code</p>
    <p style="margin: 0; font-size: 36px; font-weight: bold; letter-spacing: 8px; color: #2b6cb0;">{{ $code }}</p>
</div>

<p style="color: #718096; font-size: 13px;">
    This code is valid for <strong>{{ $expiresInMinutes }} minutes</strong> and can only be used once.
    Never share it with anyone.
</p>

<p style="color: #718096; font-size: 13px;">
    If you did not attempt to sign in, ignore this email and immediately secure your account.
</p>

<hr style="border: none; border-top: 1px solid #e2e8f0; margin: 24px 0;">
<p style="color: #a0aec0; font-size: 12px;">
    This email was sent automatically — please do not reply.
</p>

</body>
</html>
