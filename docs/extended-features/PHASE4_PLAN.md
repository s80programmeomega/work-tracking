# Phase 4 — Complete Admin Logs Implementation Plan

> Branch: `feature/support-contact`
> Approved: 2026-06-04
> Plan file: `/home/jonas/.claude/plans/ready-tender-noodle.md`

---

## Context

Phase 4 is partially done: `AdminLogs.vue` has Tab 1 (Activity Log table) and Tab 2 (log-viewer iframe). Three pieces are missing from the spec, and the user has also requested a full native replacement of the iframe:

1. **Detail drawer** — slide-in panel showing full `properties` diff on activity log rows
2. **User/causer filter** — searchable autocomplete for Tab 1 (backend already supports `causer_id`)
3. **Validation Audit Log tab** — Tab 3 surfacing `validation_audit_logs` (N0/N1/bypass decisions)
4. **Native App Logs tab** — replace the iframe with Vue components calling log-viewer's own REST API

---

## Architecture Decision

Keep `opcodesio/log-viewer` installed — its PHP log parser handles file reading, level detection, stack trace parsing, pagination (battle-tested). Replace its UI (the iframe) with a native Vue frontend calling `/log-viewer/api/*` directly. All log-viewer calls are wrapped in `useLogViewer.js` so the package can be swapped later by changing one file.

---

## Component Architecture

```
AdminLogs.vue                            ← slim 3-tab container (refactor)
resources/js/components/admin/logs/
  ActivityLogTab.vue                     ← Tab 1: extract + causer filter + drawer
  AppLogsTab.vue                         ← Tab 2: native (replaces iframe)
  ValidationAuditLogTab.vue             ← Tab 3: new
  LogDetailDrawer.vue                    ← reusable slide-in drawer
resources/js/composables/
  useLogViewer.js                        ← /log-viewer/api/* abstraction layer
```

---

## Backend

### New: `ValidationAuditLogResource`
File: `app/Http/Resources/ValidationAuditLogResource.php`
Fields: `id`, `action`, `context`, `created_at`, `actor {id, nom, email}`, `resultat {id, tache_id, tache_titre}`

### New: `AdminController::validationAuditLog()`
File: `app/Http/Controllers/Api/AdminController.php`
```
GET /api/admin/validation-audit-log
Filters: actor_id?, action?, date_from?, date_to?, per_page? (default 25)
Eager loads: actor, resultat.tache
Protected by: super_admin middleware (already on the prefix group)
```

### Route
File: `routes/api.php` — inside existing `super_admin` group:
```php
Route::get('validation-audit-log', [AdminController::class, 'validationAuditLog']);
```

---

## Frontend

### `useLogViewer.js`
Dedicated axios instance (`baseURL: window.location.origin`, Bearer token from localStorage).
Exports: `fetchFiles()`, `fetchLogs(params)`, `downloadFile(id)`, `deleteFile(id)`, `clearCache(id)`.

### `LogDetailDrawer.vue`
Teleport-based slide-in drawer. Props: `entry`, `type` ('activity' | 'audit').
- Activity: diff table (`properties.old` → `properties.attributes`)
- Audit: key-value table from `context` JSON
- Close on backdrop click or Escape

### `ActivityLogTab.vue` (Tab 1 — extended)
Extracted from AdminLogs.vue + added:
- User/causer autocomplete (calls `/api/admin/users?search=`, debounced 300 ms) → sets `filters.causer_id`
- Row click → opens `<LogDetailDrawer type="activity">`

### `AppLogsTab.vue` (Tab 2 — native replacement)
Calls `/log-viewer/api/*`. Features:
- File picker (name, size, date range, download/delete)
- Level filter chips from `levelCounts` (error=red, warning=amber, info=blue, debug=gray, etc.)
- Search (debounced 400 ms)
- Stagger table (30 ms) — level badge, datetime, message
- Row expand → full stack trace (`full_text`)
- Mail badge when `context` contains `to`/`subject` keys
- Download via signed URL; Delete with ConfirmModal

### `ValidationAuditLogTab.vue` (Tab 3 — new)
Calls `/api/admin/validation-audit-log`. Features:
- Filter: actor autocomplete, action select, date range
- Table: Date | Acteur | Action badge | Tâche | Résultat ID
- Action colours: approve/validate=green, return/reject=red, bypass=amber, timeout=gray
- Row click → `<LogDetailDrawer type="audit">`
- Stagger 30 ms

### `AdminLogs.vue` (refactor)
Slim container (~60 lines) importing the 3 tab components. Tabs: `[activity, app_logs, audit]`.

---

## i18n Keys Added (fr.json + en.json, under `admin_logs`)

```
filter_causer, drawer_title, prop_old, prop_new, no_changes
tab_audit, col_actor, col_action, col_task
audit_action_{approuve,renvoye,timeout,bypass,n1_valide,n1_rejete,n2_valide,n2_rejete}
log_level_{error,warning,info,debug,critical,alert,emergency,notice}
file_picker_label, search_logs, download, delete_file, confirm_delete_file
no_files, no_logs, scanned, mail_preview
```

---

## Tests

**PHPUnit** — `tests/Feature/Admin/AdminValidationAuditLogTest.php`
- Super-admin 200 + pagination structure
- Filter by action, actor_id, date range
- Non-super-admin 403; unauthenticated 401

**Dusk** — `tests/Browser/Admin/AdminLogsTest.php`
- Activity tab renders table; row click opens drawer
- App Logs tab shows file list
- Audit tab renders (empty state OK)

---

## Implementation Progress

| # | Item | Status |
|---|---|---|
| 1 | `ValidationAuditLogResource` | ✅ |
| 2 | `AdminController::validationAuditLog()` + route | ✅ |
| 3 | `useLogViewer.js` composable | ✅ |
| 4 | `LogDetailDrawer.vue` component | ✅ |
| 5 | `ActivityLogTab.vue` (extract + causer filter + drawer) | ✅ |
| 6 | `AppLogsTab.vue` (native log viewer) | ✅ |
| 7 | `ValidationAuditLogTab.vue` | ✅ |
| 8 | `AdminLogs.vue` refactor | ✅ |
| 9 | i18n keys (fr.json + en.json) | ✅ |
| 10 | PHPUnit: `AdminValidationAuditLogTest` | ✅ 6 tests green |
| 11 | Dusk: `AdminLogsTest` | ✅ 4 tests written |
| 12 | Pint + Larastan + `npm run build` | ✅ all clean |
| 13 | `docs/testing/TASK_PHASE4_TESTING.md` | ✅ |
| 14 | Update `PROGRESSION.md` + `SESSION_STATE.md` | ✅ |
