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

        // Task 9 — 8 weighted criteria of the evaluation sheet.
        'completion_rate' => 'Completion rate',
        'deadline_respect' => 'Deadline respect',
        'result_quality' => 'Result quality',
        'first_pass_validation' => 'First-pass validation',
        'justified_returns' => 'Justified returns',
        'inactions' => 'Inactions (timeouts)',
        'work_volume' => 'Work volume',
        'team_coordination' => 'Team coordination',
    ],

    // Task 9 — Agent evaluation sheet page.
    'sheet' => [
        'title' => 'Evaluation sheet',
        'global_score' => 'Global score',
        'period_label' => 'Period',
        'sections' => [
            'directed_tasks' => 'Directed tasks',
            'directed_subtasks' => 'Directed subtasks',
            'assignee_tasks' => 'Assigned tasks',
            'assignee_subtasks' => 'Assigned subtasks',
        ],
        'filters' => [
            'from' => 'From',
            'to' => 'To',
            'statut' => 'Status',
            'apply' => 'Apply',
            'reset' => 'Reset',
            'all' => 'All',
        ],
        'indicators' => [
            'return_quality' => 'Return quality',
            'unjustified_threshold' => 'Above threshold (40%)',
            'escalades_abusives' => 'Abusive escalations',
            'export' => 'Export',
            'justified' => 'Justified returns',
            'unjustified' => 'Unjustified returns',
            'threshold_note' => 'An unjustified-return rate above 40% triggers an automatic manager alert.',
        ],
        'criteria_panel_title' => '8-criteria breakdown',
        'weight_label' => 'Weight',
        'raw_label' => 'Value',
        'weighted_label' => 'Weighted',
        'subtask_coefficient_note' => 'Subtasks contribute to the score at coefficient 0.5.',
        'empty' => 'No items in this section for the selected period.',
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
