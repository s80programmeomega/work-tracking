<?php

return [
    'status' => [
        'brouillon' => 'Draft',
        'en_verification_n0' => 'Pending N0 check',
        'en_validation_n1' => 'Pending N1 validation',
        'en_validation_n2' => 'Pending N2 validation',
        'valide' => 'Validated',
        'rejete' => 'Rejected',
        'a_refaire' => 'To redo',
    ],
    'errors' => [
        'comment_too_short' => 'The return comment must be at least 30 characters.',
        'unauthorized' => 'You are not authorized to perform this action.',
        'invalid_statut' => 'The result is not in the correct state for this action.',
        'motif_too_short' => 'The bypass reason must be at least 50 characters.',
        'bypass_already_used' => 'The bypass has already been used for this submission.',
    ],
    'success' => [
        'approuve_n0' => 'Result approved and forwarded to N1.',
        'renvoye_n0' => 'Result returned to the author.',
    ],
    'notifications' => [
        'soumis_n0' => [
            'subject' => 'A result is waiting for your review',
            'line1' => 'A result has been submitted for task: :titre',
            'line2' => 'Submitted by: :by — Completion rate: :taux%',
            'action' => 'View result',
        ],
        'renvoye_n0' => [
            'subject' => 'Your result has been returned for revision',
        ],
        'approuve_n0' => [
            'subject' => 'Your result has been approved',
            'line1' => 'Your result for task: :titre has been approved by your supervisor.',
            'line2' => 'It has now been forwarded to the N1 validator.',
            'action' => 'View task',
        ],
        'transmis_auto' => [
            'subject' => 'Result automatically forwarded to N1',
            'line1' => 'The N0 review deadline has passed for task: :titre.',
            'line2' => 'The result has been automatically forwarded to the N1 validator.',
            'action' => 'View task',
        ],
    ],
];
