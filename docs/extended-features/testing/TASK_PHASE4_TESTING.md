# Phase 4 Testing Guide — Admin Logs (Native Log Viewer)

> Branch: `feature/support-contact`
> Feature: Unified admin logs page — Activity Log, App Logs (native), Validation Audit Log

---

## Prerequisites

1. App server running: `php artisan serve`
2. Frontend built: `npm run dev` (or `npm run build`)
3. Logged in as a user with `is_super_admin = true`
4. At least one Spatie activity log entry exists (perform any create/update on any model)
5. `storage/logs/laravel.log` exists (Laravel writes to it automatically)
6. At least one `ValidationAuditLog` row exists — submit a result and have a N0/N1 actor approve or return it

---

## Tab 1 — Activity Log

### TC-01 Page loads
- Visit `/admin/logs`
- **Expected:** "Journal d'activité" tab is active; table renders with activity rows

### TC-02 Text search filters results
- Type a known username or description in the search box
- **Expected:** table refreshes (debounced), only matching rows shown; "Aucune entrée" if no match

### TC-03 User/causer autocomplete filter
- Click the "Filtrer par auteur" input and type at least 2 characters of a user's name
- **Expected:** dropdown suggestions appear after ~300 ms
- Select a suggestion → table filters to that user's activity only
- **Expected:** all rows show the selected user as "Auteur"

### TC-04 Event type filter
- Select "updated" from the event dropdown
- **Expected:** only `updated` event rows remain

### TC-05 Date range filter
- Set `date_from` and `date_to` to a past week where activity exists
- **Expected:** only entries in that range appear; entries outside are hidden

### TC-06 Reset button
- Apply any filter, then click "Réinitialiser"
- **Expected:** all filters clear; full list reloads

### TC-07 Row click opens detail drawer
- Click any row in the table
- **Expected:** drawer slides in from the right; shows actor name/email, event badge, human-readable description, and a diff table if properties changed

### TC-08 Drawer close
- Click the × button or the backdrop outside the drawer
- **Expected:** drawer closes, table is still visible

### TC-09 Pagination
- If total > 25: click "Suivant" → second page loads; click "Précédent" → returns to page 1

---

## Tab 2 — Logs applicatifs (native)

### TC-10 Tab switch renders file picker
- Click the "Logs applicatifs" tab
- **Expected:** file picker dropdown appears (at minimum `laravel.log` should be listed with size and dates)

### TC-11 Log entries load on file selection
- Select `laravel.log` from the dropdown (auto-selected on load)
- **Expected:** level filter chips appear (ERROR, WARNING, INFO, DEBUG, etc.) with counts; log entries table renders with stagger animation

### TC-12 Level filter chips
- Click "Erreur" chip → it becomes grayed/struck-through
- **Expected:** error entries disappear from table; chip shows excluded state
- Click it again → errors reappear

### TC-13 Text search in logs
- Type a keyword from a known log message in the search box
- **Expected:** table filters to matching entries after ~400 ms debounce

### TC-14 Row expand (stack trace)
- Click any log row with a stack trace (ERROR or CRITICAL level)
- **Expected:** row expands to show `full_text` in a dark monospace pre block; click again to collapse

### TC-15 Mail badge
- If any log entry contains mail context (e.g., sent a queued email): it should show a small "Email" chip next to the level badge

### TC-16 Download file
- Click the "Télécharger" button next to the file picker
- **Expected:** browser downloads `laravel.log`; no error toast

### TC-17 Delete file (use a test log file, not production)
- Click "Supprimer le fichier" → confirmation modal appears
- Click "Supprimer le fichier" in modal → file is deleted; file picker refreshes; deleted file no longer appears
- **Expected:** confirmation modal closes; no crash

### TC-18 Pagination of log entries
- If total entries > 25: pagination controls appear; "Suivant" loads next page

---

## Tab 3 — Journal de validation

### TC-19 Tab switch renders table
- Click the "Journal de validation" tab
- **Expected:** table with columns Date | Acteur | Action | Tâche | Résultat renders; or "Aucune entrée trouvée" if no audit logs exist

### TC-20 Action badge colours
- Approve actions (`approuve`, `n1_valide`, `n2_valide`) → green badge
- Return/reject actions (`renvoye`, `n1_rejete`, `n2_rejete`) → red badge
- Bypass action (`bypass`) → amber badge
- Timeout (`timeout`) → gray badge

### TC-21 Actor filter autocomplete
- Type a user name in the actor filter
- **Expected:** suggestions appear; select one → table filters to that actor

### TC-22 Action filter dropdown
- Select "Bypass activé" from the action dropdown
- **Expected:** only bypass entries shown

### TC-23 Date range filter
- Set date range covering known audit entries
- **Expected:** only entries within range shown

### TC-24 Row click opens detail drawer
- Click any row
- **Expected:** drawer opens showing: actor info, action badge, linked task title, and context key-value table (e.g., taux_realisation, commentaire, motif)

---

## Negative Cases

| Scenario | Expected |
|---|---|
| Regular user visits `/admin/logs` | Redirected to `/404` by router guard |
| Log-viewer API returns 401 | App Logs tab shows empty state; no crash |
| No `ValidationAuditLog` rows exist | Tab 3 shows "Aucune entrée trouvée" |
| No log files in storage | App Logs tab shows "Sélectionner un fichier log …" |

---

## Cleanup

- Restore any deleted test log file if needed: `php artisan logs:rotate-browser` or manually recreate
- No database cleanup needed (RefreshDatabase handles test isolation)
