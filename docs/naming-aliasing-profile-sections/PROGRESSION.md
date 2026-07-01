# Progression — Role Label Centralization + Subtask Rename + Profile Sections

**Branch:** TBD per part (one branch per part, off `jonas`)
**Started:** 2026-06-30
**Last updated:** 2026-07-01

---

## Status Legend

| Symbol | Meaning |
|---|---|
| ⬜ | Not started |
| 🔄 | In progress |
| ✅ | Complete |
| ⚠️ | Blocked |

---

## Part A — Role Label Centralization

| Step | Task | Status | Notes |
|---|---|---|---|
| A.1 | PHP canonical registry — `app/Permissions/RoleLabel.php`, `lang/fr/roles.php`, `lang/en/roles.php`, `Role::label()` delegation | ✅ | |
| A.2 | JS canonical registry — extend `RoleLabels` in `Permission.js`, add locale-aware `getRoleLabel()`, add `task_responsable` to `RoleHierarchy` | ✅ | |
| A.3a | Fix 6 PHP Notification classes with wrong `getRoleLabel()` vocabulary | ✅ | Live bug fix — wrong vocab ('Gestionnaire', 'admin') removed |
| A.3b | Replace ~18 Vue local `getRoleLabel()` implementations, starting with `useWorkspace.js` | ✅ | |
| A.3c | Fix ~5 Vue files with hardcoded `<option>` label text | ✅ | Only text content, `value="..."` untouched |
| A.4 | Verification — grep sweep zero matches, Pint + Larastan green | ✅ | Full test suite skipped per user |
| — | **Testing guide written** → `docs/naming-aliasing-profile-sections/testing/PART_A_ROLE_LABELS_TESTING.md` | ✅ | |

---

## Part B — Rename "Sous-tâche" → "Opération"

| Step | Task | Status | Notes |
|---|---|---|---|
| B.1 | i18n value changes — `lang/fr/sous_taches.php`, `lang/fr/evaluation.php`, `lang/fr/circuit_validation.php`, `resources/js/locales/fr.json` (12 keys), `WorkspaceTachesExport.php` Excel header | ✅ | French only, English untouched |
| B.2 | Close hardcoded-text leak — `SousTacheList.vue`, `SousTacheForm.vue`, `TacheDetailModal.vue`, `TacheCardResponsable.vue`, `ValidationTaskCard.vue` | ✅ | Section titles, tab labels, tooltips all updated |
| B.3 | Verification — grep sweep zero matches, Pint + Larastan green | ✅ | |
| — | **Testing guide written** → `docs/naming-aliasing-profile-sections/testing/PART_B_SUBTASK_RENAME_TESTING.md` | ✅ | |

---

## Part C — Profile Sections

| Step | Task | Status | Notes |
|---|---|---|---|
| C.1 | Data model — 4 migrations + models (`SchoolBackground`, `Certificate`, `Qualification`, `Responsibility`) + factories + `User` relations | ✅ | 4 tables migrated, factories created |
| C.2 | Backend — Form Requests, controllers (`app/Http/Controllers/Api/`), routes, extend `profileView()` + `UserResource` | ✅ | 4 apiResource routes under `/api/users/profile/`; `profileView()` eager-loads all 4 |
| C.3 | CV upload — impact check, nullable `documents.workspace_id` migration, `DocumentService`/`DocumentAccessResolver` `User::class` support, `ProfileCvController` | ⬜ | Highest-risk piece — shared `Document` code — not yet started |
| C.4 | Frontend — section components ×4 (`readonly`+`initialData` prop pattern), mount in `UserProfile.vue` (new "Profil professionnel" tab) + `UserProfileModal.vue` (read-only), i18n fr+en, stagger | ✅ | All 4 components done; `UserProfile.vue` + `UserProfileModal.vue` updated |
| C.5 | Tests — per-section CRUD/ownership/access tests, CV upload tests | ✅ | 19 tests, 45 assertions, all passing (`ProfileSectionsTest.php`); CV upload tests pending C.3 |
| C.6 | Full integration checklist — build green, Larastan clean, manual testing guide, PROGRESSION/SESSION_STATE updates | ✅ | Pint clean, Larastan `[OK] No errors`; testing guide written; this file updated |
| — | **Testing guide written** → `docs/naming-aliasing-profile-sections/testing/PART_C_PROFILE_SECTIONS_TESTING.md` | ✅ | |

---

## Open Decisions

- None currently — plan approved 2026-06-30.

## Blocking Issues

- None.
