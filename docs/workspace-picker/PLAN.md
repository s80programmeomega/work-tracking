# Workspace Picker on Login + Owner Badge

## Context

Two related features requested after demo-prep testing:

1. **Owner badge** — show a "Propriétaire" badge on workspace cards when the logged-in user owns that workspace.
2. **Workspace picker on login** — after login, redirect to a full-screen workspace selection page instead of directly to the dashboard. Each workspace card shows a role/owner badge. Clicking a card activates the workspace and goes to the dashboard.

---

## Feature 1 — Owner Badge on Workspace Cards

**File:** `resources/js/pages/workspaces/Index.vue`

- `owner_id` is already in the workspace API response — no backend change needed.
- Add helper: `const isOwner = (ws) => ws.owner_id === authStore.user?.id`
- In the grid card (near the active/inactive badge, line ~188), add:

```html
<span v-if="isOwner(workspace)"
  class="px-2 py-1 text-xs font-medium rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">
  Propriétaire
</span>
```

- Add the same badge in the list view row.

---

## Feature 2 — Workspace Picker Page on Login

### Backend — expose `user_role` in workspace list

**File:** `app/Http/Controllers/Api/WorkspaceController.php`

Inside the `index()` transform closure, after `user_permissions`, add:

```php
if ($workspace->owner_id === $user->id) {
    $workspace->user_role = 'owner';
} else {
    $member = $workspace->members()->where('user_id', $user->id)->first();
    $workspace->user_role = $member
        ? (\Spatie\Permission\Models\Role::find($member->pivot->role_id)?->name ?? 'membre')
        : null;
}
```

### New page

**File:** `resources/js/pages/workspaces/WorkspacePicker.vue` (new)

- No `AdminLayout` — full-screen centered layout with app logo at top.
- Fetches workspaces via `api.get('/workspaces')` on mount.
- Responsive card grid (same visual style as `Index.vue`).
- Each card shows: logo/initials, name, description, project count, member count, **role badge**:
  - `user_role === 'owner'` → amber **Propriétaire** badge
  - otherwise → role name in a neutral badge (Manager, Cadre, etc.)
- On card click: call `selectWorkspace(workspace)` from `useWorkspace.js`, then `router.push({ name: 'Dashboard' })`.
- Stagger animation: `useStagger(staggerRef, 50)` + `applyStagger()` after load.
- No create/settings actions — picker only.

### Router changes

**File:** `resources/js/router/index.ts`

1. Add route:

```ts
{
  path: '/workspaces/select',
  name: 'workspaces.select',
  component: () => import('@/pages/workspaces/WorkspacePicker.vue'),
  meta: { requiresAuth: true },
}
```

2. In the guest-route redirect (authenticated user hitting `/signin` or `/signup`), change:

```ts
// Before
return next({ name: 'Dashboard' })
// After
return next({ name: 'workspaces.select' })
```

3. The existing "no workspace → workspaces.create" guard is unchanged.
4. After workspace selection the picker redirects to `Dashboard` — normal navigation continues.

---

## Files to touch

| File | Change |
|---|---|
| `app/Http/Controllers/Api/WorkspaceController.php` | Add `user_role` to index transform |
| `resources/js/pages/workspaces/WorkspacePicker.vue` | **New file** — picker page |
| `resources/js/router/index.ts` | New route + post-login redirect target |
| `resources/js/pages/workspaces/Index.vue` | `isOwner()` helper + owner badge (grid + list) |

---

## Dusk Tests

**File:** `tests/Browser/Workspaces/WorkspacePickerTest.php`

Namespace: `Tests\Browser\Workspaces` — uses `WorkTrackingTestCase` + `DatabaseTruncation`.

### Auth & routing

| Test | What it verifies |
|---|---|
| `test_unauthenticated_user_is_redirected_to_signin` | Visit `/workspaces/select` without a token → redirected to `/signin` |
| `test_authenticated_user_is_redirected_to_picker_after_login` | `signInViaUi()` → after successful login the URL becomes `/workspaces/select` |

### Picker page content

| Test | What it verifies |
|---|---|
| `test_picker_shows_owned_workspace_with_proprietaire_badge` | User owns workspace → card shows `dusk="owner-badge"` with text "Propriétaire" |
| `test_picker_shows_member_workspace_with_role_badge` | User is member (manager) → card shows `dusk="role-badge"` with role name |
| `test_picker_shows_all_user_workspaces` | User owns 1, is member of 1 → both cards visible |
| `test_picker_card_click_switches_workspace_and_redirects_to_dashboard` | Click a card → URL becomes `/` (Dashboard), sidebar shows correct workspace name |

### Responsiveness

| Test | What it verifies |
|---|---|
| `test_picker_is_responsive_on_mobile` | Resize to 375×812 (iPhone) → cards stack in single column, badges remain visible, no horizontal overflow |
| `test_picker_is_responsive_on_tablet` | Resize to 768×1024 (iPad) → cards display in 2-column grid |

### Owner badge on Index page

**File:** `tests/Browser/Workspaces/WorkspaceIndexOwnerBadgeTest.php`

| Test | What it verifies |
|---|---|
| `test_owner_badge_visible_on_owned_workspace_card` | Visit `/workspaces` as owner → card shows `dusk="owner-badge"` |
| `test_owner_badge_not_visible_on_member_workspace_card` | Visit `/workspaces` as non-owner member → no `dusk="owner-badge"` on that card |

### Dusk attributes to add

These `dusk=""` attributes must be added to the Vue templates so Dusk selectors work:

- `WorkspacePicker.vue` card: `dusk="workspace-card-{id}"`, `dusk="owner-badge"`, `dusk="role-badge"`
- `Index.vue` grid card: `dusk="owner-badge"` on the owner badge span

---

## Checklist

- [x] `WorkspaceController::index()` — add `user_role` field
- [x] `WorkspacePicker.vue` — create picker page (with dusk attrs)
- [x] Router — add `workspaces.select` route (`requiresAuth: true`)
- [x] Router — change post-login redirect to `workspaces.select`
- [x] `Index.vue` — add `isOwner()` + badge in grid card (with dusk attr)
- [x] `Index.vue` — add badge in list view row
- [x] `WorkspacePickerTest.php` — auth, content, responsiveness, click-to-select tests
- [x] `WorkspaceIndexOwnerBadgeTest.php` — owner badge visibility tests
- [x] Pint + PHPStan clean
- [ ] Manual verification: login flow, badge display, workspace switch, unauthenticated redirect, mobile layout

---

## Sidebar Context-Aware Display

- [x] `AppSidebar.vue` — replace `filteredWorkspaces` with context-aware `sidebarWorkspaces` computed
- [x] `AppSidebar.vue` — add "View all workspaces" link for super_admin (`dusk="view-all-workspaces-btn"`)
- [x] `AppSidebar.vue` — import `ArrowRightIcon`
- [x] `fr.json` / `en.json` — add `sidebar.view_all_workspaces`
- [x] `SidebarWorkspaceListTest.php` — 5 Dusk tests
- [x] Pint + PHPStan clean
- [ ] Manual verification (super_admin, owner, non-owner scenarios)
