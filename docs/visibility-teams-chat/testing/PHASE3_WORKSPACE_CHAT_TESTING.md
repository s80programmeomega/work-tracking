# Phase 3 Testing Guide — Multi-Type Workspace Chat

**Task:** Add Workspace Responsibles Chat and Global Workspace Chat alongside existing Team Chat.  
**Branch:** `feature/visibility-teams-chat`  
**Status:** ⬜ To be completed after Phase 3 implementation

---

## Prerequisites

1. Run `php artisan migrate` (Phase 3 migrations)
2. Run `php artisan workspace:seed-channels` (seeds channels for existing workspaces)
3. Start Laravel Reverb: `php artisan reverb:start`
4. Start the queue: `php artisan queue:work`
5. Start the dev server: `php artisan serve`
6. Start Vite: `npm run dev`
7. Have 3 browser sessions ready: one `directeur`, one `cadre`, one `collaborateur`

---

## Test Cases

### TC-3.1 — Workspace channels auto-created for new workspace

**Action:** Create a brand new workspace as a `directeur` user.  
**Expected:** Two `workspace_channels` records exist: one with `type=responsibles`, one with `type=global`.  
**How to verify:** `php artisan tinker` → `WorkspaceChannel::where('workspace_id', $id)->get()` — 2 records.

### TC-3.2 — Chat nav section visible in sidebar

**Action:** Log in as any workspace member. Check the left sidebar.  
**Expected:** A "Chat" section appears with: "Global" link and a "Teams" sub-list. "Responsables" link visible only for directeur/manager/cadre.  
**How to verify:** Visual inspection; sidebar shows Chat section.

### TC-3.3 — Responsibles tab hidden for collaborateur

**Action:** Log in as a `collaborateur`. Open the Chat section in the sidebar.  
**Expected:** No "Responsables" tab or link visible.  
**How to verify:** Tab absent from sidebar and from WorkspaceChat.vue channel list.

### TC-3.4 — Collaborateur cannot send to responsibles channel via API

**Action:** Log in as `collaborateur`. Try `POST /api/workspaces/{id}/channels/responsibles/messages` with `{"content": "hello"}`.  
**Expected:** 403 Forbidden.  
**How to verify:** Network tab shows 403.

### TC-3.5 — Cadre CAN send to responsibles channel

**Action:** Log in as `cadre`. Navigate to `/workspace/chat`. Click "Responsables". Type and send a message.  
**Expected:** Message appears in the Responsables chat. Other cadre/manager/directeur users see it appear in real-time.  
**How to verify:** Message visible; second browser session (directeur) receives it without page refresh.

### TC-3.6 — Global channel: any workspace member can send

**Action:** Log in as `collaborateur`. Navigate to `/workspace/chat`. Click "Global". Type and send a message.  
**Expected:** Message appears for all workspace members in real-time.  
**How to verify:** Second browser session (cadre) sees the message appear without refresh.

### TC-3.7 — Real-time delivery (Reverb)

**Action:** Open two browser tabs for two different workspace members. Tab 1 sends a message to Global.  
**Expected:** Tab 2 receives the message within ~1 second, without refreshing.  
**How to verify:** Watch Tab 2 — message appears automatically.

### TC-3.8 — @mention triggers notification

**Action:** Send a message in Global that mentions "@UserX" (e.g., `"Bonjour @UserX, merci"`).  
**Expected:** UserX receives an in-app notification (bell badge increments). If UserX has email enabled for `workspace_chat_mention`, an email is sent.  
**How to verify:** Log in as UserX — notification bell shows 1 new notification; clicking shows mention preview.

### TC-3.9 — Unread count badge in sidebar

**Action:** UserX is offline. UserY sends 3 messages in Global.  
**Expected:** When UserX logs in, the sidebar shows a badge "3" next to "Global".  
**How to verify:** Sidebar badge count matches unread messages.

### TC-3.10 — Unread count clears on markRead

**Action:** UserX opens the Global chat (auto-calls markRead).  
**Expected:** Unread badge on "Global" disappears.  
**How to verify:** Badge gone after opening the channel.

### TC-3.11 — Message edit by author

**Action:** Send a message, then click the edit button. Modify the text and save.  
**Expected:** Message shows updated content + "(modifié)" indicator.  
**How to verify:** Visual change visible to sender and other members (real-time update).

### TC-3.12 — Message edit blocked for non-author

**Action:** Try `PATCH /api/workspaces/{id}/messages/{uuid}` as a user who did NOT send the message.  
**Expected:** 403 Forbidden.  
**How to verify:** Network tab shows 403.

### TC-3.13 — Pin requires manager+

**Action:** Log in as `cadre`. Try to pin a message in Global via the UI or API.  
**Expected:** Pin button absent for cadre. API call returns 403.  
**How to verify:** No pin icon visible; API 403.

**Action:** Log in as `manager`. Pin the same message.  
**Expected:** Message shows a pin indicator for all channel members.  
**How to verify:** Pin icon visible on message.

### TC-3.14 — Emoji reactions

**Action:** Click the emoji reaction button on a message. Select 👍.  
**Expected:** Reaction count shows `👍 1`. Another user can add the same emoji — count becomes `👍 2`. Clicking again removes own reaction.  
**How to verify:** Visual count change; removing reaction decrements count.

### TC-3.15 — Reply-to reference

**Action:** Click "Reply" on a message. Send a reply.  
**Expected:** Reply message shows a quote of the original message above the content.  
**How to verify:** Visual quote block present in the reply bubble.

### TC-3.16 — File attachment in message

**Action:** Attach a file (< workspace storage limit) to a Global message.  
**Expected:** File attached; message shows a download link. File size counted against workspace storage quota.  
**How to verify:** Attachment link visible; `SubscriptionService::summary()` shows updated storage used.

### TC-3.17 — Teams sub-list in Chat sidebar

**Action:** Navigate to `/workspace/chat`. Check the Teams sub-list in the left sidebar.  
**Expected:** All workspace teams where user is a member are listed. Clicking one navigates to `Teams/Show.vue` (the existing team chat tab).  
**How to verify:** Team name visible; clicking opens existing team page.

---

## Negative Cases

| Scenario | Expected result |
|---|---|
| Non-workspace member tries to access global channel API | 403 |
| Collaborateur posts to responsibles channel | 403 |
| Message with file exceeding storage limit | 422 with clear error message |
| User mentions themselves | Notification NOT sent to self (NotificationService::sendUnlessSelf behavior) |
| Delete message as non-author | 403 |

---

## Dusk Browser Tests

```bash
php artisan serve &
php artisan reverb:start &
php artisan dusk tests/Browser/WorkspaceChat/WorkspaceChatTest.php
```

Verify screenshots saved in `tests/Browser/screenshots/` for:
- Global chat with a message sent and visible
- Responsibles tab hidden for collaborateur (screenshot of sidebar)
- @mention notification badge increment
- Unread count badge visible, then cleared after opening channel

---

## Cleanup

If testing on shared database: clear test messages via tinker or rollback migrations. Otherwise nothing to clean up — features are permanent.
