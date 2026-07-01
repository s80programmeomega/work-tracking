# Part B — Subtask Rename ("Sous-tâche" → "Opération"): Manual Testing Guide

**Branch:** `feature/role-label-centralization`
**Date:** 2026-07-01

---

## What changed

- **`lang/fr/sous_taches.php`** — all display values updated: "Sous-tâche(s)" → "Opération(s)"
- **`lang/fr/evaluation.php`** — 3 values updated (`directed_subtasks`, `assignee_subtasks`, `subtask_coefficient_note`, `sous_taches`)
- **`lang/fr/circuit_validation.php`** — 1 value updated (`mandatory_subtasks_not_done`)
- **`resources/js/locales/fr.json`** — 12 display strings updated across `taches.subtasks.*`, `agent_sheet_sections.*`, `task_detail.tabs.subtasks`, `activity.subject_subtask`, `search.*`, `search_page.*`, `roles_permissions.*`; added `taches.subtasks.new_operation` and `taches.subtasks.title_placeholder` keys
- **`resources/js/locales/en.json`** — added `taches.subtasks.new_operation` and `taches.subtasks.title_placeholder` (English values untouched: "Subtask")
- **`app/Exports/WorkspaceTachesExport.php`** — Excel column header "Sous-tâches" → "Opérations"
- **`SousTacheList.vue`** — 3 hardcoded strings now route through `$t()`: section title, empty state, create-first CTA; confirm-delete message updated
- **`SousTacheForm.vue`** — header and input placeholder now route through `$t()`
- **`TacheDetailModal.vue`** — section title and tab label updated to "Opérations"
- **`TacheCardResponsable.vue`, `ValidationTaskCard.vue`** — `:title` tooltip updated

English locale untouched — "Subtask" stays in English everywhere.

---

## Verification grep (must return zero results)

```bash
grep -rn "Sous-tâche\|sous-tâche" \
  lang/fr/ \
  resources/js/locales/fr.json \
  resources/js/components/taches/ \
  app/Exports/WorkspaceTachesExport.php
```

Should return **no output**.

---

## UI checks (browser, French locale)

### 1. Task detail — Opérations section

1. Open any task that has subtasks (or create one).
2. In the task detail view, the collapsible section header should read **"Opérations"**, not "Sous-tâches".
3. The tab (if using tab navigation) should also read **"Opérations"**.
4. Empty state: if no operations exist, text should read **"Aucune opération pour l'instant."**
5. The create CTA link should read **"Créer la première opération"**.
6. The "Add" button (+) opens the creation form with header **"Nouvelle opération"**.
7. The title input placeholder should read **"Titre de l'opération…"**

### 2. Task cards

1. On a kanban board or task list, hover the subtask-count badge on a card.
2. Tooltip should read **"N opération(s)"**, not "N sous-tâche(s)".

### 3. Role/permissions panel

1. Go to **Admin → Rôles & Permissions** (or workspace settings permissions).
2. The permission group header should read **"Opérations"** (was "Sous-tâches").
3. Individual permissions: "Voir les opérations", "Modifier les opérations", "Supprimer les opérations", "Assigner des opérations".

### 4. Search results

1. Open the global search.
2. In the results type filter or results list, the category should appear as **"Opérations"**, not "Sous-tâches".

### 5. Agent evaluation sheet

1. Open any evaluation sheet / agent report.
2. Section headers for "directed subtasks" and "assigned subtasks" should read **"Opérations dirigées"** and **"Opérations assignées"**.

### 6. Activity log

1. Open the activity/audit log.
2. An event logged against a subtask should display **"Opération"** as the subject type, not "Sous-tâche".

### 7. Excel export

1. Go to **Workspace → Export → Tâches** (or wherever the WorkspaceTachesExport is triggered).
2. Download the Excel file.
3. Open it — the column header that previously said **"Sous-tâches"** should now say **"Opérations"**.

### 8. Circuit validation error

1. Try to submit a task result when a mandatory subtask (blocking operation) is not yet completed.
2. The error message should read: **"Certaines opérations obligatoires ne sont pas encore terminées. Veuillez les compléter avant de soumettre votre résultat."**

---

## Smoke test — rename atomicity

To confirm that changing the lang file value instantly renames everywhere:

1. Temporarily change `lang/fr/sous_taches.php`:
   ```php
   'section_title' => 'Activité atomique',
   ```
2. Reload the task detail page.
3. The section header should say **"Activité atomique"** — with zero other code changes.
4. Revert the change.

---

## What did NOT change (regression check)

- `sous_taches` table name, columns, model class — unchanged
- Route paths (e.g. `/api/taches/{id}/sous-taches`) — unchanged
- Permission strings (e.g. `sous_taches.view`) — unchanged
- Event/channel names — unchanged
- English locale — all "Subtask" strings intact
- `value="..."` attributes in dropdowns — unchanged (internal keys)
