# Phase 6 Testing Guide — Global Search (Typesense + Scout)

> Branch: `feature/phase6-search`
> Requires: Typesense running locally

---

## Starting Typesense (dev)

```bash
# With Podman (Docker-compatible):
podman run -d --name typesense -p 8108:8108 \
  -v ./typesense-data:/data \
  typesense/typesense:27 \
  --data-dir /data \
  --api-key=xyz \
  --enable-cors

# Set in .env:
SCOUT_DRIVER=typesense
TYPESENSE_API_KEY=xyz
TYPESENSE_HOST=localhost
TYPESENSE_PORT=8108
TYPESENSE_PROTOCOL=http

# Index all models:
php artisan scout:import "App\Models\Projet"
php artisan scout:import "App\Models\Activite"
php artisan scout:import "App\Models\Tache"
php artisan scout:import "App\Models\Document"
php artisan scout:import "App\Models\User"
```

---

## Prerequisites

1. App server running: `php artisan serve`
2. Frontend built: `npm run dev`
3. Typesense running (see above) with indexed data
4. At least one workspace with: 2+ membres, 1 projet, 1 activité, 1 tâche, 1 document
5. Two users: one **manager** (or owner), one **cadre**

---

## TC-01 — Search bar visible for manager, hidden for cadre

- Log in as a **manager**
- **Expected:** Search bar appears in the header (large screens)
- Log in as a **cadre**
- **Expected:** Search bar is present but disabled (grayed out, cursor-not-allowed)

---

## TC-02 — Cmd+K / Ctrl+K shortcut

- Log in as manager
- Press **Cmd+K** (Mac) or **Ctrl+K** (Linux/Windows)
- **Expected:** Search input receives focus; dropdown appears ready

---

## TC-03 — Type 2+ characters triggers search

- Type `re` (2 chars) in the search bar
- **Expected:** Debounced API call fires after ~400 ms; spinner appears while loading; results grouped by type appear in dropdown

---

## TC-04 — Results grouped correctly

- Search for a known project name (e.g., `Gestion`)
- **Expected:** Dropdown shows sections: Projets, Activités, Tâches, Documents, Membres — each with relevant matches

---

## TC-05 — Navigate with keyboard

- Open search → type to get results
- Press **↓** to move focus down through results
- Press **↑** to move back up
- Press **Enter** on a focused result
- **Expected:** Browser navigates to the result's URL

---

## TC-06 — Click result navigates

- Click any result in the dropdown
- **Expected:** Dropdown closes; browser navigates to the result (project detail, task detail, etc.)

---

## TC-07 — Escape closes dropdown

- Open search with results showing
- Press **Esc**
- **Expected:** Dropdown closes; input retains its value

---

## TC-08 — No results message

- Search for a string that matches nothing (e.g., `xyzxyzxyz123`)
- **Expected:** "Aucun résultat pour «xyzxyzxyz123»" message shown

---

## TC-09 — Workspace scope — no cross-workspace leak

- User A is manager of **Workspace A** (does NOT have access to Workspace B)
- A record exists in Workspace B with a unique name (e.g., `ProjetConfidentiel`)
- Log in as User A → search `ProjetConfidentiel`
- **Expected:** Zero results — Workspace B data never appears

---

## TC-10 — Cadre gets 403 via API

```bash
curl -H "Authorization: Bearer <cadre_token>" \
  "http://localhost:8000/api/search?q=test&workspace_id=<ws_id>"
# Expected: {"error":"Non autorisé"} with 403
```

---

## TC-11 — Filter by type

```bash
curl -H "Authorization: Bearer <manager_token>" \
  "http://localhost:8000/api/search?q=test&workspace_id=<ws_id>&types[]=projets"
# Expected: results.projets present; no results.taches key
```

---

## TC-12 — New records indexed automatically (queued sync)

- Create a new project named `TestIndexAuto`
- Wait for queue worker to process (`php artisan queue:work --once`)
- Search `TestIndexAuto`
- **Expected:** New project appears in results

---

## PHPUnit Verification

```bash
php artisan test --compact --filter=SearchTest
# Expected: 8 tests passing (runs with collection driver — no Typesense needed)
```

---

## Negative Cases

| Scenario | Expected |
|---|---|
| Query < 2 chars (`?q=a`) | 422 Unprocessable Entity |
| No `workspace_id`, no `current_workspace_id` | 403 (no valid workspace) |
| Unauthenticated | 401 |
| Manager of Workspace A passes Workspace B ID | 403 (non-member check) |
