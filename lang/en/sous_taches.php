<?php

return [
    'status' => [
        'a_faire' => 'To do',
        'en_cours' => 'In progress',
        'en_retard' => 'Overdue',
        'termine' => 'Done',
        'a_refaire' => 'To redo',
        'annule' => 'Cancelled',
    ],
    'errors' => [
        'max_depth_exceeded' => 'A subtask cannot contain other subtasks.',
        'weights_sum_invalid' => 'The sum of subtask weights cannot exceed 100%.',
        'date_exceeds_parent' => 'The due date cannot exceed the parent task due date.',
        'status_blocked' => 'The parent task status cannot be changed manually while subtasks exist.',
        'unauthorized' => 'You are not authorized to perform this action.',
    ],
    'success' => [
        'created' => 'Subtask created successfully.',
        'updated' => 'Subtask updated.',
        'deleted' => 'Subtask deleted.',
        'intervenant_assigned' => 'Intervenant assigned to subtask.',
        'intervenant_removed' => 'Intervenant removed from subtask.',
    ],
    'notifications' => [
        'assigned' => [
            'subject' => 'You have been assigned to a subtask',
            'line1' => 'You have been assigned to the subtask: :titre',
            'line2' => 'Assigned by: :by',
            'due' => 'Due date: :date',
        ],
    ],
    'ui' => [
        'section_title' => 'Subtasks',
        'add_button' => 'Add',
        'create_first' => 'Create the first subtask',
        'empty' => 'No subtasks yet.',
        'form' => [
            'title_placeholder' => 'Subtask title…',
            'description_placeholder' => 'Description (optional)…',
            'poids_label' => 'Weight (%)',
            'poids_remaining' => 'remaining: :n%',
            'echeance_label' => 'Due date',
            'echeance_max' => 'max: :date',
            'needs_validation' => 'Requires validation',
            'blocks_progress' => 'Blocks progress',
            'submit' => 'Create',
            'cancel' => 'Cancel',
        ],
        'weighted_progress' => 'Weighted progress',
        'total_poids' => 'Total weight allocated: :n% / 100%',
        'mark_done' => 'Mark as done',
        'mark_in_progress' => 'Mark as in progress',
        'blocking_badge' => 'Blocking',
        'submit_disabled_tooltip' => 'Some blocking subtasks are not yet completed.',
        'kanban_badge' => ':done/:total ST',
    ],
];
