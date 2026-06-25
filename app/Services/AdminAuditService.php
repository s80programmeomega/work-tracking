<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AdminAuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AdminAuditService
{
    public static function log(
        User $actor,
        string $action,
        ?Model $target = null,
        array $context = [],
    ): void {
        AdminAuditLog::create([
            'actor_id' => $actor->id,
            'actor_type' => self::resolveActorType($actor),
            'action' => $action,
            'target_type' => $target ? class_basename($target) : null,
            'target_id' => $target?->getKey(),
            'context' => $context ?: null,
            'ip_address' => request()->ip(),
            'created_by' => $actor->admin_expires_at ? $actor->created_by : null,
        ]);
    }

    private static function resolveActorType(User $actor): string
    {
        if ($actor->isSystemOwner()) {
            return 'system_owner';
        }

        if ($actor->isSuperAdmin() && $actor->admin_expires_at !== null) {
            return 'temporary_superadmin';
        }

        if ($actor->isSuperAdmin()) {
            return 'permanent_superadmin';
        }

        return 'directeur';
    }
}
