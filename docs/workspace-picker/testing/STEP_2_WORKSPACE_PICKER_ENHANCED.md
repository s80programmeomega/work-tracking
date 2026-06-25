# User Testing Guide — Step 2: WorkspacePicker Enhanced

**Branch:** `feature/workspace-picker`
**File:** `resources/js/pages/workspaces/WorkspacePicker.vue`
**Goal:** Verify the picker is now the account-level overview screen with stat tiles, search, new workspace button, and per-card settings access.

---

## What Changed

- 4 aggregate stat tiles added at the top (total workspaces, projects, members, active)
- Search bar filters workspace cards in real time
- "New workspace" button navigates to `/workspaces/create`
- Gear icon per workspace card links to its settings page
- Both stats and cards use stagger animations on load

---

## Manual Testing Steps

### Prerequisites
- Dev server running + `npm run dev` (or build)
- At least 2 workspaces, one with projects and one with members

### Test A — Stat tiles visible and accurate

1. Log in and land on `/workspaces/select`
2. Verify 4 stat tiles are visible at the top of the page:
   - **Total workspaces** — should match how many workspaces your account has
   - **Total projects** — sum of all `projets_count` across all workspaces
   - **Total members** — sum of all `members_count` across all workspaces
   - **Active** — count of workspaces where `is_active = true`
3. Create a new workspace — navigate back to the picker and verify the total workspaces tile increments by 1

### Test B — Stagger animation on load

1. Reload the picker page
2. Stat tiles should fade in with a staggered delay (50ms between each)
3. Workspace cards below should also stagger in sequence after the tiles

### Test C — Search bar filters cards

1. Type a partial name of one of your workspaces in the search bar
2. Only matching workspaces should remain visible
3. Non-matching workspaces should disappear without a page reload
4. Clear the search — all workspaces should reappear
5. Search by workspace `code` — verify it also matches
6. Search for a string that matches no workspace — verify an empty state is shown

### Test D — New workspace button

1. Click the "New workspace" button (top right of picker)
2. Verify navigation to `/workspaces/create`
3. Cancel back — verify return to `/workspaces/select`

### Test E — Per-card settings gear button

1. Hover over a workspace card
2. A gear / settings icon should appear (or be always visible)
3. Click it — verify navigation to `/workspaces/{id}/settings` for that specific workspace
4. Verify the correct workspace settings page loads (check workspace name in the heading)
5. Navigate back using the back button — verify return to `/workspaces/select`

---

## Automated Coverage

- `tests/Browser/Workspaces/WorkspacePickerEnhancedTest.php`
  - `test_account_stat_tiles_are_visible`
  - `test_stat_total_workspaces_shows_correct_count`
  - `test_stat_total_projects_shows_project_count`
  - `test_stat_active_reflects_active_workspaces`
  - `test_search_input_is_present`
  - `test_search_filters_workspace_cards`
  - `test_new_workspace_button_navigates_to_create`
  - `test_settings_button_is_present_per_card`
  - `test_settings_button_navigates_to_workspace_settings`
