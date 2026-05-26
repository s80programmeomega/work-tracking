<?php

return [
    'actions' => [
        'upload' => 'Upload',
        'download' => 'Download',
        'delete' => 'Delete',
        'share' => 'Share',
        'view' => 'View',
    ],

    'share' => [
        'email_subject' => 'A document has been shared with you: :nom',
        'email_greeting' => 'Hello,',
        'email_intro' => ':user shared the document ":nom" with you.',
        'email_footer' => 'This link provides direct access to the document.',
        'success' => 'Document successfully shared by email.',
    ],

    'notifications' => [
        'uploaded' => [
            'title' => 'New document',
            'body' => ':user added ":nom" to the project.',
        ],
        'deleted' => [
            'title' => 'Document deleted',
            'body' => ':user deleted the document ":nom".',
        ],
        'shared' => [
            'title' => 'Document shared',
            'body' => ':user shared ":nom" with :email.',
        ],
    ],

    'errors' => [
        'not_found' => 'Document not found.',
        'unauthorized_view' => 'You do not have access to this document.',
        'unauthorized_upload' => 'You do not have permission to upload documents here.',
        'unauthorized_delete' => 'You do not have permission to delete this document.',
        'unauthorized_share' => 'You do not have permission to share this document.',
        'upload_failed' => 'Upload failed. Please try again.',
    ],
];
