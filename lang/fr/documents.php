<?php

return [
    'actions' => [
        'upload' => 'Téléverser',
        'download' => 'Télécharger',
        'delete' => 'Supprimer',
        'share' => 'Partager',
        'view' => 'Voir',
    ],

    'share' => [
        'email_subject' => 'Un document a été partagé avec vous : :nom',
        'email_greeting' => 'Bonjour,',
        'email_intro' => ':user a partagé le document « :nom » avec vous.',
        'email_footer' => 'Ce lien est valable pour un accès direct au document.',
        'success' => 'Document partagé par email avec succès.',
    ],

    'notifications' => [
        'uploaded' => [
            'title' => 'Nouveau document',
            'body' => ':user a ajouté « :nom » au projet.',
        ],
        'deleted' => [
            'title' => 'Document supprimé',
            'body' => ':user a supprimé le document « :nom ».',
        ],
        'shared' => [
            'title' => 'Document partagé',
            'body' => ':user a partagé « :nom » avec :email.',
        ],
    ],

    'errors' => [
        'not_found' => 'Document introuvable.',
        'unauthorized_view' => 'Vous n\'avez pas accès à ce document.',
        'unauthorized_upload' => 'Vous n\'avez pas la permission d\'uploader des documents ici.',
        'unauthorized_delete' => 'Vous n\'avez pas la permission de supprimer ce document.',
        'unauthorized_share' => 'Vous n\'avez pas la permission de partager ce document.',
        'upload_failed' => 'L\'upload a échoué. Veuillez réessayer.',
    ],
];
