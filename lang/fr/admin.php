<?php

return [
    'dashboard' => [
        'title' => 'Tableau de bord plateforme',
        'workspaces' => 'Workspaces',
        'users' => 'Utilisateurs',
        'trial' => 'Essais',
        'paid' => 'Payants',
        'expired' => 'Expirés',
        'expiring_soon' => 'Expirent bientôt',
        'active_last_30d' => 'Actifs (30 j)',
    ],
    'workspaces' => [
        'title' => 'Gestion des workspaces',
        'subscription' => 'Abonnement',
        'members' => 'Membres',
        'status' => 'Statut',
        'active' => 'Actif',
        'suspended' => 'Suspendu',
    ],
    'users' => [
        'title' => 'Gestion des utilisateurs',
        'last_login' => 'Dernière connexion',
        'workspace' => 'Workspace actuel',
        'role_updated' => 'Rôle utilisateur mis à jour avec succès.',
    ],
    'roles' => [
        'title' => 'Rôles & Permissions',
        'permissions_updated' => 'Permissions mises à jour avec succès.',
        'global' => 'Global',
        'contextual' => 'Contextuel',
    ],
    'actions' => [
        'extend_trial' => 'Prolonger l\'essai',
        'suspend_workspace' => 'Suspendre le workspace',
        'reactivate_workspace' => 'Réactiver le workspace',
        'workspace_suspended' => 'Workspace suspendu avec succès.',
        'workspace_reactivated' => 'Workspace réactivé avec succès.',
    ],
    'notifications' => [
        'trial_extended' => [
            'subject' => 'Votre essai Work Tracking a été prolongé — :workspace',
            'line1' => 'Bonne nouvelle ! Votre période d\'essai pour le workspace « :workspace » a été prolongée.',
            'line2' => 'Nouvelle durée : :days jour(s) — expire le :expires_at.',
            'action' => 'Accéder à mon workspace',
        ],
        'workspace_suspended' => [
            'subject' => 'Votre workspace Work Tracking a été suspendu — :workspace',
            'line1' => 'Votre workspace « :workspace » a été suspendu par un administrateur.',
            'reason' => 'Motif : :reason',
            'line2' => 'Pour toute question, veuillez contacter le support.',
            'action' => 'Contacter le support',
        ],
    ],
];
