# User Testing Guide — Step 3: Dashboard Multi-Workspace Dropdown Removed

**Branch:** `feature/workspace-picker`
**File:** `resources/js/pages/Dashboard.vue`
**Goal:** Verify the workspace selector dropdown is gone from the dashboard and the page correctly scopes to the active workspace automatically.

---

## What Changed

- Removed the `<select>` workspace switcher from the dashboard header
- Dashboard now always reads from `currentWorkspace` (set via the picker)
- `selectedWorkspace`, `fetchWorkspaces`, `selectWorkspace` logic removed
- Unused icon imports (`BuildingOfficeIcon`, `ChevronDownIcon`) removed

---

## Manual Testing Steps

### Prerequisites
- You have selected a workspace via the picker (you should be on `/`)

### Test A — No workspace dropdown visible

1. Navigate to the dashboard (`/`)
2. Inspect the page header and filter bar
3. Verify there is **no** `<select>` element or dropdown for switching workspaces
4. The workspace name may appear as a label but should not be a clickable switcher

### Test B — Dashboard data scoped to current workspace

1. Select workspace "A" via the picker, confirm you land on `/`
2. Note the dashboard statistics (tasks, projects, members shown)
3. Go back to the picker, select workspace "B"
4. Confirm the dashboard stats change to reflect workspace "B"'s data
5. No manual workspace switch should be needed — it happens via the picker

### Test C — Dashboard loads without errors

1. Open browser DevTools → Console
2. Navigate to the dashboard
3. Confirm no JavaScript errors related to `selectedWorkspace` or missing workspace data
4. Confirm API calls go to the correct workspace-scoped endpoints

### Test D — Return to picker to switch workspace

1. To switch workspaces, the user should navigate to the picker via the sidebar or workspace switcher button
2. Verify there is a clear navigation path to `/workspaces/select` from the dashboard
3. After selecting a new workspace in the picker, verify the dashboard refreshes with the new workspace's data

---

## Automated Coverage

- `tests/Browser/Workspaces/DashboardWorkspaceDropdownRemovedTest.php`
  - `test_no_workspace_select_element_on_dashboard`
  - `test_all_workspaces_option_absent`
  - `test_dashboard_loads_with_current_workspace_data`
