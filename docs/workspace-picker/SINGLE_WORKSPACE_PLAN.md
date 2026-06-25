# Plan — Single-Workspace Enforcement & WorkspacePicker Unification

**Branch:** `feature/workspace-picker`
**Date:** 2026-06-25
**Status:** Pending implementation

---

## Context

The WorkspacePicker (post-login workspace selection screen) was added in the previous phase. The design intent is clear: work always happens inside **one active workspace at a time**. However, the current codebase still has several leaks:

1. `Workspaces/Index.vue` — multi-workspace list with cross-workspace aggregate stats
2. `Workspaces/Show.vue` — per-workspace detail page with tabs (overview, projects, members)
3. `Dashboard.vue` — dropdown allowing users to filter by `"all workspaces"` — a direct contradiction of the single-workspace model
4. Several `router-link` and `router.push` calls still pointing to `workspaces.index` and `workspaces.show`
5. The `/api/workspaces` endpoint does not return `member_count`, even though WorkspacePicker.vue already expects it

**Goal:** Make the WorkspacePicker the unified account overview screen (it sees all workspaces + their aggregate stats), enforce single-workspace-at-a-time everywhere else, and remove orphaned routes and pages without losing any feature.

---

## Change Overview

| File | Action |
|---|---|
| `WorkspaceController.php` | Add `members` to `withCount` |
| `WorkspacePicker.vue` | Enhance: aggregate stats banner + search + "New" button + per-card ⋮ settings button |
| `Workspaces/Index.vue` | **Delete** |
| `Workspaces/Show.vue` | **Delete** |
| `Dashboard.vue` | Remove workspace dropdown; always scope to `currentWorkspace` |
| `router/index.ts` | Remove `workspaces.index` + `workspaces.show` routes |
| `AppSidebar.vue` | Remove `/workspaces` main-nav entry; fix fallback link |
| 12 other Vue files | Update orphaned links |
| `fr.json` + `en.json` | Add new picker keys; remove `workspaces_index.*` and `workspace_show.*` blocks |
| PHPUnit Feature tests | New tests for backend changes |
| Dusk Browser tests | New tests for picker behaviour |
| `docs/workspace-picker/testing/` | User testing guides per step |

**Estimated total: 1 backend + 14 frontend + 2 i18n + tests + docs ≈ 20 files**

---

## Step 1 — Backend: Add `member_count` to `/api/workspaces`

**File:** `app/Http/Controllers/Api/WorkspaceController.php`

In the `index()` method (~line 54), change:
```php
->withCount('projets')
```
to:
```php
// Chargement du nombre de projets et de membres pour les cartes du picker
->withCount(['projets', 'members'])
```

This makes `member_count` available on every workspace card in the picker without an extra API call. The `/api/workspaces/user-workspaces` endpoint already does this — the index endpoint just needs to be brought to parity.

**Verify:**
```bash
php artisan tinker
\App\Models\Workspace::withCount(['projets','members'])->first()->member_count
# Expected: integer ≥ 0
```

---

## Step 2 — WorkspacePicker.vue: Full Enhancement

**File:** `resources/js/pages/workspaces/WorkspacePicker.vue`

### 2a. New i18n keys (fr.json + en.json)

Add under the `workspace_picker` namespace in both locale files:

**fr.json:**
```json
"search_placeholder": "Rechercher un espace de travail...",
"new_workspace": "Nouveau workspace",
"stat_workspaces": "Workspaces",
"stat_projects": "Projets",
"stat_members": "Membres",
"stat_active": "Actifs",
"settings_tooltip": "Paramètres"
```

**en.json:**
```json
"search_placeholder": "Search a workspace...",
"new_workspace": "New workspace",
"stat_workspaces": "Workspaces",
"stat_projects": "Projects",
"stat_members": "Members",
"stat_active": "Active",
"settings_tooltip": "Settings"
```

### 2b. Template changes

**Action bar** — between the subtitle and `<main>`, add a row with:
- A search `<input>` (v-model → `searchQuery`)
- A "New workspace" `<button>` → `router.push({ name: 'workspaces.create' })`

**Aggregate stats banner** — first child inside `<main>`, visible only when `workspaces.length > 0` and not loading:
- Responsive grid: `grid grid-cols-2 sm:grid-cols-4 gap-3 mb-8`
- 4 tiles: Total workspaces / Total projects / Total members / Active workspaces
- Same visual style as the stat tiles in `Index.vue` (colored icon + count + label)
- Tiles must have `class="stagger-item"` (Guide 23 — mandatory animation)
- Dusk attributes: `dusk="stat-total-workspaces"`, `dusk="stat-total-projects"`, `dusk="stat-total-members"`, `dusk="stat-active"`

**Per-card ⋮ button** — top-right of each workspace card, visible on hover:
- `@click.stop="openSettings(workspace.id)"`
- Navigates to `{ name: 'workspaces.settings', params: { id: workspace.id } }`
- Same pattern as `Index.vue` line 256
- Dusk attribute: `dusk="workspace-settings-btn-{id}"` (interpolated in v-for)

**Search filtering** — the `v-for` loops over `filteredWorkspaces` (computed) instead of raw `workspaces`.

Additional picker dusk attributes:
- `dusk="picker-search-input"` on the search input
- `dusk="picker-create-btn"` on the create button

### 2c. Script changes

New refs/computed to add:

```typescript
// Filtre de recherche local — aucun appel API supplémentaire
const searchQuery = ref('')

// Workspaces filtrés selon le texte de recherche
const filteredWorkspaces = computed(() =>
  workspaces.value.filter(w =>
    w.nom.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
    w.code?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
    w.description?.toLowerCase().includes(searchQuery.value.toLowerCase())
  )
)

// Stats agrégées calculées côté client depuis les données déjà chargées
const accountStats = computed(() => ({
  total: workspaces.value.length,
  active: workspaces.value.filter(w => w.is_active).length,
  totalProjects: workspaces.value.reduce((a, w) => a + (w.projets_count || 0), 0),
  totalMembers: workspaces.value.reduce((a, w) => a + (w.member_count || 0), 0),
}))

// Navigation vers les paramètres — empêche le clic de sélection du workspace
const openSettings = (id: number) => {
  router.push({ name: 'workspaces.settings', params: { id } })
}
```

**Stagger (Guide 23 — mandatory):** Stats banner tiles get their own `ref="statStaggerRef"` with `applyStatStagger()` called after `workspaces.value` is populated. 50ms delay. Existing card stagger ref stays unchanged.

---

## Step 3 — Dashboard.vue: Remove Multi-Workspace Dropdown

**File:** `resources/js/pages/Dashboard.vue`

### 3a. Template changes

Remove the `<select>` element (lines 27–33) containing `<option value="all">` and the `workspaces` loop.

The workspace name in the header becomes a plain display of `currentWorkspace?.nom`.

### 3b. Script changes

- Remove `selectedWorkspace` ref (was `ref('all')`)
- Remove `onWorkspaceChange` handler
- Remove `workspaces` from the `useWorkspace()` destructure
- In `loadDashboardData()`, change the `workspace_id` param: always send `currentWorkspace.value?.id` (never `null` or `'all'`)
- In `onMounted`, remove the `if (currentWorkspace.value) { selectedWorkspace.value = ... }` block

Replace the `currentWorkspaceName` computed:
```javascript
// Nom du workspace actif pour l'en-tête du tableau de bord
const currentWorkspaceName = computed(() => currentWorkspace.value?.nom || '')
```

### 3c. i18n note

The key `sidebar.all_workspaces` is used elsewhere (sidebar filter). **Do not delete it.** Only the Dashboard template reference is removed.

---

## Step 4 — Router: Remove Dead Routes

**File:** `resources/js/router/index.ts`

### 4a. Remove route definitions

Delete the two route objects:
- `workspaces.index` (path `/workspaces`, component `Index.vue`) — lines 87–95
- `workspaces.show` (path `/workspaces/:id`, component `Show.vue`) — lines 115–122

### 4b. Keep navigation guards intact

- The no-workspace fallback → `workspaces.create` (lines 921–928): unchanged
- The guest-route redirect → `workspaces.select` (lines 932–935): unchanged

---

## Step 5 — AppSidebar.vue: Clean Up Navigation

**File:** `resources/js/components/layout/AppSidebar.vue`

Remove the main navigation menu entry with `path: "/workspaces"` (~line 807). Users reach the picker via the sidebar workspace switcher dropdown "Switch workspace" link (line 189 — already correct).

Existing links at lines 179 (`/admin/workspaces`), 189 (`/workspaces/select`), 197 (`/workspaces/create`) stay as-is.

The settings link (~line 499) has a fallback `'/workspaces'` — update to `{ name: 'workspaces.select' }`.

---

## Step 6 — Update All Orphaned Links

### Pattern A — "Back" links that used to point to Index → now point to picker

| File | Line(s) | Old target | New target |
|---|---|---|---|
| `pages/workspaces/Create.vue` | 11, 171 | `to="/workspaces"` | `{ name: 'workspaces.select' }` |
| `pages/AcceptProjetInvitation.vue` | 28 | `to="/workspaces"` | `{ name: 'workspaces.select' }` |
| `pages/AcceptInvitation.vue` | 23 | `to="/workspaces"` | `{ name: 'workspaces.select' }` |

### Pattern B — router.push to `workspaces.index` or `workspaces.show`

| File | Line(s) | Old call | New call |
|---|---|---|---|
| `pages/workspaces/Edit.vue` | 438, 576 | `{ name: 'workspaces.index' }` | `{ name: 'workspaces.select' }` |
| `pages/workspaces/Edit.vue` | 553 | `{ name: 'workspaces.show', params: { id } }` | `{ name: 'workspaces.select' }` |
| `pages/workspaces/Settings.vue` | 582, 619 | `{ name: 'workspaces.index' }` | `{ name: 'workspaces.select' }` |
| `pages/workspaces/Settings.vue` | 17 (back link) | `{ name: 'workspaces.show', params: { id } }` | `{ name: 'workspaces.select' }` |

### Pattern C — String interpolation

| File | Line | Old call | New call |
|---|---|---|---|
| `pages/Users/Invitations.vue` | 444 | `` `/workspaces/${workspaceId}` `` | `{ name: 'workspaces.select' }` |
| `pages/taches/TacheDetail.vue` | 30 | `` `/workspaces/${id}` `` | `{ name: 'workspaces.select' }` |
| `pages/documents/WorkspaceDocuments.vue` | 7 | `{ name: 'workspaces.show', ... }` | `{ name: 'workspaces.select' }` |

### Pattern D — Post-creation redirect

| File | Line | Old call | New call |
|---|---|---|---|
| `pages/workspaces/Create.vue` | 324 | `{ name: 'workspaces.show', params: { id: response.id } }` | `{ name: 'workspaces.select' }` |

---

## Step 7 — Delete Dead Pages

After all links are updated and verified:

```bash
rm resources/js/pages/workspaces/Index.vue
rm resources/js/pages/workspaces/Show.vue
```

These files are only lazy-loaded via the router — no direct imports elsewhere.

---

## Step 8 — i18n Cleanup

**Files:** `resources/js/locales/fr.json` and `resources/js/locales/en.json`

- Delete the entire `workspaces_index` key block (used exclusively in `Index.vue`)
- Delete the entire `workspace_show` key block (used exclusively in `Show.vue`)
- **Keep:** `workspace_picker.*`, `ws_settings.*`, `workspace_form.*`, and all other blocks still in use

---

## Step 9 — PHPUnit Feature Tests

**Directory:** `tests/Feature/Workspace/`

### 9a. `WorkspaceIndexMemberCountTest.php` (new)

```
test_workspace_index_includes_member_count()
  → Authenticated user calls GET /api/workspaces
  → Assert every item in response has 'member_count' (integer ≥ 0)
  → Assert 'projets_count' is also present

test_workspace_index_member_count_is_accurate()
  → Create a workspace with 3 members via factory
  → GET /api/workspaces
  → Assert member_count === 3 for that workspace
```

### 9b. Additions to `WorkspaceCrudTest.php` (existing)

```
test_workspace_show_route_no_longer_registered()
  → Verify the route 'workspaces.show' does not exist in the router
  → (Via PHPUnit asserting RouteCollection)

test_workspace_index_route_no_longer_registered()
  → Same for 'workspaces.index'
```

---

## Step 10 — Dusk Browser Tests

**Directory:** `tests/Browser/Workspaces/`

### 10a. `WorkspacePickerEnhancedTest.php` (new)

```
test_picker_shows_aggregate_stats_banner()
  → Login, land on /workspaces/select
  → Assert dusk="stat-total-workspaces", "stat-total-projects", "stat-total-members", "stat-active" visible
  → Assert displayed values are non-negative integers

test_picker_stats_match_workspace_data()
  → Create 2 workspaces: one with 3 projects + 2 members, one with 1 project + 1 member
  → Assert "Projects" tile shows 4, "Members" tile shows 3

test_picker_search_filters_workspace_cards()
  → Load picker with 3 workspaces with distinct names
  → Type first workspace name in dusk="picker-search-input"
  → Assert only the matching card is visible

test_picker_search_shows_no_results_when_unmatched()
  → Type "zzznomatch" in search input
  → Assert no workspace cards visible

test_picker_settings_button_navigates_to_settings()
  → Hover on a workspace card
  → Click dusk="workspace-settings-btn-{id}"
  → Assert URL is /workspaces/{id}/settings

test_picker_create_button_navigates_to_create()
  → Click dusk="picker-create-btn"
  → Assert URL is /workspaces/create

test_picker_card_click_enters_workspace_dashboard()
  → Click workspace card body (not ⋮ button)
  → Assert URL is / (Dashboard)
  → Assert sidebar shows correct workspace name
```

### 10b. `DashboardWorkspaceDropdownRemovedTest.php` (new)

```
test_dashboard_has_no_workspace_select_dropdown()
  → Login, select a workspace, land on dashboard
  → Assert no <select> with option "Tous les workspaces" exists

test_dashboard_data_scoped_to_current_workspace()
  → Login with workspace A active
  → Assert dashboard stats correspond to workspace A
```

### 10c. `WorkspaceOrphanLinksTest.php` (new)

```
test_workspace_create_cancel_redirects_to_picker()
  → Visit /workspaces/create, click Cancel
  → Assert URL is /workspaces/select

test_workspace_edit_save_redirects_to_picker()
  → Save an edited workspace
  → Assert URL is /workspaces/select

test_workspace_settings_back_button_goes_to_picker()
  → Visit /workspaces/{id}/settings, click back link
  → Assert URL is /workspaces/select

test_accept_invitation_reject_redirects_to_picker()
  → Reject a workspace invitation
  → Assert URL is /workspaces/select

test_old_workspace_index_path_redirects_or_404()
  → Visit /workspaces directly
  → Assert either 404 or redirect to /workspaces/select (SPA behaviour)
```

---

## Step 11 — Pre-Commit Gates (Project Working Guidelines)

In this exact order before every commit:

```bash
# 1. PHP code style
vendor/bin/pint --dirty --format agent

# 2. Larastan static analysis (= PHPStan with Laravel extension)
php artisan clear-compiled && php -d memory_limit=1500M vendor/bin/phpstan analyse --memory-limit=1500M

# 3. Frontend build (catches dead imports and TypeScript errors)
npm run build

# 4. Full test suite
php artisan test --compact
```

All must pass with zero errors before committing.

---

## Code Production Rules

- **French explanatory comments** in all produced code (PHP and Vue/JS/TS) — this applies to code, not documentation
- **Stagger mandatory** (Guide 23): `useStagger` 50ms + `stagger-item` on all stat tiles and card grids
- **No Co-Authored-By** in commit messages
- **Progress tracking**: update `SINGLE_WORKSPACE_PROGRESSION.md` after each step is completed

---

## Manual Verification Checklist (Golden Path)

1. Login → redirected to `/workspaces/select`
2. Picker shows 4 aggregate stat tiles above workspace cards
3. Search input filters cards in real time by name/code/description
4. Hover on a card → ⋮ button visible → click → navigates to settings
5. Click card body (not ⋮) → enters workspace → lands on Dashboard
6. Dashboard: no workspace dropdown; workspace name shown as plain text
7. Sidebar workspace switcher → "Switch workspace" → back to picker
8. Navigate to `/workspaces` → 404 or redirect (route no longer exists)
9. Navigate to `/workspaces/1` → 404 or redirect (route no longer exists)
10. `Edit.vue` after save → redirects to picker (not to deleted Show page)
11. `Settings.vue` back button → to picker
12. `Create.vue` cancel → to picker; after success → to picker

---

## Full File List

### Backend (1 file)
- `app/Http/Controllers/Api/WorkspaceController.php`

### Frontend — Modified (12 files)
- `resources/js/pages/workspaces/WorkspacePicker.vue`
- `resources/js/pages/Dashboard.vue`
- `resources/js/router/index.ts`
- `resources/js/components/layout/AppSidebar.vue`
- `resources/js/pages/workspaces/Edit.vue`
- `resources/js/pages/workspaces/Settings.vue`
- `resources/js/pages/workspaces/Create.vue`
- `resources/js/pages/AcceptProjetInvitation.vue`
- `resources/js/pages/AcceptInvitation.vue`
- `resources/js/pages/Users/Invitations.vue`
- `resources/js/pages/taches/TacheDetail.vue`
- `resources/js/pages/documents/WorkspaceDocuments.vue`

### Frontend — Deleted (2 files)
- `resources/js/pages/workspaces/Index.vue`
- `resources/js/pages/workspaces/Show.vue`

### i18n (2 files)
- `resources/js/locales/fr.json`
- `resources/js/locales/en.json`

### Tests (4 new files)
- `tests/Feature/Workspace/WorkspaceIndexMemberCountTest.php`
- `tests/Browser/Workspaces/WorkspacePickerEnhancedTest.php`
- `tests/Browser/Workspaces/DashboardWorkspaceDropdownRemovedTest.php`
- `tests/Browser/Workspaces/WorkspaceOrphanLinksTest.php`

### Documentation (2 files)
- `docs/workspace-picker/SINGLE_WORKSPACE_PROGRESSION.md`
- `docs/workspace-picker/testing/` (one guide per completed step)
