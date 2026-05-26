# Task 12 — Manual Testing Guide

Document management: workspace-level page, share-by-email, upload/delete notifications, permission gates.

---

## Prerequisites

- At least one workspace with owner + cadre + collaborateur members
- At least one project within that workspace
- Mail configured (or check logs for email content)

---

## TC-1 — Owner can access WorkspaceDocuments page

**Steps:**
1. Log in as workspace owner
2. Navigate to `/workspaces/{id}/documents`

**Expected:**
- Page loads with workspace info card
- `DocumentManager` component shows (empty or with documents)

---

## TC-2 — Non-owner blocked from WorkspaceDocuments page

**Steps:**
1. Log in as a collaborateur workspace member
2. Navigate directly to `/workspaces/{id}/documents`

**Expected:**
- Page shows "Vous n'avez pas accès aux documents de ce workspace." message
- OR the API call to `GET /api/documents/workspace/{id}` returns 403

---

## TC-3 — Project member can view project documents

**Steps:**
1. Log in as a project member (cadre or owner)
2. Navigate to `/projets/{id}/documents`

**Expected:**
- Page loads and shows the `DocumentManager` component with existing documents

---

## TC-4 — Share-by-email from document manager

**Steps:**
1. Upload a document to a project
2. Open the document's actions menu
3. Click Share → enter an external email address (not a registered user)
4. Confirm

**Expected:**
- `POST /api/documents/{id}/share-by-email` returns `{"success": true}`
- Log line appears: `Partage de document par email` with user_id, document_id, shared_to_email
- Email is sent to the given address with document name, download link, CTA button

---

## TC-5 — Upload notification sent to cadre/manager

**Steps:**
1. Log in as project owner; upload a document to a project that has a cadre member
2. Log in as that cadre member

**Expected:**
- Cadre has a new in-app notification of type `document_uploaded`
- Owner does NOT receive the notification (they uploaded it)

---

## TC-6 — Delete notification sent to project responsable

**Steps:**
1. Log in as project manager; delete a document that was NOT uploaded by the manager
2. Log in as the project responsable (if different from manager)

**Expected:**
- Responsable has a new in-app notification of type `document_deleted`
- Log line appears: `Suppression de document` with user_id and document_nom

---

## TC-7 — Unauthorized delete attempt is logged

**Steps:**
1. Log in as a collaborateur
2. Attempt `DELETE /api/documents/{id}` via the API (collaborateurs cannot delete by default)

**Expected:**
- Response 403
- Log line appears: `Tentative non autorisée de suppression de document` with reason: "unauthorized"

---

## TC-8 — Document permission keys in ProjetResource

**Steps:**
1. Call `GET /api/projets/{id}` as a cadre member

**Expected:**
- `user_permissions` object includes:
  - `can_view_documents: true`
  - `can_upload_documents: true`
  - `can_delete_documents: false` (cadres don't have DELETE by default)
  - `can_share_documents: true`

---

## Automated Tests

| Suite | File | Count |
|---|---|---|
| PHPUnit Feature | `tests/Feature/Task12/DocumentManagementTest.php` | 5 |

Run with:
```bash
php artisan test --compact tests/Feature/Task12/
```
