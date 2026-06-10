# Plan — Phase 11: Dashboard Accuracy + Profile Page Fixes

> Reference spec for sub-branches 11A–11E. Approved 2026-06-10. Source plan file:
> `/home/jonas/.claude/plans/good-now-we-are-memoized-pelican.md`.

## Context

Two related cleanup efforts:

1. **Dashboard/statistics accuracy.** A code audit found the dashboard stat cards display
   hardcoded fake "change" percentages (`+12%`, `+5%`, `-2%`) instead of real period-over-period
   data, leftover `console.log` debug statements, and a Kanban view whose status columns don't
   match the actual `ProjetStatus` enum (so the "pending" column is permanently empty and
   "completed" never shows real completed projects).

2. **Profile page cleanup.** Several sections in `/profile` (Security, Notifications, Preferences,
   Activité tabs) and a navbar link are either non-functional, broken, or fully mocked. The user
   has decided, per section, what to implement vs. remove — including a meaningful scope addition:
   **workspace owners get their own "Users" management page** (workspace-scoped version of the
   existing super-admin `/admin/users`), with per-user activity drill-down for both super-admins
   and owners.

This plan covers both. Per CLAUDE.md, every sub-task needs: backend + frontend + i18n + stagger
(where lists are involved) + tests (PHPUnit + Dusk where UI-facing) + Pint/Larastan clean + build
green + doc updates (PROGRESSION.md, SESSION_STATE.md, PERMISSIONS_MATRIX.md if permissions
change, `docs/testing/PHASE11{A-E}_TESTING.md`).

---

## Part A — Dashboard & Statistics Accuracy

### A1. Real period-over-period "change" percentages

**File:** `app/Http/Controllers/Api/DashboardController.php`

- `calculateStats()` (lines 157-177) currently hardcodes `change`/`trend` for `projets_actifs`,
  `taux_completion`, `taches_en_retard`. A `calculateChange($current, $previous)` helper
  (lines 182-192) already exists but is **never called**.
- Compute a "previous period" comparison value for each metric:
  - `projets_actifs`: count of active projects as of "now" vs. count as of `now()->subMonth()`
    (or simplest: projects created before vs. within the comparison window — needs a clear,
    cheap definition; recommend comparing **counts at two points in time** using `created_at <=`
    cutoffs rather than re-running the whole accessible-projects query twice).
  - `taux_completion`: completion rate computed over current period's tasks vs. previous period's
    tasks (use the same `$taches` collection split by `created_at`, or a second lightweight query
    scoped to the prior period).
  - `taches_en_retard`: overdue count now vs. overdue count as of the prior period's end (this one
    is trickier since "overdue" is a moving target — simplest honest approach: compare counts of
    tasks meeting the overdue definition at two snapshot times).
- Wire the result through `calculateChange()` for the `change` string and derive `trend` from the
  sign (`up` if change >= 0, `down` if negative — note `taches_en_retard` trend should likely be
  inverted semantically, i.e. a decrease in overdue tasks is "good", but keep `trend` as a literal
  direction indicator and let the frontend color it; don't over-engineer).
- **Caching note:** this endpoint is cached via `AdaptiveCache` (`docs/caching/CACHING_STRATEGY.md`)
  — the extra "previous period" queries happen inside `computeDashboard()`, so they're cached
  too. No change to the caching layer needed, just be mindful of added query count (still cached,
  60s TTL).

### A2. Fix Kanban project-status mismatch

**Files:**
- `app/Http/Controllers/Api/DashboardController.php` — `getRecentProjects()` (~line 308-336)
- `resources/js/pages/Dashboard.vue` — `kanbanColumns` (lines 387-410) and the filter at line 511
  (`projects.filter(p => p.status === status)`)

- `ProjetStatus` enum (`app/Enums/ProjetStatus.php`) is `active | archived | completed`.
- `getRecentProjects()` currently hardcodes `where('status', 'active')` — change to fetch recent
  projects across all statuses (or at least `active` + `completed`; `archived` projects are
  arguably not useful on a "recent activity" dashboard — **decide with the simplest correct
  option:** fetch `active` + `completed`, exclude `archived` from the Kanban but don't filter them
  out of `recent_projects` data entirely if other parts of the dashboard use that array — check
  for other consumers of `recent_projects` before changing the query).
- Update `kanbanColumns` in Dashboard.vue: replace `pending` → `active`, keep `active` → maybe
  rename to a different label, `completed` stays. Concretely: align the 3 columns to
  `active` / `completed` / (decide a 3rd — possibly drop to 2 columns, or use `archived` as the
  3rd if recent_projects includes it). **This needs a quick look at the actual UI/i18n labels
  (`statuts.en_attente`, `statuts.en_cours`, `statuts.termine`) before deciding the exact 3-column
  mapping** — these i18n keys are task-status keys being reused for project status, which may
  itself be a mismatch worth fixing (project statuses should probably use `ProjetStatus::label()`
  i18n, not task-status translation keys).

### A3. Remove debug console.logs

**File:** `resources/js/pages/Dashboard.vue:484-485`

- Remove the two `console.log('Dashboard', ...)` lines in `updateStatsCards()`. Trivial, but
  required by Guide 24 ("no console.log left in").

### A4. AdminController::computeStats() — overdue semantics review

**File:** `app/Http/Controllers/Api/AdminController.php:86`

- Confirmed via tinker: `en_retard` is a real stored `statut` value (1 task), already counted once
  inside `$taskStatsByStatus` via `groupBy('statut')`. `$overdueTasks = Tache::where('statut',
  'en_retard')->count()` is **redundant but not double-counted** in `$totalTasks`.
- However, `Tache::isOverdue()` (the richer definition: `echeance` passed AND `statut !=
  termine`) can be true for tasks whose stored `statut` is `a_faire`/`en_cours` — those are
  "actually overdue" but won't be counted by `$overdueTasks`. **Decide:** either (a) leave as-is
  (it's measuring "tasks explicitly marked en_retard", a valid distinct metric — just maybe
  rename the JSON key/label for clarity), or (b) change `$overdueTasks` to use the `isOverdue()`
  definition via a query equivalent to `Tache::scopeOverdue()` (already exists, commented out at
  `app/Models/Tache.php` near `scopeOverdue` — there's actually an active `scopeOverdue` defined
  earlier in the file too, check for duplicate scope definitions). **Recommend (b)** for accuracy
  — "overdue" on an admin stats page should mean "actually late", not "manually flagged late".
  This is a one-line query change: `Tache::overdue()->count()` instead of
  `Tache::where('statut', 'en_retard')->count()`.

---

## Part B — Profile Page Fixes

### B1. Security tab — Session management (real "logout all devices" + session list)

**Backend:**
- Sanctum already issues `personal_access_tokens` per login. Add an endpoint
  `GET /api/users/sessions` (or reuse `/api/auth/sessions`) returning the current user's active
  tokens: `id`, `name` (device/browser if stored — check if `last_used_at` and any user-agent
  tracking exists on `personal_access_tokens`; Sanctum's default table doesn't store user-agent,
  so this may need a small additive migration to add `user_agent`/`ip_address` columns to
  `personal_access_tokens`, populated at token-creation time in `AuthService::login`).
- Add `DELETE /api/users/sessions/{tokenId}` — revoke a specific token (must belong to the
  authenticated user; 403 otherwise).
- Add `POST /api/users/sessions/revoke-all` — revoke all tokens except the current one
  ("Logout All Devices" / "Logout other sessions"). This is the fix for the broken
  `authStore.logoutAllDevices()` call.

**Frontend:**
- `resources/js/components/settings/SessionSettings.vue` — replace/extend with a real session
  list (each row: device/browser label or fallback "Session #N", created_at, last_used_at,
  "this device" badge for current token, a checkbox/select + "Terminate selected" action, and
  "Logout all other devices" button).
- Wire `authStore` with `fetchSessions()`, `revokeSession(id)`, `revokeAllOtherSessions()` —
  replace the non-existent `logoutAllDevices()` call.
- This is a list → apply `useStagger` (Guide 23) on the session rows.
- Keep the existing client-side inactivity-timeout picker (it's a separate, working concern) —
  only the device-list/revocation part is new.

**Tests:** PHPUnit feature tests for list/revoke/revoke-all (own-token only, 403 on others' token
ids), 1 Dusk test for the revoke flow.

**Reuse:** `app/Services/AuthService.php` (existing token issuance) — extend `login()` to capture
`request()->userAgent()` / `request()->ip()` if the migration adds those columns.

### B2. Notifications tab — functional notification sound

**Files:**
- `resources/js/components/settings/NotificationSettings.vue` — existing toggle
  (`settings.notificationSounds`), keep as-is (already persists to localStorage — fine, this is a
  pure client-side preference, no backend needed).
- `resources/js/composables/useLiveNotifications.js` — the Echo-based real-time notification
  composable (Task 8). On receiving a new notification, check
  `localStorage.getItem('notificationSettings')` (or expose the toggle via a shared composable/
  store rather than re-parsing localStorage each time — prefer a small `useNotificationSound()`
  composable that reads the preference once and exposes `playIfEnabled()`).
- Add a short notification sound asset under `public/sounds/notification.mp3` (or `.ogg` for
  broader codec support — check what's lightweight and license-safe; a short "ding" ~1s).
- `useNotificationSound.js` (new composable): `const audio = new Audio('/sounds/notification.mp3')`,
  `playIfEnabled()` checks the localStorage preference before calling `audio.play()`. Wrap in
  try/catch — browsers block autoplay without user interaction; a failed `.play()` (e.g.
  `NotPermittedError`) must not throw an unhandled rejection.

**Tests:** Dusk test is impractical for audio playback — instead, a lightweight unit/component
test (Vitest, if configured — check `package.json` for a test runner) verifying
`useNotificationSound().playIfEnabled()` calls `Audio.prototype.play` when the preference is
`true` and skips it when `false`. If no JS test runner exists, document manual verification steps
in the testing guide instead (don't introduce a new test framework for this alone).

### B3. Preferences tab — remove Theme/Language/Timezone/Date Format/Display Density/Auto-Save

**File:** `resources/js/components/settings/PreferencesSettings.vue`

- Remove all six options: Theme, Language, Timezone (duplicated by working navbar controls — see
  `resources/js/components/layout/header/` for the theme toggle and language switcher), Date
  Format, Display Density, Auto-Save (none consumed anywhere).
- **Before removing Timezone:** the explorer found Timezone IS persisted via `PUT /users/profile`
  (real backend field) — confirm whether `timezone` is read anywhere server-side (e.g. for
  formatting notification timestamps, digest send times). If it IS used server-side, **don't
  remove the backend field** — only remove the *Preferences-tab UI* for it if the navbar doesn't
  already cover timezone (the navbar likely does NOT have a timezone switcher, only theme +
  language). **Flag this to Jonas before removing** — timezone is plausibly NOT duplicated by the
  navbar, unlike theme/language. Re-check scope: user said "why not remove the language as there
  is a functional language switch... and the theme too" — timezone wasn't explicitly named.
  **Recommendation: keep Timezone in Preferences** (it's functional and not duplicated), remove
  Theme + Language (duplicated) + Date Format + Display Density + Auto-Save (unused).
- After removal, if Preferences tab would only contain Timezone, consider whether it still
  warrants its own tab or could merge into the main Profile tab — **decide once the diff is
  visible**, don't over-plan this.
- Guide 14 (No Silent Deletions): before deleting the Theme/Language UI controls from this
  component, grep for any other reads of `preferences.theme`/`preferences.language` from this
  component's localStorage key to confirm the navbar versions are fully independent and nothing
  breaks.

### B4. Help Center — Tiptap draft auto-save

**Files:**
- New migration (Guide 3 naming: `2026_06_xx_add_draft_columns_to_help_articles_table`): add
  nullable `draft_body_fr`, `draft_body_en`, `draft_saved_at` columns to `help_articles`. This
  keeps published content (`body_fr`/`body_en` + `published_at`) untouched by auto-save — auto-
  save writes ONLY to the draft columns, regardless of whether the article is currently published.
- `app/Models/HelpArticle.php` — add the 3 new columns to `$fillable`/`$casts`/`@property` PHPDoc.
- `app/Http/Controllers/Api/AdminHelpController.php` — new endpoint
  `PATCH /admin/help/articles/{article}/draft` — minimal validation (just the body fields),
  authorization same as `update()` (help_articles.manage / edit permission), writes
  `draft_body_fr`/`draft_body_en`/`draft_saved_at = now()`. Does NOT touch `published_at` or the
  live `body_fr`/`body_en`.
- On the edit form load (`HelpArticleForm.vue` / `AdminHelpController::show` or `edit`), if
  `draft_saved_at > updated_at` (draft is newer than last published save), surface a banner:
  "You have an unsaved draft from {time} — Restore / Discard". Restore copies draft columns into
  the live editor fields (doesn't touch DB until the user explicitly saves/publishes).
- Frontend: `resources/js/components/help/HelpArticleEditor.vue` /
  `resources/js/pages/admin/HelpArticleForm.vue` — debounced (~5s after last edit) call to the new
  draft endpoint. Show a small "Draft saved at HH:MM" indicator near the editor.

**Tests:** PHPUnit — draft endpoint persists to draft columns without touching `body_fr`/
`published_at`; authorization matches `update()`. Dusk — type in editor, wait for auto-save
indicator, reload page, confirm "Restore draft" banner appears and restores content.

### B5. Profile Activité tab — wire to real per-user activity endpoint

**Files:**
- `resources/js/components/profile/ActivityLog.vue` — replace `generateMockActivities()` with a
  real call to `GET /users/{user}/activity` (existing endpoint per explorer findings — verify its
  controller/route, likely `UserController::activity`).
- Keep existing time/type filters if the endpoint supports equivalent params; otherwise filter
  client-side on the returned page (check pagination — don't fetch unbounded history).
- Apply `useStagger` (Guide 23) on the activity list (it's a `v-for` list — check if it already
  has this from the mock version; if so just confirm it survives the data-source swap).

**Tests:** PHPUnit feature test for `GET /users/{user}/activity` (if not already tested) — own
activity returns 200 with real spatie activitylog entries scoped to `causer_id = $user->id`.
1 Dusk test: profile Activité tab shows real entries (seed an activity-logged action, e.g. update
own profile, then visit `/profile?tab=activity` and assert the entry appears).

### B6. Per-user activity drill-down — super-admin (from Users Management) + workspace owner (new "Users" page)

This is the largest item. Two related but distinct deliverables:

**B6a. Super-admin: per-user activity link from `/admin/users`**
- `resources/js/pages/admin/AdminUsers.vue` — add an "Activity" action per row, opening
  `ActivityLogTab` (or a wrapping modal/drawer) **pre-filtered to `causer_id = {user.id}`**.
- Refactor `resources/js/components/admin/logs/ActivityLogTab.vue` to accept an optional prop
  (e.g. `fixedCauserId`) — when set, hide the causer-picker filter and lock the query to that
  user. Keep all other filters (event, subject_type, date range) available.
- Backend `ActivityController::adminFeed()` (`app/Http/Controllers/ActivityController.php:193`)
  already accepts `causer_id` as a filter per the explorer's findings — confirm this and that
  passing it from the frontend just works without backend changes. If `causer_id` isn't currently
  a supported query param, add it (it's a `where` clause on an indexed column — cheap).

**B6b. Workspace owner: new "Users" page, workspace-scoped**
- New route, e.g. `/workspace/users` (or `/workspaces/{id}/users`), gated to workspace owners
  (and managers? — **decide**: the user said "workspace owner", so gate to owner only unless
  there's a reason to extend to managers — follow Guide 4 permission checklist if a new
  permission is needed, e.g. `WORKSPACE_MANAGE_USERS` or reuse an existing
  `can_manage_workspace` / `DOCUMENTS_MANAGE_WORKSPACE`-style owner-only permission).
- **New backend endpoint** `GET /api/workspaces/{workspace}/users` — similar shape to
  `AdminController::users()` but scoped: `User::whereHas('workspaces', fn($q) =>
  $q->where('workspace_id', $workspace->id))` (or via the existing membership relation used by
  `WorkspaceMemberManagement.vue`). Returns the SAME columns as `AdminUsers.vue` expects (name,
  email, role-in-workspace instead of global role, last activity, joined date) — **reuse the
  `AdminUsers.vue` table component/layout** by extracting a shared presentational component (e.g.
  `UsersTable.vue`) parameterized by: data source, role-display logic (global super_admin role vs.
  workspace pivot role), and available row actions (super-admin: change global role; owner: maybe
  change workspace role — reuse existing role-change logic from `WorkspaceMemberManagement.vue`
  if it exists, don't duplicate).
- Each row gets the same "Activity" action as B6a, opening `ActivityLogTab` with `fixedCauserId`
  — but the **owner's view of `adminFeed()` must be scoped**: the owner should only see activity
  for users who are members of their workspace, and arguably only activity on
  subjects within their workspace (a manager shouldn't see a user's actions in a *different*
  workspace they also belong to). This requires either:
  - (a) `adminFeed()` gains an optional `workspace_id` param; when present and the requester is
    NOT super_admin, authorize that the requester owns that workspace, then filter activity to
    `causer_id` IN (workspace member ids) AND/OR subject belongs to workspace-scoped models
    (Projet/Activite/Tache joined through workspace_id) — this is non-trivial filtering across
    polymorphic `subject_type`/`subject_id`. **Realistic minimal version:** filter by `causer_id`
    only (the acting user must be a workspace member) — full subject-side workspace scoping is a
    bigger lift and may be a follow-up. Flag this scoping limitation in the testing guide.
  - (b) A separate, simpler endpoint for owners that doesn't reuse `adminFeed()` at all —
    given the scoping complexity, **this may be cleaner**: a small dedicated method, e.g.
    `WorkspaceController::memberActivity($workspace, $user)`, authorized via a policy check
    (`$workspace->owner_id === auth()->id()`), querying spatie `Activity::where('causer_id',
    $user->id)` with the same filters as `adminFeed` but without the super_admin gate.
- **New permission** (Guide 4 full checklist): something like `can_view_member_activity` or fold
  into an existing owner-only permission — update `PermissionService`, `RolePermissionSeeder`,
  `Permission.php`/`forRole()`, `Permission.js`, `useWorkspacePermissions.js`,
  `WorkspaceController` user_permissions payload (3 locations), and
  **`docs/PERMISSIONS_MATRIX.md`** (hard gate, Guide 15).
- Sidebar: add "Users" entry for workspace owners (parallel to the existing "Members" entry if
  one exists under workspace settings — **check for redundancy with
  `WorkspaceMemberManagement.vue` before adding a second members-ish page**; the user explicitly
  asked for a *Users management* page mirroring admin's, so this may intentionally coexist with
  "Members" as a different view, e.g. Members = workspace-role management, Users = activity-
  oriented directory — clarify the distinction in the UI copy so it's not confusing).

**Tests:**
- PHPUnit: `GET /api/workspaces/{id}/users` — 200 for owner, 403 for non-owner member, only
  returns workspace members; per-user activity endpoint — 200 for owner viewing own-workspace
  member, 403 for owner viewing a user outside their workspace, 403 for non-owner.
- Dusk: owner navigates to Users page, sees workspace members, clicks "Activity" on a member, sees
  that member's activity log (filtered).

### B7. Remove dead navbar link "Paramètres du compte"

**File:** `resources/js/components/layout/header/UserMenu.vue:115`

- The link points to `/settings`, which doesn't exist as a route → 404. Either:
  - Remove the menu item entirely (settings live at `/profile`, already linked elsewhere in the
    same dropdown per the explorer's findings — "Editer le profil" or similar), or
  - Repoint it to `/profile` (possibly with a query param to land on a specific tab, e.g.
    `/profile?tab=preferences`).
- **Recommend:** remove it if "Editer le profil" already exists in the same dropdown (avoid
  duplicate entry points); otherwise repoint to `/profile`. Confirm which by reading the full
  `UserMenu.vue` dropdown list before deciding — this is a 1-line fix either way, decide at
  implementation time, no need for further planning.
- Update `lang/fr/*.json` / `lang/en/*.json` — remove the now-unused `user_menu.account_settings`
  key if the item is removed (Guide 14: grep for other usages of this key first).

---

## Sequencing — Sub-branches

Following the existing "Phase N" convention (`docs/PROGRESSION.md`, `docs/SESSION_STATE.md`),
this whole effort is **Phase 11 — Dashboard Accuracy + Profile Page Fixes**, broken into 5
sub-branches, each its own row/checklist in `docs/phase11-dashboard-profile/PROGRESSION.md` and
its own `docs/testing/PHASE11{A-E}_TESTING.md`:

1. **Phase 11A — Dashboard accuracy** (`feature/phase11a-dashboard-accuracy`, A1-A4):
   self-contained, backend + small frontend changes, moderate test additions. Smallest, ships
   first.
2. **Phase 11B — Profile quick wins** (`feature/phase11b-profile-quickwins`, B2 sound, B3
   preferences cleanup, B7 dead link): small, independent, low-risk.
3. **Phase 11C — Session management** (`feature/phase11c-session-management`, B1):
   self-contained Sanctum token feature.
4. **Phase 11D — Help Center draft auto-save** (`feature/phase11d-help-draft-autosave`, B4):
   self-contained, touches a different feature area (Help Center).
5. **Phase 11E — Activity tabs + Users management** (`feature/phase11e-activity-users`, B5 + B6):
   the biggest item, touches permissions, new pages, and reuses ActivityLogTab — do this last
   since B6a/B6b benefit from B5's real-activity-endpoint groundwork.

Each branches from `jonas`. Per Guide 1, feature branches push freely to both `origin` and
`client`; merging into `jonas` always needs per-push approval (ask every time).

**Open question for Jonas:** confirm this sequencing/branch split (or prefer fewer, larger
branches — e.g. combine 11A+11B, or combine 11C+11D). Also confirm the B3 Timezone decision
(recommend keeping it) and the B6b "Users" vs "Members" page naming/distinction before 11E
implementation starts, since that's the one item with real ambiguity left.

---

## Doc Trail (Guide 7 / 13 / 15 — done at the end of EACH sub-branch)

For every sub-branch (11A–11E):

1. Tick the relevant items in that branch's checklist in
   `docs/phase11-dashboard-profile/PROGRESSION.md`, **and** add/update a one-line summary row for
   "Phase 11" in the main `docs/PROGRESSION.md` Task Progress table.
2. Update `docs/SESSION_STATE.md` — Current Task, Last Completed Task, Open PRs/Active Branches
   table, and append a Session Log row.
3. Write `docs/testing/PHASE11{A-E}_TESTING.md` (Guide 13 structure: Prerequisites / Test cases /
   Negative cases / Cleanup).
4. `docs/PERMISSIONS_MATRIX.md` — update for 11E only (new permission for workspace-owner Users
   page + activity view), with a changelog row (Guide 15 hard gate).
5. End-of-task summary per Guide 7 (files created/modified, migrations, permissions, notifications,
   translation keys, commits).

---

## Verification

- Each branch: `php artisan test --compact` (full suite) + targeted `--filter` runs per new test
  class; `vendor/bin/pint --dirty --format agent`; Larastan
  (`php artisan clear-compiled && php -d memory_limit=1500M vendor/bin/phpstan analyse
  --memory-limit=1500M`); `npm run build`.
- Manual verification via dev server for each UI change (dashboard stat cards show real %, Kanban
  columns show correct projects, session list/revoke works, notification sound plays, Help Center
  draft banner appears, profile Activité shows real entries, owner Users page shows
  workspace-scoped data + activity drill-down).
- Write `docs/testing/PHASE11{A-E}_TESTING.md` per branch (Guide 13).
- Update `docs/phase11-dashboard-profile/PROGRESSION.md`, the main `docs/PROGRESSION.md`,
  `docs/SESSION_STATE.md`, and `docs/PERMISSIONS_MATRIX.md` (11E) at the end of each branch
  (Guide 13/15) — see "Doc Trail" section above.
