# Phase 6 — Global Search (Typesense + Scout + Enhanced Search Page)

> Branch: `feature/phase6-search`
> Status: 🔄 In progress — command palette complete (uncommitted), search page pending

---

## Context

Phase 6 implements global search using Laravel Scout + Typesense. It is split into two parts:

- **Part A (command palette):** Already implemented — `SearchBar.vue` (Cmd+K), `SearchController`, 5 Searchable models, `search.global` permission.
- **Part B (search page, this plan):** Full `/search` page with super-admin global scope, Typesense highlights, context-aware read-only previews, selective + global export, file content extraction, and TeamMessage as a 6th searchable model.

---

## Part A — Command Palette (complete)

### What was built
- **`laravel/scout` + `typesense/typesense-php`** installed; `config/scout.php` with 5 Typesense schemas; `SCOUT_DRIVER=collection` in `phpunit.xml`
- **`search.global` permission** — full Guide 4+15 flow: `Permission.php`, `forRole(owner/manager)`, `Permission.js`, `useWorkspacePermissions.js` (`canSearchGlobal`), `WorkspaceController` `can_search_global` (3 locations), `PERMISSIONS_MATRIX.md` updated
- **5 Searchable models:** `Projet`, `Activite`, `Tache`, `Document`, `User` — `toSearchableArray()` with workspace-scoped fields; `searchableAs()` returns collection name
- **`SearchController::search()`** — `GET /api/search`, validates q/types/workspace_id/per_type, double-checks workspace membership, searches 5 types with `->query()` scope (works with both drivers), returns grouped results
- **`UserService::searchUsers()`** — fixed: `User::search()` now resolves to Scout; renamed to `User::query()->search()` (Eloquent scope)
- **`SearchBar.vue`** — functional command palette: Cmd+K/Ctrl+K global shortcut, debounced 400ms, grouped results with emoji type icons, keyboard nav (↑↓/Enter/Esc), Teleport dropdown, hidden/disabled for non-managers
- **8 PHPUnit tests** in `tests/Feature/Search/SearchTest.php` — all green

---

## Part B — Enhanced Search Page (planned, not yet implemented)

### Architecture decisions

#### Typesense highlights via `->raw()`
`->get()` returns Eloquent models with no highlight information. `->raw()` returns the full Typesense response including `highlight` blocks with `<mark>` tags around matched terms. This is abstracted so the collection driver (tests) still works.

#### Super-admin global scope
Super-admin bypasses the workspace filter and sees results from ALL workspaces. Every result includes `workspace_name` so the admin can identify the source. This requires adding `workspace_name` to all `toSearchableArray()` methods and re-indexing (Typesense does not support in-place schema updates).

#### Read-only everywhere
**All search result views are strictly read-only.** The search page is an observation tool. No edit/delete/validate/submit actions are available from any modal or action opened from it. "Open in new tab" sends the user to the resource's own page where their normal permissions apply.

#### File content extraction
A `documents.content_text` column (nullable TEXT) stores up to 50,000 chars of extracted text. Extraction runs asynchronously via `ExtractDocumentTextJob`. Only the first 5,000 chars go into the Typesense index; results show only a highlighted snippet (30–80 chars), never the full content.

Supported file types: PDF (`smalot/pdfparser`), .docx (`phpoffice/phpword`), .xlsx (`phpoffice/phpspreadsheet`), plain text — `Storage::get()` directly. OCR for images deferred (requires Tesseract server binary).

#### TeamMessage as 6th searchable model
Requires teams to have a direct `workspace_id` (migration). TeamMessage indexes content (capped 1000 chars), user_nom, team_name, workspace context. Both super-admin and workspace owner/manager see messages from their workspace.

---

### Expanded `toSearchableArray()` — security-first field selection

**Rule:** Index rich text + human-readable context names. Never index credentials, file paths, or security flags.

```
Projet:      nom, description, code | statut, workspace_id, workspace_name | responsable_nom, dates
Activite:    nom, description | workspace_id, workspace_name, projet_id | projet_nom, responsable_nom, dates
Tache:       titre, description, objectif, indicateurs_resultats, commentaire
             | statut, priorite, workspace_id, workspace_name, activite_id
             | code, echeance, activite_nom, projet_nom, assignees_noms
             EXCLUDE: taux_realisation, verrou_reevaluation, validation_* cols
Document:    nom, description | type, mime_type, workspace_id, workspace_name
             | uploader_nom, projet_nom, content_text (5000 chars)
             EXCLUDE: chemin, nom_stockage, hash_sha256
User:        nom, prenom, email, fonction, nom_complet
             EXCLUDE: password, tokens, two_factor_*, is_super_admin, avatar path
TeamMessage: content (1000 chars) | workspace_id, workspace_name, team_id
             | user_nom, team_name, team_uuid, reply_to_snippet (120 chars)
             EXCLUDE: attachments JSON paths, mentions IDs
```

---

### Search page layout

```
/search
  ├── Input + filters (types, workspace dropdown for super-admin, date range)
  ├── TypeTabs: Tout / Projets / Activités / Tâches / Documents / Membres / Messages
  │   └── badge per tab = count from API
  ├── ResultsList (paginated, stagger 40ms)
  │   └── ResultRow
  │       ├── checkbox (selective export)
  │       ├── TypeBadge + WorkspaceBadge (super-admin)
  │       ├── Title (<mark> highlights via v-html + DOMPurify)
  │       ├── Excerpt (Typesense snippet)
  │       ├── Metadata chips
  │       └── [Aperçu 👁] [Ouvrir ↗]  ← READ-ONLY ONLY
  └── ExportBar
      ├── [Exporter la sélection] → POST /api/search/export
      └── [Exporter tous les résultats] → GET /api/search/export (cap dialog: 500/1000/2000/Tout)
```

### Result preview modals (all read-only)
| Type | Preview | Open |
|---|---|---|
| Projet | Inline modal: nom, description, statut, dates, responsable | `/projets/{id}` new tab |
| Activite | Inline modal: nom, description, projet, dates | `/activites/{id}` new tab |
| Tache | `TacheDetailModal` with `readonly` prop (new) | `/taches/{id}` new tab |
| Document | Preview modal: PDF iframe / image | Download (read action) |
| User | Inline modal: nom, email, fonction, role | `/users/{id}` new tab |
| TeamMessage | Inline modal: content, author, team, timestamp | `/teams/{uuid}?message={uuid}` new tab |

### Export
- **Immediate** (under cap): `Excel::download()` direct response
- **"Tout"** (no cap): `SearchExportJob` dispatched, emails download link when done
- **Format:** `SearchExport` class — one sheet per type, column set varies by type
- **Selective:** `POST /api/search/export` with `{ids, type, format, cap}`
- **Global:** `GET /api/search/export` with same query params as search

---

### Files to create / modify

| Action | File |
|---|---|
| New | `database/migrations/…add_workspace_id_to_teams_table.php` |
| New | `database/migrations/…add_content_text_to_documents_table.php` |
| Modify | `app/Models/Team.php` (workspace_id + workspace() BelongsTo) |
| Modify | `app/Models/Projet/Activite/Tache/Document/User.php` (expanded toSearchableArray) |
| New | `app/Models/TeamMessage.php` (add Searchable) |
| New | `app/Services/DocumentTextExtractorService.php` |
| New | `app/Jobs/ExtractDocumentTextJob.php` |
| New | `app/Console/Commands/ExtractDocumentText.php` |
| Modify | `app/Http/Controllers/Api/DocumentController.php` (dispatch job) |
| Modify | `config/scout.php` (all 6 schemas, full field lists) |
| Modify | `app/Http/Controllers/Api/SearchController.php` (super-admin, highlights, pagination) |
| New | `app/Exports/SearchExport.php` |
| New | `app/Jobs/SearchExportJob.php` |
| Modify | `routes/api.php` (export routes) |
| New | `resources/js/pages/Search.vue` |
| Modify | `resources/js/components/taches/TacheDetailModal.vue` (readonly prop) |
| Modify | `resources/js/components/layout/header/SearchBar.vue` ("Voir tous" CTA) |
| Modify | `resources/js/router/index.ts` (/search route) |
| Modify | `resources/js/pages/Teams/Show.vue` (?message= highlight) |
| Modify | `resources/js/locales/fr.json` + `en.json` (search_page section) |

---

## Implementation Progress

### Part A (command palette)
| Item | Status |
|---|---|
| Scout + Typesense installed, config/scout.php | ✅ |
| search.global permission (Guide 4+15 full flow) | ✅ |
| 5 Searchable models (basic fields) | ✅ |
| SearchController GET /api/search | ✅ |
| SearchBar.vue command palette | ✅ |
| 8 PHPUnit tests | ✅ |
| Pint + Larastan + build | ✅ |

### Part B (search page)
| # | Item | Status |
|---|---|---|
| 1 | Migration: workspace_id on teams + Team model | ⬜ |
| 2 | Migration: content_text on documents | ⬜ |
| 3 | DocumentTextExtractorService | ⬜ |
| 4 | ExtractDocumentTextJob + dispatch from DocumentController | ⬜ |
| 5 | documents:extract-text artisan command | ⬜ |
| 6 | Expanded toSearchableArray() × 5 existing models + config/scout.php | ⬜ |
| 7 | TeamMessage Searchable (6th model) | ⬜ |
| 8 | SearchController extended (super-admin, highlights, pagination) | ⬜ |
| 9 | SearchController::export() + SearchExport + SearchExportJob | ⬜ |
| 10 | Export routes | ⬜ |
| 11 | pages/Search.vue | ⬜ |
| 12 | TacheDetailModal.vue readonly prop | ⬜ |
| 13 | SearchBar.vue "Voir tous" CTA | ⬜ |
| 14 | /search router route | ⬜ |
| 15 | Teams/Show.vue ?message= handler | ⬜ |
| 16 | i18n keys (search_page section) | ⬜ |
| 17 | PHPUnit: super-admin, isolation, export, extraction | ⬜ |
| 18 | Pint + Larastan + build | ⬜ |
| 19 | Re-index all 6 collections | ⬜ |
| 20 | docs/extended-features/testing/TASK_PHASE6_ENHANCED_TESTING.md | ⬜ |
| 21 | Update PROGRESSION.md + SESSION_STATE.md | ⬜ |
