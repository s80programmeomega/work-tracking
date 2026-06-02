<?php

return [
    'ticket_submitted' => 'Votre ticket a été soumis avec succès. Nous vous répondrons dans les plus brefs délais.',
    'status_updated' => 'Statut du ticket mis à jour.',
    'reply_added' => 'Réponse ajoutée avec succès.',
    'unauthorized' => 'Accès non autorisé.',

    'categories' => [
        'bug' => 'Problème technique',
        'feature' => 'Demande de fonctionnalité',
        'billing' => 'Facturation',
        'account' => 'Compte',
        'other' => 'Autre',
    ],

    'statuses' => [
        'open' => 'Ouvert',
        'in_progress' => 'En cours',
        'resolved' => 'Résolu',
    ],

    'reproducibilities' => [
        'always' => 'Toujours',
        'sometimes' => 'Parfois',
        'rarely' => 'Rarement',
        'not_reproducible' => 'Non reproductible',
        'na' => 'Non applicable',
    ],

    'validation' => [
        'invalid_category' => 'Catégorie invalide.',
        'invalid_reproducibility' => 'Valeur de reproductibilité invalide.',
        'subject_required' => 'Le sujet est obligatoire.',
        'message_required' => 'Le message est obligatoire.',
        'attachment_too_large' => 'La pièce jointe ne doit pas dépasser 5 Mo.',
        'reply_required' => 'Une réponse est obligatoire lors d\'un changement de statut.',
    ],
];
