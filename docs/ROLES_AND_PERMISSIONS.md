# Roles & Permissions Reference

## Global Roles (Spatie — assigned to the user account)

| Role | Value | Who | Key capability |
|---|---|---|---|
| Super Admin | `super_admin` | Platform administrator | Bypasses all permission checks |
| Directeur | `directeur` | Workspace owner | Full control over their workspace(s) |
| Utilisateur | `utilisateur` | Newly registered user | Can only create a workspace |

A new signup gets `utilisateur`. On first workspace creation, they become `directeur`.

---

## Contextual Roles (stored in pivot tables — per workspace/project/activity)

| Role | Value | N validation | Scope |
|---|---|---|---|
| Owner | `owner` | — | Workspace/Project pivot only |
| Manager | `manager` | N2 validator | Workspace, Project, Activity |
| Cadre | `cadre` | N1 validator | Workspace, Project, Activity |
| Collaborateur | `collaborateur` | Submits results | Workspace, Project, Activity |
| Stagiaire | `stagiaire` | Submits results | Workspace, Project, Activity |
| Observateur | `observateur` | Read-only | Workspace, Project, Activity |

### Task level (`tache_user` pivot)
- `role` — `collaborateur`, `stagiaire`, `observateur`
- `is_responsable` (boolean) — can create subtasks for this task

---

## Validation Flow

```
collaborateur / stagiaire  →  submits TacheResultat
        ↓
    cadre (N1)  →  validates at activity level
        ↓
   manager (N2)  →  validates at project level
        ↓
    task marked complete
```

---

## Task Breakdown Flow (subtasks)

```
cadre creates task
    → assigns collaborateur with is_responsable = true
        → responsable creates subtasks
            → assigns subtasks to other collaborateurs
```

---

## Backend: PermissionService

All authorization logic lives in `app/Services/PermissionService.php`.
Controllers inject it and call methods directly — no Laravel Policies.

**Key methods:**

```php
// Workspace
canManageWorkspace(User, Workspace): bool
canViewWorkspace(User, Workspace): bool
canCreateProject(User, Workspace): bool
canInviteWorkspaceMember(User, Workspace): bool
canRemoveWorkspaceMember(User, Workspace): bool
canManageWorkspaceSettings(User, Workspace): bool

// Project
canViewProject(User, Projet): bool
canEditProject(User, Projet): bool
canDeleteProject(User, Projet): bool
canManageProjectMembers(User, Projet): bool

// Activity
canViewActivity(User, Activite): bool
canEditActivity(User, Activite): bool
canDeleteActivity(User, Activite): bool
canCreateTask(User, Activite): bool

// Task
canViewTask(User, Tache): bool
canEditTask(User, Tache): bool
canDeleteTask(User, Tache): bool
canValidateN1(User, Tache): bool   // cadre
canValidateN2(User, Tache): bool   // manager
canCreateSubtask(User, Tache): bool // is_responsable

// Document
canViewDocument(User, Document): bool
canEditDocument(User, Document): bool
canDeleteDocument(User, Document): bool
```

Usage in controllers:
```php
abort_unless($this->permissionService->canEditProject(auth()->user(), $projet), 403);
```

---

## Frontend: Permission Composables

| Composable | Scope | Key exports |
|---|---|---|
| `useWorkspacePermissions(workspaceRef)` | Workspace | `isDirecteur`, `isManager`, `canCreateProjects`, `canManageSettings` |
| `useProjetPermissions(projetRef)` | Project | `isResponsable`, `canEdit`, `canManageMembers`, `canCreateActivity` |
| `useActivitePermissions(activiteRef)` | Activity | `isCadre`, `canCreateTask`, `canValidateN1`, `canAssignUsers` |

---

## Pivot Tables Summary

| Table | Role column values |
|---|---|
| `workspace_members` | `owner`, `manager`, `cadre`, `collaborateur`, `stagiaire`, `observateur` |
| `projet_user` | `owner`, `manager`, `cadre`, `collaborateur`, `stagiaire`, `observateur` |
| `activite_user` | `cadre`, `collaborateur`, `stagiaire`, `observateur` |
| `tache_user` | `collaborateur`, `stagiaire`, `observateur` + `is_responsable` boolean |

---

## Test Users (seeded)

| Email | Password | Role |
|---|---|---|
| superadmin@worktracking.com | password | super_admin |
| directeur@worktracking.com | password | directeur (workspace owner) |
| manager@worktracking.com | password | manager (N2 validator) |
| cadre@worktracking.com | password | cadre (N1 validator) |
| collaborateur@worktracking.com | password | collaborateur |
| stagiaire@worktracking.com | password | stagiaire |
| observateur@worktracking.com | password | observateur (read-only) |
| utilisateur@worktracking.com | password | utilisateur (no workspace) |
