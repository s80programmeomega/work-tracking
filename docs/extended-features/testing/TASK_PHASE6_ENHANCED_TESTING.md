# Phase 6 Enhanced Testing Guide — Global Search Page (Part B)

> Branch: `feature/phase6-search`
> Prerequisites: Typesense running + `php artisan serve` + `npm run dev`

---

## Starting Typesense and indexing

```bash
# Start Typesense (Podman — Docker-compatible):
podman run -d --name typesense -p 8108:8108 \
  -v ./typesense-data:/data \
  typesense/typesense:27 \
  --data-dir /data \
  --api-key=xyz \
  --enable-cors

# .env:
SCOUT_DRIVER=typesense
TYPESENSE_API_KEY=xyz

# Index all 6 models (drop + recreate schemas first):
php artisan scout:flush "App\Models\Projet"
php artisan scout:flush "App\Models\Activite"
php artisan scout:flush "App\Models\Tache"
php artisan scout:flush "App\Models\Document"
php artisan scout:flush "App\Models\User"
php artisan scout:flush "App\Models\TeamMessage"
php artisan scout:import "App\Models\Projet"
php artisan scout:import "App\Models\Activite"
php artisan scout:import "App\Models\Tache"
php artisan scout:import "App\Models\Document"
php artisan scout:import "App\Models\User"
php artisan scout:import "App\Models\TeamMessage"

# Backfill document text content (PDF/docx extraction):
php artisan documents:extract-text --sync  # sync mode for testing
```

---

## Prerequisites

1. At least 2 users: one **manager** (or owner), one super-admin
2. At least one workspace with: 2 projets, 2 activités, 2 tâches, 1 document (PDF), 1 team with messages
3. Queue worker: `php artisan queue:work`
4. Team must have `workspace_id` set (migration ran, backfill applied)

---

## TC-01 — Search page loads

- Visit `/search` as a manager
- **Expected:** "Recherche globale" page renders with search input, type filter chips, no results yet

---

## TC-02 — Basic search returns grouped results

- Search for a known project name
- **Expected:** Results appear under "Projets" tab; excerpt shows matching text; workspace badge absent (single workspace)
- Click the "Projets" tab → shows project results
- **Expected:** Type badge "PROJET" on each row; metadata chips show statut, workspace

---

## TC-03 — Typesense highlights

- Search for a word that appears inside a task description
- **Expected:** The matched word appears **highlighted** (yellow `<mark>`) in the excerpt — not just the title

---

## TC-04 — Aperçu modal (read-only)

- Click "Aperçu 👁" on any result
- **Expected:** Modal slides in showing metadata; NO edit/delete/validate buttons; only "Ouvrir ↗" action
- Close modal → search page still visible with results

---

## TC-05 — Ouvrir in new tab

- Click "↗ Ouvrir" on a task result
- **Expected:** Browser opens `/taches/{id}` in a NEW tab; task detail renders with normal permissions

---

## TC-06 — Super-admin global scope

- Log in as super-admin (is_super_admin = true)
- Search for a term that exists in multiple workspaces
- **Expected:** `is_global: true` in API response; results from ALL workspaces show; each result has a purple **workspace badge** showing workspace name

---

## TC-07 — Document content search (PDF text extraction)

- Upload a PDF containing the word "budget"
- Wait for queue to process `ExtractDocumentTextJob`
- Search "budget"
- **Expected:** The PDF document appears in results; excerpt shows the highlighted "budget" text from inside the file

---

## TC-08 — File content extraction backfill

```bash
php artisan documents:extract-text --sync
```
- **Expected:** Progress bar runs; documents get `content_text` set; can be verified via tinker:
  `Document::whereNotNull('content_text')->count()`

---

## TC-09 — TeamMessage search

- Search for a word from a team chat message
- **Expected:** "Messages" tab shows the matching message; excerpt shows first 120 chars; "Ouvrir ↗" goes to `/teams/{uuid}?message={uuid}`
- Navigate to the team via that link
- **Expected:** Chat scrolls to the message; 3-second blue ring highlight

---

## TC-10 — Selective export

- Perform a search → check 3–5 result rows
- Click "Exporter la sélection"
- **Expected:** Excel file downloads with one sheet containing only the selected rows

---

## TC-11 — Global export with cap dialog

- Perform a search with 20+ results
- Click "Exporter tout ({n})"
- **Expected:** Dialog appears offering 500 / 1 000 / 2 000 / Tous
- Choose "500 premiers résultats" → "Exporter"
- **Expected:** Excel file downloads immediately with ≤500 rows, multi-sheet per type

---

## TC-12 — Global export "Tous" queued

- Choose "Tous les résultats" → "Exporter"
- **Expected:** Response says "Export en cours — vous recevrez un email"
- Queue job runs → email arrives with download link → file is valid Excel

---

## TC-13 — "Voir tous les résultats" CTA in command palette

- Press Cmd+K → type a search term → get results in the palette
- Click "Voir tous les résultats pour 'X' →" at the bottom
- **Expected:** Navigates to `/search?q=X` with results already loaded

---

## TC-14 — Role tiers (search.global vs search.scoped)

- **Manager/owner** (`search.global`): search bar + `/search` enabled; results span the **whole workspace**.
- **Cadre / collaborateur / stagiaire** (`search.scoped`): search bar + `/search` enabled; results limited to **assigned resources only** (projects/activities/tasks/subtasks they're on, teams they belong to). Verify: a cadre searching a term matching a workspace project they are NOT assigned to → that project does NOT appear.
- **Observateur / utilisateur** (no search permission): search bar disabled (grayed); `GET /api/search` → **403**.

## TC-14b — Notification search is private (all tiers)

- Log in as a user with notifications, open `/search`, select the **🔔 Notifications** tab, search a term in your notifications → only **your own** notifications appear.
- As **super-admin** (with no personal notifications), search notifications → **0 results** (super-admin does NOT see other users' notifications — notification search is always self-scoped).

---

## TC-15 — Workspace isolation (CRITICAL — Typesense path)

- Log in as **manager of Workspace A** (Typesense driver active, not collection).
- Search a term that matches projects in **both** Workspace A and Workspace B.
- **Expected:** Only Workspace A results appear; the tab count (total) reflects A only. Workspace B data never appears.
- (This guards the fixed `fromRaw` scoping leak — filter_by is applied at the Typesense level.)

## TC-16 — Export IDOR guard (manager+)

- As manager of Workspace A, `POST /api/search/export` with `type=projets` and `ids` that include a Workspace B project id.
- **Expected:** The exported file contains only Workspace A rows; the forged Workspace B id is silently dropped.
- As cadre, call any export endpoint → **403** (export is manager+ only).

---

## PHPUnit Verification

```bash
php artisan test --compact tests/Feature/Search
# Expected: 17 tests passing (collection driver — no Typesense needed)
```

> Note: PHPUnit uses `SCOUT_DRIVER=collection` which respects `->query()`. The workspace/tier
> leak only manifested under **Typesense** (`->raw()`); verify TC-15 manually against Typesense.

---

## Negative Cases

| Scenario | Expected |
|---|---|
| Query < 2 chars | 422 |
| Unauthenticated | 401 |
| Observateur/utilisateur API call | 403 |
| Cadre calls export endpoint | 403 (export is manager+) |
| Non-member sending workspace_id | 403 |
| Export with invalid cap value | 422 |
| Manager exports forged cross-workspace ids | foreign ids dropped (only own workspace) |
| Super-admin searches notifications | only own (0 if none) |
| Team `?message=` with invalid UUID | Page loads, no highlight (graceful) |
