# Global Authenticated Header Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add a consistent navigation header across all authenticated intake pages so users always know where they are and how to get back.

**Architecture:** Create a shared `IntakeHeader.svelte` component with logo, breadcrumb, progress indicator, and locale toggle. Integrate it into Dashboard, Form, and FormComplete pages. Remove duplicate header elements from those pages and strip the sidebar's header section.

**Tech Stack:** Svelte 5 (`$props()`), Inertia `Link`, Wayfinder routes (`@/routes/intake`, `@/routes/intake/form`), Tailwind CSS v4, bilingual `t.key[locale]` pattern, Pest 4 browser tests.

---

### Task 1: Create the IntakeHeader component

**Files:**
- Create: `resources/js/components/intake/IntakeHeader.svelte`

**Context:** This is a new shared component used on all authenticated intake pages. It replaces the per-page headers. It needs logo, breadcrumb navigation, progress indicator, and locale toggle.

Existing patterns to follow:
- `LocaleToggle.svelte` is imported from `@/components/intake/LocaleToggle.svelte`
- `AppLogoIcon.svelte` is imported from `@/components/AppLogoIcon.svelte`
- Inertia `Link` from `@inertiajs/svelte`
- Wayfinder `dashboard` from `@/routes/intake`
- Bilingual strings use `const t = { key: { en: '...', es: '...' } } as const` with `t.key[locale]`
- Progress type: `{ completed: number; total: number }`

**Step 1: Create the component file**

```svelte
<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import AppLogoIcon from '@/components/AppLogoIcon.svelte';
    import LocaleToggle from '@/components/intake/LocaleToggle.svelte';

    type Breadcrumb = {
        label: Record<string, string>;
        href?: string;
    };

    let {
        locale = 'en',
        progress,
        breadcrumbs = [],
    }: {
        locale?: string;
        progress: { completed: number; total: number };
        breadcrumbs?: Breadcrumb[];
    } = $props();

    const t = {
        of: { en: 'of', es: 'de' },
        complete: { en: 'complete', es: 'completos' },
    } as const;

    const circumference = 2 * Math.PI * 8;
    let progressPercent = $derived(
        progress.total > 0 ? Math.round((progress.completed / progress.total) * 100) : 0,
    );
    let strokeDashoffset = $derived(circumference - (progressPercent / 100) * circumference);
</script>

<header class="sticky top-0 z-40 border-b bg-background">
    <div class="flex h-14 items-center justify-between px-4 lg:px-6">
        <!-- Left: Logo + Breadcrumb -->
        <div class="flex items-center gap-3">
            <div class="flex items-center gap-2">
                <AppLogoIcon class="size-6" />
                <span class="text-sm font-bold text-foreground">Acorn</span>
            </div>

            {#if breadcrumbs.length > 0}
                <nav class="hidden items-center gap-1 text-sm sm:flex" aria-label="Breadcrumb">
                    {#each breadcrumbs as crumb, i (i)}
                        <span class="text-muted-foreground">/</span>
                        {#if crumb.href}
                            <Link
                                href={crumb.href}
                                class="text-muted-foreground transition-colors hover:text-foreground"
                            >
                                {crumb.label[locale]}
                            </Link>
                        {:else}
                            <span class="font-medium text-foreground">{crumb.label[locale]}</span>
                        {/if}
                    {/each}
                </nav>
            {/if}
        </div>

        <!-- Right: Progress + Locale -->
        <div class="flex items-center gap-4">
            <div class="flex items-center gap-2">
                <svg class="size-5 -rotate-90" viewBox="0 0 20 20">
                    <circle cx="10" cy="10" r="8" fill="none" stroke="currentColor" stroke-width="2" class="text-border" />
                    <circle
                        cx="10" cy="10" r="8" fill="none" stroke="currentColor" stroke-width="2"
                        class="text-primary transition-all duration-500"
                        stroke-dasharray={circumference}
                        stroke-dashoffset={strokeDashoffset}
                        stroke-linecap="round"
                    />
                </svg>
                <span class="hidden text-xs text-muted-foreground sm:inline">
                    {progress.completed} {t.of[locale]} {progress.total} {t.complete[locale]}
                </span>
            </div>
            <LocaleToggle {locale} />
        </div>
    </div>
</header>
```

**Step 2: Verify it compiles**

Run: `npm run build`
Expected: Build succeeds (component isn't used yet, but syntax is validated by Vite)

**Step 3: Commit**

```bash
git add resources/js/components/intake/IntakeHeader.svelte
git commit -m "feat: create IntakeHeader component with breadcrumb and progress"
```

---

### Task 2: Integrate IntakeHeader into Dashboard page

**Files:**
- Modify: `resources/js/pages/intake/Dashboard.svelte:1-104`

**Context:** The Dashboard currently has its own `<header>` block (lines 96-104) with `AppLogoIcon`, "Acorn" text, and `LocaleToggle`. Replace this with `IntakeHeader`. The Dashboard is the "home" page, so its breadcrumb should just show "Dashboard" as plain text (no link since you're already there).

The Dashboard receives these props from `DashboardController`: `forms`, `progress`, `intake`, `allIntakes`, `timeEstimate`, `locale`.

**Step 1: Replace the header**

Remove these imports (no longer needed directly in this file):
- `AppLogoIcon` from `@/components/AppLogoIcon.svelte`
- `LocaleToggle` from `@/components/intake/LocaleToggle.svelte`

Add this import:
- `IntakeHeader` from `@/components/intake/IntakeHeader.svelte`

Replace lines 96-104 (the `<header>...</header>` block) with:

```svelte
    <IntakeHeader
        {locale}
        {progress}
        breadcrumbs={[
            { label: { en: 'Dashboard', es: 'Panel' } },
        ]}
    />
```

**Step 2: Run build to verify**

Run: `npm run build`
Expected: Build succeeds

**Step 3: Run existing tests**

Run: `php artisan test --compact --filter=Dashboard`
Expected: All Dashboard tests pass

Run: `php artisan test --compact tests/Browser/IntakeFlowTest.php`
Expected: All browser tests pass

**Step 4: Commit**

```bash
git add resources/js/pages/intake/Dashboard.svelte
git commit -m "feat: integrate IntakeHeader into Dashboard page"
```

---

### Task 3: Integrate IntakeHeader into Form page

**Files:**
- Modify: `resources/js/pages/intake/Form.svelte:1-114`

**Context:** The Form page has two navigation areas to replace:
1. **Mobile header** (lines 63-85): Has `AppLogoIcon`, "Acorn" text, `LocaleToggle`, close button, and a progress bar below. All of this gets replaced by IntakeHeader.
2. **Desktop**: The sidebar handles navigation, but IntakeHeader still appears above it.

The Form page receives: `schema`, `savedData`, `forms`, `progress`, `locale`. The `schema` object has `schema.key` (string) and `schema.title` (Record<string, string>) which we use for the breadcrumb.

The breadcrumb should be: Dashboard (link) > Form Title (plain text).

**Step 1: Update imports and replace the header**

Remove these imports:
- `AppLogoIcon` from `@/components/AppLogoIcon.svelte`
- `LocaleToggle` from `@/components/intake/LocaleToggle.svelte`
- `{ Link }` from `@inertiajs/svelte` (only if not used elsewhere — check: the `dashboard` import from `@/routes/intake` is still used by `FormRenderer` props, but `Link` itself is only used in the mobile header close button, so it can be removed)
- `{ dashboard }` from `@/routes/intake` — keep this, it's used in the `FormRenderer` `dashboardUrl` prop

Add this import:
- `IntakeHeader` from `@/components/intake/IntakeHeader.svelte`

Replace the `<div class="flex min-h-screen bg-background">` structure. The new structure should be:

```svelte
<div class="flex min-h-screen flex-col bg-background">
    <!-- Global Header -->
    <IntakeHeader
        {locale}
        {progress}
        breadcrumbs={[
            { label: { en: 'Dashboard', es: 'Panel' }, href: dashboard.url() },
            { label: schema.title },
        ]}
    />

    <div class="flex flex-1">
        <!-- Desktop Sidebar -->
        <div class="hidden lg:block">
            <IntakeSidebar
                {forms}
                {progress}
                activeFormKey={schemaKey}
                activeSectionIndex={currentSectionIndex}
                {locale}
                onSectionClick={(index) => formRenderer?.navigateToSection(index)}
            />
        </div>

        <!-- Main Content -->
        <div class="flex min-h-0 flex-1 flex-col">
            <!-- Form Content -->
            <main class="mx-auto w-full max-w-2xl flex-1 px-4 py-8 pb-24 sm:px-6 lg:px-8 lg:pb-8">
                <FormRenderer
                    bind:this={formRenderer}
                    {schema}
                    {savedData}
                    {locale}
                    saveUrl={save.url(schemaKey)}
                    completeUrl={complete.url(schemaKey)}
                    dashboardUrl={dashboard.url()}
                    bind:currentSectionIndex
                    onSectionChange={(index) => { currentSectionIndex = index; }}
                />
            </main>
        </div>
    </div>

    <!-- Mobile Bottom Nav -->
    <IntakeBottomNav
        currentStep={currentSectionIndex + 1}
        totalSteps={totalSections}
        {progressPercent}
        isLastSection={currentSectionIndex === totalSections - 1}
        {locale}
        onPrevious={() => formRenderer?.handlePrevious()}
        onNext={() => formRenderer?.handleNext()}
        onComplete={() => formRenderer?.handleComplete()}
    />
</div>
```

Key changes:
- Outer div becomes `flex-col` (header stacks above content)
- New `<div class="flex flex-1">` wraps sidebar + main content side by side
- Mobile header (lines 63-85) is completely removed — IntakeHeader handles it
- Mobile progress bar below the old header is removed — progress ring in IntakeHeader replaces it

**Step 2: Run build to verify**

Run: `npm run build`
Expected: Build succeeds

**Step 3: Run tests**

Run: `php artisan test --compact tests/Browser/IntakeFlowTest.php`
Expected: All browser tests pass

**Step 4: Commit**

```bash
git add resources/js/pages/intake/Form.svelte
git commit -m "feat: integrate IntakeHeader into Form page"
```

---

### Task 4: Strip IntakeSidebar header section

**Files:**
- Modify: `resources/js/components/intake/IntakeSidebar.svelte:1-142`

**Context:** The IntakeSidebar currently has its own header (lines 53-59) with logo, "Acorn", and locale toggle. Since IntakeHeader now provides these, the sidebar should drop its header section and start directly with the progress ring.

Remove:
- `AppLogoIcon` import
- `LocaleToggle` import
- The header `<div>` block (lines 53-59) and the first `<Separator />` (line 61)
- The `locale` prop is still needed for form titles and "Back to Dashboard" text — keep it

The sidebar should now start with the progress ring section (line 64).

Also: the sidebar was `sticky top-0 h-screen` but now it sits below IntakeHeader. Update to use `h-[calc(100vh-3.5rem)]` to account for the 14/h-14 header height (`3.5rem = 56px`). Keep `sticky top-14` so it sticks below the header.

**Step 1: Remove header elements from sidebar**

Remove imports for `AppLogoIcon` and `LocaleToggle`.

Remove the header div and first separator. The component should start the aside content with the progress ring:

```svelte
<aside class="sticky top-14 flex h-[calc(100vh-3.5rem)] w-[280px] shrink-0 flex-col border-r bg-primary/5">
    <!-- Progress Ring -->
    <div class="flex items-center gap-3 px-5 py-4">
        <!-- ... existing progress ring code ... -->
    </div>

    <Separator />

    <!-- Form List (unchanged) -->
    <!-- ... -->

    <Separator />

    <!-- Footer (unchanged) -->
    <!-- ... -->
</aside>
```

**Step 2: Run build**

Run: `npm run build`
Expected: Build succeeds

**Step 3: Run tests**

Run: `php artisan test --compact tests/Browser/IntakeFlowTest.php`
Expected: All browser tests pass

**Step 4: Commit**

```bash
git add resources/js/components/intake/IntakeSidebar.svelte
git commit -m "refactor: strip header from IntakeSidebar, now handled by IntakeHeader"
```

---

### Task 5: Integrate IntakeHeader into FormComplete page

**Files:**
- Modify: `resources/js/pages/intake/FormComplete.svelte:1-148`

**Context:** FormComplete currently has a floating `LocaleToggle` (lines 48-50) and a branding section at the bottom (lines 139-146). Replace both with `IntakeHeader` at the top.

FormComplete receives: `completedForm` (`{ key, title }`), `nextForm` (`{ key, title } | null`), `progress`, `locale`.

The breadcrumb should be: Dashboard (link) > Completed Form Title (plain text).

**Step 1: Update imports and layout**

Remove these imports:
- `AppLogoIcon` from `@/components/AppLogoIcon.svelte`
- `LocaleToggle` from `@/components/intake/LocaleToggle.svelte`

Add:
- `IntakeHeader` from `@/components/intake/IntakeHeader.svelte`
- `{ dashboard }` from `@/routes/intake` (for breadcrumb link)

Remove the floating locale toggle div (lines 48-50):
```svelte
<!-- REMOVE THIS -->
<div class="fixed top-4 right-4 z-50">
    <LocaleToggle {locale} />
</div>
```

Remove the branding section at the bottom (lines 139-146):
```svelte
<!-- REMOVE THIS -->
<div class="flex items-center justify-center gap-2 pt-4 ...">
    <AppLogoIcon class="size-5" />
    <span ...>JumpStart Autism Collective</span>
</div>
```

Change the outer container from centered full-screen to a column layout with the header:

```svelte
<div class="flex min-h-screen flex-col bg-background">
    <IntakeHeader
        {locale}
        {progress}
        breadcrumbs={[
            { label: { en: 'Dashboard', es: 'Panel' }, href: dashboard.url() },
            { label: completedForm.title },
        ]}
    />

    <div class="flex flex-1 flex-col items-center justify-center px-6">
        <div class="w-full max-w-md space-y-8 text-center">
            <!-- Animated checkmark (unchanged) -->
            <!-- Content (unchanged) -->
            <!-- Actions (unchanged) -->
        </div>
    </div>
</div>
```

**Step 2: Run build**

Run: `npm run build`
Expected: Build succeeds

**Step 3: Run tests**

Run: `php artisan test --compact tests/Browser/IntakeFlowTest.php`
Expected: All browser tests pass

**Step 4: Commit**

```bash
git add resources/js/pages/intake/FormComplete.svelte
git commit -m "feat: integrate IntakeHeader into FormComplete page"
```

---

### Task 6: Add browser tests for the global header

**Files:**
- Modify: `tests/Browser/IntakeFlowTest.php`

**Context:** Add browser tests to verify the global header appears on authenticated pages with correct breadcrumbs, progress, and locale toggle. Use existing test patterns from this file — `visit()`, `assertSee()`, `assertNoJavaScriptErrors()`, `on()->mobile()`.

**Step 1: Write the tests**

Add these tests to the end of the file:

```php
it('shows global header with breadcrumb on dashboard', function (): void {
    $patient = Patient::factory()->create([
        'magic_link_token' => 'test-header-dashboard',
        'magic_link_expires_at' => now()->addMinutes(30),
    ]);

    $pendingAwaitablePage = visit('/intake/verify/test-header-dashboard');

    $pendingAwaitablePage->assertPathIs('/intake/dashboard')
        ->assertSee('Acorn')
        ->assertSee('Dashboard')
        ->assertSee('EN')
        ->assertNoJavaScriptErrors();
});

it('shows global header with breadcrumb on form page', function (): void {
    $patient = Patient::factory()->create([
        'magic_link_token' => 'test-header-form',
        'magic_link_expires_at' => now()->addMinutes(30),
    ]);

    $pendingAwaitablePage = visit('/intake/verify/test-header-form');

    $pendingAwaitablePage->click('Get Started')
        ->assertSee('Acorn')
        ->assertSee('Dashboard')
        ->assertSee('Parent/Guardian Information')
        ->assertNoJavaScriptErrors();
});

it('shows global header on mobile form page', function (): void {
    $patient = Patient::factory()->create([
        'magic_link_token' => 'test-header-mobile',
        'magic_link_expires_at' => now()->addMinutes(30),
    ]);

    $on = visit('/intake/verify/test-header-mobile')->on()->mobile();

    $on->click('Get Started')
        ->assertSee('Acorn')
        ->assertSee('EN')
        ->assertNoJavaScriptErrors();
});

it('global header breadcrumb links back to dashboard from form', function (): void {
    $patient = Patient::factory()->create([
        'magic_link_token' => 'test-header-nav',
        'magic_link_expires_at' => now()->addMinutes(30),
    ]);

    $pendingAwaitablePage = visit('/intake/verify/test-header-nav');

    $pendingAwaitablePage->click('Get Started')
        ->assertSee('Parent/Guardian Information')
        ->click('Dashboard')
        ->assertPathIs('/intake/dashboard')
        ->assertNoJavaScriptErrors();
});
```

**Step 2: Run the browser tests**

Run: `php artisan test --compact tests/Browser/IntakeFlowTest.php`
Expected: All tests pass (existing + new)

**Step 3: Commit**

```bash
git add tests/Browser/IntakeFlowTest.php
git commit -m "test: add browser tests for global header breadcrumbs and navigation"
```

---

### Task 7: Run full quality checks

**Step 1: Run composer check**

Run: `composer check`
Expected: Rector, Pint, PHPStan, and all tests pass

**Step 2: Fix any issues**

If Pint formats files, stage and commit:

```bash
git add -A
git commit -m "style: apply pint formatting"
```
