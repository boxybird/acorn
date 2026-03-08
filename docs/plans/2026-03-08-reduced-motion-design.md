# Reduced Motion & Card Interactions Design

## Goal

Respect `prefers-reduced-motion` accessibility preference and add subtle hover interactions to intake page cards.

## Reduced Motion

Add a global CSS rule that disables transitions and animations for users who prefer reduced motion. This blanket rule covers all existing animations (progress bars, checkmark scale, form transitions, stagger entrances) and any new interactions.

## Card Interactions (Intake Pages Only)

- **Dashboard child cards:** subtle lift on hover (`-translate-y-0.5`, `shadow-md`)
- **Dashboard action cards (Get Started / Continue area):** same lift treatment
- **All card interactions:** use `transition-all duration-200`, suppressed by reduced-motion rule

## Scope

- Intake pages only (Dashboard, Form, FormComplete)
- No changes to auth/Welcome pages
- No entrance animations — interaction-only
