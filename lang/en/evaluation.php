<?php

return [
    'errors' => [
        'cannot_view_pending' => 'You do not have permission to view pending validations.',
        'cannot_view_score' => 'You do not have permission to view evaluation scores.',
        'cannot_view_others_score' => 'You do not have permission to view another user\'s score.',
        'cannot_view_fiche' => 'You do not have permission to view this evaluation sheet.',
        'no_workspace' => 'No current workspace selected.',
        'immutable_post_n2' => 'This task is locked: it has been validated at N2 and can no longer be modified.',
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
        'sheet_ready' => [
            'subject' => 'Your evaluation sheet is ready',
        ],
        'unjustified_return_alert' => [
            'subject' => 'Alert: high unjustified-return rate',
            'line1' => ':nom has exceeded the unjustified-return threshold (:rate% of N1 decisions reversed a return).',
            'line2' => 'Period assessed: :start → :end.',
            'action' => 'View their sheet',
        ],
    ],
];
