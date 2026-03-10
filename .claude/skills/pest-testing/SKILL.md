---
name: pest-testing
description: "Tests applications using the Pest 4 PHP framework. Activates when writing tests, creating unit or feature tests, adding assertions, testing Livewire components, browser testing, debugging test failures, working with datasets or mocking; or when the user mentions test, spec, TDD, expects, assertion, coverage, or needs to verify functionality works."
license: MIT
metadata:
  author: laravel
---

# Pest Testing 4

## When to Apply

Activate this skill when:

- Creating new tests (unit, feature, or browser)
- Modifying existing tests
- Debugging test failures
- Working with browser testing or smoke testing
- Writing architecture tests or visual regression tests
- **Invoked standalone** — run the audit workflow (see below)

## Audit Mode (Standalone Invocation)

When this skill is invoked without a specific test task (e.g., user runs `/pest-testing`), perform a **test coverage audit**:

1. **Run coverage:** `php artisan test --compact --coverage --min=100`
2. **If coverage is below 100%**, identify every uncovered file and line range from the output
3. **For each gap**, determine what tests are needed:
   - Read the uncovered source file to understand the logic
   - Check if a test file already exists for that class — update it rather than creating a new one
   - Write tests that cover the missing lines
4. **Re-run coverage** after writing tests to confirm gaps are closed
5. **Repeat** until 100% coverage is achieved
6. **Run `composer check`** to verify everything passes (Rector → Pint → PHPStan → Tests + Coverage)

### Audit Checklist

- [ ] Run `php artisan test --compact --coverage --min=100`
- [ ] Identify all files below 100% coverage
- [ ] Write/update tests for each gap
- [ ] Re-run coverage to verify 100%
- [ ] Run `composer check` to confirm full pipeline passes

## Documentation

Use `search-docs` for detailed Pest 4 patterns and documentation.

## Coverage Enforcement

This project enforces **100% test coverage** via PCOV.

- `composer check` runs `php artisan test --compact --coverage --min=100` — it will fail if coverage drops below 100%
- Every new class, method, or branch must have corresponding tests
- When writing new production code, always write tests in the same change
- Use `@codeCoverageIgnoreStart` / `@codeCoverageIgnoreEnd` sparingly and only for truly untestable code (e.g., framework boilerplate)

### Running Coverage

```bash
# Coverage with minimum enforcement (used by composer check)
php artisan test --compact --coverage --min=100

# Coverage report only (no minimum)
php artisan test --compact --coverage

# HTML report for detailed analysis
php artisan test --coverage-html coverage-report
```

## Basic Usage

### Creating Tests

All tests must be written using Pest. Use `php artisan make:test --pest {name}`.

### Test Organization

- Unit/Feature tests: `tests/Feature` and `tests/Unit` directories.
- Browser tests: `tests/Browser/` directory.
- Do NOT remove tests without approval — but proactively identify obsolete tests (see Test Hygiene below).

### Basic Test Structure

<!-- Basic Pest Test Example -->
```php
it('is true', function () {
    expect(true)->toBeTrue();
});
```

### Running Tests

- Run minimal tests with filter before finalizing: `php artisan test --compact --filter=testName`.
- Run all tests: `php artisan test --compact`.
- Run file: `php artisan test --compact tests/Feature/ExampleTest.php`.

## Assertions

Use specific assertions (`assertSuccessful()`, `assertNotFound()`) instead of `assertStatus()`:

<!-- Pest Response Assertion -->
```php
it('returns all', function () {
    $this->postJson('/api/docs', [])->assertSuccessful();
});
```

| Use | Instead of |
|-----|------------|
| `assertSuccessful()` | `assertStatus(200)` |
| `assertNotFound()` | `assertStatus(404)` |
| `assertForbidden()` | `assertStatus(403)` |

## Mocking

Import mock function before use: `use function Pest\Laravel\mock;`

## Datasets

Use datasets for repetitive tests (validation rules, etc.):

<!-- Pest Dataset Example -->
```php
it('has emails', function (string $email) {
    expect($email)->not->toBeEmpty();
})->with([
    'james' => 'james@laravel.com',
    'taylor' => 'taylor@laravel.com',
]);
```

## Pest 4 Features

| Feature | Purpose |
|---------|---------|
| Browser Testing | Full integration tests in real browsers |
| Smoke Testing | Validate multiple pages quickly |
| Visual Regression | Compare screenshots for visual changes |
| Test Sharding | Parallel CI runs |
| Architecture Testing | Enforce code conventions |

### Browser Test Example

Browser tests run in real browsers for full integration testing:

- Browser tests live in `tests/Browser/`.
- Use Laravel features like `Event::fake()`, `assertAuthenticated()`, and model factories.
- Use `RefreshDatabase` for clean state per test.
- Interact with page: click, type, scroll, select, submit, drag-and-drop, touch gestures.
- Test on multiple browsers (Chrome, Firefox, Safari) if requested.
- Test on different devices/viewports (iPhone 14 Pro, tablets) if requested.
- Switch color schemes (light/dark mode) when appropriate.
- Take screenshots or pause tests for debugging.

<!-- Pest Browser Test Example -->
```php
it('may reset the password', function () {
    Notification::fake();

    $this->actingAs(User::factory()->create());

    $page = visit('/sign-in');

    $page->assertSee('Sign In')
        ->assertNoJavaScriptErrors()
        ->click('Forgot Password?')
        ->fill('email', 'nuno@laravel.com')
        ->click('Send Reset Link')
        ->assertSee('We have emailed your password reset link!');

    Notification::assertSent(ResetPassword::class);
});
```

### Smoke Testing

Quickly validate multiple pages have no JavaScript errors:

<!-- Pest Smoke Testing Example -->
```php
$pages = visit(['/', '/about', '/contact']);

$pages->assertNoJavaScriptErrors()->assertNoConsoleLogs();
```

### Visual Regression Testing

Capture and compare screenshots to detect visual changes.

### Test Sharding

Split tests across parallel processes for faster CI runs.

### Architecture Testing

Pest 4 includes architecture testing (from Pest 3):

<!-- Architecture Test Example -->
```php
arch('controllers')
    ->expect('App\Http\Controllers')
    ->toExtendNothing()
    ->toHaveSuffix('Controller');
```

## Test Hygiene — Cleaning Up Obsolete Tests

The codebase changes quickly. When modifying or removing production code, always check for tests that have become obsolete.

### When to Check

- **Removing a feature, route, controller, or model** — find and remove tests that cover the deleted code
- **Renaming or restructuring** — update tests to match new names/paths, or remove if the old behavior no longer exists
- **Changing business logic** — check if existing tests assert outdated behavior that no longer applies
- **Deleting an Action, Form Request, or Job** — search for tests that reference the deleted class

### How to Check

1. When you delete or significantly change production code, search for related tests:
   - `grep -r 'DeletedClassName' tests/`
   - Check test files that mirror the deleted file's path (e.g., `app/Actions/Foo.php` → `tests/Feature/Actions/FooTest.php`)
2. Run the full test suite (`php artisan test --compact`) — tests referencing deleted classes will fail with class-not-found or similar errors
3. Review failing tests: if the test is for removed functionality, delete it. If it tests something that still exists but changed, update it.

### Rules

- **Always get approval before deleting tests** — flag obsolete tests to the user and confirm before removing
- **When deleting a test, explain why** — state what was removed/changed that makes the test obsolete
- **Prefer updating over deleting** — if the feature still exists but changed shape, update the test rather than removing it
- **Never leave broken tests behind** — if code changes cause test failures, fix or remove them in the same change

## Common Pitfalls

- Not importing `use function Pest\Laravel\mock;` before using mock
- Using `assertStatus(200)` instead of `assertSuccessful()`
- Forgetting datasets for repetitive validation tests
- Leaving obsolete tests behind after removing production code
- Deleting tests without getting approval first
- Forgetting `assertNoJavaScriptErrors()` in browser tests
- Not writing tests for new code — `composer check` enforces 100% coverage