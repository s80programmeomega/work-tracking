# Phase 1 Testing Guide — Visibility Full Replacement

**Task:** Drop `visibility` columns from projets/taches/documents/teams; replace all access rules with ContextualPermissionGate checks.  
**Branch:** `feature/visibility-teams-chat`  
**Status:** ✅ Implementation complete (Steps 1.1–1.6). Tests deferred to end of plan.

---

## Prerequisites

1. Run `php artisan migrate:fresh --seed` on the dev database
2. Start the dev server: `php artisan serve`
3. Start Vite: `npm run dev`
4. Ensure you have test users for each role: `directeur`, `manager`, `cadre`, `collaborateur`, `observateur`

---

## Test Cases

### TC-1.1 — Project listing respects role (no visibility field)

**Action:** Log in as a `cadre` user who is NOT a member of any project. Navigate to `/projets`.  
**Expected:** No projects visible (empty list or appropriate message).  
**How to verify:** Projects list page shows empty state.

### TC-1.2 — Project member can view project

**Action:** Log in as a `cadre` user who IS a member of "Projet Alpha". Navigate to `/projets`.  
**Expected:** "Projet Alpha" appears in the list.  
**How to verify:** Project card visible.

### TC-1.3 — Manager sees all workspace projects

**Action:** Log in as a `manager` user. Navigate to `/projets`.  
**Expected:** ALL projects in the workspace are visible, including ones where the manager is not a direct member.  
**How to verify:** Project count matches total workspace projects.

### TC-1.4 — Project responsable always sees own project

**Action:** Log in as a user who is `responsable_id` of "Projet Beta" but has no workspace role higher than `cadre`. Navigate to `/projets`.  
**Expected:** "Projet Beta" appears in the list.  
**How to verify:** Project card visible.

### TC-1.5 — Visibility dropdown no longer present in project form

**Action:** Log in as a `directeur`. Open "Create Project" modal or form.  
**Expected:** No visibility radio buttons or dropdown exists in the form.  
**How to verify:** Form does not contain any visibility field.

### TC-1.6 — Task has view policy enforcement

**Action:** Log in as a workspace member who is NOT assigned to Task #42 and is not a project member. Try to access `/api/taches/42` directly.  
**Expected:** 403 Forbidden.  
**How to verify:** Browser network tab shows 403.

### TC-1.7 — Task assignee can view their task

**Action:** Log in as a user who IS assigned to Task #42. Navigate to the task detail page.  
**Expected:** Task detail page loads successfully.  
**How to verify:** Task title and details visible.

### TC-1.8 — Document without explicit permission is not viewable by non-owner

**Action:** Log in as a workspace member who has no `DocumentPermission` record for Document #10, and is not the document owner. Try to access `/api/documents/10`.  
**Expected:** 403 Forbidden.  
**How to verify:** Network tab shows 403. Previously (with visibility=public), this would have returned 200 — this is the key behavioral change.

### TC-1.9 — Document with explicit permission IS viewable

**Action:** Log in as a user who has a `DocumentPermission` record for Document #10 with `can_view=true`. Access `/api/documents/10`.  
**Expected:** 200 with document data.  
**How to verify:** Network tab shows 200.

### TC-1.10 — Visibility badge gone from DocumentCard

**Action:** Log in as a project member. Open the documents list for a project.  
**Expected:** No green globe (public) or orange users (team) icon on document cards.  
**How to verify:** Visual inspection — icons are absent.

---

## Negative Cases (must be blocked)

| Scenario | Expected result |
|---|---|
| `cadre` not in project tries to view project detail | 403 |
| `observateur` tries to edit a project (already blocked) | 403 — no change |
| Submit project form with `visibility` field in request body | Field silently ignored (stripped by Form Request) or 422 if strict |
| Non-member task access via API | 403 |

---

## Dusk Browser Tests (run after implementation)

```bash
php artisan serve &
php artisan dusk tests/Browser/Projet/ProjetAccessTest.php
```

Expected: all browser tests green, screenshots saved.

---

## Cleanup

- No cleanup needed — the visibility columns are gone from the schema. The feature is permanent.
