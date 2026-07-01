# Part C — Profile Sections: Manual Testing Guide

**Branch:** `feature/role-label-centralization`
**Date:** 2026-07-01

---

## What changed

### Backend
- **4 new tables**: `school_backgrounds`, `certificates`, `qualifications`, `responsibilities`
- **4 new models**: `SchoolBackground`, `Certificate`, `Qualification`, `Responsibility` with `belongsTo(User)`, ordered by `ordre` / `date_debut desc`
- **4 factories**: one per model
- **4 Form Request classes** in `app/Http/Requests/Profile/`
- **4 controllers** in `app/Http/Controllers/Api/` — `index`, `store`, `update`, `destroy`, ownership enforced inline
- **16 routes** under `GET|POST|PUT|DELETE /api/users/profile/{section}/{id?}`
- **`UserController::profileView()`** — now eager-loads all 4 sections
- **`UserResource`** — exposes `school_backgrounds`, `certificates`, `qualifications`, `responsibilities` via `whenLoaded()`
- **`User` model** — 4 new `hasMany()` relations

### Frontend
- **4 new Vue components** in `resources/js/components/profile/`:
  - `SchoolBackgroundSection.vue`
  - `CertificateSection.vue`
  - `QualificationSection.vue`
  - `ResponsibilitySection.vue`
- Each component accepts `:readonly` (default `false`) and `:initial-data` props
- **`UserProfile.vue`** — new "Profil professionnel" tab (`tab_cv`) mounting all 4 sections (editable)
- **`UserProfileModal.vue`** — mounts all 4 sections read-only in the view mode (hidden when empty)
- **i18n** — `profile_sections.*` block added to both `fr.json` and `en.json`
- **Tests** — 19 tests in `tests/Feature/Profile/ProfileSectionsTest.php` — all passing

---

## API smoke tests (Tinker or curl)

```bash
# As authenticated user (replace TOKEN)
curl -H "Authorization: Bearer TOKEN" http://localhost/api/users/profile/school-backgrounds
# → { "data": [] }

curl -X POST http://localhost/api/users/profile/school-backgrounds \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"etablissement":"Université de Paris","diplome":"Master","date_debut":"2018-09-01","date_fin":"2020-06-30"}'
# → 201 { "data": { "id": 1, "etablissement": "Université de Paris", ... } }

# profileView now includes sections
curl -H "Authorization: Bearer TOKEN" http://localhost/api/users/1/profile-view | jq .data.school_backgrounds
```

---

## UI checks — Self-service profile (`/profile`)

1. Navigate to your user profile page.
2. Click the **"Profil professionnel"** tab (briefcase icon).
3. You should see 4 empty sections: Formation, Certifications, Qualifications, Missions & Responsabilités.

### Formation (School Background)

1. Click **Ajouter** in the "Formation" section.
2. Fill in: Établissement = "Sciences Po", Diplôme = "Master", Domaine = "Relations Internationales", dates.
3. Click **Enregistrer** → entry appears in the list with stagger animation.
4. Click the **edit pencil** → form pre-fills with existing data.
5. Change the établissement → save → list updates inline.
6. Click the **trash** icon → confirm dialog → entry removed.

### Certifications

1. Add a certificate: Title = "AWS Cloud Practitioner", Organisme = "Amazon", date, Credential URL.
2. The credential URL should appear as a **"Vérifier"** link that opens in a new tab.
3. Edit → change expiry date → save → list reflects new date.

### Qualifications

1. Add a qualification with title + description.
2. Description shows truncated (line-clamp-2) if long.
3. Edit → modify → save.

### Missions & Responsabilités

1. Add: Titre = "Chef de projet", Organisation = "Acme", date_debut required, date_fin optional.
2. If `date_fin` is empty → shows "janv. 2020 — Présent".
3. If `date_fin` is set → shows "janv. 2020 — déc. 2022".

### Validation errors

1. Try to submit Formation without Établissement → inline error appears.
2. Try to submit Responsabilité without date_debut → inline error appears.
3. Try to submit Certificate with an invalid URL → inline error appears.

### Stagger animation

After adding items, the list items should animate in sequentially with 50ms delay between each.

---

## UI checks — Directeur/Superadmin read-only view (`UserProfileModal`)

1. Go to **Admin → Utilisateurs** and open a user's profile modal.
2. Scroll past the stats block.
3. If the user has any school backgrounds / certificates / qualifications / responsibilities, the sections appear **read-only** (no Add/Edit/Delete buttons).
4. If all 4 sections are empty for this user, the sections are hidden (no empty state shown — cleaner for admins).

---

## Responsive check

- On mobile (< 640px): grid-cols inputs collapse to single column.
- Section headers stay readable.
- Add/edit buttons remain accessible.

---

## Dark mode check

All section cards, inputs, labels, and buttons must render correctly in dark mode.

---

## i18n check

Switch locale to English (language setting in profile):
- Tab label: "Professional profile"
- Section titles: "Education", "Certifications", "Qualifications", "Missions & Responsibilities"
- Empty states switch to English phrasing.
- "Présent" → "Present" in date ranges.

---

## Regression check

- All existing profile tabs (Profil, Sécurité, Notifications, Activité) continue to work unchanged.
- `UserProfileModal` edit mode (super-admin) continues to work for basic fields.
- 19 automated tests in `tests/Feature/Profile/ProfileSectionsTest.php` all pass.
