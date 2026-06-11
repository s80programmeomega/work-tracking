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
| View members + activity (`workspaces.view_members`) | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |

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

## Search Permissions

> Phase 6. Two tiers: global (workspace-wide) for owner/manager; scoped (assigned resources only) for cadre/collaborateur/stagiaire.

| Permission | owner | manager | cadre | collaborateur | stagiaire | observateur |
|---|:---:|:---:|:---:|:---:|:---:|:---:|
| `search.global` — Recherche workspace complète | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `search.scoped` — Recherche limitée aux ressources assignées | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ |

---

## Help Center Permissions

> Phase 7. Read is granted to every authenticated role (the help center is general documentation). Management is **action-based** (no single broad `manage`): create, edit, publish, delete, image upload, and category management are separate permissions. By default all management permissions go to `owner`/`directeur` (via `forRole('owner')` = `all()` minus task-participant exclusions) and `super_admin` (`['*']`); managers and below stay read-only. Each is enforced per-endpoint in `AdminHelpController` via `authorizeHelp()` / `authorizeHelpAny()` (super_admin, OR the permission held in any of the user's workspaces — help content is global, not per-workspace).

| Permission | owner | manager | cadre | collaborateur | stagiaire | observateur |
|---|:---:|:---:|:---:|:---:|:---:|:---:|
| `help_articles.read` — Consulter le centre d'aide | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| `help_articles.create` — Créer un brouillon | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `help_articles.edit` — Modifier un article | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `help_articles.publish` — Publier / dépublier | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `help_articles.delete` — Supprimer (corbeille) | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `help_articles.upload_image` — Téléverser / supprimer images | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `help_categories.manage` — Gérer les catégories (CRUD) | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |

> **No `help_articles.manage` permission exists.** The frontend helper `canManageHelpArticles` (in `useWorkspacePermissions.js`) is **not** a permission — it is a pure OR-aggregate of the granular helpers, used only to decide whether to show the back-office entry link. Authorization for every action is enforced server-side per-endpoint.
>
> **Category management UI:** `/admin/help-categories` (list + create/edit/delete via `HelpCategoryModal.vue`), plus an inline "new category" modal in the article form — both gated by `help_categories.manage` (`canManageHelpCategories`).
>
> **Global search inclusion (Phase 7):** published help articles, categories, and images are indexed in Scout (`help_articles`, `help_categories`, `help_images` collections) and appear in `GET /api/search`. Only published content is indexed (`shouldBeSearchable`); drafts never appear, and images are indexed only while their parent article is published. Help content is global (no workspace/tier filter) but still requires an authenticated, search-eligible user.

> The grants are intentionally splittable: a custom contextual role can hold, e.g., only `help_articles.create` (draft authoring) without `publish`/`delete`. The defaults above mirror the previous single-`manage` scope, just decomposed.

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
| 2026-06-04 | Phase 6 | `search.global` + `search.scoped` added to `Permission.php` + `Permission::all()` + `forRole()`. `search.global` → owner/manager; `search.scoped` → cadre/collaborateur/stagiaire. Mirror in `Permission.js`. `canSearchGlobal` + `canSearchScoped` + `canSearch` in `useWorkspacePermissions.js`. Both in `WorkspaceController.user_permissions` (3 locations). Matrix: new "Search Permissions" section with 2 rows. |
| 2026-06-07 | Phase 7 | `help_articles.read` + `help_articles.manage` added to `Permission.php` + `Permission::all()` + `forRole()`. `read` → all contextual roles (owner via `all()`, manager/cadre/collaborateur/stagiaire/observateur explicit); `manage` → owner only (via `all()` minus task-participant exclusions) + super_admin (`['*']`). Mirror in `Permission.js`. `canReadHelpArticles` + `canManageHelpArticles` in `useWorkspacePermissions.js`. `can_help_articles_read` + `can_help_articles_manage` in `WorkspaceController.user_permissions` (3 locations). Server-side gate is `AdminHelpController::authorizeManage()` (super_admin OR `ownedWorkspaces()->exists()`). Matrix: new "Help Center Permissions" section with 2 rows. 15 PHPUnit tests green. |
| 2026-06-07 | Phase 7 (granular) | Replaced the broad `help_articles.manage` with **6 action-based permissions**: `help_articles.create/edit/publish/delete/upload_image` + `help_categories.manage`. Updated `Permission.php` (consts + `all()`; owner still gets all via `all()` minus exclusions, read-only roles unchanged), `Permission.js`. `useWorkspacePermissions.js` gains `canCreate/Edit/Publish/Delete/UploadHelpImages` + `canManageHelpCategories` (+ aggregate `canManageHelpArticles`). `WorkspaceController.user_permissions` now exposes `can_help_articles_create/edit/publish/delete/upload_image` + `can_help_categories_manage` (3 locations). `AdminHelpController` checks per-action via `authorizeHelp()`/`authorizeHelpAny()` (renamed from `authorizeManage` to avoid clashing with the base `Controller::authorize()`), granting if super_admin OR the permission is held in any of the user's owned/joined workspaces. Frontend list page gates each action button (create/edit/publish/delete). Matrix Help section expanded to 6 rows. 18 PHPUnit tests green (3 new granularity tests). |
| 2026-06-10 | Phase 11E | `workspaces.view_members` added to `Permission.php` (const + `all()` + `forRole('owner')` via array_diff + `forRole('manager')` explicit). Mirror in `Permission.js`. `canViewMembers` computed in `useWorkspacePermissions.js`. `can_view_members` in `WorkspaceController.user_permissions` (3 locations). New endpoint `GET /api/workspaces/{workspace}/users` in `WorkspaceController::workspaceUsers()` — paginated, searchable, gated by `abort_unless(WORKSPACES_VIEW_MEMBERS)`. New `WorkspaceUsers.vue` page + `workspace.users` route + sidebar entry for owner/manager. Matrix workspace-level row added. 8 PHPUnit tests green. |
