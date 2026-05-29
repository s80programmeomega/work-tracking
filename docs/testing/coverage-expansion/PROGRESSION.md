# Test Coverage Expansion — Progression

> Update this file at the end of every phase. One row per phase in the summary table.
> Companion: [`PLAN.md`](./PLAN.md) for the phase definitions.

---

## Status legend

| Symbol | Meaning |
|---|---|
| ⬜ | Not started |
| 🔄 | In progress |
| ✅ | Complete |
| ⚠️ | Blocked |

---

## Phase summary

| Phase | Scope | Test target | Branch commit | Status | Completed | PHPUnit total | Notes |
|---|---|---|---|---|---|---|---|
| P1 | Policy guards (5 policies) | 62 | 2cc323f+ | ✅ | 2026-05-29 | 363 | — |
| P2 | Projet CRUD + members + stats | 52 | 0ddd82d+ | ✅ | 2026-05-29 | 398 | Fixes: hasMember on Workspace, soft-delete assertion, cloneProjet status/dates |
| P3 | Activité CRUD + members + kanban | 26 | 39f13e2+ | ✅ | 2026-05-29 | 424 | Fixes: soft-delete assertion, tache titre uniqueness |
| P4 | Task gaps (attachments, links, move, responsable) | 22 | — | ✅ | 2026-05-29 | 446 | Fixes: moveTache null position, deleteExternalLink auth(), membres()→members() on Projet |
| P5 | Workspace management (CRUD, transfer, invitations, subscription) | 37 | — | ✅ | 2026-05-29 | 483 | Fix: MemberRemovalService::getTransferCandidates wherePivotIn role→role_id |
| P6 | Document management (resolver + controller) | 37 | — | ✅ | 2026-05-29 | 520 | Fixes: DocumentController search $request->query→input(), DocumentService::shareWithUsers missing model method |
| P7 | Team features (controllers + service) | 52 | — | ✅ | 2026-05-29 | 572 | Fix: TeamResourceController missing `name` field on create |
| P8 | Comments (controller + reactions + mentions) | ~15 | — | ⬜ | — | — | — |
| P9 | Labels (label + template + tache_label) | ~20 | — | ⬜ | — | — | — |
| P10 | Pure service layer (Projet/Activite/Auth) | ~20 | — | ⬜ | — | — | — |

**Cumulative target**: ~240 new tests on top of the existing 65 (≈ 305 total).

---

## Per-phase detail

### Phase 1 — Policy guards

- [x] `tests/Feature/Policies/ProjetPolicyTest.php` — view, update, delete, manageMembers
- [x] `tests/Feature/Policies/TachePolicyTest.php` — view, update, delete, validateN1, validateN2, approveN0 (virtual role)
- [x] `tests/Feature/Policies/ActivitePolicyTest.php` — view, update, delete + pivot overrides
- [x] `tests/Feature/Policies/WorkspacePolicyTest.php` — view, manageSettings, manageMembers, createProject
- [x] `tests/Feature/Policies/DocumentPolicyTest.php` — view, update, delete, share (uploader shortcut + contextual gate)

### Phase 2 — Projet CRUD + members + stats

- [x] `tests/Feature/Projet/ProjetCrudTest.php` — store, update, destroy, archive, unarchive, complete, clone, toggleFavorite
- [x] `tests/Feature/Projet/ProjetMembersTest.php` — getMembers, addMember, updateMember, removeMember, getMemberRemovalImpact, removeMemberWithTransfer
- [x] `tests/Feature/Projet/ProjetStatsTest.php` — getActivites, getTaches, getStatistics, dashboardStats, performanceReport, accessibleTasks, accessible

### Phase 3 — Activité CRUD + members + kanban

- [x] `tests/Feature/Activite/ActiviteCrudTest.php`
- [x] `tests/Feature/Activite/ActiviteMembersTest.php`
- [x] `tests/Feature/Activite/ActiviteKanbanTest.php`

### Phase 4 — Task gaps

- [x] `tests/Feature/Tache/TacheAttachmentsTest.php`
- [x] `tests/Feature/Tache/TacheExternalLinksTest.php`
- [x] `tests/Feature/Tache/TacheMoveTest.php`
- [x] `tests/Feature/Tache/TacheResponsableTest.php`

### Phase 5 — Workspace management

- [x] `tests/Feature/Workspace/WorkspaceCrudTest.php`
- [x] `tests/Feature/Workspace/WorkspaceMembersTest.php`
- [x] `tests/Feature/Workspace/WorkspaceInvitationsTest.php`

### Phase 6 — Document management

- [x] `tests/Feature/Services/DocumentAccessResolverTest.php`
- [x] `tests/Feature/Document/DocumentCrudTest.php`
- [x] `tests/Feature/Document/DocumentPermissionsTest.php`
- [x] `tests/Feature/Document/DocumentQueriesTest.php`

### Phase 7 — Team features

- [x] `database/factories/TeamFactory.php`
- [x] `tests/Feature/Team/TeamCrudTest.php`
- [x] `tests/Feature/Team/TeamMembersTest.php`
- [x] `tests/Feature/Team/TeamMessagesTest.php`
- [x] `tests/Feature/Team/TeamAnnouncementsTest.php`
- [x] `tests/Feature/Team/TeamEventsTest.php`
- [x] `tests/Feature/Team/TeamResourcesTest.php`

### Phase 8 — Comments

- [ ] `tests/Feature/Comment/CommentCrudTest.php`
- [ ] `tests/Feature/Comment/CommentReactionsTest.php`
- [ ] `tests/Feature/Comment/CommentMentionsTest.php`
- [ ] `tests/Feature/Services/CommentServiceTest.php`

### Phase 9 — Labels

- [ ] `tests/Feature/Label/LabelCrudTest.php`
- [ ] `tests/Feature/Label/LabelTemplateTest.php`
- [ ] `tests/Feature/Label/TacheLabelTest.php`
- [ ] `tests/Feature/Services/LabelServiceTest.php`
- [ ] `tests/Feature/Services/LabelTemplateServiceTest.php`

### Phase 10 — Pure service layer

- [ ] `tests/Feature/Services/ProjetServiceTest.php`
- [ ] `tests/Feature/Services/ActiviteServiceTest.php`
- [ ] `tests/Feature/Services/AuthServiceTest.php`
