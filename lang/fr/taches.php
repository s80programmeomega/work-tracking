<?php

return [
    'wizard' => [
        'steps' => [
            'informations' => 'Informations de base',
            'assignation' => 'Assignation',
            'ressources' => 'Ressources',
            'validation' => 'Options de validation',
        ],
        'intervenant_picker' => [
            'placeholder' => 'Rechercher un membre…',
            'no_results' => 'Aucun membre trouvé.',
            'selected' => ':count intervenant(s) sélectionné(s)',
        ],
        'resources' => [
            'notification_sent' => 'Les intervenants ont été notifiés des ressources attachées.',
        ],
    ],

    'notifications' => [
        'assignee' => [
            'subject' => 'Nouvelle tâche assignée : :titre',
        ],
        'resources' => [
            'subject' => 'Ressources attachées à la tâche : :titre',
        ],
    ],

    'errors' => [
        'step_incomplete' => 'Veuillez compléter cette étape avant de continuer.',
        'titre_required' => 'Le titre de la tâche est obligatoire.',
        'responsable_required' => 'Vous devez sélectionner un responsable.',
    ],
];
