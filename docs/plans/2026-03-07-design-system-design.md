# Acorn Design System Design

## Overview

A warm, earthy visual language derived from the acorn logo, applied via CSS custom property tokens to the existing shadcn-svelte component library. No new components — the entire design system is implemented by replacing token values and swapping the font.

## Core Principle

Warm, welcoming, and friction-reducing for parents. Professional and efficient for staff. Same brand, different tone.

## Color Palette

Derived from the acorn logo SVG gradients.

### Parent-Facing (`:root`)

| Token | Value | Usage |
|-------|-------|-------|
| `--primary` | `#5F3124` (warm dark brown) | Buttons, links, key actions |
| `--primary-foreground` | `#FDF8F3` (warm white) | Text on primary |
| `--accent` | `#F9DFA4` (pale gold) | Highlights, hover states |
| `--accent-foreground` | `#5F3124` (dark brown) | Text on accent |
| `--secondary` | `#F5EDE3` (warm off-white) | Secondary buttons, tags |
| `--secondary-foreground` | `#3D1F18` (deep brown) | Text on secondary |
| `--background` | `#FFFCF8` (very warm white) | Page background |
| `--foreground` | `#3D1F18` (deep brown) | Body text |
| `--card` | `#FFFFFF` | Card surfaces |
| `--card-foreground` | `#3D1F18` (deep brown) | Card text |
| `--popover` | `#FFFFFF` | Popover surfaces |
| `--popover-foreground` | `#3D1F18` (deep brown) | Popover text |
| `--muted` | `#F5EDE3` (warm gray) | Disabled, subtle backgrounds |
| `--muted-foreground` | `#8B6F5C` (warm mid-brown) | Secondary text |
| `--border` | `#E8DDD0` (warm border) | Borders |
| `--input` | `#E0D3C3` (warm input border) | Input borders |
| `--ring` | `#D17A2B` (burnt orange) | Focus rings |
| `--destructive` | `#C4432B` (warm red) | Error states |
| `--destructive-foreground` | `#FDF8F3` (warm white) | Text on destructive |

### Chart Colors

| Token | Value | Source |
|-------|-------|--------|
| `--chart-1` | `#D17A2B` | Burnt orange |
| `--chart-2` | `#C99743` | Golden brown |
| `--chart-3` | `#5F3124` | Dark brown |
| `--chart-4` | `#F6B958` | Bright gold |
| `--chart-5` | `#9A391F` | Deep rust |

### Staff-Facing (`.staff` scope)

Same brand palette with a slightly cooler, more utilitarian tone:
- Background shifts to `#FAFAFA` (neutral white)
- Borders shift to more neutral grays
- Primary, accent, and destructive colors remain the same for brand cohesion

### Sidebar Tokens (Staff)

| Token | Value |
|-------|-------|
| `--sidebar-background` | `#FAF8F5` (warm off-white) |
| `--sidebar-foreground` | `#3D1F18` (deep brown) |
| `--sidebar-primary` | `#5F3124` (warm dark brown) |
| `--sidebar-primary-foreground` | `#FDF8F3` (warm white) |
| `--sidebar-accent` | `#F5EDE3` (warm off-white) |
| `--sidebar-accent-foreground` | `#3D1F18` (deep brown) |
| `--sidebar-border` | `#E8DDD0` (warm border) |
| `--sidebar-ring` | `#D17A2B` (burnt orange) |

### Status Colors (Used in badges, progress indicators)

| Status | Color | Hex |
|--------|-------|-----|
| Not Started | Muted | `--muted` |
| In Progress | Burnt orange | `#D17A2B` |
| Complete | Warm green | `#5A8A3C` |
| Failed/Error | Warm red | `#C4432B` |

## Typography

### Font: Nunito

- Rounded terminals — warm and friendly, matches the organic acorn brand
- Excellent readability at small sizes (critical for form labels on mobile)
- Loaded via Google Fonts
- Replaces `Instrument Sans` in `app.css`

### Weight Scale

| Use | Weight |
|-----|--------|
| Body, labels | 400 (Regular) |
| Buttons, emphasis | 600 (Semibold) |
| Headings | 700 (Bold) |

### Size Scale

| Context | Base Size |
|---------|-----------|
| Parent-facing body | 16px |
| Parent-facing mobile form labels | 18px |
| Staff-facing body | 14-16px |

## Layout Strategy

### Parent-Facing (Intake)

- Uses existing `AppHeaderLayout`
- Logo in header, language toggle in corner
- Content centered, max-width constrained (~640px for forms, ~768px for dashboard)
- Generous padding and spacing — forms breathe

### Staff-Facing (Dashboard)

- Uses existing `AppSidebarLayout`
- Content area wraps in `.staff` class for token overrides
- Standard density, tables and lists use full width

### Shared Behavior

Components render identically in both contexts — CSS tokens handle the tonal shift automatically. No conditional component logic needed.

## Component Styling

No new components. Existing shadcn-svelte components are restyled via token values.

### Buttons

- Primary: warm brown background, warm white text
- Secondary: warm off-white background
- Destructive: warm red
- Border radius: existing `--radius` (0.5rem)

### Cards (Dashboard Checklist)

- White background with warm border
- Subtle gold left-border accent on hover or "in progress"
- Status badges use the status color system

### Form Inputs

- Warm border (`#E0D3C3`)
- Focus ring: burnt orange (`#D17A2B`)
- Error: warm red with light red-tinted background

### Progress Bar

- Fill gradient: `#D17A2B` → `#F6B958` (burnt orange to gold)
- Echoes the acorn body gradient

## Dark Mode

Not implemented. Light mode only across the entire app. The existing `.dark` CSS block can be removed or left inert.

## Implementation Scope

1. Replace `:root` CSS custom property values in `app.css`
2. Add `.staff` scoped overrides in `app.css`
3. Swap font from Instrument Sans to Nunito (Google Fonts)
4. Remove or ignore `.dark` theme block
5. No component file changes needed
