# Intake Model — Design

## Goal

Introduce an `Intake` model between `Patient` and `FormResponse` so a single parent can complete separate intake paperwork for multiple children without data leaking between them.

## Architecture

`Patient` remains the auth identity (the parent logging in via magic link). `Intake` becomes the container for one child's complete set of forms, documents, and signatures. All data previously scoped by `patient_id` moves to `intake_id`.

```
Patient (parent — auth identity)
  └── Intake (one per child)
        ├── FormResponse (6 forms)
        ├── Document (uploads)
        └── Signature (consent)
```

## Database

### New table: `intakes`

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| patient_id | FK → patients | cascade delete |
| child_name | string, nullable | Populated from child_information form data |
| status | string, default 'active' | active, completed |
| timestamps | | |

### Modified tables

- `form_responses`: replace `patient_id` FK with `intake_id` FK. Unique constraint becomes `[intake_id, schema_key]`.
- `documents`: replace `patient_id` FK with `intake_id` FK.
- `signatures`: replace `patient_id` FK with `intake_id` FK.

### Data migration

Existing `form_responses`, `documents`, and `signatures` rows get an auto-created `Intake` record per patient. The migration creates one intake per patient, then re-points the FKs.

## Session

Session stores both `patient_id` and `intake_id`. The `AuthenticatePatient` middleware validates both exist and that the intake belongs to the patient.

## Auto-Fill on New Intake

When a parent starts a second intake, parent-level forms auto-fill from the most recent completed intake:

- **Demographics** — parent name, phone, email, address, preferred language, secondary guardian info
- **Insurance** — insurance details are parent-level, not child-specific

Child-specific forms start blank: child_information, medical_history, developmental_concerns, consent.

Implementation: when creating a new intake, copy `FormResponse.data` from the source intake for demographics and insurance schemas, set status to `in_progress` (not completed — parent should review/confirm).

## Flow

### First-time parent
1. Magic link → `patient_id` set in session
2. No intakes exist → auto-create first intake, set `intake_id` in session
3. Dashboard shows that intake's 6 forms (identical to current experience)

### Returning parent, one intake
1. Magic link → auto-resume single intake
2. Same as current experience

### Returning parent, multiple intakes
1. Magic link → lands on intake selector
2. Shows each intake with child name (or "Intake #N" if child_information not yet filled), progress, status
3. "Start intake for another child" button
4. Selecting an intake sets `intake_id` in session and goes to form dashboard

### Starting a new intake
1. Creates new `Intake` record
2. Copies demographics + insurance data as pre-filled drafts
3. Sets `intake_id` in session
4. Redirects to form dashboard with fresh forms (demographics/insurance pre-filled but editable)

## Dashboard Changes

**Single intake:** Dashboard looks exactly like today — no intake selector visible.

**Multiple intakes:** Dashboard becomes a two-level view:
- Top level: intake selector (cards showing child name, progress, status)
- Clicking an intake: shows the 6-form checklist for that intake (current dashboard view)
- "Start intake for another child" button always visible when on the selector

## Monday.com Sync

`SyncPatientToMonday` receives an `Intake` instead of a `Patient`. Each completed intake is a separate Monday.com item, since each child is a separate case.

## What Stays the Same

- Magic link auth flow (Patient model, tokens, email)
- Form schemas (PHP config files)
- Field components
- Auto-save mechanism
- Form section navigation (sidebar, bottom nav)
- Bilingual support
