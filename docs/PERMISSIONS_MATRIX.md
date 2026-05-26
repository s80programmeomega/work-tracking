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
| Approve result at N0 | ✅ | 🔑 | 🔑 | 🔑 | 🔑 | 🔑 | ❌ |
| Return result at N0 with comment | ✅ | 🔑 | 🔑 | 🔑 | ✅ | ❌ | ❌ |
| Activate bypass | ❌ | ❌ | ❌ | ❌ | ✅ (own result) | ✅ (own result) | ❌ |

> 🔑 pivot flag: `can_edit` on `tache_user`, `can_delete_tasks` on `activite_user`, `is_responsable` on `tache_user` (grants the virtual `task_responsable` role which carries N0 actions, subtask creation, and assignment)

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
| View agent evaluation sheet | ✅ | ✅ | ✅ (own scope) | ✅ (own assignees) | ✅ (own only) | ✅ (own only, read-only) | ✅ (own only, read-only) |
| Export evaluation sheet | ✅ | ✅ | ✅ (own scope) | ✅ (own assignees) | ❌ | ❌ | ❌ |
| View evaluation dashboard | ✅ | ✅ | ✅ (own projects) | ✅ (own activities) | ❌ | ❌ | ❌ |
| View workspace-wide task list | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Inline edit task (status/priority) | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |

---

## Notification Permissions

> Added in Task 8 (Reverb + Web Push notifications).

| Permission | super_admin | directeur/owner | manager | cadre | collaborateur | stagiaire | observateur |
|---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| Manage workspace notification preferences (defaults, mandatory channels) | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Manage personal notification preferences (digest, channels) | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |

> All users can edit their own notification preferences via `/notifications/preferences`. The workspace-level permission is for setting defaults that apply to new members and for overriding member preferences when required (e.g., mandatory bypass notifications for managers).

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
| 2026-05-21 | Defaults rebalance | Option A cleanup of `Permission::forRole()`: (1) `owner` exclusion list extended to drop auto-grant of `resultats.activer_bypass`, `resultats.approuver_n0`, `resultats.renvoyer_n0` — symmetric with the existing exclusion of `taches.submit_result` and `taches.approve_n0`; (2) `RESULTATS_RENVOYER_N0` removed from `manager` and `cadre` (they are N2/N1 reviewers, not N0 gatekeepers — N0 actions remain available via the `task_responsable` virtual role); (3) `stagiaire` gains `DOCUMENTS_UPLOAD` and `SOUS_TACHES_EDIT` to close the accidental capability gap with `collaborateur`. Matrix Task-Level rows updated to show 🔑 for N0 actions on roles that only get them via `is_responsable` pivot. 90 tests still green. |
| 2026-05-21 | Task 8 | `notifications.manage_preferences` added to `Permission.php` and `Permission::all()`. Granted only via the `owner` contextual role's array_diff (workspace-level notification policy is owner/directeur-only per spec). Mirror in `Permission.js`. `canManageNotificationPreferences` in `useWorkspacePermissions.js`. `WorkspaceController.user_permissions` payload extended in 3 locations. Matrix gains a "Notification Permissions" section. |
| 2026-05-22 | Task 8b | No new permission strings — Web Push is gated by per-user `notification_preferences.push_enabled` (existing column) plus the presence of at least one active row in `push_subscriptions` (existing table). Permission to manage personal preferences was already covered by Task 8's "Manage personal notification preferences" row (granted to every authenticated role). No matrix row changes; this changelog entry documents the no-op for traceability. |
| 2026-05-22 | Task 9 (perms) | `evaluations.view_fiche` + `evaluations.export_fiche` added to `Permission.php` and `Permission::all()`. Seeded in `forRole()`: VIEW for manager/cadre/collaborateur/stagiaire/observateur (own-scope for collaborateur+stagiaire+observateur, broader for cadre+manager — controller re-applies per-target scope); EXPORT only for manager/cadre (+ owner via the `all() minus exclusions` array_diff). Mirror in `Permission.js`. `canViewFicheEvaluation` + `canExportFicheEvaluation` in `useWorkspacePermissions.js`. `WorkspaceController.user_permissions` payload extended in 3 locations. `PermissionService::canViewFicheEvaluation/canExportFicheEvaluation` add the per-target scope check (own / cadre→assignees / manager→activity / owner→workspace). Matrix observateur changed from ❌ to ✅ (own only, read-only) for "View agent evaluation sheet" — aligns with the plan's read-only own clause. 132 tests still green. |
| 2026-05-26 | Task 10 | `evaluations.view_dashboard` + `evaluations.view_workspace_taches` + `taches.inline_edit` added to `Permission.php`, `Permission::all()`, and `forRole()`. Dashboard: owner/manager/cadre (scoped); workspace tasks: owner only. Mirror in `Permission.js`. `canViewEvaluationDashboard` + `canViewWorkspaceTaches` + `canInlineEditTache` in `useWorkspacePermissions.js`. `WorkspaceController.user_permissions` payload extended in 3 locations. `PermissionService` gains 3 helpers. Matrix "View evaluation dashboard" cadre row corrected to ✅ (own activities); 2 new rows added (workspace-wide task list, inline edit). 216 tests green. |
