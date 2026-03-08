# Unified Hub Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Replace Dashboard + IntakeSelector with a single Hub page that shows orientation, child selection, status with time estimates, and form entry.

**Architecture:** Simplify MagicLinkController to always redirect to dashboard. Expand DashboardController to load all intakes and compute time estimates. Rewrite Dashboard.svelte as a Hub with welcome card, child cards (multi-intake), and time-estimated form checklist. Remove IntakeSelector GET route and Svelte component. Keep POST routes for choose/create on IntakeSelectorController.

**Tech Stack:** Laravel 12, Inertia v2, Svelte 5, Pest 4, Wayfinder

---

### Task 1: Simplify MagicLinkController — Always Redirect to Dashboard

**Files:**
- Modify: `app/Http/Controllers/Intake/MagicLinkController.php:52-69`
- Test: `tests/Feature/Intake/MagicLinkTest.php`

**Step 1: Update the test for multi-intake redirect**

In `tests/Feature/Intake/MagicLinkTest.php`, there's no test for the multi-intake case — it lives in `IntakeSelectorTest.php:84-90`. Add a new test and update expectations:

```php
test('valid magic link with multiple intakes sets first intake and redirects to dashboard', function (): void {
    $patient = Patient::factory()->withMagicLink()->create();
    $firstIntake = Intake::factory()->create(['patient_id' => $patient->id]);
    Intake::factory()->create(['patient_id' => $patient->id]);

    $this->get(route('intake.verify', ['token' => $patient->magic_link_token]))
        ->assertRedirect(route('intake.dashboard'))
        ->assertSessionHas('patient_id', $patient->id)
        ->assertSessionHas('intake_id', $firstIntake->id);
});
```

**Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter="valid magic link with multiple intakes sets first intake"`
Expected: FAIL — currently redirects to `intake.select`, not `intake.dashboard`

**Step 3: Simplify `verify()` method**

In `app/Http/Controllers/Intake/MagicLinkController.php`, replace the three-branch logic (lines 52-69) with:

```php
$intake = Intake::query()
    ->where('patient_id', $patient->id)
    ->oldest()
    ->first();

if (! $intake) {
    $intake = Intake::query()->create(['patient_id' => $patient->id]);
}

$request->session()->put('intake_id', $intake->id);

return redirect()->route('intake.dashboard');
```

**Step 4: Run tests to verify they pass**

Run: `php artisan test --compact --filter="MagicLinkTest"`
Expected: All pass (including existing tests)

**Step 5: Run `composer check`**

Run: `composer check`
Expected: PASS

**Step 6: Commit**

```bash
git add app/Http/Controllers/Intake/MagicLinkController.php tests/Feature/Intake/MagicLinkTest.php
git commit -m "feat: simplify MagicLinkController to always redirect to dashboard"
```

---

### Task 2: Expand DashboardController — Load All Intakes and Time Estimate

**Files:**
- Modify: `app/Http/Controllers/Intake/DashboardController.php`
- Test: `tests/Feature/Intake/DashboardTest.php`

**Step 1: Write failing tests for new Hub data**

Add these tests to `tests/Feature/Intake/DashboardTest.php`:

```php
test('dashboard provides time estimate from incomplete forms', function (): void {
    $patient = Patient::factory()->create();
    $intake = Intake::factory()->create(['patient_id' => $patient->id]);
    FormResponse::factory()->completed()->create([
        'intake_id' => $intake->id,
        'schema_key' => 'demographics',
    ]);

    $this->withSession(['patient_id' => $patient->id, 'intake_id' => $intake->id])
        ->get(route('intake.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('intake/Dashboard')
            ->has('timeEstimate')
            ->where('timeEstimate', fn ($value) => $value > 0)
        );
});

test('dashboard provides all intakes for multi-intake patients', function (): void {
    $patient = Patient::factory()->create();
    $intake1 = Intake::factory()->create(['patient_id' => $patient->id, 'child_name' => 'Liam']);
    Intake::factory()->create(['patient_id' => $patient->id, 'child_name' => 'Emma']);

    $this->withSession(['patient_id' => $patient->id, 'intake_id' => $intake1->id])
        ->get(route('intake.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('intake/Dashboard')
            ->has('allIntakes', 2)
            ->where('allIntakes.0.child_name', 'Liam')
            ->where('allIntakes.1.child_name', 'Emma')
        );
});

test('dashboard provides single intake for single-intake patients', function (): void {
    $patient = Patient::factory()->create();
    $intake = Intake::factory()->create(['patient_id' => $patient->id]);

    $this->withSession(['patient_id' => $patient->id, 'intake_id' => $intake->id])
        ->get(route('intake.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('intake/Dashboard')
            ->has('allIntakes', 1)
        );
});
```

**Step 2: Run tests to verify they fail**

Run: `php artisan test --compact --filter="DashboardTest"`
Expected: New tests FAIL — `timeEstimate` and `allIntakes` props don't exist yet

**Step 3: Expand the DashboardController**

In `app/Http/Controllers/Intake/DashboardController.php`, add time estimate calculation and all-intakes loading. Replace the `Inertia::render` return with:

```php
// After the existing $forms array is built...

// Time estimate: sum estimated_minutes for non-completed forms
$timeEstimate = array_sum(
    array_map(
        fn (array $form): int => $form['status'] !== 'completed' ? (int) ($form['estimated_minutes'] ?? 0) : 0,
        $forms,
    )
);

// All intakes for this patient (for child cards)
$allIntakes = Intake::query()
    ->where('patient_id', $patientId)
    ->withCount([
        'formResponses as completed_forms_count' => function ($query): void {
            $query->where('status', 'completed');
        },
    ])
    ->oldest()
    ->get()
    ->map(fn (Intake $i): array => [
        'id' => $i->id,
        'child_name' => $i->child_name,
        'status' => $i->status,
        'completed_forms_count' => (int) $i->getAttribute('completed_forms_count'),
        'is_current' => $i->id === $intakeId,
    ])
    ->all();

return Inertia::render('intake/Dashboard', [
    'forms' => $forms,
    'progress' => [
        'completed' => $completed,
        'total' => count($forms),
    ],
    'intake' => [
        'id' => $intake->id,
        'child_name' => $intake->child_name,
    ],
    'allIntakes' => array_values($allIntakes),
    'timeEstimate' => $timeEstimate,
]);
```

Remove the `$hasMultipleIntakes` variable — replaced by `allIntakes`.

**Step 4: Run tests to verify they pass**

Run: `php artisan test --compact --filter="DashboardTest"`
Expected: All pass

**Step 5: Run `composer check`**

Run: `composer check`
Expected: PASS

**Step 6: Commit**

```bash
git add app/Http/Controllers/Intake/DashboardController.php tests/Feature/Intake/DashboardTest.php
git commit -m "feat: expand DashboardController with time estimates and all-intakes data"
```

---

### Task 3: Rewrite Dashboard.svelte as Unified Hub

**Files:**
- Modify: `resources/js/pages/intake/Dashboard.svelte`

**Step 1: Update prop types**

Replace old types with new Hub types. Remove `hasMultipleIntakes` boolean. Add `allIntakes`, `timeEstimate`, update `intake` to include `id`.

```typescript
type IntakeCard = {
    id: number;
    child_name: string | null;
    status: string;
    completed_forms_count: number;
    is_current: boolean;
};

type IntakeContext = {
    id: number;
    child_name: string | null;
};

let { forms, progress, intake, allIntakes, timeEstimate }: {
    forms: FormItem[];
    progress: Progress;
    intake: IntakeContext;
    allIntakes: IntakeCard[];
    timeEstimate: number;
} = $props();
```

**Step 2: Add Welcome Card**

Show welcome card when all forms are `not_started`:

```svelte
{#if forms.every(f => f.status === 'not_started')}
    <Card class="border-primary/20 bg-primary/5">
        <CardContent class="p-5">
            <h2 class="font-semibold text-foreground">Welcome!</h2>
            <p class="mt-1 text-sm text-muted-foreground">
                Complete {forms.length} short forms at your own pace. Your progress saves automatically — come back anytime.
            </p>
        </CardContent>
    </Card>
{/if}
```

**Step 3: Add Time Estimate**

Replace the plain "X of Y complete" with time-aware progress:

```svelte
<div class="space-y-2">
    <div class="flex items-center justify-between text-sm">
        <span class="text-muted-foreground">
            {#if timeEstimate > 0}
                ~{timeEstimate} min remaining
            {:else}
                All complete
            {/if}
        </span>
        <span class="font-medium text-foreground">{progress.completed} of {progress.total} complete</span>
    </div>
    <!-- progress bar unchanged -->
</div>
```

**Step 4: Add Child Cards for Multi-Intake**

Import `router` from `@inertiajs/svelte` and `choose` from `@/routes/intake/select`. Show child cards when `allIntakes.length > 1`:

```svelte
{#if allIntakes.length > 1}
    <div class="flex flex-wrap gap-2">
        {#each allIntakes as intakeCard (intakeCard.id)}
            <button
                class="rounded-lg border px-4 py-2 text-sm font-medium transition-colors
                    {intakeCard.is_current
                        ? 'border-primary bg-primary/10 text-primary'
                        : 'border-border bg-background text-muted-foreground hover:border-primary/50'}"
                onclick={() => {
                    if (!intakeCard.is_current) {
                        router.post(choose.url(intakeCard.id));
                    }
                }}
            >
                {intakeCard.child_name ?? `Child #${allIntakes.indexOf(intakeCard) + 1}`}
            </button>
        {/each}
        <button
            class="rounded-lg border border-dashed border-border px-4 py-2 text-sm text-muted-foreground hover:border-primary/50 hover:text-primary"
            onclick={() => router.post(create.url())}
        >
            + Add child
        </button>
    </div>
{/if}
```

Import `create` as: `import { choose, newMethod as create } from '@/routes/intake/select';`

**Step 5: Remove old "Switch child" link**

Remove the `{#if hasMultipleIntakes}` block and the `select` import from `@/routes/intake`. Remove the `Link` import if no longer used elsewhere in this file.

**Step 6: Run `npm run build` to verify frontend compiles**

Run: `npm run build`
Expected: BUILD SUCCESS

**Step 7: Commit**

```bash
git add resources/js/pages/intake/Dashboard.svelte
git commit -m "feat: rewrite Dashboard as Unified Hub with welcome card, time estimates, and child cards"
```

---

### Task 4: Remove IntakeSelector GET Route and Svelte Component

**Files:**
- Delete: `resources/js/pages/intake/IntakeSelector.svelte`
- Modify: `routes/intake.php:25`
- Modify: `app/Http/Controllers/Intake/IntakeSelectorController.php`

**Step 1: Remove the GET route**

In `routes/intake.php`, remove line 25:
```php
Route::get('/select', [IntakeSelectorController::class, 'index'])->name('select');
```

Keep the POST routes (`select.new` and `select.choose`).

**Step 2: Remove the `index()` method from IntakeSelectorController**

In `app/Http/Controllers/Intake/IntakeSelectorController.php`, delete the `index()` method (lines 18-52) and the unused imports (`Inertia`, `Response`).

**Step 3: Delete IntakeSelector.svelte**

```bash
rm resources/js/pages/intake/IntakeSelector.svelte
```

**Step 4: Run `npm run build` to verify frontend still compiles**

Run: `npm run build`
Expected: BUILD SUCCESS (IntakeSelector.svelte was only referenced by Inertia::render which is now removed)

**Step 5: Run `composer check`**

Run: `composer check`
Expected: PASS (some tests will fail — we fix those in Task 5)

**Step 6: Commit**

```bash
git add routes/intake.php app/Http/Controllers/Intake/IntakeSelectorController.php
git rm resources/js/pages/intake/IntakeSelector.svelte
git commit -m "feat: remove IntakeSelector page and GET route, keep POST endpoints"
```

---

### Task 5: Update IntakeSelectorTest for Removed GET Route

**Files:**
- Modify: `tests/Feature/Intake/IntakeSelectorTest.php`

**Step 1: Remove the GET route test and the multi-intake redirect test**

Remove these two tests from `tests/Feature/Intake/IntakeSelectorTest.php`:

1. `'intake selector shows all intakes for patient'` (lines 7-18) — GET route no longer exists
2. `'magic link with multiple intakes redirects to selector'` (lines 84-90) — now covered by the test we added in Task 1

Keep the remaining three tests:
- `'selecting an intake sets intake_id in session'`
- `'cannot select another patients intake'`
- `'creating new intake copies demographics and insurance data'`

**Step 2: Run tests to verify they pass**

Run: `php artisan test --compact --filter="IntakeSelectorTest"`
Expected: All 3 remaining tests PASS

**Step 3: Run `composer check`**

Run: `composer check`
Expected: PASS

**Step 4: Commit**

```bash
git add tests/Feature/Intake/IntakeSelectorTest.php
git commit -m "test: remove IntakeSelector GET route tests, keep POST endpoint tests"
```

---

### Task 6: Update Browser Tests for Unified Hub

**Files:**
- Modify: `tests/Browser/IntakeFlowTest.php`

**Step 1: Update the multi-intake browser test**

Replace `'shows intake selector for patients with multiple intakes'` (lines 146-159) with a test that verifies the Hub shows child cards:

```php
it('shows child cards on hub for patients with multiple intakes', function (): void {
    $patient = Patient::factory()->create([
        'magic_link_token' => 'test-multi-intake',
        'magic_link_expires_at' => now()->addMinutes(30),
    ]);
    Intake::factory()->create(['patient_id' => $patient->id, 'child_name' => 'Liam']);
    Intake::factory()->create(['patient_id' => $patient->id, 'child_name' => 'Emma']);

    $pendingAwaitablePage = visit('/intake/verify/test-multi-intake');

    $pendingAwaitablePage->assertPathIs('/intake/dashboard')
        ->assertSee('Liam')
        ->assertSee('Emma')
        ->assertSee('Add child')
        ->assertNoJavaScriptErrors();
});
```

**Step 2: Add welcome card browser test**

```php
it('shows welcome card when no forms started', function (): void {
    $patient = Patient::factory()->create([
        'magic_link_token' => 'test-welcome',
        'magic_link_expires_at' => now()->addMinutes(30),
    ]);

    $pendingAwaitablePage = visit('/intake/verify/test-welcome');

    $pendingAwaitablePage->assertPathIs('/intake/dashboard')
        ->assertSee('Welcome!')
        ->assertSee('Your progress saves automatically')
        ->assertNoJavaScriptErrors();
});
```

**Step 3: Add time estimate browser test**

```php
it('shows time estimate on hub', function (): void {
    $patient = Patient::factory()->create([
        'magic_link_token' => 'test-time-estimate',
        'magic_link_expires_at' => now()->addMinutes(30),
    ]);

    $pendingAwaitablePage = visit('/intake/verify/test-time-estimate');

    $pendingAwaitablePage->assertPathIs('/intake/dashboard')
        ->assertSee('min remaining')
        ->assertNoJavaScriptErrors();
});
```

**Step 4: Run browser tests**

Run: `php artisan test --compact --filter="IntakeFlowTest"`
Expected: All pass

**Step 5: Commit**

```bash
git add tests/Browser/IntakeFlowTest.php
git commit -m "test: update browser tests for Unified Hub — child cards, welcome card, time estimate"
```

---

### Task 7: Update PatientSeeder for Hub Testing

**Files:**
- Modify: `database/seeders/PatientSeeder.php`

**Step 1: Verify seeder still works with removed GET route**

The seeder doesn't reference routes, but the multi-child patient (Sarah Williams) has a magic link for manual testing. Verify `php artisan migrate:fresh --seed` works.

Run: `php artisan migrate:fresh --seed`
Expected: SUCCESS — no exceptions

**Step 2: Run `composer check`**

Run: `composer check`
Expected: PASS

**Step 3: Commit (if any changes needed)**

Only commit if changes were required. If seeder works as-is, skip this commit.
