# Roles & Permissions Refactor — Implementation Plan

## Problem Statement

The current permission system has three uncoordinated layers that can silently diverge:

1. **Spatie global roles** (`super_admin`, `directeur`, `utilisateur`) — DB-driven, context-free
2. **Pivot ENUM strings** (`workspace_members.role`, `projet_user.role`, etc.) — contextual but hardcoded in PHP/JS, not connected to Spatie
3. **Pivot boolean columns** (`activite_user.can_edit_tasks`, etc.) — per-row overrides, disconnected from the role system

**Consequences:**
- Role→permission logic hardcoded in `PermissionService` (30+ `in_array($role, ['owner', 'manager'])` calls)
- Same authorization decision duplicated across PermissionService, model helpers (`canBeEditedBy`), and API resources
- Magic role strings scattered across 30+ files — a typo grants no access silently
- Admin UI (Task 14) cannot change what `cadre` can do without a code deploy
- `ProjetMemberRole` enum defines `admin/member/viewer` but DB has `manager/cadre/collaborateur` — always inconsistent

## Solution: DB-Driven Contextual Roles via Spatie FK

Replace the `role` ENUM string columns in all pivot tables with `role_id` foreign keys pointing to Spatie's `roles` table. All role→permission mappings live exclusively in `role_has_permissions`. A single `ContextualPermissionGate` resolves effective permissions by walking the resource hierarchy.

**Super admin rule:** `super_admin` is the only globally-scoped role. It bypasses all permission checks app-wide via `Gate::before()`, regardless of workspace. All other roles are strictly contextual — scoped to a specific pivot row.

**Workspace creator rule:** When a workspace is created, the creator's `workspace_members` pivot row is set to `role_id` pointing to the `owner` role. No global role assignment needed.

---

## New Architecture

```
Spatie roles table          role_has_permissions (editable via admin UI)
┌─────────────────┐         ┌──────────────────────────────────────────┐
│ id │ name        │ ──────▶ │ role_id │ permission_id                  │
│  1 │ super_admin │         │    2    │ taches.edit                    │
│  2 │ owner       │         │    3    │ projets.view                   │
│  3 │ manager     │         │    4    │ activites.validate_n1          │
│  4 │ cadre       │         │    5    │ taches.submit_result           │
│  5 │ collaborateur│        └──────────────────────────────────────────┘
│  6 │ stagiaire   │
│  7 │ observateur │
│  8 │task_responsable│      ← virtual role for is_responsable=true flag
└─────────────────┘

Pivot tables — role string replaced by FK
┌──────────────────────────────────────────────┐
│ workspace_members:  workspace_id, user_id, role_id ──▶ roles.id │
│ projet_user:        projet_id,    user_id, role_id ──▶ roles.id │
│ activite_user:      activite_id,  user_id, role_id ──▶ roles.id │
│ tache_user:         tache_id,     user_id, role_id ──▶ roles.id │
└──────────────────────────────────────────────┘

Request flow:
Controller → $this->authorize('update', $tache)
                  ↓
         TachePolicy::update(User, Tache)
                  ↓
  ContextualPermissionGate::userCan(user, 'taches.edit', tache)
      1. Walk hierarchy: workspace → projet → activite → tache
      2. Collect role_ids from all pivot rows for this user
      3. workspace.owner_id === user.id → add 'owner' role_id
      4. tache_user.is_responsable → add 'task_responsable' role_id
      5. Load permissions for all role_ids from Spatie DB (cached)
      6. Merge pivot boolean overrides (can_edit_tasks, can_edit, etc.)
      7. Return 'taches.edit' ∈ deduplicated set
```

---

## Role Definitions

### Global Roles (Spatie — assigned to user account, context-free)

| Role | Value | Scope | Who |
|---|---|---|---|
| Super Admin | `super_admin` | **App-wide** — bypasses all checks | Platform administrator |
| Directeur | `directeur` | Global Spatie role | Workspace creator (also gets `owner` pivot row) |
| Utilisateur | `utilisateur` | Global Spatie role | New signup, can only create a workspace |

### Contextual Roles (Spatie roles — assigned via pivot `role_id`, scoped per resource)

| Role | Value | Valid at levels | Key capabilities |
|---|---|---|---|
| Owner | `owner` | workspace, projet | Full control, auto-assigned to workspace creator |
| Manager | `manager` | workspace, projet, activite | N2 validation, sees all projects |
| Cadre | `cadre` | workspace, projet, activite | N1 validation, manages activities |
| Collaborateur | `collaborateur` | workspace, projet, activite, tache | Executes tasks, submits results |
| Stagiaire | `stagiaire` | workspace, projet, activite, tache | Limited task access, submits results |
| Observateur | `observateur` | workspace, projet, activite, tache | Read-only |
| Task Responsable | `task_responsable` | tache (virtual — `is_responsable=true`) | N0 validation, creates subtasks |

### Permission Propagation (hierarchy)

A user's effective permissions on a resource = union of permissions from all roles they hold **at or above** that resource level. Deduplication guaranteed by `ContextualPermissionGate`.

```
Workspace role (propagates to all resources in this workspace)
  └── Project role (propagates to activities and tasks in this project)
        └── Activity role (propagates to tasks in this activity)
              └── Task role (task-level only — submit_result, approve_n0)
```

Example: A user who is `manager` in workspace and `collaborateur` on a task gets the union of both roles' permissions. If both grant `taches.view`, it appears once.

---

## Validation Flow (unchanged)

```
collaborateur / stagiaire  →  submits TacheResultat
        ↓
    cadre (N1)  →  validates at activity level
        ↓
   manager (N2)  →  validates at project level
        ↓
    task marked complete
```

`taches.submit_result` is only grantable at task level — collaborateur/stagiaire on `tache_user`.
`activites.validate_n1` is grantable at activity level — cadre on `activite_user`.
`taches.validate_n2` is grantable at project level — manager on `projet_user`.

---

## Permission String Constants

All permission strings defined once in `app/Permissions/Permission.php` (PHP constants) and mirrored in `resources/js/permissions/Permission.js` (JS exports). No magic strings anywhere.

**Permissions catalogue:**
```
workspaces.view, workspaces.create_project, workspaces.invite_member,
workspaces.remove_member, workspaces.manage_settings
projets.view, projets.edit, projets.delete, projets.manage_members
activites.view, activites.edit, activites.delete, activites.create_task, activites.validate_n1
taches.view, taches.edit, taches.delete, taches.submit_result,
taches.approve_n0, taches.create_subtask, taches.validate_n1, taches.validate_n2
sous_taches.view, sous_taches.edit, sous_taches.delete, sous_taches.assign
documents.view, documents.upload, documents.delete, documents.share
resultats.approuver_n0, resultats.renvoyer_n0
```

---

## Default Role→Permission Seeding

`RolePermissionSeeder` seeds defaults into `role_has_permissions`. Admin UI overwrites at runtime.

| Role | Permission set |
|---|---|
| `owner` | All permissions |
| `manager` | All except `taches.submit_result` |
| `cadre` | projets.view, activites.*, taches.view/edit/validate_n1/approve_n0, sous_taches.*, documents.view/upload |
| `collaborateur` | projets.view, activites.view, taches.view/submit_result/approve_n0, sous_taches.view/edit, documents.view/upload |
| `stagiaire` | projets.view, activites.view, taches.view/submit_result, sous_taches.view, documents.view |
| `observateur` | projets.view, activites.view, taches.view, sous_taches.view, documents.view |
| `task_responsable` | taches.approve_n0, taches.create_subtask, sous_taches.assign |

---

## Backend Authorization Layer

### ContextualPermissionGate (`app/Permissions/ContextualPermissionGate.php`)

Single resolver — the only place permission decisions are made (beyond the super_admin Gate::before bypass).

```php
class ContextualPermissionGate {
    public function userCan(User $user, string $permission, Model $resource): bool
    // Walks hierarchy, collects role_ids, loads Spatie permissions, merges overrides, deduplicates

    private function collectRoleIds(User $user, Model $resource): array
    // Returns unique role_id integers from all pivot levels at/above resource

    private function resolvePermissions(array $roleIds): array
    // Loads from role_has_permissions (request-level cache, no N+1)

    private function applyPivotOverrides(User $user, Model $resource, array $perms): array
    // Merges activite_user boolean columns, tache_user boolean columns into permission set
}
```

### Policies (thin wrappers)

```php
class TachePolicy {
    public function update(User $user, Tache $tache): bool {
        return $this->gate->userCan($user, Permission::TACHES_EDIT, $tache);
    }
}
```

`Gate::before()` in `AuthServiceProvider` handles `super_admin` — no policy method is ever called for super_admin.

### PermissionService (relationship helpers only)

After refactor: no `canXxx()` authorization methods. Only:
- `isResponsable(User, Model): bool`
- `isMember(User, Model): bool`
- `getContextualRoleName(User, Model): ?string`
- `getPivotFlags(User, Tache): array`

---

## Frontend Authorization Layer

### Permission.js (`resources/js/permissions/Permission.js`)

Mirror of PHP Permission constants. All composables and components import from here.

### Composables (read API response — no raw pivot derivation)

```js
// useActivitePermissions(activite)
// reads activite.value.permissions (pre-computed by ContextualPermissionGate in ActiviteResource)
// returns { canView, canEdit, canCreateTask, canValidateN1, ... }
```

All composables read from the API resource's `permissions` object. They never re-derive permissions from raw `pivot.role` or boolean columns.

### authStore

Retains `isSuperAdmin` getter (reads `user.is_super_admin`). Removes numeric role hierarchy levels (unused). No changes to login/logout flow.

---

## Migration Path

### Phase 1 — Infrastructure (no behavior change)
1. Create `app/Permissions/Permission.php`
2. Create `app/Permissions/ContextualPermissionGate.php`
3. Seed contextual roles into Spatie `roles` table
4. Migration: add `role_id` FK to pivot tables, populate from existing `role` string, drop `role` ENUM
5. Bind `ContextualPermissionGate` in `AppServiceProvider`

### Phase 2 — Backend wiring
6. Update Policies to use `ContextualPermissionGate`
7. Refactor `PermissionService` — remove `canXxx()`, keep helpers
8. Remove model-level permission methods (`canBeEditedBy`, `canBeValidatedN1By`, etc.)
9. Update API Resources to use `ContextualPermissionGate` for `permissions` key

### Phase 3 — Frontend alignment
10. Create `resources/js/permissions/Permission.js`
11. Update composables to read `resource.permissions` instead of raw pivot
12. Remove raw pivot role checks from components

### Phase 4 — Cleanup
13. Delete `app/Enums/ProjetMemberRole.php`
14. Delete `app/Services/MemberRemovalService copy.php`
15. Remove `User::hasRoleLevel()`, `User::isAdmin()` remnants
16. Run pint, run tests, update docs

---

## Admin UI (Task 14) Integration

The admin panel interacts with Spatie's standard API:

```php
// Super admin changes what cadre can do — no deploy needed
$cadreRole = Role::findByName('cadre');
$cadreRole->syncPermissions([
    Permission::PROJETS_VIEW,
    Permission::ACTIVITES_VIEW,
    Permission::TACHES_VIEW,
    // taches.edit removed from cadre
]);
```

`ContextualPermissionGate` reads `role_has_permissions` on each request (request-level cache). Change is effective immediately on next request.

Workspace owners get a scoped UI to assign/change member `role_id` in pivot tables. Super admin gets global role→permission matrix editor.

---

## Tests

- All 65 existing PHPUnit tests must pass after refactor
- `PermissionsMatrixTest` (17 tests) exercises every role × endpoint combination
- `RoleVisibilityTest` (6 Dusk browser tests) validates UI-level permission enforcement
- New: unit test for `ContextualPermissionGate` covering:
  - Super admin bypass
  - Workspace owner auto-gets owner permissions
  - User with manager at workspace + collaborateur at task → merged permissions, no duplicates
  - is_responsable flag grants task_responsable permissions
  - Pivot boolean override grants permission beyond role
  - Admin UI change to role_has_permissions takes effect on next request
