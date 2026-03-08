# Global Authenticated Header Design

## Goal

Add a consistent navigation header across all authenticated intake pages to prevent users from feeling lost.

## Architecture

A shared `IntakeHeader.svelte` component rendered on all authenticated pages (Dashboard, Form, FormComplete). Not shown on the Landing page or magic link verify flow.

## Header Contents (left to right)

- **Left:** Logo + "Acorn" text
- **Center-left:** Contextual breadcrumb — "Dashboard" on the dashboard, "Dashboard > Form Name" on form/complete pages. Breadcrumb items are clickable links.
- **Right:** Progress indicator (X of Y forms complete) + Locale toggle

## Behavior

- Slim, single-row bar with consistent height across all pages
- Breadcrumb links: "Dashboard" always navigable, current page shown as plain text
- Responsive: on mobile, breadcrumb truncates long form names, progress shrinks to compact form
- Bilingual: breadcrumb labels use `t.key[locale]` pattern

## Impact on Existing Components

### IntakeSidebar (desktop Form page)

- **Remove:** Logo, "Acorn" text, locale toggle from header area
- **Keep:** Progress ring, form list with status indicators, section sub-steps, "Back to Dashboard" link

### Form page

- **Remove:** Mobile header (logo, locale toggle, close button)
- **Remove:** Mobile progress bar below header (progress now in global header)
- **Add:** `IntakeHeader` component
- **Keep:** IntakeBottomNav for section stepping on mobile

### Dashboard page

- **Remove:** Inline header (logo + locale toggle)
- **Add:** `IntakeHeader` component

### FormComplete page

- **Remove:** Fixed-position locale toggle
- **Add:** `IntakeHeader` component

### Not affected

- `Landing.svelte` — keeps its own layout
- `IntakeBottomNav.svelte` — unchanged, handles section navigation within forms

## Component Props

```typescript
type IntakeHeaderProps = {
    locale: string;
    progress: { completed: number; total: number };
    breadcrumbs?: { label: Record<string, string>; href?: string }[];
};
```

## Tech Stack

- Svelte 5 with `$props()`
- Inertia `Link` component for navigation
- Wayfinder route functions for URLs
- Tailwind CSS v4 for styling
- Bilingual support via `t.key[locale]` pattern
