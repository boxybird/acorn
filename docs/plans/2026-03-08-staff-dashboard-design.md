# Staff Dashboard Design

## Overview

A lightweight intake-centric staff dashboard for reviewing parent-submitted intakes. Staff's primary work happens in Monday.com — this portal is a helper tool for reviewing submissions, flagging issues, adding notes, and exporting PDFs.

## Intake Status Workflow

**`IntakeStatus` enum:**
`Submitted → UnderReview → Flagged → CorrectionSubmitted → Approved → SyncedToMonday`

**Transitions:**
- Staff opens intake → `UnderReview`
- Staff flags a form → `Flagged` (creates `IntakeFlag` record)
- Parent resubmits corrected form → `CorrectionSubmitted`
- Staff approves → `Approved`
- Monday.com sync completes → `SyncedToMonday`

## New Models

### IntakeNote
- `intake_id` — which intake
- `user_id` (nullable) — staff author
- `patient_id` (nullable) — parent author
- `body` — note text
- `timestamps`

All notes visible to both staff and parents. Chronological thread.

### IntakeFlag
- `intake_id` — which intake
- `form_response_id` — which specific form has the issue
- `reason` — explanation of what needs correction
- `resolved_at` — nullable, set when staff resolves the flag
- `timestamps`

## Staff Dashboard — Intake List View

**Route:** `/staff/intakes`

- Status filter tabs: All | Submitted | Under Review | Flagged | Corrections Submitted | Approved | Synced — with counts
- Badge on `Submitted` and `Corrections Submitted` tabs to highlight actionable intakes
- Default view filters to `Submitted` + `Corrections Submitted`
- Sortable table: Child Name, Parent Name, Submitted Date, Status, Last Updated
- Search bar (uses blind index for encrypted fields)
- Pagination
- Visual indicator (dot/badge) on intakes updated since staff last viewed
- Click row → intake detail view
- Built using existing Laravel starter kit components and design patterns for consistent branding

## Intake Detail View

**Route:** `/staff/intakes/{intake}`

### Top Bar
- Child name, parent name, current status badge
- Status action buttons (Approve, Flag)
- PDF export button

### Form Responses Section
- Accordion-style list of all 6 forms
- Each shows: form title, completion status, flag indicator
- Expand → read-only key/value layout of submitted data
- "Flag this form" button per form → inline input for reason
- Flagged forms show reason + "Resolve" button

### Notes Section
- Chronological thread with author name, role badge (Staff/Parent), timestamp
- Text input + submit to add note
- New notes from parent since staff's last visit get subtle highlight

## Notifications & Parent Re-entry

### When staff flags a form:
- Email to parent: child name, flagged form(s), reason
- Email contains magic link back into portal
- Parent dashboard shows banner: "Action needed — [form name] needs corrections"
- Flagged form unlocked for editing, others remain locked

### When parent resubmits:
- Intake status → `CorrectionSubmitted`
- Email to staff: "[Child name]'s intake has been updated"
- Dashboard shows visual indicator on that intake

### When staff approves:
- Existing Monday.com sync job fires
- On successful sync → `SyncedToMonday`

## PDF Export

**Server-side generation** (e.g., `barryvdh/laravel-dompdf`).

**Content:**
- Header: JumpStart logo, "Intake Summary", generated date
- Patient info: parent name, child name, submission date, status
- Each form as a section with field labels and values
- Signatures rendered as images
- Uploaded documents listed with filenames (not embedded)
- Notes thread appended at the end

**Styling:** Matches app branding (colors, fonts) for professional printable output.
