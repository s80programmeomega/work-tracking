# User Testing Guide — Steps 4–8: Route & Link Cleanup

**Branch:** `feature/workspace-picker`
**Files:** `router/index.ts`, `AppSidebar.vue`, 8 Vue page files, `fr.json`, `en.json`
**Goal:** Verify all former `/workspaces` and `/workspaces/:id` (show) links now redirect correctly to the workspace picker, and that deleted pages no longer exist.

---

## What Changed

| Step | Change |
|------|--------|
| 4 | `workspaces.index` and `workspaces.show` routes removed from router |
| 5 | `/workspaces` nav entry removed from sidebar; fallback link → `workspaces.select` |
| 6a | `Create.vue`, `AcceptInvitation.vue`, `AcceptProjetInvitation.vue` back links → `workspaces.select` |
| 6b | `Edit.vue`, `Settings.vue` back links + post-action redirects → `workspaces.select` |
| 6c | `Invitations.vue`, `TacheDetail.vue`, `WorkspaceDocuments.vue` → `workspaces.select` |
| 6d | `Create.vue` post-creation redirect → `workspaces.select` (not `workspaces.show`) |
| 7 | `Index.vue` and `Show.vue` deleted |
| 8 | `workspaces_index.*` and `workspace_show.*` i18n blocks removed |

---

## Manual Testing Steps

### Test A — /workspaces route no longer exists

1. Manually type `/workspaces` in the URL bar
2. The Vue router should not load the old Index page
3. Expected: 404 page, redirect to picker, or the root route — **not** the old workspace list
4. Confirm "Mes Workspaces" heading from the old Index page is **not** visible

### Test B — /workspaces/:id (show) route no longer exists

1. Manually type `/workspaces/1` in the URL bar
2. Expected: 404 page or redirect — **not** the old workspace detail page
3. Confirm the old "Statistics", "Projects", "Members" tabs from `Show.vue` are **not** visible

### Test C — Sidebar no longer has /workspaces link

1. Open the sidebar (expand if collapsed)
2. Verify there is no "Workspaces" link pointing to `/workspaces`
3. The workspace section in the sidebar should show the workspace switcher/picker access, not a list link

### Test D — Create workspace → redirects to picker

1. Navigate to `/workspaces/create`
2. Fill in workspace details and submit
3. Verify redirect goes to `/workspaces/select` (not `/workspaces/{id}`)
4. Verify the new workspace appears in the picker card grid

### Test E — Cancel on Create/Edit/Settings → picker

1. Navigate to `/workspaces/create` — click Cancel/Back → lands on `/workspaces/select`
2. Navigate to `/workspaces/{id}/edit` — click Cancel/Back → lands on `/workspaces/select`
3. Navigate to `/workspaces/{id}/settings` — click Back → lands on `/workspaces/select`

### Test F — Delete workspace → redirects to picker

1. Go to workspace Settings → find the delete/archive option
2. Confirm deletion → verify redirect to `/workspaces/select`

### Test G — Accept invitation links

1. Use an invitation link that lands on `/accept-invitation/:token`
2. After accepting (or on error), click the "Back to workspaces" button
3. Verify it goes to `/workspaces/select`

### Test H — TacheDetail breadcrumb

1. Navigate to a task detail page (`/taches/:id`)
2. Inspect the breadcrumb — the workspace segment should be a link
3. Click it — verify navigation to `/workspaces/select`

### Test I — WorkspaceDocuments breadcrumb

1. Navigate to `/workspaces/{id}/documents`
2. Click the workspace name in the breadcrumb
3. Verify navigation to `/workspaces/select`

---

## Automated Coverage

- `tests/Browser/Workspaces/WorkspaceOrphanLinksTest.php`
  - `test_workspaces_index_route_is_gone`
  - `test_create_page_back_link_points_to_picker`
  - `test_settings_back_link_points_to_picker`
  - `test_edit_page_back_links_point_to_picker`
  - `test_no_direct_workspace_show_links_in_sidebar`
