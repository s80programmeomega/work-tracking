# Phase 5 Testing Guide — Chat real-time, @mentions, typing, unread badge

> Branch: `feature/phase5-chat`
> Prerequisites: `php artisan serve` + `php artisan reverb:start` + `npm run dev`

---

## Prerequisites

1. Two browser sessions (two different users, both team members) for real-time tests
2. At least one Team with at least 2 members
3. `.env` has Reverb keys configured (`REVERB_APP_KEY`, etc.)
4. Queue worker running: `php artisan queue:work`

---

## Tab 1 — Real-time messaging

### TC-01 Send a message (optimistic)
- Open `/teams/{uuid}`, click the Chat tab
- Type a message and press Send
- **Expected:** Message appears instantly (optimistic, slight opacity), then solidifies after API response

### TC-02 Real-time receive (two sessions)
- User A and User B both have the team page open
- User A sends a message
- **Expected:** Message appears in User B's chat without refresh

### TC-03 Message updated live
- User A edits their message (hover → pencil icon → edit → save)
- **Expected:** User B sees the updated content with "(modifié)" label appear in real time

### TC-04 Message deleted live
- User A deletes their message
- **Expected:** Message disappears from User B's view in real time

### TC-05 Reaction live
- User A clicks a reaction emoji on User B's message
- **Expected:** User B sees the reaction count increment in real time

---

## Tab 2 — Edit & Delete (own messages)

### TC-06 Edit inline
- Hover over own message → click pencil icon
- Modify text → press Enter or click Save
- **Expected:** Content updates, "(modifié)" label appears

### TC-07 Edit cancel
- Start editing → press Escape or click Cancel
- **Expected:** Original content restored, no changes sent

### TC-08 Delete own message
- Hover over own message → click trash icon → confirm dialog
- **Expected:** Message removed from list

### TC-09 Team owner can delete others' messages
- Log in as team owner, hover over another member's message
- **Expected:** Trash icon visible; click deletes message

### TC-10 Member cannot delete others' messages
- Log in as a regular member, hover over another member's message
- **Expected:** Only reply button visible, no delete icon

---

## Tab 3 — Reactions

### TC-11 Add quick emoji
- Hover over a message → emoji buttons appear
- Click 👍
- **Expected:** 👍 1 badge appears below message

### TC-12 Toggle own reaction off
- Click 👍 on a message where you already reacted
- **Expected:** Count decrements; badge removed if count = 0

### TC-13 Reaction visible to other members
- Add a reaction; check from another user's session
- **Expected:** Same reaction shown to all members

---

## Tab 4 — Reply-to

### TC-14 Reply to a message
- Hover over a message → click reply arrow
- **Expected:** A blue quote banner appears above the input showing the original message
- Type a reply and send
- **Expected:** Sent message shows a quote block referencing the original

### TC-15 Cancel reply
- Start a reply → click × on the quote banner
- **Expected:** Banner disappears; message sent without reply_to

---

## Tab 5 — Attachments

### TC-16 Attach a file
- Click the paperclip button → select a PDF or image
- **Expected:** File preview "📎 filename.pdf" appears above input
- Send the message
- **Expected:** Message displays clickable attachment link

### TC-17 Remove attachment before send
- Select a file → click ✕ on the preview
- **Expected:** Preview disappears; send without attachment

---

## Tab 6 — Typing indicator

### TC-18 Typing appears for other member
- User A starts typing (don't send)
- **Expected:** User B sees "A est en train d'écrire…" below the message list
- After 3 seconds of inactivity: indicator disappears

---

## Tab 7 — @mention notification

### TC-19 Mention sends notification
- Send a message with `mentions: [userId]` (via API or by wiring mention support)
- **Expected:** Mentioned user receives in-app notification + email

### TC-20 No self-notification
- Send a message mentioning yourself
- **Expected:** No notification received

---

## Tab 8 — Unread badge (sidebar)

### TC-21 Badge appears for new messages
- User B sends a message to a team while User A is not on the team page
- User A navigates away from `/teams/{uuid}`
- **Expected:** Red numeric badge appears on "Équipes" in the sidebar

### TC-22 Badge clears on visit
- User A visits the team page
- **Expected:** Badge disappears (last_read_at updated)

---

## Negative Cases

| Scenario | Expected |
|---|---|
| Regular member tries PATCH /api/teams/messages/{uuid} on another's message | 403 |
| Regular member tries DELETE on another's message | 403 |
| Non-member tries to subscribe to `private-team.{id}` channel | Auth denied |
| Send message with empty content and no attachment | Validation error |
| Attachment > 10 MB | Validation error |

---

## PHPUnit Verification

```bash
php artisan test --compact --filter=TeamChatBroadcast
```
Expected: 12 tests passing.
