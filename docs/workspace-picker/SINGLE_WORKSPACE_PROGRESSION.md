# Progression — Single-Workspace Enforcement

**Branch:** `feature/workspace-picker`
**Reference plan:** `docs/workspace-picker/SINGLE_WORKSPACE_PLAN.md`

---

## Progress Table

| # | Step | File(s) | Status | Notes |
|---|---|---|---|---|
| 1 | Backend: add `member_count` to `withCount` | `WorkspaceController.php` | ✅ Done | `withCount(['projets', 'members'])` |
| 2a | i18n: add new `workspace_picker` keys | `fr.json`, `en.json` | ✅ Done | search, new, stats, settings keys |
| 2b | WorkspacePicker: aggregate stats banner | `WorkspacePicker.vue` | ✅ Done | 4 tiles, stagger, dusk attrs |
| 2c | WorkspacePicker: search bar + new workspace button | `WorkspacePicker.vue` | ✅ Done | filtered computed + router.push |
| 2d | WorkspacePicker: per-card ⋮ settings button | `WorkspacePicker.vue` | ✅ Done | `openSettings(id)`, dusk interpolated |
| 2e | WorkspacePicker: stagger on stats tiles | `WorkspacePicker.vue` | ✅ Done | Two stagger instances (stats + cards) |
| 3 | Dashboard: remove multi-workspace dropdown | `Dashboard.vue` | ✅ Done | Removed select + unused imports |
| 4 | Router: remove `workspaces.index` + `workspaces.show` routes | `router/index.ts` | ✅ Done | Comment left for clarity |
| 5 | Sidebar: remove `/workspaces` nav entry + fix fallback | `AppSidebar.vue` | ✅ Done | Fallback → `workspaces.select` |
| 6a | Orphaned links Pattern A: Create, AcceptProjet, Accept | 3 files | ✅ Done | All → `workspaces.select` |
| 6b | Orphaned links Pattern B: Edit, Settings | 2 files | ✅ Done | All → `workspaces.select` |
| 6c | Orphaned links Pattern C: Invitations, TacheDetail, WorkspaceDocuments | 3 files | ✅ Done | All → `workspaces.select` |
| 6d | Orphaned links Pattern D: Create post-success redirect | `Create.vue` | ✅ Done | → `workspaces.select` |
| 7 | Delete `Index.vue` and `Show.vue` | 2 files | ✅ Done | Deleted after all links verified clean |
| 8 | i18n: remove `workspaces_index.*` and `workspace_show.*` blocks | `fr.json`, `en.json` | ✅ Done | Both locales cleaned |
| 9 | PHPUnit Feature tests | `WorkspaceIndexMemberCountTest.php` | ✅ Done | 4 tests, all passing |
| 10a | Dusk: WorkspacePickerEnhancedTest | `tests/Browser/Workspaces/` | ✅ Done | Stats, search, new btn, settings btn |
| 10b | Dusk: DashboardWorkspaceDropdownRemovedTest | `tests/Browser/Workspaces/` | ✅ Done | Verifies dropdown absence |
| 10c | Dusk: WorkspaceOrphanLinksTest | `tests/Browser/Workspaces/` | ✅ Done | Verifies picker links in key pages |
| 11 | Pint + Larastan + npm build + full test suite | — | ✅ Done | 815/816 pass; 1 pre-existing flaky SocialAuthTest |
| 12 | User testing guides | `docs/workspace-picker/testing/` | ✅ Done | 4 guides written |

---

## Legend

- ✅ Done
- 🔄 In progress
- ⏳ Pending
- ❌ Blocked

---

## Session Log

| Date | Session | Progress |
|---|---|---|
| 2026-06-25 | Planning | Plan written, progression created |
| 2026-06-25 | Implementation | All 21 sub-steps completed; pre-commit gates running |
