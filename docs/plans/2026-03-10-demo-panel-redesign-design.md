# Demo Panel Redesign

## Problem

The current demo panel uses a two-column layout with FAQ-style text on the left and an account switcher on the right. The left side reads like documentation — flat, wordy, and not compelling for decision-makers evaluating Acorn. Features like Monday.com integration are buried in paragraphs. The panel needs to sell, not just explain.

## Audience

JumpStart Autism Collective leadership and decision-makers evaluating the product.

## Design

### Layout

Three-column full-screen modal (same FAB trigger, same open/close behavior):

| Left (~30%) | Middle (~35%) | Right (~35%) |
|---|---|---|
| The Pitch | Features & Integrations | Account Switcher |

Responsive: on smaller screens, columns stack vertically (pitch, features, account switcher).

### Left Column — The Pitch

Three stacked sections. Each has a bold headline and 1-2 sentences. No Q&A format — direct, confident copy.

1. **The Problem** — Only 1 in 3 referred families complete intake. The current paper-based process is long, confusing, and loses families before they ever get help.

2. **The Solution** — Acorn is a digital intake portal that meets parents where they are — on their phone, in their language, on their schedule. No accounts, no passwords. Just a link in their email.

3. **The Result** — Completed intake packages land directly in your workflow. Staff reviews, flags corrections, and approves — all from one dashboard.

### Middle Column — Features & Integrations

Two groups of small cards. Each card has: icon + title + one-line description + status badge.

**Live features** (badge: "Live"):
- Magic link auth — No passwords, no friction
- Bilingual — Full English & Spanish support
- Auto-save — Parents can stop and resume anytime
- Staff dashboard — Review, flag, and approve intakes
- Monday.com sync — Completed intakes flow into your workflow

**Coming soon** (badge: "Coming Soon", visually muted — reduced opacity/saturation compared to live cards):
- Automated insurance verification
- SMS notifications
- JumpStart software integrations
- Analytics & reporting

### Right Column — Account Switcher

Unchanged from current implementation. Parent accounts with intake details, staff accounts, logout, and reset buttons.

## What Changes

- `DemoAbout.svelte` — gutted and replaced with the three-section pitch
- New `DemoFeatures.svelte` component — feature cards with icons and badges
- `DemoPanel.svelte` — updated from two-column to three-column grid layout
- `DemoAccountSwitcher.svelte` — no changes

## What Doesn't Change

- FAB trigger (acorn icon, bottom-left)
- Modal behavior (backdrop, escape key, animations)
- Account switcher functionality
- Demo login/logout/reset endpoints
