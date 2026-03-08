# Intake Form Redesign — Design

## Goal

Redesign the intake form experience from a single-scroll, mobile-only layout to a polished split-screen design with section-per-step navigation, progress visibility, and completion interstitials. Every decision optimizes for parent completion rates.

## Architecture

The existing form schema system (PHP config files with sections and fields) stays unchanged. The redesign is purely frontend — new layout, navigation, and interaction patterns. No backend changes required.

## Desktop Layout

Split-screen, borrowing the homepage/landing pattern:

- **Left sidebar (~280px fixed)** — Full intake checklist showing all 6 forms with status indicators (checkmark = complete, filled dot = in-progress, empty circle = not started). The active form is expanded to show its sections as clickable sub-steps with the current section highlighted. Progress ring at the top showing overall intake completion percentage. "Back to Dashboard" link at the bottom.
- **Right content area (fluid)** — The active section's fields. Generous padding, max-width container so fields don't stretch on wide screens. Section title at top, fields below with comfortable spacing. Previous/Next navigation buttons at the bottom right.
- **Header** — Minimal. Logo + current form title.

## Mobile Layout

Full-width single column, no sidebar.

- **Header** — Compact. Logo left, form title center, back/close button to return to dashboard.
- **Form content** — Full width with comfortable horizontal padding. Section title, then fields. Same spacing and breathing room as desktop.
- **Sticky bottom nav** — Three elements: "Previous" button (left, hidden on first section), step indicator center ("Step 1 of 3" with small overall progress ring), "Next" button (right). On the last section, "Next" becomes "Complete" with distinct styling.

## Section-Per-Step Navigation

Each form's existing sections become individual steps. One section visible at a time.

- **Transition** — Quick fade between sections. No heavy slide animations.
- **Validation** — Fields validated when advancing to next section. Errors shown inline, parent stays on current section until resolved. No wall-of-errors on final submit.
- **Auto-save** — Preserved from current implementation. Field blur triggers save (1000ms debounce). Section transitions also trigger save.
- **Back navigation** — Always available (except section 1). Going back preserves data, no re-validation.
- **Direct section jump (desktop)** — Clicking any section in the sidebar jumps directly to it. No gatekeeping — parents can skip around freely.

## Completion Interstitial

Full page shown after completing the last section of a form.

- **Layout** — Centered card, no sidebar or form chrome. Full focus on the moment.
- **Content** — Animated checkmark icon (circle fills with check), form name + "Complete", encouraging dynamic message (e.g., "One down, five to go"), two buttons: primary "Continue to [Next Form]" and secondary "Back to Dashboard."
- **Final form completion** — Different tone: "You're all done!" with message about JumpStart reviewing their information. Single "Back to Dashboard" button. Dashboard shows fully complete state.
- **No auto-redirect** — Parent decides when to move on.

## Visual Polish

- **Section cards** — Fields grouped inside subtle cards (white bg, light border, rounded corners using existing theme tokens). Creates visual structure without clutter.
- **Field spacing** — Increased vertical space between fields. Labels above inputs with consistent gap. Error messages below without layout shift.
- **Section titles** — Larger, bolder than field labels. Clear hierarchy.
- **Save indicator** — Subtle "Saved" in header area (checkmark + text), appears briefly after auto-save then fades. No persistent "Saving..." spinners.
- **Brand consistency** — Sidebar uses `bg-primary/5` background. Active states use primary brown. Progress indicators use accent amber.

## What Stays the Same

- Form schema system (PHP config files)
- Field components (TextField, SelectField, etc.)
- Auto-save mechanism
- Backend controllers and routes
- Data models (Patient, FormResponse)
- Magic link authentication flow
- Dashboard page (existing checklist approach)
- Bilingual support
