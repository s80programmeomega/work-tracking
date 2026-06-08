<?php

return [
    'trial' => [
        'banner' => 'Votre essai gratuit expire dans :days jour(s).',
        'expires_in' => 'Expire dans :days jour(s)',
        'expired' => 'Votre période d\'essai a expiré. Veuillez passer à un plan payant pour continuer.',
        'expiring_soon' => 'Votre essai expire bientôt',
        'dismiss' => 'Fermer',
    ],
    'limits' => [
        'members' => 'Vous avez atteint la limite de :max membres pour le plan d\'essai.',
        'file_size' => 'Ce fichier dépasse la taille maximale autorisée (:max Mo) pour le plan d\'essai.',
        'storage' => 'Vous avez atteint la limite de stockage (:max Mo) pour le plan d\'essai.',
    ],
    'errors' => [
        'trial_expired' => 'Votre période d\'essai a expiré. Veuillez passer à un plan payant.',
        'locked' => 'L\'accès à ce workspace est suspendu. Un abonnement actif est requis pour continuer.',
        'limit_reached' => [
            'add_member' => 'Limite de membres atteinte pour le plan d\'essai (:max maximum).',
            'upload_file' => 'Ce fichier dépasse la taille maximale autorisée pour le plan d\'essai.',
        ],
    ],
    'plan_errors' => [
        'cannot_delete_free' => 'Le plan gratuit de repli ne peut pas être supprimé.',
        'cannot_delete_in_use' => 'Ce plan est utilisé par au moins un workspace et ne peut pas être supprimé.',
    ],
    'notifications' => [
        'trial_expiring' => [
            'subject' => 'Votre essai Work Tracking expire bientôt',
            'line1' => 'Votre période d\'essai pour le workspace « :workspace » se termine dans :days jour(s).',
            'line2' => 'Passez à un plan payant pour continuer à utiliser toutes les fonctionnalités.',
            'action' => 'Gérer l\'abonnement',
        ],
        'trial_expired' => [
            'subject' => 'Votre essai Work Tracking a expiré',
            'line1' => 'Votre période d\'essai pour le workspace « :workspace » est terminée.',
            'line2' => 'Certaines fonctionnalités sont maintenant bloquées. Passez à un plan payant pour les débloquer.',
            'action' => 'Voir les plans',
        ],
        'limit_reached' => [
            'subject' => 'Limite atteinte — :workspace',
            'line1' => 'La limite « :limit » a été atteinte dans le workspace « :workspace ».',
            'line2' => 'Valeur actuelle : :current / Maximum autorisé : :max.',
            'action' => 'Gérer l\'abonnement',
        ],
    ],
];
