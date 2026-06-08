<?php

return [
    'trial' => [
        'banner' => 'Your free trial expires in :days day(s).',
        'expires_in' => 'Expires in :days day(s)',
        'expired' => 'Your trial period has expired. Please upgrade to a paid plan to continue.',
        'expiring_soon' => 'Your trial is expiring soon',
        'dismiss' => 'Dismiss',
    ],
    'limits' => [
        'members' => 'You have reached the member limit (:max members) for the trial plan.',
        'file_size' => 'This file exceeds the maximum allowed size (:max MB) for the trial plan.',
        'storage' => 'You have reached the storage limit (:max MB) for the trial plan.',
    ],
    'errors' => [
        'trial_expired' => 'Your trial period has expired. Please upgrade to a paid plan.',
        'locked' => 'Access to this workspace is suspended. An active subscription is required to continue.',
        'limit_reached' => [
            'add_member' => 'Member limit reached for the trial plan (:max maximum).',
            'upload_file' => 'This file exceeds the maximum allowed size for the trial plan.',
        ],
    ],
    'plan_errors' => [
        'cannot_delete_free' => 'The free fallback plan cannot be deleted.',
        'cannot_delete_in_use' => 'This plan is used by at least one workspace and cannot be deleted.',
    ],
    'notifications' => [
        'trial_expiring' => [
            'subject' => 'Your Work Tracking trial is expiring soon',
            'line1' => 'Your trial period for workspace « :workspace » ends in :days day(s).',
            'line2' => 'Upgrade to a paid plan to keep using all features.',
            'action' => 'Manage subscription',
        ],
        'trial_expired' => [
            'subject' => 'Your Work Tracking trial has expired',
            'line1' => 'Your trial period for workspace « :workspace » has ended.',
            'line2' => 'Some features are now blocked. Upgrade to a paid plan to unlock them.',
            'action' => 'View plans',
        ],
        'limit_reached' => [
            'subject' => 'Limit reached — :workspace',
            'line1' => 'The ":limit" limit has been reached in workspace « :workspace ».',
            'line2' => 'Current value: :current / Maximum allowed: :max.',
            'action' => 'Manage subscription',
        ],
    ],
];
