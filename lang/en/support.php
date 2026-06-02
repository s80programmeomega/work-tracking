<?php

return [
    'ticket_submitted' => 'Your ticket has been submitted successfully. We will get back to you as soon as possible.',
    'status_updated' => 'Ticket status updated.',
    'reply_added' => 'Reply added successfully.',
    'unauthorized' => 'Unauthorized access.',

    'categories' => [
        'bug' => 'Technical issue',
        'feature' => 'Feature request',
        'billing' => 'Billing',
        'account' => 'Account',
        'other' => 'Other',
    ],

    'statuses' => [
        'open' => 'Open',
        'in_progress' => 'In progress',
        'resolved' => 'Resolved',
    ],

    'reproducibilities' => [
        'always' => 'Always',
        'sometimes' => 'Sometimes',
        'rarely' => 'Rarely',
        'not_reproducible' => 'Not reproducible',
        'na' => 'Not applicable',
    ],

    'validation' => [
        'invalid_category' => 'Invalid category.',
        'invalid_reproducibility' => 'Invalid reproducibility value.',
        'subject_required' => 'Subject is required.',
        'message_required' => 'Message is required.',
        'attachment_too_large' => 'Attachment must not exceed 5 MB.',
        'reply_required' => 'A reply is required when changing status.',
    ],
];
