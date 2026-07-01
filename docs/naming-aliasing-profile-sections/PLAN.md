# Plan: Role Label Centralization + "Sous-tâche" → "Opération" Rename + Profile Sections

**Branch:** TBD per part (one branch per part, off `jonas`)
**Approved:** 2026-06-30

## Context

Three related asks from this session:

1. **Naming governance**: the manager keeps complaining about role/subtask naming, and Jonas wants to stop renaming actions from rippling across the whole codebase. Investigation confirmed the *identifier* layer (Spatie role strings, table/column names) is already properly decoupled from *display* — the real gap is that display labels are duplicated and drifted across ~14 Vue files, 6 PHP Notification classes, and a polluted locale JSON, instead of having one source of truth per side (PHP/JS). Decision: centralize labels, never touch internal identifiers.
2. **Immediate rename**: rename "Sous-tâche" → "Opération" (French only) without any DB/schema change. Investigation found this isn't a one-line edit today because some subtask UI text is hardcoded in Vue components instead of routed through i18n — that leak has to be closed as part of doing the rename correctly, or the rename will be incomplete.
3. **Profile expansion**: add 4 free-form CV-style sections (School Background, Certificates, Qualifications, Responsibilities) plus multi-file CV upload to the user profile, surfaced consistently in both the self-service profile page and the directeur/superadmin read-only profile view.

These are independent, sequenced as: (A) role labels, (B) subtask rename, (C) profile sections — A and B share no files, C is the largest and independent of the other two.

---

## Part A — Role Label Centralization

**Principle:** internal role identifier strings (`'manager'`, `'cadre'`, `'observateur'`, etc.) are the permanent, never-renamed contract used in all gating/logic (`in_array()`, `hasRole()`, `Role::findByName()`, pivot `role_id` resolution, seeders, tests). They never change. Only the *display text* shown to users gets centralized so a future rename touches one PHP file + one JS file.

### A.1 — New PHP canonical registry

New file `app/Permissions/RoleLabel.php` — a static-method class (mirrors `Permission::forRole()`'s precedent), covering all 10 role names (3 global Spatie roles + 7 contextual pivot-only roles, including `task_responsable` which is currently missing from both existing partial registries):

```php
final class RoleLabel
{
    public static function label(string $role): string; // resolves via __() + lang file
    public static function all(): array;                 // role => translated label, for dropdowns
}
```

Backed by new bilingual lang files `lang/fr/roles.php` and `lang/en/roles.php` (one flat array, role-key => label). This replaces the current hardcoded-French-only `Role::label()` enum method — `app/Enums/Role.php::label()` becomes a one-line delegate to `RoleLabel::label($this->value)` so existing callers (if any — check via `grep -rn "->label()" app` first) keep working.

**Do not** add the 7 contextual role names as new cases to `app/Enums/Role.php` — that enum represents real Spatie global roles assignable to a user account; contextual roles are pivot-only `role_id` values and were never meant to be enum cases.

### A.2 — JS canonical registry

Extend `resources/js/permissions/Permission.js`'s existing `RoleLabels` object to cover all 10 names (add the 3 global roles + `task_responsable`, currently missing). Add a new exported `getRoleLabel(role, locale)` function that is locale-aware (French/English maps), since the rename in Part B and any future English copy changes need both languages without forking the registry.

Keep manual PHP↔JS sync (matches the existing established convention — `Permission.js` is already hand-mirrored from `Permission.php`, not generated). Add a one-line comment in both files pointing at the other, like `Permission.js` already does.

### A.3 — Sweep and fix duplicated/wrong label logic

Found via reconnaissance — concrete fix list:

- **6 PHP Notification classes** each have a private `getRoleLabel()` with a **wrong vocabulary** that doesn't match this app's real role strings (e.g. `'admin' => 'Administrateur'`, `'manager' => 'Gestionnaire'` — neither `'admin'` nor `'Gestionnaire'` are real values in this app). This is a **live bug**, not just duplication — these notifications have been emitting incorrect role names. Replace each with a call to `RoleLabel::label($role)` and delete the broken private method:
  - `app/Notifications/WorkspaceMemberAddedNotification.php`
  - `app/Notifications/WorkspaceInvitationNotification.php`
  - `app/Notifications/ActiviteMemberAdded.php`
  - `app/Notifications/ActiviteMemberPermissionsUpdated.php`
  - `app/Notifications/ProjetInvitationNotification.php`
  - `app/Notifications/ProjetMemberAddedNotification.php`

- **~14 Vue files** with a local hand-rolled `getRoleLabel()` (incomplete/drifted versions) — replace each with an import of the new shared `getRoleLabel` from `@/permissions/Permission`. Highest-leverage fix first: `resources/js/composables/useWorkspace.js` (many components destructure `getRoleLabel` from this composable — fixing it here cascades for free to those callers without touching them). Remaining local copies: `ProjetDetail.vue`, `EditProjetMemberModal.vue`, `Users/Index.vue`, `ActiviteDetail.vue`, `AcceptInvitation.vue`, `AcceptProjetInvitation.vue`, `Teams/Show.vue`, `UserViewModal.vue`, `InviteExternalMemberModal.vue`, `WorkspaceDocumentsBrowser.vue`, `WorkspaceInvitationNotificationItem.vue`, `NotificationDetailModal.vue` (has its own `ROLE_LABELS` const).

- **~10 Vue files hardcode `<option>` label text** directly (e.g. `EditMemberPermissionsModal.vue:76-79` has `<option value="cadre">Cadre</option>`) — fix the **text content only**, never the `value="..."` attribute (that's the internal key and must stay literal). Prefer replacing manual `<option>` lists with a `v-for` over `RoleHierarchy.map(r => ({ value: r, label: getRoleLabel(r) }))` where multiple options exist in one file. Same files list: `AddMemberModal.vue` (×2 locations), `AdminUserManagement.vue`, `UserModal.vue`, `Register.vue`, `InviteExternalMemberModal.vue`, `Users/Index.vue`, `WorkspaceMemberManagement.vue`.

**Important — do not touch:** the existing scattered `role_owner`/`role_collaborator`/`role_viewer`/`role_membre` keys already in `resources/js/locales/fr.json`/`en.json` — these serve other unrelated features (workspace settings dropdown, team page copy) and are a separate, pre-existing pollution issue, out of scope here. New work routes through `Permission.js`'s `RoleLabels`/`getRoleLabel`, not through the locale JSON.

### A.4 — Verification
- `grep -rn "getRoleLabel\b" resources/js` should show exactly one definition (in `Permission.js`) and many imports, zero local re-implementations.
- `grep -rn "'Gestionnaire'\|'Administrateur'" app/Notifications` should return nothing.
- Manual check: trigger one of each of the 6 fixed notifications (or a feature test asserting the notification's rendered text) to confirm the role name in the message is now correct.
- Run `php artisan test --compact` for any test asserting notification content (search `tests/` for `assertSentTo` on the 6 touched notification classes) plus Pint + Larastan per project gates.

---

## Part B — Rename "Sous-tâche" → "Opération" (French only, no schema change)

Confirmed safe: the table (`sous_taches`), columns, model class `SousTache`, route segments (`/sous-taches`), permission strings (`sous_taches.view`, etc.), and event/channel names all stay **exactly as-is** — none of these are user-visible. Only display text changes.

### B.1 — i18n value changes (the "easy" part)

Update every value (not key) containing "Sous-tâche"/"sous-tâche" to "Opération"/"opération" in:
- `lang/fr/sous_taches.php` — 16 values across `errors.*`, `success.*`, `notifications.assigned.*`, `ui.*` (full list already enumerated by reconnaissance: lines 13,14,16,20-24,28-29,35,37-38,40,51-52,56).
- `resources/js/locales/fr.json` — 9 duplicate keys carrying the same text outside the lang-file system: `taches.subtasks.title`, `taches.subtasks.create_first`, `taches.subtasks.empty`, `agent_sheet_sections.directed_subtasks`, `agent_sheet_sections.assignee_subtasks`, `task_detail.tabs.subtasks`, `activity.subject_subtask`, `search.type_sous_taches`, `search_page.type_sous_taches`, `roles_permissions.perm_groups.subtasks`.
- `app/Exports/WorkspaceTachesExport.php:40` — hardcoded Excel column header "Sous-tâches", change to "Opérations".

**Leave `lang/en/sous_taches.php` and `resources/js/locales/en.json` untouched** — user asked for the French display name only; "Subtask" stays in English.

### B.2 — Close the hardcoded-text leak (the part that makes the rename actually work)

Two Vue components don't use `t()`/i18n for their subtask text at all — they hardcode French directly in the template. Renaming only the lang files would silently miss these, leaving "Sous-tâche" visible in the UI despite the lang file saying "Opération." Fix:

- `resources/js/components/taches/SousTacheList.vue` — section title (line 7: "Sous-tâches"), empty state (line 55), CTA (line 61), and related strings are hardcoded. Route them through `$t('sous_taches.ui.section_title')` etc. (the i18n keys already exist in `lang/fr/sous_taches.php` — they're just not being called). After wiring to `t()`, the Part B.1 lang-file edit alone makes the new term appear everywhere.
- `resources/js/components/taches/SousTacheForm.vue` — header (line 5: "Nouvelle sous-tâche"), placeholder (line 16), and other static strings — same fix, route through existing `sous_taches.ui.form.*` keys (add a key for "Nouvelle sous-tâche"/"Nouvelle opération" if one doesn't already exist under `ui.form.*`).

Scope note: while fixing these two files, **only touch the strings that say "Sous-tâche"** or are part of the rename; do not refactor unrelated hardcoded strings in the same files (e.g. "Annuler"/"Créer" button labels) — that's a separate i18n-completeness cleanup, not part of this task.

### B.3 — Verification
- `grep -rn "Sous-tâche\|sous-tâche" lang/fr resources/js/locales/fr.json resources/js/components/taches/SousTacheList.vue resources/js/components/taches/SousTacheForm.vue app/Exports/WorkspaceTachesExport.php` → should return zero matches after the change.
- Visual check: open a task with subtasks in the browser (French locale), confirm the section title, empty state, create button, and the Excel export column header all read "Opération(s)."
- No backend tests should be affected (i18n value-only change); run `vendor/bin/pint --dirty --format agent` since `WorkspaceTachesExport.php` is touched.

---

## Part C — Profile Sections (School Background, Certificates, Qualifications, Responsibilities, CV upload)

### C.1 — Data model: 4 dedicated tables/models (not a shared polymorphic table)

Matches this codebase's convention (e.g. `SousTache` is its own model rather than folding into `Tache`). Models: `SchoolBackground`, `Certificate`, `Qualification`, `Responsibility` — each `belongsTo(User)`, each gets a factory (project convention: every new model needs one).

**Migrations** (4 new, `user_id` FK with cascade delete on each):

- `school_backgrounds`: `etablissement`, `diplome` (nullable), `domaine` (nullable), `date_debut` (nullable), `date_fin` (nullable), `description` (nullable), `ordre` (default 0).
- `certificates`: `titre`, `organisme_emetteur` (nullable), `date_obtention` (nullable), `date_expiration` (nullable), `credential_id` (nullable), `credential_url` (nullable), `description` (nullable), `ordre`.
- `qualifications`: `titre`, `description` (nullable), `date_obtention` (nullable), `ordre`.
- `responsibilities`: `titre`, `organisation` (nullable, free text — not an FK to Workspace/Team), `description` (nullable), `date_debut` (required), `date_fin` (nullable = ongoing), `ordre`.

No soft deletes (simple owner-managed CRUD, no audit requirement stated). `User` model gets 4 new `hasMany()` relations, each ordered sensibly (`ordre` asc for the first three, `date_debut` desc for Responsibilities so the current/most recent shows first).

### C.2 — Backend: Form Requests, Controllers, Routes

One Form Request per section (covers both store and update — these are small forms), in `app/Http/Requests/Profile/`. Ownership enforced in the controller (`abort_unless($model->user_id === $request->user()->id, 403)`), not via a policy (matches this codebase's existing inline-authorization convention — confirmed no `UserPolicy` exists today).

One slim resource controller per section under new `app/Http/Controllers/Api/Profile/` namespace (`SchoolBackgroundController`, `CertificateController`, `QualificationController`, `ResponsibilityController`) — `index`/`store`/`update`/`destroy`, always scoped to `$request->user()`, no `{user}` route parameter needed since these only ever operate on the authenticated user's own records.

Routes — new `Route::prefix('profile')` group in `routes/api.php` using `Route::apiResource(...)->except(['show'])` ×4 (list + inline edit pattern, no single-item view needed).

**Directeur/superadmin read access** reuses the existing `UserController::profileView()` endpoint rather than adding parallel read routes — extend its eager-loading and `UserResource` to include the 4 new relations via `whenLoaded()`. This is a deliberate reuse of the already-correct authorization (self / super_admin / shared-workspace-with-`WORKSPACES_VIEW_MEMBERS`) — no new Permission constant needed anywhere in Part C.

### C.3 — CV upload: reuse `Document` + `DocumentService`, with required surgical changes

Full reuse blocked as-is: `documents.workspace_id` is a hard NOT-NULL FK, and `DocumentService`'s entity/workspace resolution has no `User::class` case — a bare CV upload would throw. Rather than build a second parallel upload pipeline (which would recreate the exact "6 duplicated implementations drift" problem just fixed in Part A), extend the existing single pipeline:

1. Migration: make `documents.workspace_id` nullable (`->nullable()->change()` — confirm `doctrine/dbal` is available for column changes in Laravel 10, check `composer.json`).
2. `DocumentService::getEntity()` and `getWorkspaceFromEntity()` gain a `User::class` match arm (workspace resolves to `null`, explicitly allowed rather than treated as "not found").
3. `DocumentService::upload()`'s "workspace required" guard becomes conditional, skipped when `entityType === User::class`.
4. `DocumentAccessResolver` (`canUpload`/`canView`/`canDownload`/`canEdit`/`canDelete`) gains a `User::class` branch: owner can do everything; directeur/superadmin can view only (same shared-workspace + `WORKSPACES_VIEW_MEMBERS` check as `profileView()`); no one but the owner edits/deletes another person's CV.
5. No new column needed for "this is a CV" — `documentable_type = User::class` is already that signal; `Document.description` (existing field) covers free-text labeling like "CV 2026 (English)."

**This touches shared code used by every existing document feature in the app — flag explicitly for a full impact check (per CLAUDE.md's code-deletion/shared-code rule) before implementing**, even though the touched surface per file is small (one new `match` arm each).

Multi-file support is free — multiple `Document` rows with the same `documentable_type=User, documentable_id` already means "multiple CVs," no schema change beyond the nullable workspace_id.

New thin controller `ProfileCvController` (`index`/`store`/`destroy`) under the same `profile` route prefix, delegating to the now-extended `DocumentService`.

### C.4 — Frontend

New components in `resources/js/components/profile/`: one `*List.vue` + one `*FormModal.vue` per section (4 pairs), plus `ProfileCvSection.vue` wrapping the existing `DocumentUpload.vue`/`DocumentCard.vue` components (verify their props are generic over entity type before wiring; add a prop if they're currently hardcoded to Projet/Activite/Tache).

Each `*List.vue` takes a `readonly` boolean prop (default `false`) — this lets the **same component** serve both `UserProfile.vue` (self, editable) and `UserProfileModal.vue` (directeur/superadmin, `:readonly="!canEdit"` using the `can_edit` flag `profileView()` already returns), avoiding an 8-component duplicate sprawl. Each list follows the established stagger pattern (`useStagger`, `ref="staggerRef"`, `class="stagger-item"`, `applyStagger()` after fetch) — mandatory per project rule.

**Mounting**: new tab in `UserProfile.vue` (alongside existing Profile/Security/Notifications/Activity tabs) housing all 5 new components stacked — cleaner than crowding the existing Profile tab given 5 distinct sections. Same 5 components mounted in `UserProfileModal.vue`, read-only, after the existing field display.

**i18n**: new `lang/fr/profile.php` / `lang/en/profile.php` (backend validation messages if any go beyond Form Request defaults) and a new `profile_sections` top-level key block in `resources/js/locales/fr.json`/`en.json` (confirmed collision-free against the existing scattered `role_*` keys).

### C.5 — Tests

Per section (×4): create/update/delete own record, 403 on someone else's record, validation errors (422), directeur read-only access via shared workspace + `WORKSPACES_VIEW_MEMBERS`, directeur denied without shared workspace, superadmin can view any user's records. Style precedent: `tests/Feature/Task12/DocumentManagementTest.php` (uses `RolePermissionSeeder`, `AttachesWithRoleId`, Sanctum-acting-as).

CV-specific: upload, multi-upload, delete own, 403 on someone else's, directeur can view-not-delete, file-type/size validation rejection.

### C.6 — Deliverables checklist (per CLAUDE.md Guide 24, all required before done)
Backend + frontend + responsive UI + i18n (fr + en) + stagger + tests + `npm run build` green + Larastan clean + manual testing guide (this folder's `testing/`) + `docs/PROGRESSION.md`/`docs/SESSION_STATE.md` updates.

---

## Suggested execution order

1. **Part A** (role labels) — self-contained, fixes a live bug (wrong notification text) along the way, low risk.
2. **Part B** (subtask rename) — small, fast, depends on nothing from A or C.
3. **Part C** (profile sections) — largest piece; do the 4 CRUD sections first (lower risk, no shared-code changes), then the CV upload (touches `DocumentService`/`DocumentAccessResolver`, needs its own impact check before starting).

Each part gets its own branch off `jonas` per the project's branch-per-feature convention, its own test run, and its own commit — do not bundle all three into one branch given they touch unrelated areas and have different risk profiles (B is trivial, C-CV-upload is the highest-risk piece due to shared `Document` code).
