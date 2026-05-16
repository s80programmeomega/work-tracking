# Roles & Permissions Reference

> Architecture: DB-driven contextual roles via Spatie FK.
> All role→permission mappings live in `role_has_permissions` and are editable from the admin UI (Task 14).
> See `ROLES_AND_PERMISSIONS_PLAN.md` for the full refactor plan.

---

## Global Roles (Spatie — assigned to user account, context-free)

| Role | Value | Scope | Who |
|---|---|---|---|
| Super Admin | `super_admin` | **App-wide** — bypasses ALL checks regardless of workspace | Platform administrator |
| Directeur | `directeur` | Global | Workspace creator (also receives `owner` contextual role in their workspace) |
| Utilisateur | `utilisateur` | Global | New signup — can only create a workspace |

`super_admin` is the **only** globally-scoped role. It is enforced via `Gate::before()` in `AuthServiceProvider` and never reaches any policy method.

---

## Contextual Roles (stored in pivot `role_id` → Spatie `roles` table)

These roles are **scoped to a specific resource row** (workspace/project/activity/task). A user can hold different contextual roles in different workspaces.

| Role | Value | Valid at levels | Key capabilities |
|---|---|---|---|
| Owner | `owner` | workspace, projet | Full control; auto-assigned to workspace creator |
| Manager | `manager` | workspace, projet, activite | N2 validation, sees all projects |
| Cadre | `cadre` | workspace, projet, activite | N1 validation, manages activities |
| Collaborateur | `collaborateur` | workspace, projet, activite, tache | Executes tasks, submits results |
| Stagiaire | `stagiaire` | workspace, projet, activite, tache | Limited task access, submits results |
| Observateur | `observateur` | workspace, projet, activite, tache | Read-only |
| Task Responsable | `task_responsable` | tache (virtual — `is_responsable=true` flag) | N0 validation, creates subtasks |

`task_responsable` is not stored as a pivot role string — it is derived at runtime when `tache_user.is_responsable = true`.

---

## Pivot Tables

| Table | Columns (role) | Notes |
|---|---|---|
| `workspace_members` | `role_id` → `roles.id` | Workspace creator auto-gets `owner` |
| `projet_user` | `role_id` → `roles.id` | |
| `activite_user` | `role_id` + boolean overrides | Boolean columns grant extra permissions beyond the role |
| `tache_user` | `role_id` + `is_responsable` + boolean overrides | |
| `sous_tache_user` | boolean overrides only | No role_id — inherits from tache_user |

### Pivot boolean overrides (activite_user)
These grant permissions **in addition to** the assigned role — for individual exceptions:
`can_edit_activity`, `can_delete_activity`, `can_create_tasks`, `can_edit_tasks`, `can_delete_tasks`, `can_validate_results`, `can_assign_users`, `can_delete_member`

### Pivot boolean overrides (tache_user)
`is_responsable`, `can_edit`, `can_complete`, `can_validate`

---

## Permission Propagation

A user's effective permissions on a resource = union of permissions from all roles they hold **at or above** that resource in the hierarchy. Deduplication is guaranteed.

```
Workspace role  →  propagates to all resources in this workspace
  └── Project role  →  propagates to activities and tasks in this project
        └── Activity role  →  propagates to tasks in this activity
              └── Task role  →  task-scoped only (submit_result, approve_n0)
```

---

## Validation Flow

```
collaborateur / stagiaire  →  submits TacheResultat
        ↓
    cadre (N1)  →  validates at activity level   [activites.validate_n1]
        ↓
   manager (N2)  →  validates at project level   [taches.validate_n2]
        ↓
    task marked complete
```

N0 circuit (anti-sabotage):
```
collaborateur / stagiaire submits result
    → task_responsable (is_responsable=true) reviews at task level  [resultats.approuver_n0]
        → approved: escalates to N1
        → returned: submitter can appeal (Task 6)
```

---

## Authorization Architecture

### Backend

```
Controller
  $this->authorize('update', $tache)
          ↓
  Gate::before() — super_admin? → true (bypass)
          ↓
  TachePolicy::update(User, Tache)
          ↓
  ContextualPermissionGate::userCan(user, Permission::TACHES_EDIT, tache)
    1. Walk hierarchy: workspace → projet → activite → tache
    2. Collect role_ids from pivot rows for this user at each level
    3. workspace.owner_id === user.id → add 'owner' role_id
    4. tache_user.is_responsable → add 'task_responsable' role_id
    5. Load permissions from Spatie role_has_permissions (request-cached)
    6. Merge pivot boolean overrides into permission set
    7. Return: Permission::TACHES_EDIT ∈ deduplicated set
```

**Key classes:**
- `app/Permissions/Permission.php` — typed string constants for all permission names
- `app/Permissions/ContextualPermissionGate.php` — single resolver, no duplicates possible
- `app/Policies/TachePolicy.php` (and others) — thin wrappers, one line per method
- `app/Services/PermissionService.php` — relationship helpers only (isResponsable, isMember, etc.)

### Frontend

```
API Resource (TacheResource, ActiviteResource, ProjetResource)
  Computed by ContextualPermissionGate server-side
  Returns: { permissions: { can_edit: true, can_validate_n1: false, ... } }
          ↓
Composable (useActivitePermissions, useTachePermissions, etc.)
  Reads resource.permissions — never re-derives from raw pivot
  Returns named computed booleans: canEdit, canValidateN1, canSubmitResult
          ↓
Template: v-if="canEdit"
```

**Key files:**
- `resources/js/permissions/Permission.js` — JS mirror of PHP Permission constants
- `resources/js/composables/useWorkspacePermissions.js`
- `resources/js/composables/useProjetPermissions.js`
- `resources/js/composables/useActivitePermissions.js`
- `resources/js/composables/useTachePermissions.js`

---

## Permission Strings Catalogue

All defined in `app/Permissions/Permission.php` and `resources/js/permissions/Permission.js`.

```
# Workspace
workspaces.view
workspaces.create_project
workspaces.invite_member
workspaces.remove_member
workspaces.manage_settings

# Project
projets.view
projets.edit
projets.delete
projets.manage_members

# Activity
activites.view
activites.edit
activites.delete
activites.create_task
activites.validate_n1

# Task
taches.view
taches.edit
taches.delete
taches.submit_result       ← collaborateur/stagiaire at task level only
taches.approve_n0          ← task_responsable (is_responsable=true)
taches.create_subtask      ← task_responsable
taches.validate_n1         ← cadre at activity level
taches.validate_n2         ← manager at project level

# Subtask
sous_taches.view
sous_taches.edit
sous_taches.delete
sous_taches.assign

# Documents
documents.view
documents.upload
documents.delete
documents.share

# Results / N0
resultats.approuver_n0
resultats.renvoyer_n0
```

---

## Default Role→Permission Matrix

Seeded by `RolePermissionSeeder`. Editable at runtime via admin UI (Task 14).

| Permission | owner | manager | cadre | collaborateur | stagiaire | observateur | task_responsable |
|---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| workspaces.view | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | — |
| workspaces.create_project | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | — |
| workspaces.invite_member | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | — |
| workspaces.remove_member | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | — |
| workspaces.manage_settings | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | — |
| projets.view | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | — |
| projets.edit | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | — |
| projets.delete | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | — |
| projets.manage_members | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | — |
| activites.view | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | — |
| activites.edit | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | — |
| activites.delete | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | — |
| activites.create_task | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | — |
| activites.validate_n1 | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | — |
| taches.view | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | — |
| taches.edit | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | — |
| taches.delete | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | — |
| taches.submit_result | ❌ | ❌ | ❌ | ✅ | ✅ | ❌ | — |
| taches.approve_n0 | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ✅ |
| taches.create_subtask | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ✅ |
| taches.validate_n2 | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | — |
| sous_taches.view | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | — |
| sous_taches.edit | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | — |
| sous_taches.delete | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | — |
| sous_taches.assign | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ✅ |
| documents.view | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | — |
| documents.upload | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | — |
| documents.delete | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | — |
| documents.share | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | — |
| resultats.approuver_n0 | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ✅ |
| resultats.renvoyer_n0 | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ✅ |

> `super_admin` bypasses the entire matrix via `Gate::before()` — always granted.

---

## Test Users (seeded)

| Email | Password | Global role | Contextual role (in test workspace) |
|---|---|---|---|
| superadmin@worktracking.com | password | `super_admin` | — (bypasses all) |
| directeur@worktracking.com | password | `directeur` | `owner` in workspace 1 |
| manager@worktracking.com | password | `utilisateur` | `manager` in workspace 1 |
| cadre@worktracking.com | password | `utilisateur` | `cadre` in workspace 1 |
| collaborateur@worktracking.com | password | `utilisateur` | `collaborateur` in workspace 1 |
| stagiaire@worktracking.com | password | `utilisateur` | `stagiaire` in workspace 1 |
| observateur@worktracking.com | password | `utilisateur` | `observateur` in workspace 1 |
| utilisateur@worktracking.com | password | `utilisateur` | no workspace membership |

---

## Admin UI Integration (Task 14)

The super admin panel will expose:

1. **Role→Permission matrix editor** — reads/writes `role_has_permissions` via Spatie API
2. **Member role assignment** — reads/writes `role_id` in pivot tables
3. **User global role management** — assigns `directeur` / `utilisateur` Spatie roles

Changes to `role_has_permissions` take effect on the next request (request-level cache in `ContextualPermissionGate`). No code deploy required.

---

## Changelog

| Date | Change |
|---|---|
| 2026-05-12 | Initial matrix created from existing PermissionService |
| 2026-05-14 | Task 2: sous_taches permissions added |
| 2026-05-14 | Task 3: subtask assign_intervenant wired |
| 2026-05-14 | Task 4: can_create_subtask in TacheResource |
| 2026-05-15 | Task 5: canApprouverN0, canRenvoyerN0 added |
| 2026-05-15 | Architecture: pivot ENUM → role_id FK, ContextualPermissionGate introduced |
