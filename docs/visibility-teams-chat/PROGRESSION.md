# Progression — Visibility Refactor + Team Integration + Multi-Type Chat

**Branch:** `feature/visibility-teams-chat`  
**Started:** —  
**Last updated:** 2026-06-28

---

## Status Legend

| Symbol | Meaning |
|---|---|
| ⬜ | Not started |
| 🔄 | In progress |
| ✅ | Complete |
| ⚠️ | Blocked |

---

## Phase 1 — Visibility Full Replacement

| Step | Task | Status | Notes |
|---|---|---|---|
| 1.1 | New permission constants (`PROJETS_VIEW_ALL`, `TACHES_VIEW`), seeder, Role enum, composable, PERMISSIONS_MATRIX | ⬜ | |
| 1.2 | Drop visibility from Projet — migration, model scopes rewrite, ProjetPolicy cleanup, form requests, factory, seeder, ProjetForm.vue | ⬜ | |
| 1.3 | Drop visibility from Tache — migration, new TachePolicy, AuthServiceProvider, TacheController validation, TacheForm.vue | ⬜ | |
| 1.4 | Drop visibility from Document — migration, DocumentPolicy public-bypass removal, DocumentAccessResolver, DocumentController, DocumentCard.vue, DocumentFactory | ⬜ | |
| 1.5 | Drop visibility from Team — migration, Team model, TeamController, TeamFactory | ⬜ | |
| 1.6 | Workspace settings cleanup — StoreWorkspaceRequest, UpdateWorkspaceRequest, Workspace defaults | ⬜ | |
| 1.7 | Tests — ProjetAccessTest (replace ProjetVisibilityTest), TachePolicyTest, DocumentAccessTest, Dusk ProjetAccessTest | ⬜ | |
| — | **Testing guide written** → `docs/testing/PHASE1_VISIBILITY_TESTING.md` | ⬜ | |
| — | **Pint + Larastan + full test suite green** | ⬜ | |

---

## Phase 2 — Team Integration at Project Level

| Step | Task | Status | Notes |
|---|---|---|---|
| 2.1 | Migration `use_teams`, Projet model fillable + cast, UpdateProjetRequest | ⬜ | |
| 2.2 | `PROJETS_MANAGE_TEAMS` permission — Permission.php, seeder, Role enum, ProjetPolicy::manageTeams(), composable, PERMISSIONS_MATRIX | ⬜ | |
| 2.3 | New API endpoints — linkTeam, unlinkTeam, toggleUseTeams, linkedTeams, memberCandidates, assigneeCandidates, intervenantCandidates; new routes | ⬜ | |
| 2.4 | Observer/events — TeamLinkedToProject, TeamUnlinkedFromProject, TeamProjectObserver, AppServiceProvider registration; TeamController member add/remove sync | ⬜ | |
| 2.5 | Notifications — TeamLinkedToProjectNotification, TeamMemberAutoAddedNotification, NotificationService event keys, i18n | ⬜ | |
| 2.6 | Frontend — ProjetForm.vue use_teams toggle + linked teams panel, useProjets.js, useActivites.js, useTaches.js, IntervenantPicker.vue candidates prop, Teams/Show.vue badge, i18n | ⬜ | |
| 2.7 | Tests — TeamProjectIntegrationTest.php (10+), Dusk TeamProjectIntegrationTest.php with screenshots | ⬜ | |
| — | **Testing guide written** → `docs/testing/PHASE2_TEAM_INTEGRATION_TESTING.md` | ⬜ | |
| — | **Pint + Larastan + full test suite green** | ⬜ | |

---

## Phase 3 — Multi-Type Chat

| Step | Task | Status | Notes |
|---|---|---|---|
| 3.1 | Migrations — workspace_channels, workspace_messages, workspace_message_reactions, workspace_channel_reads | ⬜ | |
| 3.2 | Models + service — WorkspaceChannel, WorkspaceMessage (Searchable), WorkspaceMessageReaction, WorkspaceMessageService, Workspace::channels(), WorkspaceObserver | ⬜ | |
| 3.3 | Artisan command `workspace:seed-channels` for existing workspaces | ⬜ | |
| 3.4 | Broadcast events — WorkspaceMessageSent, WorkspaceMessageUpdated, WorkspaceMessageDeleted, WorkspaceReactionChanged | ⬜ | |
| 3.5 | Channel authorization — `routes/channels.php` responsibles + global auth guards | ⬜ | |
| 3.6 | API controller + routes — WorkspaceChatController (10 methods), StoreWorkspaceMessageRequest, UpdateWorkspaceMessageRequest, all routes | ⬜ | |
| 3.7 | API Resources — WorkspaceChannelResource, WorkspaceMessageResource | ⬜ | |
| 3.8 | Frontend composable — `useWorkspaceMessages.js` (subscribe, send, edit, delete, pin, react, markRead, typing whisper) | ⬜ | |
| 3.9 | Frontend UI — WorkspaceChat.vue (sidebar + message panel + composer), router entries, AppSidebar.vue Chat section with unread badge | ⬜ | |
| 3.10 | Notifications — WorkspaceMessageMentionNotification, NotificationService event keys, i18n (lang/fr/chat.php + lang/en/chat.php) | ⬜ | |
| 3.11 | Tests — WorkspaceChatTest.php (15+), Dusk WorkspaceChatTest.php with screenshots | ⬜ | |
| — | **Testing guide written** → `docs/testing/PHASE3_WORKSPACE_CHAT_TESTING.md` | ⬜ | |
| — | **Pint + Larastan + full test suite green** | ⬜ | |
| — | **`php artisan scribe:generate` run, output committed** | ⬜ | |

---

## Final Checklist (all phases done)

- [ ] All migrations run cleanly on a fresh database
- [ ] `php artisan migrate:fresh --seed` succeeds without errors
- [ ] `php artisan test --compact` → all green
- [ ] `vendor/bin/phpstan analyse` → zero errors
- [ ] `npm run build` → no errors
- [ ] `docs/PERMISSIONS_MATRIX.md` up to date
- [ ] All three testing guides written in `docs/testing/`
- [ ] `docs/SESSION_STATE.md` updated
- [ ] `docs/PROGRESSION.md` (main) row added
- [ ] Branch pushed to both `origin` and `client`
