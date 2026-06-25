# User Testing Guide — Step 1: Backend member_count

**Branch:** `feature/workspace-picker`
**File:** `app/Http/Controllers/Api/WorkspaceController.php`
**Goal:** Confirm `GET /api/workspaces` returns both `projets_count` and `members_count` on every workspace object.

---

## What Changed

`WorkspaceController::index()` now includes `members` in the `withCount` call alongside `projets`:

```php
->withCount(['projets', 'members'])
```

This means the picker can display aggregate stats without any extra API requests.

---

## Manual Testing Steps

### Prerequisites
- Laravel dev server running (`php artisan serve`)
- At least one workspace with members and projects exists

### Test A — API response contains both counts

1. Open browser DevTools → Network tab
2. Navigate to `/workspaces/select`
3. Find the `GET /api/workspaces` request
4. Inspect the response JSON — each workspace object should have:
   ```json
   {
     "id": 1,
     "nom": "My Workspace",
     "projets_count": 3,
     "members_count": 2,
     ...
   }
   ```
5. Verify `projets_count` and `members_count` are present and non-null

### Test B — Count accuracy

1. In the database, note how many projects and members a workspace has
2. Compare with the API response values
3. Both counts should match exactly

### Test C — Empty workspace

1. Create a new workspace with no projects and no members
2. Call `GET /api/workspaces`
3. Verify `projets_count: 0` and `members_count: 0` for that workspace

---

## Automated Coverage

- `tests/Feature/Workspace/WorkspaceIndexMemberCountTest.php` (4 tests)
  - `index_includes_members_count_in_response`
  - `index_includes_projets_count_in_response`
  - `index_returns_zero_counts_for_empty_workspace`
  - `index_requires_authentication`
