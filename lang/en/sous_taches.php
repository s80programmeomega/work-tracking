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
    ],
    'success' => [
        'created' => 'Subtask created successfully.',
        'updated' => 'Subtask updated.',
        'deleted' => 'Subtask deleted.',
    ],
];
