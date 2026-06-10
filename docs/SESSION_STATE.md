# Session State — Work Tracking v2.0

> **This file is updated at the END of every working session.**
> When resuming, read this file first — it tells you exactly where you are and what to do next.

---

## How to Resume

1. Read `docs/WORKING_GUIDELINES.md` (conventions + tools)
2. Read `docs/IMPLEMENTATION_PLAN.md` (full task details)
3. Read this file (current state)
4. Say: _"I've read the docs. Resuming from [Current Task] — [what's next]."_

---

## Current Session

**Date:** 2026-06-09
**Session goal:** Phase 10 — deep security + performance hardening pass.
**Branch:** `chore/hardening-pass` (from `jonas`) → **merged into `jonas` `6abe4fb`, pushed both remotes.**
**Status:** ✅ **Phase 10 done & merged.** Security: composer 7/8 CVEs patched (incl. HIGH symfony/mime CRLF; 1 laravel CVE deferred → Laravel 11), npm 2 HIGH (`ws`) fixed, **CORS `*`+credentials → env-driven `CORS_ALLOWED_ORIGINS`**, F3 tight throttles (login/register 5/min, refresh 10/min, payment 6/min). Perf: **dashboard 74→34 queries, admin/workspaces 41→12, admin/stats 44→22** (N+1 fixes incl. `Plan::free()` memoization + `Workspace` appended-count accessors), **frontend app.js 1 MB→197 KB** (vendor splitting + lazy libs + one chart library). 771/772 tests (1 pre-existing SocialAuth ordering flake), Pint + Larastan clean, build green. Full before/after in `SECURITY_PERF_BASELINE.md` (P1–P5); deferred majors in `MAJOR_UPGRADES_PLAN.md`.
**Phase 9** (Payment gateway) was completed + merged earlier (`106984f`): provider-agnostic seam, webhook-confirmed activation, MTN sandbox verified, Orange deferred (sandbox gated). Real-money go-live needs production merchant credentials (checklist in `TASK_PHASE9_TESTING.md`).

---

## Current Task

**Task:** Phase 11 — Dashboard Accuracy + Profile Page Fixes
**Plan:** `docs/phase11-dashboard-profile/PLAN.md` (approved 2026-06-10)
**Tracker:** `docs/phase11-dashboard-profile/PROGRESSION.md`
**Branch:** `feature/phase11b-profile-quickwins` (2 of 5 sub-branches: 11A-11E; 11A merged into
`jonas`, 11B not yet committed)
**Status:** ✅ 11A done (2026-06-10, merged into `jonas`). ✅ 11B done (2026-06-10, not yet
committed). 🔄 Next: 11C — session management.

**11A — done (merged into `jonas` `4367a3b`, pushed to both remotes):**
- `calculateStats()` now wires `calculateChange()` for `projets_actifs`/`taux_completion`/
  `taches_en_retard` — real period-over-period (`now()->subMonth()` snapshot) `change`/`trend`,
  no more hardcoded `+12%`/`+5%`/`-2%`.
- `getRecentProjects()` includes `active` + `completed` `ProjetStatus` (was `active`-only);
  `archived` excluded.
- `kanbanColumns` in `Dashboard.vue` reduced to 2 columns (`active`/`completed`) aligned to
  `ProjetStatus`; new `common.completed` i18n key (fr/en) added alongside existing
  `common.active`/`common.archived`.
- Debug `console.log`s removed from `updateStatsCards()`.
- `AdminController::computeStats()` `tasks.overdue` switched to `Tache::overdue()->count()`
  (matches `isOverdue()` — counts échéance-based overdue regardless of stored `statut`).
- New: `tests/Feature/Dashboard/DashboardStatsTest.php` (4 tests) +
  `PlatformDashboardTest::test_stats_overdue_uses_isoverdue_semantics_not_just_en_retard_status`.
  Pint + Larastan clean, `npm run build` green, full suite 788/789 (1 pre-existing unrelated
  SocialAuth ordering flake, passes in isolation).
- Testing guide: `docs/testing/PHASE11A_TESTING.md`.

**11B — done (on `feature/phase11b-profile-quickwins`, branched from `jonas` post-11A-merge; NOT
yet committed):**
- B2: New `useNotificationSound.js` composable (`playIfEnabled()`, reads
  `localStorage.notificationSettings.notificationSounds`, `.play()` wrapped in try/catch for
  autoplay restrictions) + new asset `public/sounds/notification.ogg` (short two-tone chime,
  generated with `sox`, ~5.5KB). Wired into `App.vue`'s existing `onNotification(...)` callback
  (registered via `useLiveNotifications`) — plays on every incoming live notification when the
  setting is enabled.
- B3: `PreferencesSettings.vue` reduced to Timezone section + Reset/Save actions only — removed
  Theme, Language, Date Format, Display Density, Auto-Save (UI + `preferences.*` fields +
  `applyTheme()`). `language`/`timezone` still sent via `PUT /users/profile` on save (unchanged
  backend contract). Guide 14 check: `userPreferences` localStorage key only used within this
  component; navbar theme/language switchers are independent. Removed now-unused
  `pref_settings.theme_*`/`lang_*`/`date_*`/`density_*`/`autosave_*` i18n keys (fr/en).
- B7: Removed dead `/settings` entry (`user_menu.account_settings`, 404) from `UserMenu.vue`'s
  `menuItems` + unused `SettingsIcon` import; removed `user_menu.account_settings` i18n key
  (fr/en) after confirming single usage.
- No JS test runner configured — manual verification documented in
  `docs/testing/PHASE11B_TESTING.md`. Pint + Larastan clean, `npm run build` green.

**Sub-branch sequence:**
1. ~~**11A** — Dashboard accuracy~~ ✅ done, merged into `jonas`.
2. ~~**11B** — Profile quick wins~~ ✅ done, awaiting commit.
3. **11C** (next) — Session management: Sanctum token list/revoke endpoints + real
   `SessionSettings.vue` UI (replaces broken `authStore.logoutAllDevices()`).
4. **11D** — Help Center Tiptap draft auto-save: new `draft_body_fr/en`/`draft_saved_at`
   columns on `help_articles`, draft endpoint, restore/discard banner.
5. **11E** — Activity tabs + Users management: wire profile Activité tab to real
   `GET /users/{user}/activity`; per-user activity drill-down for super-admin (from
   `/admin/users`) and a new workspace-owner-scoped "Users" page + endpoint + permission.

**What to do next:**
1. 11B is implemented but **not yet committed** — awaiting user instruction to commit (per
   CLAUDE.md: never commit without explicit instruction).
2. After commit, start 11C on a new branch `feature/phase11c-session-management` from `jonas`
   (after 11B is merged into `jonas`, mirroring the 11A flow — confirm with user).
3. After each sub-branch: tick `docs/phase11-dashboard-profile/PROGRESSION.md`, update the Phase
   11 row in main `docs/PROGRESSION.md`, write `docs/testing/PHASE11{A-E}_TESTING.md`, update this
   file (Current Task → next sub-branch).

---

## (Previous) Phase 9 — Payment Gateway (MTN MoMo + Orange Money)

**Branch:** `feature/phase9-payment-momo-orange`
**Status:** 🚧 Implemented; uncommitted; awaiting commit + push/merge approval. (Unrelated to
Phase 11 — left as-is; resume separately.)

**What was done (Phase 9):**
- **Provider-agnostic seam** — `PaymentProviderInterface` + `PaymentResult` DTO + `MtnMomoProvider` (token cache, requesttopay 202, status map) + `OrangeMoneyProvider` (token, webpayment→pay_token/payment_url, transactionstatus) + `PaymentProviderRegistry` (key→impl). Strategy pattern.
- **Data** — `payments` migration + `Payment` model (uuid `reference` = idempotency key + MTN `X-Reference-Id`) + `PaymentFactory`. `amount` frozen at checkout.
- **`PaymentService`** — `startCheckout()` (create pending + initiate, persist redirect_url) + **idempotent** `confirm()` (no-op if not pending → safe on replayed callbacks). `SubscriptionService::activateFromPayment()` = pending→active (plan_id, mode=paid, status=active, ends_at by billing period).
- **Webhook-confirmed only** — `PaymentWebhookController::{momo,orange}` look up our payment by reference and **re-verify status via the provider API** (`fetchStatus`); never trust the POST body. Public routes (no auth/CSRF; `api` group is stateless).
- **Routes** — `POST /api/payment/initiate`, `GET /api/payment/{reference}/status` (auth; `payment` prefix already exempt from `subscription.status`); public `POST /api/webhooks/payment/{momo,orange}`.
- **Secrets** — `config/payment.php` reads `.env` only; `.env.example` placeholders added; nothing logged/committed.
- **Frontend** — `Plans.vue` paid-plan button opens a payment modal (provider + phone); MTN = push + poll `payment/{ref}/status`; Orange = `window.location` redirect; `?payment=return\|cancel` handled on mount; fr/en `subscription_plans.payment.*` i18n.
- **Tests/gates** — 8 `PaymentFlowTest` via `Http::fake()` (initiate MTN/Orange, webhook success/fail, idempotent replay, unknown ref, non-owner, free plan); 51 with subscription suite. Pint + Larastan clean, build green.

**What to do next (when resumed):**
1. Commit Phase 9; on approval push + merge into `jonas` (both remotes).
2. Real sandbox testing needs MTN/Orange sandbox credentials in `.env` (Jonas fills; build never asks for real values). Orange base URLs/payloads vary by operator — confirm vs onboarding pack.

---

## (Previous) Phase 8 — merged

**Branch:** `feature/phase8-subscription-plans` → merged into `jonas` (`9ace2e9` core, `4011b26` plan CRUD, `3820162` UI refinements).

**What was done (Phase 8 — core, merged `9ace2e9`):**
- **Plan entity** — `plans` table + `Plan` model/factory/`PlanSeeder` (free/starter/pro). `workspace.plan_id` (FK nullOnDelete) + `subscription_status` (trial/active/lapsed/locked/free/pending) + `subscription_ends_at`. **Migration ordering gotcha:** `create_plans` retimed to `210206` so it runs before `add_plan_to_workspaces` (`210207`).
- **Plan-aware `SubscriptionService`** — `effectivePlan` (paid plan or free fallback), `planLimit` (`-1` = unlimited), `isLocked`, `reconcileStatus` (lazy trial/subscription expiry → `lapsed`), plan + status in `summary()`. `canAddMember`/`canUpload*` now read the effective plan's limits.
- **Global `CheckSubscriptionStatus` middleware** — alias `subscription.status` on the `auth:sanctum` route group → runs on **every authenticated request**; HTTP **402** when workspace is `locked`; exempt prefixes (`auth`, `subscription`, `admin/subscription`, `payment`, `webhooks`, `user`); super_admin bypass.
- **`SubscriptionController`** — plans / current / select (free=immediate, paid=`pending`) / activate / lock / unlock. Manual activation only (Phase 9 = real payment).
- **Frontend** — `/subscription/plans` pricing page + select; **402 interceptor → redirect to plans**; admin activate/lock/unlock UI in `AdminWorkspaces`; sidebar links; fr/en i18n.

**What was done (plan CRUD — merged `4011b26`):**
- `AdminPlanController` (index/store/update/destroy), super_admin-gated. `StorePlanRequest`/`UpdatePlanRequest` — **super_admin check in `authorize()`** so a non-admin gets 403 *before* validation (not a misleading 422). Delete guards: free fallback + in-use plan → **422**.
- Routes `GET/POST/PUT/DELETE /api/admin/plans`; `subscription.plan_errors.*` lang.
- `/admin/plans` **ManagePlans** page (table + create/edit modal + guarded delete) + sidebar link + fr/en i18n.
- **43 subscription tests** (21 pre-existing + 13 `SubscriptionStatusTest` + 9 `AdminPlanTest`).

**Uncommitted on the feature branch (frontend refinements):**
- `Plans.vue` — cards now show **dynamic plan limits only** (members/storage/file, `-1`=Illimité); static seeded `features` list removed.
- `ManagePlans.vue` — `openEdit` tightened to send only editable fields (drops `id`/timestamps/`workspaces_count` from the PUT).
- `fr.json`/`en.json` — `subscription_plans.limit_*`/`unlimited` keys.
- Build green, Pint passed. (Root cause of "edited limits not showing" = the card rendered static `features` text, never the numeric limit columns.)

**What to do next:**
1. **Commit** the uncommitted Phase 8 frontend refinements; on approval push + merge into `jonas` (both remotes).
2. Then **Phase 9** — payment gateway (MTN MoMo + Orange Money). Reuses `subscription.status`; processing a payment flips a `pending` selection → `active`.

**What was done (Phase 6 — role tiers, notifications, security):**
- **Role-scoped search tiers** — `search.global` (super-admin/owner/manager: workspace-wide; super-admin = all workspaces) + new `search.scoped` (cadre/collaborateur/stagiaire: assigned resources only); observateur/utilisateur = none. Full Guide 4+15 flow; `PERMISSIONS_MATRIX.md` updated.
- **`SousTache` searchable (7th model)** + **`Notification` searchable (8th model)** — `App\Models\Notification` extends `DatabaseNotification` + `Searchable`; `User::notifications()` overridden for auto-indexing; notification search **always user-scoped** (privacy — even super-admin only sees their own).
- **Topbar search button** (`header/SearchPageButton.vue`) replaces the sidebar link.
- **SECURITY — critical multi-tenant leak fixed:** under Typesense, `fromRaw` ignored the `->query()` Eloquent scoping, leaking cross-workspace results (and all users' notifications). Fixed by applying scope via Scout `->where()/->whereIn()` (Typesense `filter_by`) in the raw path, keeping `->query()` for the collection/test path.
- **SECURITY — `exportSelected` IDOR fixed:** requested IDs now constrained to the caller's workspace; bulk export restricted to manager+ (`search.global`).
- All 8 Scout collections reindexed (note: `SCOUT_QUEUE=true` → `scout:import` queues jobs; reindex was run with `SCOUT_QUEUE=false` to index synchronously — a queue worker is needed for live auto-indexing).

**What was done (Phase 6 Part B):**
- Migrations: `workspace_id` on `teams` (backfilled from project chain) + `content_text` on `documents`
- `DocumentTextExtractorService` — PDF/docx/xlsx/txt parsers, graceful null on failure
- `ExtractDocumentTextJob` (ShouldQueue) — dispatched on document upload, re-indexes after extraction
- `documents:extract-text` artisan command — backfills existing documents
- Expanded `toSearchableArray()` on all 6 models — rich text + context names, hard exclusions for credentials/paths
- `TeamMessage` made Searchable (6th model) — workspace-scoped via `team.workspace_id`
- `SearchController` extended — super-admin global scope, Typesense highlights via `->raw()`, pagination, workspace_name attribution, `per_type`/`page` params
- `SearchExport` (multi-sheet Excel) + `SearchExportJob` (queued large export → email) + `SearchExportReadyNotification`
- `GET /api/search/export` (global) + `POST /api/search/export` (selective) routes
- `pages/Search.vue` — full search page: type tabs with count badges, highlighted results with DOMPurify, read-only preview modals, export bar with cap dialog (500/1000/2000/Tout)
- `TacheDetailModal.vue` — `readonly` prop hides all edit/action controls
- `SearchBar.vue` — fixed position (Teleport → relative div), fixed field names (`description` → `excerpt`, `statut` → `meta.statut`), added `stripMark()` helper, "Voir tous les résultats" CTA
- `WorkspaceController` — `can_search_global` added to all 3 `user_permissions` locations (was only in 1)
- `useWorkspacePermissions.js` — `canSearchGlobal` now includes `isDirecteur.value`
- Teams/Show.vue — `?message=` query param handler for search result deep-link
- `docs/extended-features/INDEX.md` created; PHASE5_PLAN.md + PHASE6_SEARCH_PLAN.md added; PROGRESSION.md updated through Phase 6

**What to do next:**
1. Start **Phase 7** — Help Center (reader + author, no AI). Follow `docs/extended-features/HELP_CENTER_PLAN.md` Phases 1–3 + 6 (skip AI Phase 4). Branch `feature/phase7-help-center` from `jonas`.

<details><summary>Earlier Phase 5/6 build detail (retained for reference)</summary>

**Phase 6 (Part A):**
- `laravel/scout` + `typesense/typesense-php` installed
- `config/scout.php` — Typesense driver with 5 model-schemas (Projet, Activite, Tache, Document, User); collection driver default for tests; `SCOUT_DRIVER=collection` added to `phpunit.xml`
- `.env.example` — Typesense vars + Podman startup command documented
- `search.global` permission — full Guide 4+15 flow: Permission.php constant + all() + forRole(owner/manager), Permission.js, useWorkspacePermissions.js canSearchGlobal, WorkspaceController can_search_global (3 locations), PERMISSIONS_MATRIX.md updated
- `Projet`, `Activite`, `Tache`, `Document`, `User` made Searchable: `toSearchableArray()` with workspace-scoped fields; `searchableAs()` returns collection name; `->query()` used in controller for workspace scoping (works with both collection and Typesense drivers)
- `SearchController::search()` — `GET /api/search`, validates q/types/workspace_id/per_type, double-checks workspace membership, searches 5 types with Eloquent scope, returns grouped results
- `UserService::searchUsers()` fixed — `User::search()` now resolves to Scout; renamed to `User::query()->search()` (Eloquent scope)
- `SearchBar.vue` fully replaced — functional command palette: Cmd+K/Ctrl+K global shortcut, debounced 400ms, grouped results with emoji type icons, keyboard nav (↑↓/Enter/Esc), `Teleport` dropdown, hidden/disabled for non-managers
- 8 i18n keys in `search` section (fr + en)
- 8 PHPUnit tests in `tests/Feature/Search/SearchTest.php` — all green
- Testing guide: `docs/extended-features/testing/TASK_PHASE6_TESTING.md`

**What was done (Phase 6 Part B):**
- 2 migrations: `workspace_id` on `teams` (+ backfill from project), `content_text` on `documents`
- `DocumentTextExtractorService` — PDF/docx/xlsx/txt parsers (smalot/pdfparser, phpoffice)
- `ExtractDocumentTextJob` (ShouldQueue, 2 tries, 120s timeout) dispatched on every upload
- `documents:extract-text` artisan backfill command (--all, --sync flags)
- Expanded `toSearchableArray()` on all 6 models: rich text fields + workspace_name + context names (no credentials/paths)
- `TeamMessage` Searchable (6th model), `shouldBeSearchable()` guards workspace_id
- `SearchController` fully rewritten: super-admin global scope, Typesense highlights via `->raw()`, pagination, `totals` per type, 6 model types
- `SearchController::export()` (global, cap dialog) + `exportSelected()` (POST with IDs)
- `SearchExport` + `MultiSheetSearchExport` + `SearchExportJob` + `SearchExportReadyNotification`
- Export routes (GET + POST /api/search/export) + download route in web.php
- `pages/Search.vue` — full search page: type tabs with counts, highlighted results, read-only preview modal, export bar with cap dialog, keyboard nav, stagger
- `TacheDetailModal.vue` — `readonly` prop (hides QuickActionsPanel)
- `SearchBar.vue` — "Voir tous les résultats" CTA → `/search?q=...`
- Router `/search` route
- `Teams/Show.vue` — `?message=` scroll + 3s highlight ring via `data-message-uuid`
- 28 i18n keys (search_page + search.see_all)
- 15 PHPUnit tests — all green; bug fix: `(bool) ($user->is_super_admin ?? false)` for nullable column
- Testing guide: `docs/extended-features/testing/TASK_PHASE6_ENHANCED_TESTING.md`
- Re-index note: `scout:flush` + `scout:import` per model after schema change (requires Typesense running, `SCOUT_QUEUE=false` for synchronous indexing)

**Earlier session history (pre-Phase-3, retained for reference):**
- Replaced abandoned `nunomaduro/larastan` with `larastan/larastan` v2.11; updated `phpstan.neon` extension path
- Added `@property ModelClass $resource` + `@mixin ModelClass` to all 16 API Resource classes (fixes PHPStan proxy property errors)
- Added `@responseField` annotations to each resource's `toArray()` for future Scribe API doc generation
- Added comprehensive `@property` PHPDoc blocks to: `Tache`, `TacheResultat`, `User`, `CommentReaction`, `EvaluationScore` models
- Fixed real bugs uncovered by PHPStan:
  - `DocumentPolicy`: removed non-existent `$document->uploaded_by` (replaced with `$document->user_id`)
  - `TacheResultatController`: Document field names (`chemin_fichier→chemin`, `nom_fichier→nom`, `taille_fichier→taille`, `type_fichier→mime_type`)
  - `ResultatRejeteNotification` constructor call: wrong args (`$tache,$resultat` → `$resultat,auth()->user(),'commentaire','n2'`)
  - `TacheLinkAddedNotification`: removed duplicate `link_id` key
  - `TacheResultatResource`: `$activite->titre` → `$activite->nom`, `$projet->titre` → `$projet->nom`
  - `TacheResultatController::createDocumentPermissions`: validator IDs read from `$resultat` not `$tache` (correct model)
  - `TacheResultat::notifyValidators()`: promoted from `protected` to `public` (called cross-model)
  - `TacheStatut::badgeClass()`: added missing `ANNULE` and `EN_ATTENTE` match arms
  - `DashboardController::getMonthlyProgress()`: removed extra `$workspaceIds` arg from call
  - `EvaluationController::abort_unless($workspace)`: fixed to `$workspace !== null` for bool type
  - `LabelController::$projetId`: cast from `string|null` to `int|null`
  - `DocumentService::getEntity()`: `protected` → `public` (called from DocumentController)
  - `TacheController::getAllTaches/getMyTaches/createTache`: fixed User param vs int ID mismatch
  - `DocumentAccessResolver`: added `@var` type assertions per switch case for polymorphic entity
  - `AccessManagementService`: typed raw DB row via `@var object{...}` for `temporary_access`
  - `ProjetService`: removed incorrect `@return` PHPDoc, fixed `inWorkspace` cast, added typed `@param` Builder
  - `LabelService` + `LabelTemplateService`: fixed `Collection` vs `SupportCollection` return types
  - `ActiviteController::prepend()`: replaced array arg with User model
  - `Tache.php:1023`: fixed string subtraction with `floatval()` cast
  - `TeamResourceNotification.php`: removed impossible `'file'` match arm
  - `CheckSubscriptionLimits.php`: fixed int `0` default on `header()` → `'0'`
  - `UpdateActiviteRequest/UpdateProjetRequest/UpdateTacheRequest/UpdateUserRequest`: typed route model via `/** @var */`
  - `User` model: added `HasOne`/`HasMany` return types to `notificationPreference()` and `pushSubscriptions()`
  - `TacheResultat` model: added `rejetePar(): BelongsTo` relationship
- 627 PHPUnit tests, all passing (0 regressions)

**What to do next:**
1. Continue with next planned task or client feedback
2. (Optional) Scribe API docs generation — resource annotations are now ready

</details>

## Last Completed Task

**Task 15** — Task List UX (2026-05-26)
- `TacheTable.vue` component: table with statut/priorité/échéance inline edit via `PATCH /api/taches/{id}`
- `Taches.vue`: `currentView` defaults to `'table'`; `filterAssignee` ref defaults to `'me'`; `filteredTasks` computed; `route.query.activite` read in `onMounted`; view toggle shows Tableau/Kanban/Liste
- `ActiviteDetail.vue`: "Voir toutes les tâches" `router-link` added to header → `/taches?activite={id}`
- `PATCH /api/taches/{id}` route added (same controller as PUT)
- `statut` validation in `TacheController::update()` extended with `en_retard` and `a_refaire`
- PHPUnit: 5 tests in `tests/Feature/Task15/TaskListUxTest.php`
- **269 PHPUnit tests, all passing.**

**Task 14** — Platform Super Admin Dashboard (2026-05-26)
- `AdminController` with 6 methods: `stats`, `workspaces`, `users`, `extendTrial`, `suspendWorkspace`, `reactivateWorkspace`
- All `/api/admin/*` routes protected by `super_admin` middleware (prefix group in `routes/api.php`)
- `TrialExtendedNotification` + `WorkspaceSuspendedNotification` (ShouldQueue, channelsFor-routed as high-signal events)
- `NotificationService::wantsEmail()` + `wantsWebPush()` extended with `trial_extended` + `workspace_suspended`
- Translation files `lang/fr/admin.php` + `lang/en/admin.php` (5 sections: dashboard, workspaces, users, actions, notifications)
- `AdminDashboard.vue` — 6 workspace stat cards + 2 user stat cards, recent workspaces table, quick links
- `AdminWorkspaces.vue` — paginated table with search/mode/status filters, extend-trial modal, suspend modal, reactivate button
- `AdminUsers.vue` — paginated table with search filter
- `SubscriptionBadge.vue` component (color-coded by subscription mode)
- Vue router: 4 admin routes with `requiresSuperAdmin` meta + `beforeEach` guard → redirects non-super-admins to 404
- AppSidebar: Administration section (3 items, `superAdminOnly: true`), auto-hidden from non-admins
- PHPUnit: 11 tests in `tests/Feature/Admin/PlatformDashboardTest.php`
- **264 PHPUnit tests, all passing.**

**Task 13** — Subscription Modes + Trial Duration (2026-05-26)
- Migration: `subscription_mode` (default `trial`), `trial_started_at`, `trial_duration_days` (default 30) on workspaces
- `config/subscription.php` with env-driven defaults for all limits and warning window
- `Workspace` model updated: 3 fillable fields, casts, `trial_started_at` auto-seeded in `boot()::creating()`
- `SubscriptionService`: `isPaid`, `isTrialExpired`, `getRemainingTrialDays`, `isExpiringSoon`, `canAddMember`, `canUploadFile`, `canUploadStorage`, `summary`
- `CheckSubscriptionLimits` middleware (`subscription.limits`): bypasses super_admin, checks trial expiry first, then limit-specific check via `$limitType` param
- Routes gated: `subscription.limits:add_member` on invite route, `subscription.limits:upload_file` on document store route
- `Permission::SUBSCRIPTION_MANAGE` constant + all() + Permission.js + useWorkspacePermissions.js
- `WorkspaceController::show()`: `can_manage_subscription` in user_permissions (3 locations) + `subscription_summary` in response
- `GET /api/workspaces/{id}/subscription` + `PATCH /api/workspaces/{id}/subscription` (super_admin only)
- 3 notifications: `TrialExpiringNotification`, `TrialExpiredNotification`, `SubscriptionLimitReachedNotification` (all ShouldQueue, channelsFor-routed as high-signal)
- `NotificationService::wantsEmail()` + `wantsWebPush()`: 3 new subscription event types added as high-signal
- Translation files: `lang/fr/subscription.php` + `lang/en/subscription.php`
- `TrialBanner.vue`: amber/red dismissible banner, shown when expiring soon or expired
- `AdminLayout.vue`: TrialBanner mounted above content, workspace-reactive
- `WorkspaceFactory`: `paid()`, `trialExpired()`, `trialExpiringSoon()` states
- PHPUnit: 13 tests (`SubscriptionServiceTest`) + 8 tests (`SubscriptionMiddlewareTest`) = 21 new tests
- **253 PHPUnit tests, all passing.**

**Task 12** — Document Management per Project + Workspace (2026-05-26)
- `DOCUMENTS_MANAGE_WORKSPACE` permission added to Permission.php + forRole() + `all()` (owner only)
- `can_manage_workspace_documents` added to WorkspaceController user_permissions (3 places) + useWorkspacePermissions.js
- Document permission keys (view/upload/delete/share) added to ProjetResource + useProjetPermissions.js
- `WorkspaceDocuments.vue` page created; workspace router entry uncommented
- `POST /api/documents/{id}/share-by-email` endpoint + `DocumentSharedNotification` (mail-only, queued, external email)
- `DocumentUploadedNotification` (in-app, cadre+manager project members) wired into `store()`
- `DocumentDeletedNotification` (in-app, project responsable) wired into `destroy()` with Log::warning on unauthorized attempt
- `GET /api/documents/workspace/{id}` gated to `DOCUMENTS_MANAGE_WORKSPACE` (was open to all workspace members)
- `lang/fr/documents.php` + `lang/en/documents.php` with actions/share/notifications/errors keys
- PHPUnit: 5 tests in `tests/Feature/Task12/DocumentManagementTest.php` (view 200, non-member not exposed, share-by-email notification, workspace 403/200)
- **226 PHPUnit tests, all passing. Merged into `jonas` 2026-05-26.**

**Task 11** — Task Creation UX (2026-05-26)
- 3 new permissions: `evaluations.view_dashboard`, `evaluations.view_workspace_taches`, `taches.inline_edit` — wired into Permission.php + forRole() + Permission.js + useWorkspacePermissions.js + WorkspaceController user_permissions (3 locations) + PermissionService (3 helpers)
- `EvaluationController::evaluationDashboard` — scope-aware: owner→all workspace members, manager→project members, cadre→activity members; top_performers (top 5 by score) + all scores + alerts (escalades_abusives + high_inaction_rate ≥ 33%)
- `TacheController::workspaceTaches` — owner/super_admin only; filterable by statut/projet_id/activite_id/assignee_id; paginated (25/page); TacheResource + eager loads
- Routes: `GET /api/evaluations/tableau-de-bord` + `GET /api/workspace/taches`
- 2 notifications: `AbusiveEscalationAlertNotification` (in-app + email) + `HighInactionRateAlertNotification` (in-app only)
- Translation keys in `lang/fr/evaluation.php` + `lang/en/evaluation.php` (dashboard section + workspace_tasks section)
- `EvaluationDashboard.vue` — period filter, top performers grid, all-scores table, alerts section (escalades_abusives + high_inaction_rate)
- `WorkspaceTaches.vue` — filter bar, sortable table, pagination, row click → taches.show
- AppSidebar + Vue router wired for both pages
- PHPUnit: 12 tests in `tests/Feature/Task10/` (dashboard 200/403 by role + manager scope isolation; workspace taches 200/403 by role + statut filter)
- Dusk: 2 tests in `tests/Browser/Evaluation/EvaluationDashboardTest` (owner sees all sections; period filter + refresh)
- PERMISSIONS_MATRIX.md: 3 new rows + 1 correction (cadre now ✅ for dashboard) + changelog entry
- **216 PHPUnit tests, all passing**

**Task 8b** — Web Push Notifications (2026-05-22, implementation complete)
- `composer require minishlink/web-push` v10.0.3 (free, MIT, pure PHP, no external service)
- `php artisan webpush:generate-vapid` command — génère les clés VAPID à coller dans `.env`
- `config/webpush.php` + .env.example block (VAPID_SUBJECT / VAPID_PUBLIC_KEY / VAPID_PRIVATE_KEY / TTL / urgency / VITE_VAPID_PUBLIC_KEY)
- `App\Notifications\Channels\WebPushChannel` — sérialise le payload, signe avec VAPID, gère 410/404 (soft-disable de la souscription)
- API `/webpush` : vapid-key (GET), subscribe (POST, upsert), unsubscribe (DELETE), subscriptions (GET, liste active du user)
- `NotificationService::channelsFor()` étendu : ajoute `WebPushChannel::class` quand le user a une souscription active ET `push_enabled = true` ET event high-signal (renvoye_n0/transmis_auto/bypass/escalades_abusives)
- Frontend : `public/sw-webpush.js` service worker (event 'push' → showNotification, 'notificationclick' → focus/openWindow) + `useWebPush.js` composable (permission flow, urlBase64ToUint8Array, subscribe/unsubscribe avec persistance backend)
- UI : panneau « Cet appareil » dans `NotificationPreferences.vue` quand `push_enabled` est on — boutons Activer/Désactiver, statut humain
- 13 feature tests in `WebPushSubscriptionTest` couvrant : vapid-key (200/503/401), subscribe (create/upsert/validation), unsubscribe (deactivate/no-op), index (filtre active + own), channelsFor() (with/without subscription, push_enabled false, low-signal events)
- 122 tests passing total, all green

**Task 8** — Real-Time Notifications + Daily Digest (2026-05-21, merged into jonas)

Real-time + service helpers:
- `NotificationService::channelsFor(notifiable, eventType)` — resolves `['database', 'broadcast']` ± `'mail'` based on event signal level
- `NotificationService::dedupKey(eventType, tacheResultatId)` + `isDuplicate(user, eventType, tacheResultatId)` — 5-minute deduplication window via `data.dedup_key` in `notifications` table
- `NotificationService::notifyHierarchy(recipient, workspace, notification, eventType, tacheResultatId)` — fans out to direct recipient + workspace directeur (owner_id) + all workspace managers, dedup per user
- All 7 existing notifications (`ResultatSoumisN0/Renvoye/ApprouveN0/TransmisAuto`, `BypassActivated`, `EscaladesAbusives`, `ScoreUpdated`) use `channelsFor()` in `via()` and include `dedup_key` in `toArray()`
- Frontend: `useLiveNotifications.js` composable wraps `useEcho` and subscribes to `App.Models.User.{id}` private channel; wired into `App.vue` `onMounted`, surfaces toast on incoming notification
- Notification bell (`NotificationMenu.vue`) gets `dusk` attributes for Dusk test targeting
- `NOTIFICATIONS_MANAGE_PREFERENCES` permission across Permission.php + forRole (owner only) + Permission.js + useWorkspacePermissions.js + WorkspaceController user_permissions (3 locations)
- PERMISSIONS_MATRIX.md: new Notification Permissions section + changelog row
- `phpunit.xml` adds `BROADCAST_DRIVER=log` + `BROADCAST_CONNECTION=log` so tests don't hit real Reverb

Daily digest (Task 8c — built in same PR):
- Migration `add_last_digest_sent_at_to_notification_preferences_table` (indexed timestamp column)
- `SendDailyDigest` command (`notifications:send-digest`) with `--dry-run` and `--user=` options
- Aggregation logic: filters by `digest_frequency != 'none'`, `digest_time <= now()`, `last_digest_sent_at` empty-or-before-today
- Quiet hours respected — users inside `quiet_hours_start..end` window skipped (midnight-wrapping windows handled)
- `DailyDigestMail` mailable + Blade templates `emails/daily-digest/{fr,en}.blade.php` (notifications grouped by type, top 5 per group, link to all)
- Translation keys `notifications.daily_digest.subject` (fr + en)
- Scheduled every 15 minutes in `app/Console/Kernel.php` with `withoutOverlapping(20)` + `onOneServer` + `runInBackground`

Tests: 11 feature in `NotificationServiceTest` + 8 feature in `SendDailyDigestCommandTest` + 2 Dusk in `NotificationBellTest` = **109 total, all passing**

**Deferred to Task 8b (Web Push, separate PR):**
- `minishlink/web-push` Composer package
- VAPID key generation + secure storage
- Service worker registration + browser permission UX
- `WebPushChannel` notification channel

**Task 7** — N1 Scores + Pending Validations Dashboard (merged 2026-05-21, commit `c891a6e`)
- Migration `create_evaluation_scores_table` (user_id, periode_start/end, critere, valeur, meta JSON + 3 indexes)
- `EvaluationScore` model with scopes `inPeriod` / `decidedBetween` + factory with `penalty`/`bonus`/`forUser` states
- `EvaluationScoreService::calculerImpactN1` — 3 paths (penalty / bonus / no_impact), writes evaluation_scores row + mirrors to validation_audit_logs + dispatches ScoreUpdatedNotification
- `EvaluationScoreService::totalForUser` — SUM helper with optional date range
- `TacheResultatService::validerN1` / `rejeterN1` — wrap model methods + call scoring service; `rejeterN1` calls `invaliderBypassN1` internally when bypass_active
- `TacheResultatController::validateN1` / `reject` routed through service
- `EvaluationController::pendingValidationsDashboard` — sorted by remaining deadline, urgent flag (< 24h), bypass/escalades_abusives badges
- `EvaluationController::userScore` — own-score for all roles, others gated by privileged permission
- Routes: `GET /evaluations/validations/en-attente`, `GET /evaluations/score`
- `ScoreUpdatedNotification` — database channel only (no email, per spec)
- Permissions: `EVALUATIONS_VIEW_PENDING` + `EVALUATIONS_VIEW_SCORE` across Permission.php + forRole + Permission.js + useWorkspacePermissions.js + WorkspaceController user_permissions
- Translations `lang/{fr,en}/evaluation.php`
- Vue: `pages/evaluations/PendingValidations.vue` + `PendingRow.vue` + sidebar link
- WORKING_GUIDELINES Guide 17 (logs in French) added in same session
- 10 feature tests + 1 Dusk test (90 total, all passing)

---

## Recently Completed Tasks

**Task 6** — Anti-Sabotage Bypass (merged 2026-05-19, commit `f6e98d2`)
- 2 migrations: `add_bypass_columns_to_tache_resultats`, `add_bypass_count_to_tache_user`
- `TacheResultatService`: `activerBypass` (R3 + R5), `invaliderBypassN1` (escalades_abusives flag at 3 consecutive)
- `TacheResultatController::activerBypass` — R3 checked before statut check (409 > 422 precedence)
- `RESULTATS_ACTIVER_BYPASS` permission: `Permission.php` + `forRole()` (collaborateur + stagiaire) + `Permission.js` + `useTachePermissions.js`
- `TacheResultatResource`: `bypass` block + `audit_logs` + `can_activer_bypass`
- `BypassActivatedNotification` (Blade email, fr + en) + `EscaladesAbusivesNotification` (inline)
- `circuit_validation.php` (fr + en): `success.bypass_active`, `bypass.*`, `notifications.bypass_active`, `notifications.escalades_abusives`
- 10 new tests in `BypassCircuitTest` — 80 total, all passing
- Workspace.php curly-quote bug fixed + `WorkspaceMembershipTest` (5 tests) added in same session

**Task 5** — N0 Validation Circuit + 48h Timer
- `ValidationAuditLog` model (immutable, R6)
- `TacheResultatService`: `soumettre`, `approuverN0`, `renvoyerN0`, `transmettreAuN1`
- `TransmettreResultatAuN1Job`: dispatched on submit, workspace-configured delay (default 48h), skips if N0 already acted
- 4 notifications: `ResultatSoumisN0`, `ResultatRenvoye` (Blade), `ResultatApprouveN0` (in-app), `ResultatTransmisAuto`
- `POST approuver-n0`, `POST renvoyer-n0` endpoints with R4 (min 30 chars)
- `useTachePermissions.js` created with `canApprouverN0`, `canRenvoyerN0`
- `circuit_validation.*` translation files (fr + en)
- 7 new tests, 48 total

**Task 3** — Subtask CRUD API + Automatic Progress
- `SousTacheService`, `SousTacheController` (5 endpoints), `SousTacheObserver`
- Weighted progress auto-recalculation on parent task
- Parent statut auto-change: all done → termine, any en_retard → en_retard
- Manual statut block when sous-taches exist (422)
- `canAssignSousTacheIntervenant` permission added
- 3 notifications (SousTacheAssignee, SousTacheOverdue, TacheStatutAutoChange)
- MySQL ENUM extended for taches.statut
- 10 new tests, 41 total, all passing

---

## Previously Completed

**Task 2** — Subtask Data Model
- 3 migrations: `create_sous_taches_table`, `create_sous_tache_user_table`, `drop_parent_tache_id_from_taches` (with data migration from self-referential pattern)
- `EN_RETARD` and `A_REFAIRE` added to `TacheStatut` enum
- `SousTache` model with R2 `enforceWeights()` rule, `LogsActivity`, `SoftDeletes`
- `SousTachePolicy` registered in `AuthServiceProvider`
- `SousTacheResource`, `SousTacheFactory`, `SousTacheSeeder` created
- `PermissionService`: 4 new methods (`canViewSousTache`, `canCreateSousTache`, `canEditSousTache`, `canDeleteSousTache`)
- `RolePermissionSeeder`: 4 new permissions, wired into all contextual roles
- Translation files: `lang/fr/sous_taches.php` + `lang/en/sous_taches.php`
- **Bug fix (ProjetController):** `soustaches` → `sousTaches` on 2 eager loads
- **Bug fix (Tache model):** `is_responsable` added to `assignees()` withPivot
- 6 new tests (R1 schema, R2 enforce/valid/unweighted, permission with/without is_responsable)
- 28 tests total, all passing

---

## Open PRs / Active Branches

| Branch | Phase / Task | Status |
|---|---|---|
| _(none active)_ | Phases 0–10 all merged into `jonas` | — |

**Previously merged into `jonas` (extended features):**
- `chore/security-perf-baseline` → `jonas` `4493754` (Phase 0 — baseline docs)
- `feature/mfa-2fa` → `jonas` `7525a81` (Phase 1 — MFA)
- `feature/social-auth-google` → `jonas` `19047fd` (Phase 2 — Social auth)
- `feature/support-contact` → `jonas` (Phase 3 + Phase 4 — support system + native admin logs)
- `feature/phase5-chat` → `jonas` `cae2a7e` (Phase 5 — chat real-time + @mentions)
- `feature/phase6-search` → `jonas` (Phase 6 — global search: tiers, notifications, file extraction, security hardening)
- `feature/phase7-help-center` → `jonas` `58f76da` (Phase 7 — Help Center: granular perms, global-search inclusion, category mgmt, Tiptap author UI; + project-wide animations, modal borders/drag, Guide 25 security)
- `feature/phase8-subscription-plans` → `jonas` `9ace2e9` + `4011b26` (Phase 8 — subscription plans, global 402 gate, manual activation, plan CRUD)
- `feature/phase9-payment-momo-orange` → `jonas` `106984f` (Phase 9 — payment gateway: provider-agnostic seam, webhook-confirmed activation, MTN sandbox verified, Orange deferred)
- `chore/hardening-pass` → `jonas` `6abe4fb` (Phase 10 — deep security + perf hardening: CVE patches, CORS lockdown, rate limits, dashboard/admin N+1 fixes, frontend bundle −81%)

**Previously merged (v2 tasks):**
Tasks 0–16, fix/cdc-hotfixes, fix/bug-batch, design-system-v1, chore/test-coverage-expansion — all merged into `jonas` (see PROGRESSION.md for full history).

---

## Pending Decisions / Open Questions

| # | Question | Context | Status |
|---|---|---|---|
| 1 | Frontend composable for SousTache permissions | `useTachePermissions.js` update deferred to Task 4 | Closed (Task 4 merged) |
| 2 | Per-task drill-down modal on evaluation sheet | Jonas's UX request 2026-05-19: captured in Task 9 spec. | Closed (Task 9 merged) |
| 3 | Design assets (colour palette, logo, mockups) from M. Kemtio | CDC gap A.1 — needed before UI polish sprint | Open — awaiting assets |
| 4 | SMS provider for critical alerts | CDC gap B.2 — Twilio / Vonage / local? | Open — pending decision |
| 5 | Billing/invoicing module scope | CDC gap B.4 — integrated vs external service? | Open — pending decision |
| 6 | Agent sheet §5 timing | Small enough for CDC Hotfixes, or defer to Task 16? | Open — decide during hotfix implementation |

---

## Environment Reminder

- Project path: `/media/jonas/Jonas/Work-traking` (USB drive — always push before leaving)
- DB: MySQL, database `work-tracking`
- Queue: `database` ✅
- Broadcasting: `reverb` ✅ — run `php artisan reverb:start` to start WebSocket server
- Remotes: `origin` = your repo (`s80programmeomega`), `client` = client repo (frozen until paid)
- Run backend: `php artisan serve`
- Run frontend: `npm run dev`
- Run tests: `php artisan test --compact`

---

## Session Log

| Date | Tasks worked on | Outcome |
|---|---|---|
| 2026-05-11 | Planning | Created IMPLEMENTATION_PLAN.md, WORKING_GUIDELINES.md, PROGRESSION.md, SESSION_STATE.md. Updated ONBOARDING.md. |
| 2026-05-13 | Task 0 bug fixes | Fixed Pinia readonly conflict, UserResource, hasAccess bypass, sidebar fallbacks. Added 6 factories, expanded seeder. |
| 2026-05-14 | Task 0 review + merge, Task 1, Task 2 | Reviewed Task 0 (22 tests, all clean). Merged into jonas. Task 1: queue=database, Reverb, useEcho.js. Task 2: sous_taches table, SousTache model with R1/R2, policy, resource, factory, seeder, permissions, translations. Fixed ProjetController bug (soustaches→sousTaches) and Tache assignees pivot (is_responsable missing). 28 tests passing. |
| 2026-05-15 | Task 4 bugs, Task 5 testing + fix, Dusk setup | Fixed role ENUM mismatch (6 files), ST badge missing from kanban/list (6 queries), SousTacheList modal self-contained refactor. Task 5: fixed submit() not calling TacheResultatService::soumettre() — job now dispatched. Installed Laravel Dusk, fixed 3 fragile migration rollbacks, wrote WorkTrackingTestCase base + AuthenticationTest (3 browser tests passing). |
| 2026-05-18 | Task 6, Task 4 recovery, bug fixes | Implemented Task 6 (anti-sabotage bypass): migrations, `activerBypass` + `invaliderBypassN1` services, 2 notifications + Blade emails (fr/en), 10 feature tests. Recovered orphan `feature/v2-task-4-subtask-ui` branch and merged into jonas. Fixed `Workspace.php` curly quotes, sidebar dropdown stuck/double-active, three task-detail sub-tab crashes (`/users`→`/members`, defensive `?? []`), ST badge missing from kanban (added `withCount('sousTaches')` in `TacheService`). 80 tests passing. |
| 2026-05-19 | Task 6 merge | Merged `feature/v2-task-6-bypass` into `jonas` (`--no-ff`, commit `f6e98d2`). Pushed `jonas` to `origin`. Updated SESSION_STATE, PROGRESSION, IMPLEMENTATION_PLAN. |
| 2026-05-19 | Task 7 implementation | Implemented EvaluationScoreService (penalty/bonus/no_impact paths), pending-validations dashboard endpoint + Vue page, permissions, translations. Added Guide 17 (logs in French). Wired TacheResultatService::validerN1/rejeterN1 into the controller. 10 feature tests + 1 Dusk test. 90 tests passing. |
| 2026-05-21 | Task 7 merge + Task 8 partial | Merged feature/v2-task-7-scores-dashboard into jonas (commit c891a6e, --no-ff) + pushed. Implemented Task 8 partial scope: NotificationService channelsFor/dedupKey/isDuplicate/notifyHierarchy, broadcast wired into 7 existing notifications via channelsFor, useLiveNotifications.js composable + App.vue subscription, NOTIFICATIONS_MANAGE_PREFERENCES permission, dusk attributes on NotificationMenu. Web Push and daily digest deferred to dedicated follow-up PRs. 11 feature tests + 2 Dusk tests. 101 tests passing. |
| 2026-05-26 | fix/bug-batch merge + Task 10 | Merged fix/bug-batch (G1–G10, G12; G11 skipped) into jonas. Cut feature/v2-task-10-dashboard. Implemented: 3 permissions, EvaluationController::evaluationDashboard, TacheController::workspaceTaches, 2 routes, 2 notifications, translation keys, EvaluationDashboard.vue, WorkspaceTaches.vue, sidebar + router. 12 PHPUnit feature tests + 2 Dusk tests. 216 tests passing. Merged into jonas. |
| 2026-05-26 | Task 11 | Task Creation UX. TacheCreateWizard.vue (4-step modal), IntervenantPicker.vue, TacheAssigneeNotification (mail+db queued) + TacheResourcesNotification (db), Blade email templates (fr/en), i18n keys. ActiviteDetail.vue + Taches.vue wired. 5 PHPUnit + 3 Dusk tests. 221 tests green. Merged into jonas. |
| 2026-05-26 | Task 12 | Document Management. DOCUMENTS_MANAGE_WORKSPACE permission (owner only), WorkspaceDocuments.vue, workspace docs endpoint gated, share-by-email endpoint + DocumentSharedNotification, DocumentUploaded/DeletedNotification wired, document permission keys in ProjetResource + composables, lang/fr+en/documents.php. 5 PHPUnit tests. 226 tests green. Merged into jonas. |
| 2026-05-26 | CDC Review | Cross-referenced CDC_WorkTracking_v2.pdf Rev.3 against all completed and planned tasks (T0–T14). Found 10+ gaps: A.1 (design assets), A.10–A.13 (task list UX), R7 (backend guard missing), CDC-API (audit-log endpoint missing), ST.7/E.2 (agent sheet §5 + export), B.1–B.5 (export, SMS, search, billing, rate limiting). Rate limiting verified ✅ already in place. Two new tasks added: T15 (task list UX), T16 (export). CDC hotfix batch created on `fix/cdc-hotfixes`. IMPLEMENTATION_PLAN.md + PROGRESSION.md + SESSION_STATE.md updated. |
| 2026-05-26 | CDC Hotfixes | R7 guard (`enforceMandatorySousTaches` in TacheResultatService::soumettre), `GET /api/audit-logs/validation/{tache}` endpoint, agent sheet §5 (`submitted_results` section in agentSheetSections + AgentSheet.vue 5th tab). 6 new PHPUnit tests. 232 total, all green. Committed on `fix/cdc-hotfixes`. |
| 2026-05-26 | Task 13 + Task 14 | Task 13: SubscriptionService, CheckSubscriptionLimits middleware, 3 notifications, TrialBanner.vue, WorkspaceFactory states, 21 PHPUnit tests. Task 14: AdminController (6 endpoints), 2 notifications, lang/fr+en/admin.php, AdminDashboard/Workspaces/Users pages, SubscriptionBadge, router guard, sidebar admin section, 11 PHPUnit tests. 264 total, all green. |
| 2026-05-26 | Task 15 | TacheTable.vue (table view + inline edit), Taches.vue (table default, assignee filter, deep-link), ActiviteDetail.vue shortcut, PATCH route, statut validation fix, 5 PHPUnit tests. 269 total, all green. |
| 2026-05-26 | Task 16 | PDF export (GET /api/evaluations/personnel/{user}/export-pdf, Blade+DomPDF, A4 portrait, criteria bars), Excel export (GET /api/workspace/taches/export-excel, WorkspaceTachesExport, 10-col, blue header, filter-aware). AgentSheet.vue + WorkspaceTaches.vue buttons wired. 5 PHPUnit tests. 274 total, all green. |
| 2026-05-26 | UX Polish | Inline editing on all 3 task detail views + SousTacheList. Modal state machine fix. DatePicker for echeance everywhere. Escape cancels any active edit (global keydown). No new backend changes. |
| 2026-05-28 | Document polish + notification fixes | Version manager UX, cadre upload fix, dark mode margin, share modal date fix, smart user filtering, activite role values fix, DocumentService grantPermission/revokePermission fix, DocumentPermissionGrantedNotification + push toWebPush, WebPushChannel fallback improved, Documents.vue tab from query param, expires_at 5-year cap. PHP ext-gmp installed for Web Push. |
| 2026-06-04 | Phase 4 — native admin logs | ValidationAuditLogResource + endpoint, useLogViewer.js, LogDetailDrawer.vue, ActivityLogTab (causer filter + date pickers + drawer), AppLogsTab (native: file picker / level chips / expand / download / delete), ValidationAuditLogTab (action badges + date pickers + drawer), AdminLogs.vue refactored to 3-tab container. 34 i18n keys. 6 PHPUnit + 4 Dusk tests. PDF export button fix (AgentSheet). Merged into jonas, pushed both remotes. |
| 2026-06-04 | Phase 5 — Chat real-time + @mentions | 4 broadcast events, team channel auth, ChatMentionNotification, TeamMessageResource, +5 controller methods, +7 routes, last_read_at migration, useTeamMessages.js rewrite (Echo + optimistic + whisper), Teams/Show.vue full UI overhaul, AppSidebar unread badge, 15 i18n keys, 12 PHPUnit + 3 Dusk tests. Merged into jonas. |
| 2026-06-04 | Phase 6 Part A — command palette | Scout + Typesense installed, search.global permission, 5 Searchable models, SearchController, SearchBar.vue Cmd+K palette. 8 PHPUnit tests. Uncommitted. |
| 2026-06-04 | Phase 6 Part B — full search page + file extraction | 6 migrations, DocumentTextExtractorService + jobs, TeamMessage Searchable (6th model), SearchController rewrite (super-admin, highlights, pagination), export pipeline (SearchExport+Job), Search.vue page, TacheDetailModal readonly, Teams/Show ?message= handler, 28 i18n keys, 15 PHPUnit tests. Awaiting commit + merge. |
| 2026-06-06 | Phase 6 — tiers, notifications, security | search.scoped tier (cadre/collaborateur/stagiaire); SousTache (7th) + Notification (8th, user-scoped) Searchable; topbar SearchPageButton; **fixed critical Typesense fromRaw scoping leak (filter_by) + exportSelected IDOR**; export restricted to manager+; reindexed all 8 collections; TeamMessages list test shape fix. Full suite 717 green. Merged into jonas, pushed both remotes. |
| 2026-06-07 | Phase 7 — Help Center + UI polish | Help Center (bilingual categories/articles, Purifier sanitize, MySQL FULLTEXT, Tiptap author UI); **granular** help perms (create/edit/publish/delete/upload_image + categories.manage); global-search inclusion (published-only). Plus project-wide collapse/fade animations (`animations.css`, `useDraggable`), sidebar submenu collapse fix, modal borders/draggability/outside-click sweep, mes-validations header fix, Guide 25 (security & prompt-injection defense). Committed `927565d`; merged into jonas `58f76da`, pushed both remotes. |
| 2026-06-07 | Phase 8 — Subscription plans + global gate | Plan entity (free/starter/pro) + workspace plan_id/subscription_status/ends_at; plan-aware SubscriptionService (effectivePlan, planLimit −1=unlimited, isLocked, reconcileStatus); **global CheckSubscriptionStatus middleware → 402 on locked** (exempt auth/subscription/payment; super_admin bypass); SubscriptionController (plans/current/select/activate/lock/unlock); `/subscription/plans` page + 402 interceptor + admin activate/lock UI. Migration ordering fix (create_plans→210206). 34 subscription tests. Committed `3420ef2`, merged into jonas `9ace2e9`, pushed both remotes. |
| 2026-06-08 | Phase 8 — plan CRUD + card display | Super-admin plan CRUD (AdminPlanController + Store/Update requests with super_admin authorize() → 403 before validation; delete guards free+in-use → 422) + `/admin/plans` ManagePlans page. 9 AdminPlanTest cases (43 subscription total). Committed `e579a80`, merged into jonas `4011b26`, pushed both remotes. Then frontend refinements (cards show dynamic limits only; openEdit payload tightened; i18n) — committed `b453e18`, merged `3820162`, pushed both. |
| 2026-06-08 | Phase 9 — payment gateway (MTN MoMo + Orange Money) | Provider-agnostic seam (interface + MTN/Orange providers + registry, Strategy); payments table + Payment model (uuid reference = idempotency key); PaymentService (startCheckout + idempotent confirm) + SubscriptionService::activateFromPayment (pending→active); **webhook-confirmed only** (re-verify status via provider API, never trust callback body); MTN push+poll / Orange redirect; config/payment.php + .env.example placeholders (secrets in .env only); Plans.vue payment modal + ?payment=return/cancel + i18n. Dev `PAYMENT_FAKE` toggle (prod-blocked) + `momo:provision-sandbox` cmd. 11 PaymentFlowTest (Http::fake). MTN sandbox verified live; Orange deferred (sandbox gated). Committed `dac13bd`+`724329b`, merged into jonas `106984f`, pushed both remotes. |
| 2026-06-09 | Phase 10 — deep security + perf hardening | **Security:** composer 7/8 CVEs (HIGH symfony/mime CRLF + others within L10; 1 laravel CVE deferred→L11), npm 2 HIGH (ws) fixed; CORS `*`+credentials→env-driven `CORS_ALLOWED_ORIGINS`; F3 throttles (login/register 5/min, refresh 10/min, payment 6/min); F1/webhook-CSRF/hard-lock verified sound. **Backend perf (per-endpoint sweep, DB::listen):** dashboard 74→34, admin/workspaces 41→12, admin/stats 44→22 (Plan::free memoization + Workspace appended-count accessors + GROUP BY + eager-loads). **Frontend perf:** app.js 1MB→197KB (vendor manualChunks + lazy ApexCharts + dead-CSS), one chart library (removed chart.js). 7 commits + MAJOR_UPGRADES_PLAN; P1–P5 in SECURITY_PERF_BASELINE. 771/772 tests (1 pre-existing SocialAuth flake), Pint+Larastan clean, build green. Merged into jonas `6abe4fb`, pushed both remotes. |
