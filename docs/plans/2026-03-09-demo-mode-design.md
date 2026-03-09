# Demo Mode Design

## Problem

The developer needs client feedback on the intake portal prototype but has no easy way for the client to explore the tool. The client needs to experience the app from multiple perspectives (different patients at different intake stages, staff members) without technical friction.

## Solution

A self-contained "demo mode" feature gated by a `DEMO_MODE` env var. When enabled, a persistent floating button appears on every page, opening a near-fullscreen modal with two zones: an about section explaining the tool, and a one-click account switcher for all test personas.

## Design Decisions

### Environment & Configuration

- Env var: `DEMO_MODE=true|false`, defaults to `false`
- Config file: `config/demo.php` reads the env var
- Backend checks use `config('demo.enabled')` — no `env()` calls outside config

### Backend

**Middleware:** `DemoMode` checks `config('demo.enabled')`, aborts 403 if off. Applied only to demo routes.

**Routes:** `routes/demo.php`, loaded conditionally in `bootstrap/app.php` when demo mode is on.

- `POST /demo/login/patient/{patient}` — sets patient session, redirects to intake dashboard
- `POST /demo/login/user/{user}` — Auth::login(), redirects to staff dashboard
- `POST /demo/logout` — clears both auth contexts, redirects to welcome page

**Controller:** Single `DemoController` with `loginAsPatient`, `loginAsUser`, `logout` methods.

**Registration:** Disabled when demo mode is on (Fortify registration feature toggled off).

**Inertia data:** `HandleInertiaRequests` conditionally shares `demo: { patients: [...], users: [...] }` when demo mode is enabled. This is the one touchpoint with existing shared middleware.

### Frontend

**Blade injection:** Single `@if(config('demo.enabled'))` block in `resources/views/app.blade.php` mounts a standalone Svelte component after the `@inertia` directive. Separate from the Inertia app — manages its own state, unaffected by page transitions.

**Components:**

- `DemoPanel.svelte` — floating action button (bottom-left, fixed) + near-fullscreen modal with backdrop. Animated open/close (scale + fade via Svelte transitions).
- `DemoAbout.svelte` — left column. Hardcoded about content: tool purpose, problem being solved, current build status, notes.
- `DemoAccountSwitcher.svelte` — right column. Lists patient personas (name, intake stage) and staff accounts. Each is a one-click button that POSTs to demo login routes.

**Modal:** Near-fullscreen (~90% viewport), two-column layout (about | accounts), backdrop overlay.

**Floating button:** Bottom-left corner, always visible on every page. Unobtrusive icon (beaker/flask).

**Account switching:** Form POST to `/demo/login/patient/{id}` or `/demo/login/user/{id}`. Page redirects with new identity. Logout button clears everything, returns to welcome page.

### Isolation & Removal

**Demo-specific files (7, all deletable):**

- `config/demo.php`
- `routes/demo.php`
- `app/Http/Middleware/DemoMode.php`
- `app/Http/Controllers/DemoController.php`
- `resources/js/components/demo/DemoPanel.svelte`
- `resources/js/components/demo/DemoAbout.svelte`
- `resources/js/components/demo/DemoAccountSwitcher.svelte`

**Existing files touched (3 small conditionals):**

- `bootstrap/app.php` — conditional route loading
- `resources/views/app.blade.php` — one `@if` block
- `app/Http/Middleware/HandleInertiaRequests.php` — one conditional block

**To remove:** Delete the 7 files, remove the 3 conditional blocks, remove env var. Grepable via `demo` or `DEMO_MODE`.

### Magic Link Expiry

Seeder sets magic link expiry far into the future (2099) so demo links don't expire. No production code changes needed.

## Not In Scope

- URL parameter to auto-open the dialog
- Database seeder changes (existing accounts are sufficient)
- Auth gating on the demo panel itself
- Magic link email integration (noted in about content)
- New role system
- Data reset button (reseed manually)
