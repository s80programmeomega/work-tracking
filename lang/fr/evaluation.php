<?php

return [
    'errors' => [
        'cannot_view_pending' => 'Vous n\'avez pas la permission de voir les validations en attente.',
        'cannot_view_score' => 'Vous n\'avez pas la permission de voir les scores d\'évaluation.',
        'cannot_view_others_score' => 'Vous n\'avez pas la permission de voir le score d\'un autre utilisateur.',
        'cannot_view_fiche' => 'Vous n\'avez pas la permission de consulter cette fiche d\'évaluation.',
        'no_workspace' => 'Aucun workspace courant n\'est sélectionné.',
    ],

    'criteria' => [
        'n1_validated_despite_return' => 'Renvoi non justifié (pénalité)',
        'n1_confirmed_return' => 'Renvoi confirmé (bonus)',
    ],

    'dashboard' => [
        'title' => 'Validations en attente',
        'subtitle' => 'Résultats en attente d\'action N1 ou N2, triés par échéance.',
        'refresh' => 'Rafraîchir',
        'columns' => [
            'task' => 'Tâche',
            'assignee' => 'Intervenant',
            'statut' => 'Statut',
            'deadline' => 'Échéance',
            'bypass' => 'Bypass',
        ],
        'stats' => [
            'pending_n1' => 'N1 en attente',
            'pending_n2' => 'N2 en attente',
            'urgent' => 'Urgents (< 24h)',
            'total' => 'Total',
        ],
        'alerts' => [
            'urgent' => 'Urgent (< 24h)',
            'bypass' => 'Bypass actif',
            'escalades_abusives' => 'Escalades abusives',
        ],
        'empty' => [
            'n1' => 'Aucun résultat en attente N1',
            'n2' => 'Aucun résultat en attente N2',
        ],
    ],

    'notifications' => [
        'score_updated' => [
            'subject' => 'Votre score a été mis à jour',
        ],
    ],
];
