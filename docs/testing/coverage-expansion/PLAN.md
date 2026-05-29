# Test Coverage Expansion — Plan

> Branch: `chore/test-coverage-expansion` — single rolling branch.
> Each phase below ships as one PR back into `jonas` (Guide 1).

---

## Context

PHPUnit coverage on this codebase sits at roughly 20% of the API surface. Most of the gap is concentrated in the major CRUD controllers (Projet, Activité, Tâche, Workspace, Document, Team), the entire policy layer, and several services. The `feature/design-system-v1` work shipped on 2026-05-29 highlighted the cost of that gap: the project visibility enforcement was only safe to land because the `ProjetVisibilityTest` was written in the same pass. Other recent commits did not get that protection.

This document is the rollout plan for closing the gap, in priority order. It is derived from a gap analysis run on 2026-05-29 against the test list at that date.

**Out of scope**: AdminController tests — the admin panel is in flux and tests would race the implementation. Revisit after the admin feature stabilises.

**Companion file**: [`PROGRESSION.md`](./PROGRESSION.md) tracks status per phase. Update it at the end of every phase.

---

## Cross-cutting conventions for every phase

- **Base class**: extend `Tests\TestCase` with `Illuminate\Foundation\Testing\RefreshDatabase`.
- **Trait**: `Tests\Traits\AttachesWithRoleId` for any pivot row that uses a `role_id` FK.
- **Seeder**: every test class's `setUp()` runs `$this->artisan('db:seed', ['--class' => 'RolePermissionSeeder'])` and calls `$this->refreshRoleIdCache()`.
- **Factories only**: never hand-craft a model row when a factory exists. Missing factories are created in the phase that needs them, not retroactively.
- **No mocking the database**: integration tests against the real schema, matching the existing suite style.
- **Run filter after each change**: `php artisan test --compact --filter=<TestName>`, then the full file before pushing.
- **Pint before every commit**: `vendor/bin/pint --dirty --format agent` (Guide 11 + CLAUDE.md).
- **One PR per phase** back into `jonas` from `chore/test-coverage-expansion`. PR description lists the phase scope and ticks off rows in `PROGRESSION.md`.
- **End-of-phase progression update**: tick the phase row in `PROGRESSION.md` with commit hash, completion date, PHPUnit running total.
- **Language**: French log/test/comment messages where applicable (Guides 17 + 19). Identifier-style values stay English.

---

## Phases (priority order)

Targets are estimates — measure them after the fact.

### Phase 1 — Policy guards (foundation, ~30 tests)

**Why first**: policies are the choke point every other controller test depends on. If `ProjetPolicy::view` is broken, every subsequent projet test gives misleading 403s. Lock the gates before testing what passes through them.

| File | Targets |
|---|---|
| `tests/Feature/Policies/ProjetPolicyTest.php` | `view` (5 cases — reuses visibility test pattern), `update`, `delete`, `manageMembers`. Use the real `ContextualPermissionGate` — do not mock. |
| `tests/Feature/Policies/TachePolicyTest.php` | `view`, `update`, `delete`, `validateN1`, `validateN2`. Cover `tache_user.is_responsable` virtual-role path. |
| `tests/Feature/Policies/ActivitePolicyTest.php` | `view`, `update`, `delete`. Member-with-permission flags vs. responsable inheritance. |
| `tests/Feature/Policies/WorkspacePolicyTest.php` | `view`, `update`, `delete`, `manageMembers`. Owner / owner-or-admin paths. |
| `tests/Feature/Policies/DocumentPolicyTest.php` | `view`, `download`, `update`, `delete`. Defers to `DocumentAccessResolver` — assert the delegation. |

**Reuse**: `Tests\Traits\AttachesWithRoleId`, `RolePermissionSeeder`, `Projet/Workspace/Activite/Tache` factories.

### Phase 2 — Projet CRUD, members, stats (~25 tests)

**Files**: `tests/Feature/Projet/ProjetCrudTest.php`, `ProjetMembersTest.php`, `ProjetStatsTest.php`.

**Targets**: 21 currently-untested `ProjetController` methods — `store`, `update`, `destroy`, `archive`, `unarchive`, `complete`, `clone`, `toggleFavorite`, `getMembers`, `addMember`, `updateMember`, `removeMember`, `getMemberRemovalImpact`, `removeMemberWithTransfer`, `getActivites`, `getTaches`, `getStatistics`, `dashboardStats`, `performanceReport`, `accessibleTasks`, `accessible`.

**Critical paths**: `removeMemberWithTransfer` reassigns owned activités/tâches — test reassignment correctness, not just HTTP 200.

### Phase 3 — Activité CRUD, members, kanban (~25 tests)

**Files**: `tests/Feature/Activite/ActiviteCrudTest.php`, `ActiviteMembersTest.php`, `ActiviteKanbanTest.php`.

**Targets**: 18 untested `ActiviteController` methods — `kanban`, `reorder`, `duplicate`, `archive`/`unarchive`, `availableMembers`, `addMember`, `updateMember`, `removeMember`, `changeResponsable`, `getTaches`, etc.

### Phase 4 — Task gaps (~20 tests)

**Files**: `tests/Feature/Tache/TacheAttachmentsTest.php`, `TacheExternalLinksTest.php`, `TacheMoveTest.php`, `TacheResponsableTest.php`.

**Targets**: `addAttachments`, `getAttachments`, `downloadAttachment`, `deleteAttachment`, `addExternalLink`, `deleteExternalLink`, `move`, `moveMyCard`, `changeResponsable`, `assignResponsable`, `removeResponsable`, `createSubTask`, `subTasks`.

**Note**: attachments use `Storage::fake()` — covered well by Laravel's testing layer. `move` between activités triggers cross-projet permission checks worth asserting.

### Phase 5 — Workspace management (~30 tests)

**Files**: `tests/Feature/Workspace/WorkspaceCrudTest.php`, `WorkspaceMemberRemovalTest.php`, `WorkspaceInvitationsTest.php`, `WorkspaceSubscriptionTest.php`.

**Targets**: 30 untested `WorkspaceController` methods — `update`, `destroy`, `archive`, `unarchive`, `transferOwnership`, `duplicate`, `members`, `showMember`, `updateMemberRole`, `removeMember`, `removeMemberWithTransfer`, `getRemovalPreview`, `invitations`, `resendInvitation`, `cancelInvitation`, `allInvitations`, `invitationStatistics`, `subscriptionSummary`, `updateSubscription`, `getUserProjects`, `getTransferCandidates`, `activityLog`, `projets`, `statistics`, `switch`.

**Critical paths**: `transferOwnership` and `removeMemberWithTransfer` reassign large object graphs and have been the source of past bugs.

### Phase 6 — Document management (~30 tests)

**Why before Team/Comment**: `DocumentAccessResolver` is the most complex untested access logic in the codebase.

**Files**:
- `tests/Feature/Services/DocumentAccessResolverTest.php` — `canView`, `canDownload`, `canEdit`, `canDelete`, `canShare`, `canUpload` × Workspace/Projet/Activité/Tâche/Resultat parents. Consider data-providers to keep the file readable.
- `tests/Feature/Document/DocumentCrudTest.php` — `store`, `show`, `download`, `destroy`, `createVersion`, `versions`.
- `tests/Feature/Document/DocumentPermissionsTest.php` — `grantPermission`, `revokePermission`, `shareWithUsers`, `listPermissions`, `shareByEmail`.
- `tests/Feature/Document/DocumentQueriesTest.php` — `recent`, `sharedWithMe`, `myDocuments`, `hierarchy`, `search`, `stats`, `workspaceDocuments`, `workspaceStats`, `globalStats`.

### Phase 7 — Team features (~25 tests)

**Files**: `tests/Feature/Team/TeamCrudTest.php`, `TeamMembersTest.php`, `TeamMessagesTest.php`, `TeamAnnouncementsTest.php`, `TeamEventsTest.php`, `TeamResourcesTest.php`, `tests/Feature/Services/TeamServiceTest.php`.

**Targets**: all of `TeamController` (14), `TeamMemberController` (6), `TeamMessageController` (4), `TeamAnnouncementController` (5), `TeamEventController` (5), `TeamResourceController` (5), plus `TeamService` (~10 methods).

**Note**: create `TeamFactory` and `TeamMemberFactory` in this phase if still missing.

### Phase 8 — Comments (~15 tests)

**Files**: `tests/Feature/Comment/CommentCrudTest.php`, `CommentReactionsTest.php`, `CommentMentionsTest.php`, `tests/Feature/Services/CommentServiceTest.php`.

**Targets**: 11 `CommentController` methods + `CommentService`. Cover mention notifications (calls `NotificationService::channelsFor`).

### Phase 9 — Labels (~20 tests)

**Files**: `tests/Feature/Label/LabelCrudTest.php`, `LabelTemplateTest.php`, `TacheLabelTest.php`, `tests/Feature/Services/LabelServiceTest.php`, `LabelTemplateServiceTest.php`.

**Targets**: `LabelController` (9), `LabelTemplateController` (11), `TacheLabelController` (6), plus their services.

### Phase 10 — Pure service layer (~20 tests)

**Files**: `tests/Feature/Services/ProjetServiceTest.php`, `ActiviteServiceTest.php`, `AuthServiceTest.php`.

**Targets**: business logic in services that controller tests already exercise indirectly. Useful for permutations that are hard to set up through HTTP — e.g. `ProjetService::getUserProjets` with combinations of filters, `AuthService::refreshToken` edge cases.

---

## Risks / notes

- **Phase 6 is the largest** — five parent entity types × six action verbs = 30 combinations.
- **Phase 5 transfer logic** is historically buggy. Highest catch-bug-now expected value.
- **Phases are independent** — reorder if priorities shift. Current sequence is risk-weighted, not dependency-weighted.
- **No Dusk tests** — Guide 18 only requires Dusk for UI-touching tasks. These are pure backend coverage tests. If a phase uncovers a UI bug that needs a Dusk regression test, add it per Guide 18 in the same PR.
- **No new factories anticipated** except for Phase 7 (Team*). Create on demand.
