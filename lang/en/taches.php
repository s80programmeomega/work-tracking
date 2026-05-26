<?php

return [
    'wizard' => [
        'steps' => [
            'informations' => 'Basic information',
            'assignation' => 'Assignment',
            'ressources' => 'Resources',
            'validation' => 'Validation options',
        ],
        'intervenant_picker' => [
            'placeholder' => 'Search a member…',
            'no_results' => 'No member found.',
            'selected' => ':count assignee(s) selected',
        ],
        'resources' => [
            'notification_sent' => 'Assignees have been notified of attached resources.',
        ],
    ],

    'notifications' => [
        'assignee' => [
            'subject' => 'New task assigned: :titre',
        ],
        'resources' => [
            'subject' => 'Resources attached to task: :titre',
        ],
    ],

    'errors' => [
        'step_incomplete' => 'Please complete this step before continuing.',
        'titre_required' => 'Task title is required.',
        'responsable_required' => 'You must select a responsible person.',
    ],
];
