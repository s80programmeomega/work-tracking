# Phase 5 — Chat real-time, @mentions, typing indicator, unread badge, attachments

> Branch: `feature/phase5-chat` → merged into `jonas` 2026-06-04
> Status: ✅ Complete

---

## Context

Phase 5 completes the team chat system. The data layer was already built (TeamMessage, TeamMessageReaction, TeamActivity models, TeamMessageService with full CRUD). What was missing: broadcasting, real-time frontend, @mention notifications, message edit/delete UI, attachment UI, typing indicator, unread badge.

---

## Architecture decisions

**Broadcasting:** `ShouldBroadcastNow` (synchronous, lower latency than queued) — consistent with existing events in `app/Events/Realtime/`.

**Channel:** `team.{teamId}` private channel, authorized in `routes/channels.php` via `Team::members()`.

**Unread badge:** `last_read_at` nullable timestamp added to `team_members` pivot (migration). `POST /api/teams/{uuid}/read` updates it. `GET /api/teams/unread-total` returns aggregate count for sidebar badge. Server-authoritative — no localStorage.

---

## What was built

### Backend
- **4 broadcast events** in `app/Events/Realtime/Chat/`: `MessageSent`, `MessageUpdated`, `MessageDeleted`, `ReactionChanged`
- **`routes/channels.php`** — `team.{teamId}` channel auth
- **Migration** — `last_read_at` on `team_members`
- **`TeamMessageResource`** — reactions grouped `{emoji, count, did_react}`, reply_to snippet, attachments
- **`TeamMessageController`** — +5 methods: `update`, `destroy`, `pinned`, `togglePin`, `removeReaction`, `markRead`
- **`StoreTeamMessageRequest` + `UpdateTeamMessageRequest`** form requests
- **`TeamMessageService`** — events dispatched after each mutation; @mention TODO replaced with `ChatMentionNotification`; `markTeamRead()` added
- **`ChatMentionNotification`** — ShouldQueue, `chat_mention` event key, in-app + email
- **`NotificationService`** — `chat_mention` added to `wantsEmail()` + `wantsWebPush()`
- **`TeamController::totalUnread()`** + `GET /api/teams/unread-total` route

### Frontend
- **`useTeamMessages.js`** — full rewrite: Echo private channel subscription, live handlers (`MessageSent/Updated/Deleted/ReactionChanged`), optimistic send, typing whisper (2 s throttle, 3 s auto-clear), edit/delete/reactions, `markRead`
- **`Teams/Show.vue`** — stagger on message list, per-message hover actions (reply, edit own, delete own/owner), inline edit textarea, quick emoji picker, reaction toggle with optimistic update, reply-to banner, attachment file input, typing indicator, `markRead` on mount
- **`AppSidebar.vue`** — unread badge on Teams nav item
- **15 i18n keys** in `team_show` section (fr + en)

### Tests
- 12 PHPUnit in `tests/Feature/Chat/TeamChatBroadcastTest.php`
- 3 Dusk in `tests/Browser/Teams/TeamChatTest.php`
- Testing guide: `docs/extended-features/testing/TASK_PHASE5_TESTING.md`

---

## New API routes

```
PATCH  /api/teams/messages/{uuid}         → update()
DELETE /api/teams/messages/{uuid}         → destroy()
POST   /api/teams/messages/{uuid}/pin     → togglePin()
POST   /api/teams/messages/{uuid}/reactions → addReaction()
DELETE /api/teams/messages/{uuid}/reactions → removeReaction()
POST   /api/teams/{uuid}/read             → markRead()
GET    /api/teams/unread-total            → totalUnread()
```
