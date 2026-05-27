<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Durée d'essai par défaut (jours)
    |--------------------------------------------------------------------------
    | Appliquée à tout nouveau workspace. Peut être surchargée par workspace
    | via workspaces.trial_duration_days (super_admin uniquement).
    */
    'trial_duration_days' => (int) env('SUBSCRIPTION_TRIAL_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Limites du plan d'essai
    |--------------------------------------------------------------------------
    */
    'free_max_members' => (int) env('SUBSCRIPTION_FREE_MAX_MEMBERS', 5),
    'free_max_file_size_mb' => (int) env('SUBSCRIPTION_FREE_MAX_FILE_MB', 2),
    'free_max_storage_mb' => (int) env('SUBSCRIPTION_FREE_MAX_STORAGE_MB', 100),

    /*
    |--------------------------------------------------------------------------
    | Seuil d'alerte (jours restants avant expiration)
    |--------------------------------------------------------------------------
    */
    'expiry_warning_days' => (int) env('SUBSCRIPTION_EXPIRY_WARNING_DAYS', 7),
];
