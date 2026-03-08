# Acorn

Parent intake portal for JumpStart Autism Collective. Reduces friction for families completing early paperwork to get into the system.

## Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run dev
```

## Test Accounts

After running `php artisan migrate:fresh --seed`, the following test patients are available. Click a magic link to log in as that parent.

| Parent | Status | Child | Magic Link |
|--------|--------|-------|------------|
| Maria Garcia | **All 6 forms complete** | Sofia Garcia | [Login](http://acorn.test/intake/verify/seed-maria-garcia) |
| James Thompson | **3 forms done**, child info in progress | Ethan Thompson | [Login](http://acorn.test/intake/verify/seed-james-thompson) |
| Ashley Begay | **Brand new** — no intake started | — | [Login](http://acorn.test/intake/verify/seed-ashley-begay) |
| Rosa Martinez | **1 form done** (demographics), Spanish-speaking | — | [Login](http://acorn.test/intake/verify/seed-rosa-martinez) |
| Sarah Williams | **2 children**, mixed progress | Liam Williams + Child #2 | [Login](http://acorn.test/intake/verify/seed-sarah-williams) |

### What each account shows

- **Maria Garcia** — "All Done!" completion state on the Hub.
- **James Thompson** — In-progress Hub with progress bar, percentage, time estimate, and "Continue" button.
- **Ashley Begay** — Welcome screen with "Get Started" button (no forms touched).
- **Rosa Martinez** — In-progress Hub, Spanish locale preference.
- **Sarah Williams** — Multi-child Hub with child cards, progress per child, "Switch to" buttons, and "+ Add another child".

## Running Tests

```bash
# Full quality gate (Rector, Pint, PHPStan, tests)
composer check

# Just tests
php artisan test --compact

# Filtered
php artisan test --compact --filter="DashboardTest"

# Browser tests (requires npm run build first)
php artisan test --compact --filter="IntakeFlowTest"
```

## Development

```bash
# Start dev server with HMR
composer run dev

# Or separately
php artisan serve & npm run dev

# Code formatting
vendor/bin/pint --dirty

# Static analysis
vendor/bin/phpstan analyse
```
