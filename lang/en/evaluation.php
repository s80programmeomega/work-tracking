<?php

return [
    'errors' => [
        'cannot_view_pending' => 'You do not have permission to view pending validations.',
        'cannot_view_score' => 'You do not have permission to view evaluation scores.',
        'cannot_view_others_score' => 'You do not have permission to view another user\'s score.',
    ],

    'criteria' => [
        'n1_validated_despite_return' => 'Unjustified return (penalty)',
        'n1_confirmed_return' => 'Confirmed return (bonus)',
    ],

    'dashboard' => [
        'title' => 'Pending validations',
        'subtitle' => 'Results awaiting N1 or N2 action, sorted by deadline.',
        'refresh' => 'Refresh',
        'columns' => [
            'task' => 'Task',
            'assignee' => 'Assignee',
            'statut' => 'Status',
            'deadline' => 'Deadline',
            'bypass' => 'Bypass',
        ],
        'stats' => [
            'pending_n1' => 'Pending N1',
            'pending_n2' => 'Pending N2',
            'urgent' => 'Urgent (< 24h)',
            'total' => 'Total',
        ],
        'alerts' => [
            'urgent' => 'Urgent (< 24h)',
            'bypass' => 'Active bypass',
            'escalades_abusives' => 'Abusive escalations',
        ],
        'empty' => [
            'n1' => 'No pending N1 results',
            'n2' => 'No pending N2 results',
        ],
    ],

    'notifications' => [
        'score_updated' => [
            'subject' => 'Your score has been updated',
        ],
    ],
];
