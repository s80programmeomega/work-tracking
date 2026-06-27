# Implementation Plan: Visibility Refactor + Team Integration + Multi-Type Chat

**Branch:** `feature/visibility-teams-chat`  
**Base branch:** `jonas`  
**Created:** 2026-06-28  
**Status:** Planning complete — ready for implementation

---

## Context

Three independent but interconnected improvements layered onto Work Tracking v2.0:

1. **Visibility → Permissions refactor**: The app has a dual-layer access control system — a `visibility` enum field (public/team/private) runs alongside the Spatie + ContextualPermissionGate system. This is inconsistent: Projet visibility is partially enforced through ProjetPolicy, Tache has visibility fields with zero policy enforcement, and Document has a public bypass that skips the permission gate entirely. Goal: **drop all visibility columns** and express every access rule through the existing ContextualPermissionGate + Permission constants.

2. **Team Integration at project level**: A project gains a boolean `use_teams` flag. When enabled, activity/task/subtask assignee pickers restrict candidates to members of linked teams (plus project `responsable`). When disabled, the current workspace-member pool applies unchanged. The `projet_user` pivot is **kept** — teams are additive, not a replacement. This is the least-regressive approach.

3. **Multi-type Chat**: The existing chat is team-only. Three types are needed:
   - **Team Chat** — existing, no changes
   - **Workspace Responsibles Chat** — fixed system channel per workspace, auto-created, for directeur/manager/cadre
   - **Global Workspace Chat** — all workspace members

---

## Phase 1 — Visibility Full Replacement

### Goal
Remove the `visibility` enum column from `projets`, `taches`, `documents`, `teams`. Replace every visibility-based access rule with a ContextualPermissionGate permission check. No column is dropped without a migration; no code path is removed without a verified replacement.

### Step 1.1 — Audit & new permission constants

**Files to modify:**
- `app/Permissions/Permission.php` — add:
  - `PROJETS_VIEW_ALL` — manager+ sees all workspace projects regardless of membership
  - `TACHES_VIEW` — any task assignee / project member can view a task
- `database/seeders/RolePermissionSeeder.php` — grant `PROJETS_VIEW_ALL` to `owner`, `manager`
- `app/Enums/Role.php` — update `permissions()` for `owner` and `manager`
- `resources/js/composables/useProjetPermissions.js` — add `canViewAll` flag
- `resources/js/composables/useWorkspacePermissions.js` — (no change needed; already has manager-check)
- `docs/PERMISSIONS_MATRIX.md` — add rows + changelog entry

### Step 1.2 — Drop visibility from Projet

**New migrations (additive, never edit existing):**
- `2026_07_XX_drop_visibility_from_projets_table.php` — `$table->dropColumn('visibility')` guarded with `Schema::hasColumn`

**Files to modify:**
- `app/Models/Projet.php`:
  - Remove `'visibility'` from `$fillable`
  - Remove `scopePublic()`, `scopePrivate()`, `scopeTeam()`
  - Rewrite `scopeVisibleTo(User $user)` using role-based logic (owner→all, manager→all, member→only joined, responsable→own project)
  - Remove `accessibleTachesFor()` visibility check
  - Remove `hasRoleLevel('manager')` call → replace with `PermissionService::getWorkspaceRoleName()` check (fixes Layer 3 legacy read)
- `app/Policies/ProjetPolicy.php`:
  - Remove `canViewWithVisibility()` method entirely
  - `view()` calls only `ContextualPermissionGate::userCan(Permission::PROJETS_VIEW)`; manager bypass handled by gate via `PROJETS_VIEW_ALL`
- `app/Http/Requests/Projet/StoreProjetRequest.php` — remove `visibility` rule
- `app/Http/Requests/Projet/UpdateProjetRequest.php` — remove `visibility` rule
- `database/factories/ProjetFactory.php` — remove `visibility` state
- `database/seeders/ProjetSeeder.php` — remove visibility values
- `resources/js/components/projets/ProjetForm.vue` — remove visibility radio group

### Step 1.3 — Drop visibility from Tache

**New migration:**
- `2026_07_XX_drop_visibility_from_taches_table.php`

**New file:**
- `app/Policies/TachePolicy.php` — add `view()`, `update()`, `delete()` via `ContextualPermissionGate`
- Register in `app/Providers/AuthServiceProvider.php`

**Files to modify:**
- `app/Models/Tache.php` — remove `visibility` from `$fillable` and `$casts`
- `app/Http/Controllers/Api/TacheController.php` — remove `visibility` from store/update validation rules
- `resources/js/components/taches/TacheForm.vue` — remove visibility radio group

### Step 1.4 — Drop visibility from Document

**New migration:**
- `2026_07_XX_drop_visibility_from_documents_table.php`

**Files to modify:**
- `app/Models/Document.php` — remove `visibility` from `$fillable`, remove `scopeAccessibleBy()`, remove `canBeViewedBy()`
- `app/Policies/DocumentPolicy.php` — remove `if ($document->visibility === 'public') return true` in `view()`; rely on `DocumentPermission` records
- `app/Services/DocumentAccessResolver.php` — remove `visibility === 'public'` shortcut in `canView()` and `canDownload()`
- `app/Http/Controllers/Api/DocumentController.php` — remove `visibility` from validation + default assignment
- `resources/js/components/documents/DocumentCard.vue` — remove visibility badge icons
- `database/factories/DocumentFactory.php` — remove visibility states

### Step 1.5 — Drop visibility from Team

**New migration:**
- `2026_07_XX_drop_visibility_from_teams_table.php`

**Files to modify:**
- `app/Models/Team.php` — remove `visibility` from `$fillable`, remove `scopePublic()`
- `app/Http/Controllers/TeamController.php` — remove `visibility` from store/update validation
- `database/factories/TeamFactory.php` — remove visibility states

### Step 1.6 — Workspace settings cleanup

**Files to modify:**
- `app/Http/Requests/StoreWorkspaceRequest.php` — remove `settings_array.visibility` and `settings_array.default_project_visibility` validation
- `app/Http/Requests/UpdateWorkspaceRequest.php` — same
- `app/Models/Workspace.php` — remove `'visibility' => 'private'` and `'default_project_visibility' => 'team'` from default settings

### Step 1.7 — Tests

**New/updated test files:**
- `tests/Feature/Projet/ProjetAccessTest.php` (replaces `ProjetVisibilityTest.php`):
  - owner sees all projects
  - manager sees all projects
  - cadre sees only joined projects
  - responsable sees own project regardless of role
  - project not in user's workspace → 403
- `tests/Feature/Tache/TachePolicyTest.php` (new):
  - assignee can view task
  - non-member cannot view
  - project manager can view all project tasks
- `tests/Feature/Document/DocumentAccessTest.php` (new):
  - owner always has view access
  - explicit DocumentPermission record grants view to shared user
  - no DocumentPermission → 403 for non-owner
- Dusk: `tests/Browser/Projet/ProjetAccessTest.php` — cadre sees only joined projects, manager sees all

---

## Phase 2 — Team Integration at Project Level

### Goal
Add `use_teams` boolean to `projets`. When enabled, assignee pickers pull from linked teams. Teams auto-sync membership into `projet_user`. No breaking change to existing projects.

### Step 2.1 — Migration + model

**New migration:**
- `2026_07_XX_add_use_teams_to_projets_table.php` — `use_teams boolean default false`

**Files to modify:**
- `app/Models/Projet.php` — add `use_teams` to `$fillable`, add `$casts['use_teams'] = 'boolean'`
- `app/Http/Requests/Projet/UpdateProjetRequest.php` — add `'use_teams' => 'nullable|boolean'`

### Step 2.2 — Permission + policy

**Files to modify:**
- `app/Permissions/Permission.php` — add `PROJETS_MANAGE_TEAMS`
- `database/seeders/RolePermissionSeeder.php` — grant to `owner`, `manager`
- `app/Enums/Role.php` — update
- `app/Policies/ProjetPolicy.php` — add `manageTeams()` method
- `resources/js/composables/useProjetPermissions.js` — add `canManageTeams`
- `docs/PERMISSIONS_MATRIX.md` — add row + changelog

### Step 2.3 — API endpoints

**New controller methods in `ProjetController.php`:**
- `linkTeam(Request $request, Projet $projet)` → POST `/api/projets/{projet}/teams`
- `unlinkTeam(Request $request, Projet $projet, Team $team)` → DELETE `/api/projets/{projet}/teams/{team}`
- `toggleUseTeams(Request $request, Projet $projet)` → PATCH `/api/projets/{projet}/use-teams`
- `linkedTeams(Projet $projet)` → GET `/api/projets/{projet}/teams`

**New controller methods for candidates:**
- `ActiviteController::memberCandidates(Activite $activite)` → GET `/api/activites/{activite}/members/candidates`
- `TacheController::assigneeCandidates(Tache $tache)` → GET `/api/taches/{tache}/assignees/candidates`
- `SousTacheController::intervenantCandidates(SousTache $sousTache)` → GET `/api/sous-taches/{sousTache}/intervenants/candidates`

Logic for `candidates`: if `projet->use_teams = true` → union of team members from linked teams + existing `projet_user` members; else → workspace members.

**New routes in `routes/api.php`:**
```
POST   /projets/{projet}/teams
DELETE /projets/{projet}/teams/{team}
PATCH  /projets/{projet}/use-teams
GET    /projets/{projet}/teams
GET    /activites/{activite}/members/candidates
GET    /taches/{tache}/assignees/candidates
GET    /sous-taches/{sousTache}/intervenants/candidates
```

### Step 2.4 — Observer / event for auto-sync

**New files:**
- `app/Events/TeamLinkedToProject.php`
- `app/Events/TeamUnlinkedFromProject.php`
- `app/Observers/TeamProjectObserver.php` — on `linked`: adds team members to `projet_user` (role = collaborateur, skip if existing at higher role); on `unlinked`: removes team-only members
- Register observer in `AppServiceProvider`

**Modified files:**
- `app/Http/Controllers/TeamController.php` — `addMember()`: fire `TeamMemberAdded`; if team has `project_id`, sync to `projet_user`
- `app/Http/Controllers/TeamController.php` — `removeMember()`: if team has `project_id` AND user has no other path into project, remove from `projet_user`

**Guard against downgrade:** when syncing, check existing `projet_user.role_id`; skip if user already has a role that is `owner`, `manager`, or `cadre`.

### Step 2.5 — Notifications

**New files:**
- `app/Notifications/TeamLinkedToProjectNotification.php` (ShouldQueue, database, in-app) → sent to project responsable
- `app/Notifications/TeamMemberAutoAddedNotification.php` (ShouldQueue, database + mail inline) → sent to user who was auto-added

**Modified files:**
- `app/Services/NotificationService.php` — add `team_linked_project` and `team_auto_added` to `wantsEmail()` high-signal set
- `lang/fr/teams.php`, `lang/en/teams.php` — add notification translation keys

### Step 2.6 — Frontend UI

**Modified files:**
- `resources/js/components/projets/ProjetForm.vue` — add "Utiliser les équipes" toggle (visible to manager+ only); when toggled on, show "Équipes liées" section with team search/add/remove
- `resources/js/composables/useProjets.js` — add `toggleUseTeams()`, `linkTeam()`, `unlinkTeam()`, `fetchLinkedTeams()`
- `resources/js/composables/useActivites.js` — add `fetchMemberCandidates()`
- `resources/js/composables/useTaches.js` — add `fetchAssigneeCandidates()`
- `resources/js/components/taches/IntervenantPicker.vue` — accept `candidatesUrl` prop; use candidates endpoint when provided
- `resources/js/pages/Teams/Show.vue` — show "Projet lié" badge in header when `team.project_id` set

**i18n keys** — add to `lang/fr/projets.php` and `lang/en/projets.php`:
- `use_teams`, `linked_teams`, `link_team`, `unlink_team`, `team_pool_note`, `workspace_pool_note`

### Step 2.7 — Tests

**New test files:**
- `tests/Feature/Team/TeamProjectIntegrationTest.php` (10+ tests):
  - Toggle `use_teams` requires `PROJETS_MANAGE_TEAMS`
  - Link team → team members added to `projet_user` as collaborateur
  - Link team → existing owner-level member not downgraded
  - Unlink team → team-only members removed from `projet_user`
  - Add member to linked team → auto-added to project
  - Remove member from linked team → removed from project (if team-only)
  - Candidates endpoint: `use_teams=true` returns team members + project members
  - Candidates endpoint: `use_teams=false` returns workspace members
  - Cadre cannot toggle `use_teams` (403)
  - Non-workspace member cannot be linked via team (validation error)
- Dusk: `tests/Browser/Teams/TeamProjectIntegrationTest.php`
  - Enable use_teams, link team, verify member appears in task assignee picker
  - Verify team badge in Teams/Show.vue header
  - Save full screenshots

---

## Phase 3 — Multi-Type Chat

### Goal
Add Workspace Responsibles Chat and Global Workspace Chat alongside the existing Team Chat. Use a single new `workspace_messages` table + `workspace_channels` lookup table. Two new private Reverb channels. New `WorkspaceChat.vue` page.

### Step 3.1 — Migrations

**New migrations:**
- `2026_07_XX_create_workspace_channels_table.php`:
  ```sql
  id, workspace_id (FK), type (enum: responsibles, global),
  name (string), created_at, updated_at
  unique(workspace_id, type)
  ```
- `2026_07_XX_create_workspace_messages_table.php`:
  ```sql
  id, uuid (unique), workspace_channel_id (FK),
  user_id (FK), content (text),
  mentions (json nullable), attachments (json nullable),
  reply_to_id (FK self nullable), is_pinned (bool default false),
  is_edited (bool default false), edited_at (timestamp nullable),
  timestamps, deleted_at (soft delete)
  ```
- `2026_07_XX_create_workspace_message_reactions_table.php`:
  ```sql
  id, message_id (FK), user_id (FK), emoji (string),
  timestamps; unique(message_id, user_id, emoji)
  ```
- `2026_07_XX_create_workspace_channel_reads_table.php`:
  ```sql
  id, user_id (FK), workspace_channel_id (FK),
  last_read_at (timestamp nullable); unique(user_id, workspace_channel_id)
  ```

### Step 3.2 — Models + service

**New files:**
- `app/Models/WorkspaceChannel.php` — `belongsTo(Workspace)`, `hasMany(WorkspaceMessage)`, `hasMany(WorkspaceChannelRead)`
- `app/Models/WorkspaceMessage.php` — analogous to `TeamMessage`; `Searchable` trait for Scout
- `app/Models/WorkspaceMessageReaction.php` — analogous to `TeamMessageReaction`
- `app/Services/WorkspaceMessageService.php` — `sendMessage`, `updateMessage`, `deleteMessage`, `pinMessage`, `addReaction`, `removeReaction`, `markRead`, `getUnreadCount`, `getMessages`

**Modified files:**
- `app/Models/Workspace.php` — add `channels(): HasMany` relationship
- `app/Observers/WorkspaceObserver.php` (new) — `created()`: auto-create `WorkspaceChannel` records for `responsibles` and `global` types; register in `AppServiceProvider`

### Step 3.3 — Artisan command for existing workspaces

**New file:**
- `app/Console/Commands/SeedWorkspaceChannels.php` — creates missing `workspace_channels` for all existing workspaces; run once in the migration batch

### Step 3.4 — Broadcast events

**New files under `app/Events/Realtime/Chat/`:**
- `WorkspaceMessageSent.php` (ShouldBroadcastNow) — channel: `workspace.{id}.responsibles` or `workspace.{id}.global`
- `WorkspaceMessageUpdated.php` (ShouldBroadcastNow)
- `WorkspaceMessageDeleted.php` (ShouldBroadcastNow)
- `WorkspaceReactionChanged.php` (ShouldBroadcastNow)

**Broadcast name convention:** `.workspace.message.sent`, `.workspace.message.updated`, etc.

### Step 3.5 — Channel authorization

**Modified file: `routes/channels.php`**
```php
Broadcast::channel('workspace.{workspaceId}.responsibles', function ($user, $workspaceId) {
    $role = app(PermissionService::class)->getWorkspaceRoleName($user, Workspace::find($workspaceId));
    return in_array($role, ['owner', 'manager', 'cadre']);
});

Broadcast::channel('workspace.{workspaceId}.global', function ($user, $workspaceId) {
    return Workspace::find($workspaceId)?->members()->where('user_id', $user->id)->exists();
});
```

### Step 3.6 — API controller + routes

**New file:**
- `app/Http/Controllers/Api/WorkspaceChatController.php`:
  - `channels(Workspace $workspace)` → GET `/api/workspaces/{workspace}/channels`
  - `messages(Workspace $workspace, string $type)` → GET `/api/workspaces/{workspace}/channels/{type}/messages`
  - `store(StoreWorkspaceMessageRequest $request, Workspace $workspace, string $type)` → POST
  - `update(UpdateWorkspaceMessageRequest $request, WorkspaceMessage $message)` → PATCH
  - `destroy(WorkspaceMessage $message)` → DELETE
  - `markRead(Workspace $workspace, string $type)` → POST `/api/workspaces/{workspace}/channels/{type}/read`
  - `pin(WorkspaceMessage $message)` → POST
  - `addReaction(WorkspaceMessage $message, Request $request)` → POST
  - `removeReaction(WorkspaceMessage $message, Request $request)` → DELETE
  - `unreadCounts(Workspace $workspace)` → GET `/api/workspaces/{workspace}/unread`

**New Form Requests:**
- `app/Http/Requests/Chat/StoreWorkspaceMessageRequest.php`
- `app/Http/Requests/Chat/UpdateWorkspaceMessageRequest.php`

**New routes in `routes/api.php`** (under `auth:sanctum` middleware, workspace-scoped):
```
GET    /workspaces/{workspace}/channels
GET    /workspaces/{workspace}/channels/{type}/messages
POST   /workspaces/{workspace}/channels/{type}/messages
POST   /workspaces/{workspace}/channels/{type}/read
GET    /workspaces/{workspace}/unread
PATCH  /workspaces/{workspace}/messages/{message:uuid}
DELETE /workspaces/{workspace}/messages/{message:uuid}
POST   /workspaces/{workspace}/messages/{message:uuid}/pin
POST   /workspaces/{workspace}/messages/{message:uuid}/reactions
DELETE /workspaces/{workspace}/messages/{message:uuid}/reactions
```

### Step 3.7 — API Resources

**New files:**
- `app/Http/Resources/WorkspaceChannelResource.php`
- `app/Http/Resources/WorkspaceMessageResource.php`

### Step 3.8 — Frontend composable

**New file: `resources/js/composables/useWorkspaceMessages.js`**
- `subscribeToChannel(workspaceId, type)` — Echo private channel, listen to 4 events
- `unsubscribeFromChannel()` — cleanup on unmount
- `fetchMessages(workspaceId, type, filters)` → GET
- `sendMessage(workspaceId, type, data)` — optimistic send
- `updateMessage(uuid, content)` → PATCH
- `deleteMessage(uuid)` → DELETE
- `togglePin(uuid)` → POST
- `addReaction(uuid, emoji)` → POST (optimistic)
- `removeReaction(uuid, emoji)` → DELETE (optimistic)
- `markRead(workspaceId, type)` → POST
- `sendTyping(workspaceId, type)` — whisper, throttled 2s
- Reactive: `messages`, `loading`, `error`, `typingUsers`

**Modified file: `resources/js/composables/useWorkspace.js` (or equivalent):**
- Add `fetchUnreadCounts(workspaceId)` → GET `/api/workspaces/{workspace}/unread`

### Step 3.9 — Frontend UI: WorkspaceChat.vue

**New file: `resources/js/pages/WorkspaceChat.vue`**

Layout:
```
┌─────────────────────────────────────────────────────────┐
│ Sidebar (left)           │ Message Panel (right)         │
│                           │                               │
│ 🌐 Global                 │ [Channel name + description]  │
│ 👥 Responsables (gated)   │ ─────────────────────────── │
│ ──────────────            │ [Messages list - staggered]   │
│ Teams:                    │   [msg] [msg] [msg]           │
│  • Team Alpha  [3]        │ ─────────────────────────── │
│  • Team Beta              │ [Typing indicator]            │
│                           │ [Message composer]            │
└─────────────────────────────────────────────────────────┘
```

Features:
- Responsibles tab hidden for `collaborateur`/`stagiaire`/`observateur` roles
- Team sub-list links to `Teams/Show.vue` (no re-implement of team chat)
- Unread count badge on each item
- Message composer: text input, emoji picker, @mention, file attachment
- Message list: stagger animation (Guide 23), reply-to quote, reactions, edit/delete/pin for author
- Typing indicator (whisper-based)
- Responsive: sidebar collapses to drawer on mobile

**New route in `resources/js/router/index.js`:**
```js
{ path: '/workspace/chat', component: WorkspaceChat, meta: { requiresAuth: true } }
{ path: '/workspace/chat/:type', component: WorkspaceChat, meta: { requiresAuth: true } }
```

**Modified file: `resources/js/components/layout/AppSidebar.vue`**
- Replace current "Teams" nav item with "Chat" nav item
- Chat section expands to: Global, Responsibles (gated), Teams (list with per-team unread badges)
- Top-level Chat badge = sum of all unread counts

### Step 3.10 — Notifications

**New file:**
- `app/Notifications/WorkspaceMessageMentionNotification.php` (ShouldQueue) — mirrors `ChatMentionNotification`; dispatched by `WorkspaceMessageService::sendMessage()` when `mentions` array is non-empty

**Modified files:**
- `app/Services/NotificationService.php` — add `workspace_chat_mention` to `wantsEmail()` high-signal set; add `workspace_chat_mention` to `wantsWebPush()` high-signal set
- `lang/fr/notifications.php`, `lang/en/notifications.php` — add mention notification keys
- `lang/fr/chat.php`, `lang/en/chat.php` (new) — all chat UI strings

### Step 3.11 — Tests

**New test files:**
- `tests/Feature/WorkspaceChat/WorkspaceChatTest.php` (15+ tests):
  - Any workspace member can post to global channel
  - Collaborateur cannot post to responsibles channel (403)
  - Cadre can post to responsibles channel
  - Manager can post to both channels
  - Mention notification dispatched to mentioned user
  - Unread count increments when new message sent
  - Unread count resets to 0 after markRead
  - Auto-created channels exist for new workspace (WorkspaceObserver)
  - Pin requires workspace manager+
  - Non-member cannot access any channel (403)
  - Emoji reactions: add, remove, aggregate count
  - Message edit: author can edit, non-author cannot (403)
  - Message delete: author can delete, non-author cannot (403)
  - Scout indexing: message indexed on create
  - Reply-to: reply references parent uuid correctly
- Dusk: `tests/Browser/WorkspaceChat/WorkspaceChatTest.php`
  - Global chat: send message, assert it appears in real-time
  - Responsibles tab hidden for collaborateur role
  - @mention: check notification bell badge increments
  - Save full screenshots at each step

---

## Things You Should Know (Expert Additions)

These are items not explicitly requested but necessary for a correct implementation:

1. **`hasRoleLevel('manager')` reads `users.role` (Layer 3 legacy)** — this is called in `Projet::scopeVisibleTo()` and `ProjetPolicy`. Guide 27 forbids extending this pattern. Phase 1 cleanup will replace these calls with `PermissionService::getWorkspaceRoleName()` checks against the workspace pivot, which is the correct Layer 2 approach.

2. **Message search (Phase 6 integration)**: `WorkspaceMessage` must implement `Searchable` (Scout) so it appears in the global search results. Scope the search to workspace the user belongs to (prevents cross-workspace IDOR — same class of bug fixed in Phase 6).

3. **Storage quota on message attachments**: file uploads in chat messages must call `SubscriptionService::canUploadStorage()` and `canUploadFile()` — same as document uploads. Add this check to `WorkspaceMessageService::sendMessage()`.

4. **ActivityLog on workspace messages**: use `LogsActivity` trait on `WorkspaceMessage` model — same as `TeamMessage`. Audit trail required for moderation.

5. **Workspace channel seeding for existing data**: `WorkspaceObserver::created()` handles new workspaces. An Artisan command `workspace:seed-channels` handles existing ones — must be called in a migration or seeder during the deploy that introduces Phase 3.

6. **Team chat stays in Teams/Show.vue**: `WorkspaceChat.vue`'s Teams sub-list shows team names with unread badges and navigates to `Teams/Show.vue` (which still has the team chat tab). No duplication. No breaking change.

7. **Presence in workspace channels**: use whisper events (same as typing) for lightweight presence — no new infrastructure needed. On message send, user is implicitly active. True presence tracking (away/busy/offline) is a follow-up concern.

8. **i18n completeness**: every user-visible string in all three phases must have keys in both `lang/fr/*.php` and `lang/en/*.php` before a phase is considered done (Guide 24 item 4).

9. **Scribe API docs**: run `php artisan scribe:generate` after each phase's routes are added. Commit the updated `storage/app/scribe/` output.

10. **Larastan on new models/resources**: every new model needs `@property` PHPDoc and every new JsonResource needs `@property` + `@mixin` PHPDoc (Guide 21 conventions).

---

## Pre-Commit Gates (apply after every step)

1. `vendor/bin/pint --dirty --format agent` → `"result":"passed"` or `"result":"fixed"`
2. `php artisan clear-compiled && php -d memory_limit=1500M vendor/bin/phpstan analyse --memory-limit=1500M` → `[OK] No errors`
3. `php artisan test --compact` → all green
4. `docs/PERMISSIONS_MATRIX.md` updated (hard gate — Guide 15)
5. `docs/PROGRESSION.md` row ticked
6. `docs/SESSION_STATE.md` updated
7. Testing guide written in `docs/testing/`

---

## Execution Order

Phase 1 → Phase 2 → Phase 3. Strict order required:
- Phase 1 removes `visibility` from `ProjetForm.vue`; Phase 2 modifies the same component. Wrong order = double-edit conflict.
- Phase 2 syncs team membership into `projet_user`; Phase 3 workspace channels use `workspace_members` for auth. Phase 2 before Phase 3 ensures membership pool is correct.
- Phase 3 has no dependency on Phase 2 technically but is sequenced last to keep the branch coherent.
