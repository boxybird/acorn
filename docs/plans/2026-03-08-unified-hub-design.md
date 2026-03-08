# Unified Hub Design

**Goal:** Replace the Dashboard and IntakeSelector pages with a single Hub page that earns its place by showing orientation, child selection, overall status with time estimates, and entry into forms.

## Problem

The current Dashboard is a redundant checklist — the Form page's sidebar already shows the same list of forms with statuses. The IntakeSelector is a separate page only visible with 2+ intakes, making child-switching hard to discover.

## Design

### Navigation Flow

- Magic link always lands on the Hub (regardless of intake count)
- Hub click on a form opens the Form page (sidebar unchanged)
- Form page "back" link returns to Hub
- IntakeSelector page deleted, absorbed into Hub

### Hub Layout

**Single-intake:** Welcome card (when no forms started) + child name + progress bar with time estimate + form checklist.

**Multi-intake:** Same, but with child cards above the form list. Selected child highlighted. Clicking another child swaps the form list. `[+]` button to add another child.

### Welcome Card

- Visible when all form statuses are `not_started`
- Brief, warm copy: "Complete 6 short forms at your own pace. Your progress saves automatically — come back anytime."
- Auto-hides once any form is `in_progress` or `completed`
- Derived from form statuses — no dismiss tracking needed

### Time Estimate

- Sum `estimated_minutes` from form schemas where status is not `completed`
- Display as "~X min remaining"
- No actual time tracking — just schema-defined estimates

### Backend Changes

- **MagicLinkController:** Always redirect to `intake.dashboard`. Remove branching for 2+ intakes. Still auto-create intake if 0 exist.
- **DashboardController:** Load all intakes for the patient with progress counts, form statuses for selected intake, time estimate, all intakes array.
- **IntakeSelectorController:** Remove `index()`. Keep `choose()` and `create()` POST endpoints for child switching/adding.
- **Routes:** Remove `intake.select` GET route. Keep POST routes.
- **Delete:** `IntakeSelector.svelte`

### Form Sidebar

No changes. Stays as-is for in-form navigation.

### Testing

- Hub shows welcome card when no forms started, hides when any started
- Hub shows time estimate based on incomplete forms
- Hub shows child cards when multiple intakes, hides for single intake
- Magic link always redirects to dashboard (never to selector)
- POST endpoints for choose/create still work and redirect to dashboard
- Browser tests updated for removed selector page
