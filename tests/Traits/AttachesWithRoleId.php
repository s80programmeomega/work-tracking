<?php

declare(strict_types=1);

namespace Tests\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Models\Role;

/**
 * Helper for tests that need to attach pivot members using role_id FK
 * instead of the old role string column.
 */
trait AttachesWithRoleId
{
    /** @var array<string, int> Per-test cache — reset each setUp via clearRoleIdCache() */
    private array $roleIdCache = [];

    protected function clearRoleIdCache(): void
    {
        $this->roleIdCache = [];
    }

    protected function roleId(string $roleName): int
    {
        if (! isset($this->roleIdCache[$roleName])) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();

            if (! $role) {
                throw new \RuntimeException(
                    "Role '{$roleName}' not found in DB. Did you run RolePermissionSeeder?"
                );
            }

            $this->roleIdCache[$roleName] = (int) $role->id;
        }

        return $this->roleIdCache[$roleName];
    }

    /**
     * Attach a user to a relationship (workspace/project/activity/task) with a named role.
     * Automatically resolves the role name to a Spatie role_id FK.
     */
    protected function attachWithRole(
        BelongsToMany $relation,
        int $userId,
        string $roleName,
        array $extra = []
    ): void {
        $relation->attach($userId, array_merge(['role_id' => $this->roleId($roleName)], $extra));
    }

    /**
     * Call in setUp() after seeder, when using RefreshDatabase, to reset the ID cache.
     * Required because RefreshDatabase rolls back the roles table between tests.
     */
    protected function refreshRoleIdCache(): void
    {
        $this->roleIdCache = [];
    }
}
