<?php

return [
    // Global Spatie roles
    'super_admin' => 'Super Admin',
    'directeur' => 'Director',
    'utilisateur' => 'User',

    // Contextual roles (stored via role_id on pivot tables — never assigned globally)
    'owner' => 'Owner',
    'manager' => 'Manager',
    'cadre' => 'Team Lead',
    'task_responsable' => 'Task Owner',
    'collaborateur' => 'Collaborator',
    'stagiaire' => 'Intern',
    'observateur' => 'Observer',
];
