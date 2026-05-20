# Permissions Matrix — Work Tracking v2.0

> **This file is a live reference.** Update it every time a new permission is added to `PermissionService`, `RolePermissionSeeder`, or any frontend composable.
>
> Source of truth: `app/Services/PermissionService.php` + `app/Enums/Role.php` + `database/seeders/RolePermissionSeeder.php`

---

## Legend

| Symbol | Meaning |
|---|---|
| ✅ | Granted by default for this role |
| ❌ | Not granted |
| 🔑 | Granted only via explicit pivot flag (per-row, not per-role) |
| 👤 | Granted only to the resource owner/uploader |

---

## Global Roles (Spatie — assigned to the user account)

| Role | Who | Default capability |
|---|---|---|
| `super_admin` | Platform administrator | Bypasses ALL permission checks |
| `directeur` | Workspace creator/owner | Full control over their workspace |
| `utilisateur` | Newly registered user | Can only create a workspace |

---

## Contextual Roles (stored in pivot tables — scoped per workspace/project/activity)

Assigned in: `workspace_members`, `projet_user`, `activite_user`, `tache_user`

---

## Workspace-Level Permissions

| Permission | super_admin | directeur/owner | manager | cadre | collaborateur | stagiaire | observateur |
|---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| View workspace | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Manage workspace (settings) | ✅ | ✅ | 🔑 | ❌ | ❌ | ❌ | ❌ |
| Invite workspace member | ✅ | ✅ | 🔑 | ❌ | ❌ | ❌ | ❌ |
| Remove workspace member | ✅ | ✅ | 🔑 | ❌ | ❌ | ❌ | ❌ |
| Create project | ✅ | ✅ | 🔑 | ❌ | ❌ | ❌ | ❌ |

> 🔑 pivot flag: `can_create_projects`, `can_invite_members`, `can_delete_members`, `can_manage_settings` on `workspace_members.permissions` (JSON)

---

## Project-Level Permissions

| Permission | super_admin | directeur/owner | manager | cadre | collaborateur | stagiaire | observateur |
|---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| View project | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Edit project | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Delete project | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Manage project members | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Validate N2 (task results) | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |

---

## Activity-Level Permissions

| Permission | super_admin | directeur/owner | manager | cadre | collaborateur | stagiaire | observateur |
|---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| View activity | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Edit activity | ✅ | ✅ | ✅ | 🔑 | ❌ | ❌ | ❌ |
| Delete activity | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Create task | ✅ | ✅ | ✅ | ✅ | 🔑 | ❌ | ❌ |
| Validate N1 (task results) | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ |
| Assign users to tasks | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |

> 🔑 pivot flag: `can_edit_activity`, `can_create_tasks` on `activite_user`

---

## Task-Level Permissions

| Permission | super_admin | directeur/owner | manager | cadre | collaborateur | stagiaire | observateur |
|---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| View task | ✅ | ✅ | ✅ | ✅ | ✅ (if assigned) | ✅ (if assigned) | ✅ (if assigned) |
| Edit task | ✅ | ✅ | ✅ | ✅ | 🔑 | ❌ | ❌ |
| Delete task | ✅ | ✅ | ❌ | 🔑 | ❌ | ❌ | ❌ |
| Submit result (TacheResultat) | ❌ | ❌ | ❌ | ❌ | ✅ | ✅ | ❌ |
| Create subtask | ✅ | ✅ | ✅ | ✅ | 🔑 | ❌ | ❌ |
| Approve result at N0 | ✅ | ✅ | ✅ | ✅ | 🔑 | ❌ | ❌ |
| Return result at N0 with comment | ✅ | ✅ | ✅ | ✅ | 🔑 | ❌ | ❌ |
| Activate bypass | ❌ | ❌ | ❌ | ❌ | ✅ (own result) | ✅ (own result) | ❌ |

> 🔑 pivot flag: `can_edit` on `tache_user`, `can_delete_tasks` on `activite_user`, `is_responsable` on `tache_user` (for subtask creation and N0 approval)

---

## Document-Level Permissions

| Permission | super_admin | directeur/owner | manager | cadre | collaborateur | stagiaire | observateur |
|---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| View document | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Upload document | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ |
| Edit document | ✅ | 👤 | 👤 | 👤 | 👤 | 👤 | ❌ |
| Delete document | ✅ | 👤 | 👤 | 👤 | 👤 | 👤 | ❌ |
| Share document by email | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |

> 👤 uploader-based: only the user who uploaded the document can edit/delete it (regardless of role)

---

## Evaluation & Scoring Permissions

> Added in Tasks 7, 9, 10 — update this section as those tasks are implemented.

| Permission | super_admin | directeur/owner | manager | cadre | collaborateur | stagiaire | observateur |
|---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| View pending validations | ✅ | ✅ | ✅ (own projects) | ✅ (own activities) | ❌ | ❌ | ❌ |
| View evaluation score | ✅ | ✅ | ✅ (own scope) | ✅ (own assignees) | ✅ (own only) | ✅ (own only) | ✅ (own only) |
| View agent evaluation sheet | ✅ | ✅ | ✅ (own scope) | ✅ (own assignees) | ✅ (own only) | ✅ (own only, read-only) | ❌ |
| Export evaluation sheet | ✅ | ✅ | ✅ (own scope) | ✅ (own assignees) | ❌ | ❌ | ❌ |
| View evaluation dashboard | ✅ | ✅ | ✅ (own projects) | ❌ | ❌ | ❌ | ❌ |

---

## Platform Admin Permissions

> Added in Task 14 — update this section when implemented.

| Permission | super_admin | all others |
|---|:---:|:---:|
| Access platform dashboard | ✅ | ❌ |
| Manage all workspaces | ✅ | ❌ |
| Manage all users | ✅ | ❌ |
| Manage subscriptions | ✅ | ❌ |

---

## Subscription Permissions

> Added in Task 13 — update this section when implemented.

| Permission | super_admin | directeur/owner | all others |
|---|:---:|:---:|:---:|
| Manage workspace subscription | ✅ | ❌ | ❌ |

---

## Changelog

| Date | Task | Change |
|---|---|---|
| 2026-05-12 | Planning | Initial matrix created from existing `PermissionService` |
| 2026-05-14 | Task 2 | `sous_taches.view/create/update/delete/assign_intervenant` — `SousTachePolicy` + `PermissionService` |
| 2026-05-14 | Task 3 | `canAssignSousTacheIntervenant` wired into `RolePermissionSeeder` |
| 2026-05-14 | Task 4 | `canCreateSousTache`, `canAssignSousTacheIntervenant` in `useActivitePermissions.js`. `can_create_subtask` in `TacheResource` |
| 2026-05-15 | Task 5 | `canApprouverN0`, `canRenvoyerN0` in `PermissionService`. `resultats.approuver_n0/renvoyer_n0` seeded. `useTachePermissions.js` created. `can_approuver_n0`, `can_renvoyer_n0` in `TacheResultatResource` |
| 2026-05-18 | Task 6 | `resultats.activer_bypass` added to `Permission.php`, `forRole()` (collaborateur + stagiaire), `Permission.js`. `canActiverBypass` in `useTachePermissions.js`. `can_activer_bypass` in `TacheResultatResource`. "Activate bypass" row already in matrix from earlier session |
| 2026-05-19 | Task 7 | `evaluations.view_pending` + `evaluations.view_score` added to `Permission.php`, `forRole()` (pending: owner/manager/cadre; score: all roles except contextual-only ones). Mirror in `Permission.js`. `canViewPendingValidations` + `canViewEvaluationScore` in `useWorkspacePermissions.js`. `WorkspaceController` user_permissions payload extended in 3 locations. Matrix: observateur gets ✅ (own only) for view evaluation score |
