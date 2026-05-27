# Task 16 — Manual Testing Guide

PDF/Excel exports: agent evaluation sheet PDF (CDC B.1) and workspace task list Excel (CDC ST.7 / E.2).

---

## Prerequisites

- A workspace with an owner and at least one project → activity → task
- Logged in as the workspace owner (or as the agent whose sheet you want to export)
- `barryvdh/laravel-dompdf` and `maatwebsite/excel` installed and configured

---

## TC-1 — Export agent evaluation PDF as self (B.1)

**Steps:**
1. Navigate to `/evaluations/personnel/{your-id}` (AgentSheet page)
2. Set a date range (e.g. 01/01/2026 – 31/12/2026)
3. Click **Exporter PDF**

**Expected:**
- Browser downloads (or opens in new tab) a file named `fiche-evaluation-<nom>-<date>.pdf`
- PDF contains: agent name in header, score global (0–100), criteria breakdown with bars, lists of directed/assigned tasks if any
- Content-Type header is `application/pdf`

---

## TC-2 — Export agent evaluation PDF forbidden for stranger (B.1 — 403)

**Steps:**
1. Log in as a user who has no workspace connection with the target agent
2. Call `GET /api/evaluations/personnel/{other-user-id}/export-pdf` directly (or via curl)

**Expected:**
- Response status 403

---

## TC-3 — Export workspace tasks as Excel — owner (ST.7 / E.2)

**Steps:**
1. Navigate to `/workspace/taches` as the workspace owner
2. Click **Exporter Excel**

**Expected:**
- Browser downloads a file named `taches-workspace-<YYYY-MM-DD>.xlsx`
- File opens in Excel/LibreOffice with a header row (blue background, white bold text) and one data row per task
- Columns: Projet, Activité, Titre, Statut, Priorité, Responsable, Intervenants, Avancement (%), Échéance, Sous-tâches

---

## TC-4 — Export workspace tasks as Excel — regular member (403)

**Steps:**
1. Log in as a workspace member who is not the owner
2. Call `GET /api/workspace/taches/export-excel`

**Expected:**
- Response status 403

---

## TC-5 — Export respects active filters

**Steps:**
1. Navigate to `/workspace/taches` as the workspace owner
2. Select a filter (e.g. Statut = En cours)
3. Click **Exporter Excel**

**Expected:**
- Downloaded file contains only tasks matching the selected filter (verify by spot-checking statut column)

---

## Automated tests

```
php artisan test --compact tests/Feature/Task16/
```

5 tests — all must pass:
- `test_export_pdf_returns_403_for_non_permitted_user`
- `test_export_pdf_returns_pdf_for_self`
- `test_export_excel_returns_403_for_regular_user`
- `test_export_excel_returns_excel_for_owner`
- `test_export_excel_respects_statut_filter`
