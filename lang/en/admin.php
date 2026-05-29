<?php

return [
    'dashboard' => [
        'title' => 'Platform Dashboard',
        'workspaces' => 'Workspaces',
        'users' => 'Users',
        'trial' => 'Trial',
        'paid' => 'Paid',
        'expired' => 'Expired',
        'expiring_soon' => 'Expiring Soon',
        'active_last_30d' => 'Active (30 d)',
    ],
    'workspaces' => [
        'title' => 'Workspace Management',
        'subscription' => 'Subscription',
        'members' => 'Members',
        'status' => 'Status',
        'active' => 'Active',
        'suspended' => 'Suspended',
    ],
    'users' => [
        'title' => 'User Management',
        'last_login' => 'Last Login',
        'workspace' => 'Current Workspace',
        'role_updated' => 'User role updated successfully.',
    ],
    'roles' => [
        'title' => 'Roles & Permissions',
        'permissions_updated' => 'Permissions updated successfully.',
        'global' => 'Global',
        'contextual' => 'Contextual',
    ],
    'actions' => [
        'extend_trial' => 'Extend Trial',
        'suspend_workspace' => 'Suspend Workspace',
        'reactivate_workspace' => 'Reactivate Workspace',
        'workspace_suspended' => 'Workspace suspended successfully.',
        'workspace_reactivated' => 'Workspace reactivated successfully.',
    ],
    'notifications' => [
        'trial_extended' => [
            'subject' => 'Your Work Tracking trial has been extended — :workspace',
            'line1' => 'Good news! Your trial period for workspace « :workspace » has been extended.',
            'line2' => 'New duration: :days day(s) — expires on :expires_at.',
            'action' => 'Go to my workspace',
        ],
        'workspace_suspended' => [
            'subject' => 'Your Work Tracking workspace has been suspended — :workspace',
            'line1' => 'Your workspace « :workspace » has been suspended by an administrator.',
            'reason' => 'Reason: :reason',
            'line2' => 'For any questions, please contact support.',
            'action' => 'Contact support',
        ],
    ],
];
