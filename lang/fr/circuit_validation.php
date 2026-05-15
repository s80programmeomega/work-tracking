<?php

return [
    'status' => [
        'brouillon' => 'Brouillon',
        'en_verification_n0' => 'En vérification N0',
        'en_validation_n1' => 'En validation N1',
        'en_validation_n2' => 'En validation N2',
        'valide' => 'Validé',
        'rejete' => 'Rejeté',
        'a_refaire' => 'À refaire',
    ],
    'errors' => [
        'comment_too_short' => 'Le commentaire de renvoi doit contenir au moins 30 caractères.',
        'unauthorized' => 'Vous n\'êtes pas autorisé à effectuer cette action.',
        'invalid_statut' => 'Le résultat n\'est pas dans le bon état pour cette action.',
        'motif_too_short' => 'Le motif du bypass doit contenir au moins 50 caractères.',
        'bypass_already_used' => 'Le bypass a déjà été utilisé pour cette soumission.',
    ],
    'success' => [
        'approuve_n0' => 'Résultat approuvé et transmis au N1.',
        'renvoye_n0' => 'Résultat renvoyé à l\'auteur.',
    ],
    'notifications' => [
        'soumis_n0' => [
            'subject' => 'Un résultat attend votre vérification',
            'line1' => 'Un résultat a été soumis pour la tâche : :titre',
            'line2' => 'Soumis par : :by — Taux de réalisation : :taux%',
            'action' => 'Voir le résultat',
        ],
        'renvoye_n0' => [
            'subject' => 'Votre résultat a été renvoyé pour révision',
        ],
        'approuve_n0' => [
            'subject' => 'Votre résultat a été approuvé',
            'line1' => 'Votre résultat pour la tâche : :titre a été approuvé par votre responsable.',
            'line2' => 'Il est maintenant transmis au validateur N1.',
            'action' => 'Voir la tâche',
        ],
        'transmis_auto' => [
            'subject' => 'Résultat transmis automatiquement au N1',
            'line1' => 'Le délai de vérification N0 est écoulé pour la tâche : :titre.',
            'line2' => 'Le résultat a été automatiquement transmis au validateur N1.',
            'action' => 'Voir la tâche',
        ],
    ],
];
