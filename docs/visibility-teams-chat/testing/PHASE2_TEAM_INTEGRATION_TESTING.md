# Phase 2 Testing Guide — Team Integration at Project Level

**Task:** Add `use_teams` flag to projects; make assignee pickers team-aware; auto-sync team members into projet_user.  
**Branch:** `feature/visibility-teams-chat`  
**Status:** ✅ Implementation complete — tests deferred to end of plan

---

## Prerequisites

1. Run `php artisan migrate` (Phase 2 migration adds `use_teams`)
2. Have at least one workspace with: a manager user, a cadre user, 2 teams with different members
3. Start the dev server + Vite

---

## Test Cases

### TC-2.1 — `use_teams` defaults to false

**Action:** Create a new project as a `directeur`. Open the project settings form.  
**Expected:** "Utiliser les équipes" toggle is OFF by default.  
**How to verify:** Toggle visual state is off; API `GET /api/projets/{id}` returns `use_teams: false`.

### TC-2.2 — Only manager+ can toggle `use_teams`

**Action:** Log in as a `cadre` user who is a project member. Try `PATCH /api/projets/{id}/use-teams` with `{"use_teams": true}`.  
**Expected:** 403 Forbidden.  
**How to verify:** Network tab shows 403.

### TC-2.3 — Manager can toggle `use_teams` ON

**Action:** Log in as a `manager`. Open project settings. Toggle "Utiliser les équipes" ON.  
**Expected:** Success; `use_teams = true` saved; "Équipes liées" panel appears.  
**How to verify:** Panel visible; API returns `use_teams: true`.

### TC-2.4 — Link a team to the project

**Action:** In the "Équipes liées" panel, search for "Team Alpha" and add it.  
**Expected:** Team Alpha appears in the linked teams list; all Team Alpha members are auto-added to the project as `collaborateur` (if not already a higher-role member).  
**How to verify:** Check `GET /api/projets/{id}/members` — Team Alpha members present. Check notification bell — team members received auto-add notification.

### TC-2.5 — Existing project members are NOT downgraded

**Action:** "User X" is already in the project as `manager`. Link a team that also contains User X.  
**Expected:** User X's role remains `manager`, not downgraded to `collaborateur`.  
**How to verify:** `GET /api/projets/{id}/members` — User X role is still `manager`.

### TC-2.6 — Assignee picker shows team members when `use_teams=true`

**Action:** Open a task creation form in a project with `use_teams=true` and "Team Beta" linked.  
**Expected:** Assignee picker shows members of Team Beta (and any other linked teams + direct project members).  
**How to verify:** Type a name from Team Beta in the picker — they appear in suggestions.

### TC-2.7 — Assignee picker shows workspace members when `use_teams=false`

**Action:** Open a task creation form in a project with `use_teams=false`.  
**Expected:** Assignee picker shows all workspace members (existing behavior).  
**How to verify:** Type a name from the workspace who is NOT in any team — they appear in suggestions.

### TC-2.8 — Unlink a team

**Action:** In the "Équipes liées" panel, remove "Team Alpha".  
**Expected:** Team-Alpha-only members (those who joined solely via Team Alpha, not through any other path) are removed from `projet_user`. Members who were already direct project members are kept.  
**How to verify:** `GET /api/projets/{id}/members` — team-only members gone; direct members still present.

### TC-2.9 — Add member to linked team auto-adds to project

**Action:** Add a new user to Team Beta while Team Beta is linked to the project.  
**Expected:** New user auto-added to project as `collaborateur`.  
**How to verify:** `GET /api/projets/{id}/members` — new user present.

### TC-2.10 — Remove member from linked team removes from project (if team-only)

**Action:** Remove a team-only member from Team Beta (they joined only via Team Beta linkage).  
**Expected:** User removed from project.  
**How to verify:** `GET /api/projets/{id}/members` — user absent.

### TC-2.11 — Teams/Show.vue shows project link badge

**Action:** Navigate to Team Beta's detail page (`/teams/{uuid}`).  
**Expected:** A badge like "Projet lié: [nom du projet]" appears in the team header.  
**How to verify:** Visual badge visible.

### TC-2.12 — Activity member candidates work

**Action:** With `use_teams=true`, try to assign a member to an activity inside the project.  
**Expected:** Candidate picker shows team members (same pool as task picker).  
**How to verify:** Team member appears as a suggestion.

---

## Negative Cases

| Scenario | Expected result |
|---|---|
| Cadre tries to link a team to the project | 403 |
| Link a team from a different workspace | Validation error (422) |
| Archive project → team sync observer does NOT fire | No membership changes |
| Soft-delete a team → project members NOT auto-removed | Members keep access |

---

## Dusk Browser Tests

```bash
php artisan dusk tests/Browser/Teams/TeamProjectIntegrationTest.php
```

Verify screenshots saved in `tests/Browser/screenshots/` for:
- use_teams toggle ON
- Team linkage panel with linked team
- Assignee picker showing team members

---

## Cleanup

None required — `use_teams` defaults to `false`; existing projects are unaffected.
