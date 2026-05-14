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
    ],
    'notifications' => [
        'assigned' => [
            'subject' => 'You have been assigned to a subtask',
            'line1' => 'You have been assigned to the subtask: :titre',
            'line2' => 'Assigned by: :by',
            'due' => 'Due date: :date',
        ],
    ],
];
