# Phase 3 Testing Guide — Multi-Type Workspace Chat

**Task:** Add Workspace Responsibles Chat and Global Workspace Chat alongside existing Team Chat.  
**Branch:** `feature/visibility-teams-chat`  
**Test file:** `tests/Feature/Chat/WorkspaceChatTest.php`  
**Status:** ✅ All tests passing (2026-06-28)

---

## Test Coverage

### Channel Auto-Creation (`seed_channels_command_creates_both_channels_for_workspace`)
- `workspace:seed-channels --workspace={id}` creates `global` and `responsibles` channels.
- `WorkspaceObserver::created()` uses `firstOrCreate` — idempotent on repeated calls.

### Channel Listing
- `member_can_list_workspace_channels` — workspace member gets both channels in `/api/workspaces/{id}/chat/channels`.
- `non_member_cannot_list_channels` — 403 for users not in the workspace.

### Global Channel — all members
- `any_member_can_send_to_global_channel` — collaborateur can POST to `channels/global/messages`.
- `member_can_list_global_messages` — any workspace member can read the global feed.

### Responsibles Channel — cadre+
- `cadre_can_send_to_responsibles_channel` — cadre role can send to `channels/responsibles/messages`.
- `collaborateur_cannot_send_to_responsibles_channel` — 403 for collaborateur role.

### Message Edit
- `author_can_update_their_message` — PATCH sets `content` and `is_edited = true`.
- `non_author_cannot_update_message` — 403 for non-author members.

### Message Delete
- `author_can_delete_their_message` — soft-deleted (assertSoftDeleted).
- `manager_can_delete_any_message` — manager role can delete any message in the workspace.

### Reactions
- `member_can_add_reaction` — POST `/messages/{uuid}/reactions` with `emoji`.
- `member_can_remove_reaction` — DELETE removes the reaction row.

### Read Tracking
- `member_can_mark_channel_as_read` — POST `/channels/global/read` upserts `workspace_channel_reads`.

### Unread Counts
- `unread_count_returns_correct_structure` — GET `/chat/unread` returns `{responsibles, global, total}`.

### Mention Notification
- `mention_dispatches_notification_to_mentioned_user` — sending a message with `mentions[]` dispatches `WorkspaceMessageMentionNotification` to the named user.

### Pin / Unpin
- `manager_can_pin_message` — POST `/messages/{uuid}/pin` sets `is_pinned = true`.
- `collaborateur_cannot_pin_message` — 403 for non-manager roles.

---

## Setup Notes

- `WorkspaceObserver` is registered in `AppServiceProvider` and fires on `Workspace::created`.
- In `RefreshDatabase` tests, the observer fires when `Workspace::factory()->create()` is called — channels are created automatically. **Do not** create channels manually in `setUp`; retrieve them with `firstOrFail()` after factory creation.
- Channels use a `(workspace_id, type)` unique constraint — named `wc_workspace_type_unique` to stay under MySQL's 64-char identifier limit.
- The `workspace_message_reactions` unique constraint is named `wmr_message_user_emoji_unique` for the same reason.

---

## Routes

```
GET    /api/workspaces/{workspace}/chat/channels
GET    /api/workspaces/{workspace}/chat/channels/{type}/messages
POST   /api/workspaces/{workspace}/chat/channels/{type}/messages
POST   /api/workspaces/{workspace}/chat/channels/{type}/read
GET    /api/workspaces/{workspace}/chat/unread
PATCH  /api/workspaces/{workspace}/chat/messages/{uuid}
DELETE /api/workspaces/{workspace}/chat/messages/{uuid}
POST   /api/workspaces/{workspace}/chat/messages/{uuid}/reactions
DELETE /api/workspaces/{workspace}/chat/messages/{uuid}/reactions
POST   /api/workspaces/{workspace}/chat/messages/{uuid}/pin
```

---

## Running the Tests

```bash
php artisan test --compact tests/Feature/Chat/WorkspaceChatTest.php
```

Expected: **18 passed**, 0 failed.
