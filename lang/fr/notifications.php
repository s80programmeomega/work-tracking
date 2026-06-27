<?php

return [
    'daily_digest' => [
        'subject' => 'Votre récapitulatif quotidien (:count notifications)',
    ],

    'greeting' => 'Bonjour :name,',
    'salutation' => 'Cordialement, :app',

    'temp_admin_subject' => 'Accès administrateur temporaire accordé',
    'temp_admin_intro' => ':granted_by vous a accordé un accès administrateur temporaire sur le workspace **:workspace**.',
    'temp_admin_expiry_line' => 'Cet accès expirera le **:date**. À l\'expiration : :action.',
    'temp_admin_expiry_suspend' => 'votre compte sera suspendu',
    'temp_admin_expiry_delete' => 'votre compte sera supprimé',
    'temp_admin_cta' => 'Accéder au tableau de bord admin',
    'temp_admin_credentials' => 'Vos identifiants de connexion — Email : **:email** | Mot de passe : **:password** — Changez votre mot de passe dès votre première connexion.',
    'temp_admin_warning' => 'Si vous n\'êtes pas à l\'origine de cette demande, contactez immédiatement le propriétaire du workspace.',

    'session_revoked' => [
        'subject' => 'Une session a été révoquée',
        'greeting' => 'Bonjour :name,',
        'line1' => 'Une session de votre compte a été déconnectée.',
        'line2' => 'Déconnexion effectuée le : :time.',
        'line3' => 'Si vous n\'êtes pas à l\'origine de cette action, changez immédiatement votre mot de passe.',
    ],
];
