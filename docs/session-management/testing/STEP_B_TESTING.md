# Step B Testing — Auto-Disconnect Timer

## What was fixed

| # | Bug | Fix |
|---|-----|-----|
| B1 | `setTimeoutDuration` used `minutes * 1 * 1000` (ms, not min) | Changed to `minutes * 60 * 1000` |
| B2 | "Never" (0) caused immediate logout overlay | 0 is now mapped to `Infinity` sentinel |
| B3 | Setting lost on page reload (`initialize()` deleted it) | `initialize()` now restores saved value |
| B4 | Countdown display never updated (empty `setInterval`) | Reactive `ref(Date.now())` ticked every second |
| B5 | "Never" had no i18n key | Added `session_settings.never` in fr/en |

---

## Manual Test Cases

### TC-B-01 — Timer set to 15 minutes is actually 15 minutes

**Steps:**
1. Sign in and open Profile → Security tab.
2. Click the "15 min" button in the timeout grid.
3. Watch the "Déconnexion dans" countdown.

**Expected:** Countdown reads approximately "15 min" (not "15 sec").

---

### TC-B-02 — Timer set to 30 minutes is actually 30 minutes

**Steps:**
1. Click "30 min" in the timeout grid.
2. Watch the countdown.

**Expected:** Countdown reads approximately "30 min".

---

### TC-B-03 — Countdown updates every second

**Steps:**
1. Select any timeout (e.g. 15 min).
2. Remain on the Security tab for 10 seconds.

**Expected:** The "Déconnexion dans" value counts down in real time (changes every second near the end, every minute when far away).

---

### TC-B-04 — "Jamais" option disables auto-disconnect

**Steps:**
1. Click "Jamais" in the timeout grid.
2. Check the countdown display.

**Expected:** Countdown shows "Jamais". Leave the tab idle for several minutes — no warning overlay appears, no logout occurs.

---

### TC-B-05 — Timer setting persists across page reloads

**Steps:**
1. Select "30 min" in the timeout grid.
2. Reload the page (F5).
3. Open Profile → Security.

**Expected:** The "30 min" button is highlighted (selected). Countdown reads ~30 min.

---

### TC-B-06 — "Jamais" setting persists across page reloads

**Steps:**
1. Select "Jamais".
2. Reload the page.
3. Open Profile → Security.

**Expected:** "Jamais" button is selected. Countdown shows "Jamais".

---

### TC-B-07 — Switching from "Jamais" back to a timer re-enables auto-disconnect

**Steps:**
1. Select "Jamais".
2. Wait a moment, then click "15 min".

**Expected:** Countdown immediately appears and starts counting down from ~15 min. Inactivity for 15 minutes will trigger the warning overlay.

---

### TC-B-08 — Custom timeout value is applied correctly

**Steps:**
1. Click "Personnaliser" link.
2. Enter `20` in the input and click "Appliquer".

**Expected:** Countdown reads approximately "20 min".
