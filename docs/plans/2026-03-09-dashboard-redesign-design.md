# Intake Dashboard Redesign

## Problem

The current dashboard has redundant panels — a top progress card for the current intake and separate child cards below. Switching between children requires two actions (switch + continue). The parent's progress and the child's card are decoupled when they should be the same thing.

## Design

### Core Principle

One card per child = one card per intake. The card IS the progress tracker AND the entry point into forms. No separate progress panel.

### Layout (top to bottom)

1. **Header** — simplified IntakeHeader (logo, breadcrumb, locale toggle, no progress ring)
2. **Child intake cards** — vertical stack, toggle-expandable
3. **"Add Child" card** — always visible, dashed border
4. **Notes section** — all notes across intakes, same functionality as current

### Card Design

**Collapsed (header row):**
- Child name (or "Child #N" if unnamed)
- Compact progress bar + "3 of 7 forms" or "Complete"
- Chevron toggle indicator

**Expanded (header + body):**
- Time estimate: "~15 min remaining" (hidden if complete)
- Flags alert with links to specific forms (if unresolved flags exist)
- Form checklist — each form is a clickable row with title + status indicator, navigates directly to that form
- "Continue" button — goes to next incomplete form
- If all complete: success state, no Continue button

### Interaction

- Click card header to toggle expand/collapse
- Multiple cards can be open simultaneously (client-side state)
- Single child: card pre-expanded on load
- Multiple children: current intake card (`session('intake_id')`) pre-expanded
- Clicking a form row or "Continue" in any card: switches session to that intake + navigates to form in one request (single user action)

### Data Changes

Current controller sends forms/progress/flags for current intake only. New design sends per-intake data:

```
allIntakes: [{
  id, child_name, status, is_current,
  forms: [{ key, title, status, estimated_minutes }],
  progress: { completed, total },
  time_estimate,
  flags: [{ id, reason, form_response: { schema_key } }]
}]
notes: [{ id, body, created_at, user, patient }]
```

Top-level `forms`, `progress`, `timeEstimate`, `flags`, and `intake` props removed.

### What Changes

- **Remove:** Top progress panel, progress ring from header, three conditional states (welcome/in-progress/completed)
- **Modify:** Dashboard.svelte (new layout), DashboardController.php (per-intake data), IntakeHeader.svelte (remove progress ring)
- **New:** IntakeCard.svelte component
- **Keep:** Notes section, "Add Child" flow, IntakeSelectorController, Form page, all backend Actions/models/routes
