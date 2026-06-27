# Session State — Work Tracking v2.0

> **This file is updated at the END of every working session.**
> When resuming, read this file first — it tells you exactly where you are and what to do next.

---

## How to Resume

1. Read `docs/WORKING_GUIDELINES.md` (conventions + tools)
2. Read `docs/PROGRESSION.md` (full task status table)
3. Read this file (current state)
4. Say: _"I've read the docs. Resuming from [Current Task] — [what's next]."_

---

## Current Session

**Date:** 2026-06-28
**Branch:** `feature/visibility-teams-chat`
**Status:** 🔄 In progress — Phase 2 (team integration) Steps 2.1–2.6 complete. Step 2.7 (tests) deferred to end of plan.

---

## What Was Done This Session

### Phase 1 — Visibility Full Replacement (Steps 1.1–1.6)

- **Step 1.1**: Added `Permission::PROJETS_VIEW_ALL` constant, seeded to manager+, updated `useProjetPermissions.js`, updated `PERMISSIONS_MATRIX.md`.
- **Step 1.2**: Dropped `visibility` from `projets` (migration, model, policy, form requests, factory, seeder, resource, service, controller, frontend — `ProjetDetail.vue`, `projetStore.js`, `TacheUpdatedNotification.php`).
- **Step 1.3**: Dropped `visibility` from `taches` (migration, model, controller, resource, service, frontend — `TacheTable.vue`, `DetailedTaskView.vue`, `TacheForm.vue`).
- **Step 1.4**: Dropped `visibility` from `documents` (migration, `DocumentPolicy::view()`, `DocumentAccessResolver`, `DocumentService`, `DocumentController`, `DocumentResource`, factory, frontend — `DocumentCard.vue`, `DocumentUpload.vue`, `DocumentUploadModal.vue`, `DocumentEditModal.vue`, `DocumentViewerModal.vue`, `useDocuments.js`, `DocumentList.vue`).
- **Step 1.5**: Dropped `visibility` from `teams` (migration, model, service, controller, factory, seeder, frontend — `Teams.vue`, `Teams/Show.vue`).
- **Step 1.6**: Workspace settings cleanup — removed `default_project_visibility` from `Workspace::getDefaultSettings()`, `WorkspaceController` create action, `WorkspaceFactory`, `WorkspaceSeeder`, `workspaces/Create.vue`, `workspaces/Edit.vue`, `workspaces/Settings.vue`, `StoreWorkspaceRequest`, `fr.json` i18n. Also cleaned: `DocumentUploader.vue`, `TacheDetailsTab.vue`. Workspace discoverability `settings.visibility` preserved.
- **Pre-commit gates**: Pint clean (8 fixes applied), Larastan 0 errors.

### Previous Session (2026-06-27)

### Session management — UA display + reactive termination

- **Migration** `2026_06_27_101207_add_user_agent_to_personal_access_tokens_table.php` — adds `user_agent VARCHAR(512)` to `personal_access_tokens`.
- **`AuthService::issueToken()` + `refreshToken()`** — stores `request()->userAgent()` via `DB::table()` after token creation (Sanctum fillable guard blocks Eloquent update).
- **`UserController::sessions()`** — exposes `user_agent` in session list response.
- **`SessionSettings.vue`** — parses UA into `"Browser — OS"` label (Opera/Chrome/Firefox/Safari/Edge/Chromium + Windows/macOS/Linux/Android/iOS), phone icon for mobile UAs, desktop icon otherwise, "Client inconnu" fallback for null.
- **`SessionRevoked` event** (`ShouldBroadcastNow`) — fires immediately on `App.Models.User.{id}` with `{type, token_id}` as `.session.revoked`.
- **`SessionsAllRevoked` event** (`ShouldBroadcastNow`) — fires on same channel as `.sessions.all.revoked`.
- **`AuthController::revokeSession()`** — fires `SessionRevoked` before the queued `SessionRevokedNotification`.
- **`AuthService::logoutAll()`** — fires `SessionsAllRevoked` before deleting tokens.
- **`useLiveNotifications.js`** — listens on `.session.revoked` (matches token ID from localStorage `id|hash` format) and `.sessions.all.revoked`, calls `authStore.logout()` immediately → redirect to `/signin`.

### Document sharing — scope, share link, shared-with-me, policy

- **`WorkspaceController::searchMembers()`** — new endpoint `GET /workspaces/{workspace}/members/search?q=&exclude_user_ids[]` scoped to workspace members only, excludes `super_admin`/`directeur` and already-shared users.
- **`DocumentResource`** — exposes `workspace_id` field.
- **`DocumentShareModal.vue`** — uses workspace-scoped member search (`/workspaces/{workspace_id}/members/search`) instead of global `/users/search`; passes already-shared user IDs as exclusions.
- **`DocumentSharedNotification`** — share link fixed from `url("/documents/{id}/download")` (backend URL) to `config('app.frontend_url').'/documents'` (SPA route).
- **`DocumentController::sharedWithMe()`** — removed `->inWorkspace($user->current_workspace_id)` filter; shared documents are cross-workspace and must not be filtered by the recipient's current workspace.
- **`DocumentPolicy`** — all five policy methods (`view`, `update`, `delete`, `download`, `share`) now check `DocumentPermission` records via `hasExplicitPermission()` before falling back to the workspace contextual gate. Expired permissions excluded. `download` split from `view` as a dedicated policy method.
- **`DocumentController::download()`** — uses `authorize('download', $document)` instead of `authorize('view', ...)`.

### Tests added

- `WorkspaceMembersTest` — 5 new tests for `searchMembers` (happy path, exclusion, min-2-chars, outsider-forbidden, non-member not returned).
- `DocumentPermissionsTest` — cross-workspace `sharedWithMe` test + `workspace_id` in resource + share-by-email frontend URL test.
- `DocumentPolicyTest` — 6 new tests for explicit `DocumentPermission` flags (can_share, can_edit, can_delete, can_download, no-share, expired).

**Final suite: 872 passed, 6 skipped, 0 failed.**

---

## What Was Done This Session (Phase 2)

### Phase 2 — Team Integration (Steps 2.1–2.6)

- **Step 2.1**: Migration `add_use_teams_to_projets_table`, `Projet::$fillable`/`$casts`, `UpdateProjetRequest`/`StoreProjetRequest`.
- **Step 2.2**: `Permission::PROJETS_MANAGE_TEAMS`, seeded manager+, `ProjetPolicy::manageTeams()`, `ProjetResource::can_manage_teams`, `useProjetPermissions::canManageTeams`, `PERMISSIONS_MATRIX.md` updated.
- **Step 2.3**: `ProjetTeamController` (index, link, unlink, toggleUseTeams, candidates), 5 new API routes under `/projets/{projet}/`.
- **Step 2.4**: `TeamLinkedToProject` + `TeamUnlinkedFromProject` events (constructor property promotion), `TeamProjectObserver` (onLinked/onUnlinked sync), `EventServiceProvider` `$listen` updated.
- **Step 2.5**: `TeamMemberAutoAddedNotification` (queued, mail + database via `projet_member_added` channel).
- **Step 2.6**: `ProjetDetail.vue` use_teams toggle + linked teams panel (load/link/unlink), `ProjetForm.vue` `use_teams` field, `TeamResource` HTTP resource created, `useActivityMembers` candidates fallback to `/projets/{id}/candidates`. `Teams/Show.vue` "Projet lié" badge was already present.
- **Pre-commit gates**: Pint clean, Larastan 0 errors.

---

## Current Task

**Phase 2 complete** (Steps 2.1–2.6 done; 2.7 tests deferred to end of plan).

---

## Next Task

**Phase 3 — Multi-Type Chat** (from plan):
1. Migrations: `workspace_channels`, `workspace_messages`, `workspace_message_reactions`, `workspace_channel_reads`.
2. Models + service: `WorkspaceChannel`, `WorkspaceMessage` (Searchable), `WorkspaceMessageReaction`, `WorkspaceMessageService`.
3. Artisan command `workspace:seed-channels` for existing workspaces.
4. Broadcast events + channel auth.
5. API controller + routes.
6. Frontend: `useWorkspaceMessages.js`, `WorkspaceChat.vue`, sidebar changes.
7. Notifications: `WorkspaceMessageMentionNotification`.

Read `docs/WORKING_GUIDELINES.md` before starting Phase 3.

---

## Open Decisions

- None.

---

## Blocking Issues

- None.

---

## Branch State

| Branch | Status |
|---|---|
| `feature/superadmin-scoping` | Merged into `jonas` 2026-06-27. Still exists as working branch. |
| `jonas` | Up to date — pushed to both `origin` and `client` 2026-06-27. |
| `main` | Never touched (hard rule). |
