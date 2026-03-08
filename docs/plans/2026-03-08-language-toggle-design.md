# Language Toggle Design

## Goal

Provide an always-visible, easy-to-use language toggle (English/Spanish) across the entire intake flow — from the landing page through form completion. Persist the preference to the patient record when authenticated, fall back to session storage when not.

## Approach

Shared `LocaleToggle.svelte` component (Approach A) — each page includes it in its header area. No shared layout refactor needed.

## LocaleToggle Component

- Reusable `LocaleToggle.svelte` — an "EN | ES" pill where the active language is visually highlighted
- Accepts a `locale` prop (`'en' | 'es'`)
- On click, POSTs to the appropriate set-locale endpoint with the new locale
- Uses `router.reload()` to re-render the page in the new language (preserves scroll, no data loss)
- Styling: small, unobtrusive, top-right corner — rounded border, muted text, hover highlight (matches existing Dashboard button style)

## Pre-Auth Locale (Landing Page)

Since there's no Patient record before authentication, locale is stored in the session:

- New unauthenticated route `POST /intake/set-locale-guest` stores locale in session
- `SetPatientLocale` middleware extended to check `session('locale')` as fallback when no patient exists
- On magic link verification, if the patient has no `preferred_locale`, copy the session locale to their record
- Landing page made fully bilingual (currently English-only hardcoded text)

### Flow

1. Parent lands on page — middleware checks Accept-Language header, sets locale
2. Parent clicks toggle — session stores preference
3. Parent verifies magic link — if patient has no `preferred_locale`, copy from session
4. From here on, saved to Patient record as usual

## Page-by-Page Integration

- **Landing** — Add a minimal header with logo and toggle (page currently has no header)
- **Dashboard (Hub)** — Replace existing inline toggle button with the new component (same position, top-right of header)
- **Form** — Add to the desktop sidebar header area and the mobile top bar
- **FormComplete** — Small fixed-position toggle in the top-right corner (full-screen interstitial, no header)

### Landing Page Bilingual Content

All hardcoded English strings (headings, descriptions, button labels, feature callouts) need bilingual equivalents passed as props from the controller using the existing `{ en: '...', es: '...' }` pattern.

## Hardcoded String Cleanup

Audit and fix hardcoded UI strings in Svelte components (e.g., "Guardando...", "Guardado" in FormRenderer). Replace with locale-aware inline objects: `{ en: 'Saving...', es: 'Guardando...' }[locale]`. No i18n library needed — the existing inline pattern works for two languages.

## Testing

### Feature Tests

- Guest locale switching — POST to guest endpoint stores locale in session
- Session locale carries over to Patient record on magic link verification
- Locale toggle on authenticated pages updates Patient record
- Landing page renders bilingual content based on locale

### Pest Browser Tests

- Toggle is visible and functional across the flow — landing through form completion
- Language switches mid-form without data loss

## Design Decisions

- **No i18n library** — inline `{ en, es }` pattern is sufficient for two languages and consistent with form schema approach
- **No shared layout refactor** — each page includes the component in its header, avoiding a larger refactor
- **Session fallback for pre-auth** — simplest approach that covers the landing page without cookies or URL params
- **Designed for extensibility** — adding a third language later means adding a key to the inline objects and extending the toggle UI, not a rewrite
