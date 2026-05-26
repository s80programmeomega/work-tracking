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
        'mandatory_subtasks_not_done' => 'Some mandatory subtasks are not yet complete. Please finish them before submitting your result.',
    ],
    'success' => [
        'approuve_n0' => 'Result approved and forwarded to N1.',
        'renvoye_n0' => 'Result returned to the author.',
        'bypass_active' => 'Bypass activated. Your result has been forwarded directly to the N1 validator.',
    ],

    'bypass' => [
        'submit_label' => 'Submit directly to N1',
        'motif_placeholder' => 'Explain why you consider the return unjustified (min. 50 characters)…',
        'abusive_escalations_label' => 'Abusive escalations',
        'n1_context_panel_title' => 'N1 validation context',
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
        'bypass_active' => [
            'subject' => 'Anti-sabotage bypass activated — action required',
        ],
        'escalades_abusives' => [
            'subject' => 'Alert: abusive escalations detected',
            'line1' => ':nom has triggered an abusive escalations flag on task ":tache".',
            'line2' => 'Number of consecutive invalid bypasses: :count.',
            'action' => 'View task',
        ],
    ],
];
