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
| P1 | Policy guards (5 policies) | ~30 | — | ⬜ | — | — | — |
| P2 | Projet CRUD + members + stats | ~25 | — | ⬜ | — | — | — |
| P3 | Activité CRUD + members + kanban | ~25 | — | ⬜ | — | — | — |
| P4 | Task gaps (attachments, links, move, responsable) | ~20 | — | ⬜ | — | — | — |
| P5 | Workspace management (CRUD, transfer, invitations, subscription) | ~30 | — | ⬜ | — | — | — |
| P6 | Document management (resolver + controller) | ~30 | — | ⬜ | — | — | — |
| P7 | Team features (controllers + service) | ~25 | — | ⬜ | — | — | — |
| P8 | Comments (controller + reactions + mentions) | ~15 | — | ⬜ | — | — | — |
| P9 | Labels (label + template + tache_label) | ~20 | — | ⬜ | — | — | — |
| P10 | Pure service layer (Projet/Activite/Auth) | ~20 | — | ⬜ | — | — | — |

**Cumulative target**: ~240 new tests on top of the existing 65 (≈ 305 total).

---

## Per-phase detail

### Phase 1 — Policy guards

- [ ] `tests/Feature/Policies/ProjetPolicyTest.php` — view, update, delete, manageMembers
- [ ] `tests/Feature/Policies/TachePolicyTest.php` — view, update, delete, validateN1, validateN2
- [ ] `tests/Feature/Policies/ActivitePolicyTest.php` — view, update, delete
- [ ] `tests/Feature/Policies/WorkspacePolicyTest.php` — view, update, delete, manageMembers
- [ ] `tests/Feature/Policies/DocumentPolicyTest.php` — view, download, update, delete (delegates to DocumentAccessResolver)

### Phase 2 — Projet CRUD + members + stats

- [ ] `tests/Feature/Projet/ProjetCrudTest.php` — store, update, destroy, archive, unarchive, complete, clone, toggleFavorite
- [ ] `tests/Feature/Projet/ProjetMembersTest.php` — getMembers, addMember, updateMember, removeMember, getMemberRemovalImpact, removeMemberWithTransfer
- [ ] `tests/Feature/Projet/ProjetStatsTest.php` — getActivites, getTaches, getStatistics, dashboardStats, performanceReport, accessibleTasks, accessible

### Phase 3 — Activité CRUD + members + kanban

- [ ] `tests/Feature/Activite/ActiviteCrudTest.php`
- [ ] `tests/Feature/Activite/ActiviteMembersTest.php`
- [ ] `tests/Feature/Activite/ActiviteKanbanTest.php`

### Phase 4 — Task gaps

- [ ] `tests/Feature/Tache/TacheAttachmentsTest.php`
- [ ] `tests/Feature/Tache/TacheExternalLinksTest.php`
- [ ] `tests/Feature/Tache/TacheMoveTest.php`
- [ ] `tests/Feature/Tache/TacheResponsableTest.php`

### Phase 5 — Workspace management

- [ ] `tests/Feature/Workspace/WorkspaceCrudTest.php`
- [ ] `tests/Feature/Workspace/WorkspaceMemberRemovalTest.php`
- [ ] `tests/Feature/Workspace/WorkspaceInvitationsTest.php`
- [ ] `tests/Feature/Workspace/WorkspaceSubscriptionTest.php`

### Phase 6 — Document management

- [ ] `tests/Feature/Services/DocumentAccessResolverTest.php`
- [ ] `tests/Feature/Document/DocumentCrudTest.php`
- [ ] `tests/Feature/Document/DocumentPermissionsTest.php`
- [ ] `tests/Feature/Document/DocumentQueriesTest.php`

### Phase 7 — Team features

- [ ] `database/factories/TeamFactory.php` (if missing)
- [ ] `database/factories/TeamMemberFactory.php` (if missing)
- [ ] `tests/Feature/Team/TeamCrudTest.php`
- [ ] `tests/Feature/Team/TeamMembersTest.php`
- [ ] `tests/Feature/Team/TeamMessagesTest.php`
- [ ] `tests/Feature/Team/TeamAnnouncementsTest.php`
- [ ] `tests/Feature/Team/TeamEventsTest.php`
- [ ] `tests/Feature/Team/TeamResourcesTest.php`
- [ ] `tests/Feature/Services/TeamServiceTest.php`

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
