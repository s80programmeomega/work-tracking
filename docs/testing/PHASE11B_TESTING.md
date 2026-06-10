# Phase 11B — Profile Quick Wins — Manual Testing Guide

Functional notification sound, Preferences tab cleanup, and removal of the dead "Paramètres du
compte" navbar link. See [`docs/phase11-dashboard-profile/PLAN.md`](../phase11-dashboard-profile/PLAN.md)
(Part B — B2, B3, B7) for the design.

No JS test runner (Vitest/Jest) is configured in this project (`package.json` has no test
script), so this phase is verified manually as documented below — consistent with the plan's
guidance not to introduce a new test framework for this alone.

---

## Prerequisites

- `php artisan serve` running, `npm run build` (or `npm run dev`) done
- A user account with a workspace, logged in
- Browser dev tools open (Console + Network tabs)
- Reverb/broadcasting configured so live notifications can be received (or trigger one via
  `php artisan tinker` by creating a notification for the logged-in user)

---

## TC-1 — Notification sound plays when a live notification arrives (sound enabled)

**Steps:**
1. Go to `/profile`, open the "Notifications" tab, ensure "Notification Sounds" toggle is ON, save
2. Trigger a live notification for the current user (e.g. assign them a task from another
   account, or via `php artisan tinker` dispatch a notification that broadcasts to
   `App.Models.User.{id}`)
3. Observe the toast appears (existing behavior) and listen for a short two-tone "ding"

**Expected:**
- A short (~0.4s) notification chime plays alongside the toast
- No console errors

---

## TC-2 — Notification sound does NOT play when sound is disabled

**Steps:**
1. Go to `/profile` → "Notifications" tab, toggle "Notification Sounds" OFF, save
2. Trigger a live notification as in TC-1

**Expected:**
- The toast still appears
- No sound plays

---

## TC-3 — Notification sound does not throw on autoplay restriction

**Steps:**
1. In a fresh browser tab/profile where the page has not yet received any user interaction
   (no click anywhere), trigger a live notification immediately after page load

**Expected:**
- No unhandled promise rejection / console error even if the browser blocks `audio.play()`
  (the composable swallows `NotPermittedError`/`DOMException` from `.play()`)

---

## TC-4 — Preferences tab shows only Timezone

**Steps:**
1. Go to `/profile` → "Préférences" tab

**Expected:**
- Only the "Fuseau horaire" / "Timezone" section is shown, plus Reset/Save actions
- Theme, Language, Date Format, Display Density, and Auto-Save sections are gone

---

## TC-5 — Timezone save still works (backend-persisted)

**Steps:**
1. On the Preferences tab, change the Timezone to e.g. `America/New_York`
2. Click "Enregistrer les préférences" / Save

**Expected:**
- Save succeeds (no error)
- `PUT /users/profile` request fires with `{ language, timezone: "America/New_York" }`
- Reloading the page keeps the new timezone selected

---

## TC-6 — Navbar dropdown no longer shows "Paramètres du compte"

**Steps:**
1. Click the user avatar/menu in the top-right navbar

**Expected:**
- Dropdown shows only "Editer le profil" and "Support" (plus "Se déconnecter")
- No "Paramètres du compte" entry, and no 404 link to `/settings`

---

## Cleanup

None — all changes are static UI/composable changes with no test data created.
