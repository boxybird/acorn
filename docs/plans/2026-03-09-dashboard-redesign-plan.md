# Intake Dashboard Redesign Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Unify the dashboard into toggle cards — one per child/intake — replacing the redundant progress panel and child grid.

**Architecture:** Restructure `DashboardController` to send per-intake data (forms, progress, flags). Create a new `IntakeCard.svelte` component. Rewrite `Dashboard.svelte` to use a vertical card stack. Simplify `IntakeHeader.svelte` by removing the progress ring.

**Tech Stack:** PHP 8.4, Laravel 12, Svelte (Inertia v2), Tailwind CSS v4

---

### Task 1: Restructure DashboardController to send per-intake data

**Files:**
- Modify: `app/Http/Controllers/Intake/DashboardController.php`
- Modify: `tests/Feature/Intake/DashboardTest.php`

**Step 1: Update the test to expect the new data shape**

The current tests expect top-level `forms`, `progress`, `intake`, `timeEstimate`, `flags` props plus an `allIntakes` array with minimal data. Update to expect `intakes` (renamed from `allIntakes`) with per-intake forms/progress/flags, and top-level `notes` only.

Update `tests/Feature/Intake/DashboardTest.php`:

```php
<?php

use App\Enums\FormResponseStatus;
use App\Models\FormResponse;
use App\Models\Intake;
use App\Models\IntakeFlag;
use App\Models\IntakeNote;
use App\Models\Patient;
use App\Models\User;

test('dashboard provides per-intake data with forms and progress', function (): void {
    $patient = Patient::factory()->create();
    $intake = Intake::factory()->create(['patient_id' => $patient->id]);

    $this->withSession(['patient_id' => $patient->id, 'intake_id' => $intake->id])
        ->get(route('intake.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('intake/Dashboard')
            ->has('intakes', 1)
            ->has('intakes.0.forms')
            ->has('intakes.0.progress')
            ->has('intakes.0.time_estimate')
            ->has('intakes.0.flags')
            ->where('intakes.0.is_current', true)
        );
});

test('dashboard reflects completed sections per intake', function (): void {
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
            ->where('intakes.0.progress.completed', 1)
        );
});

test('dashboard provides time estimate per intake from incomplete forms', function (): void {
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
            ->where('intakes.0.time_estimate', fn ($value): bool => $value > 0)
        );
});

test('dashboard provides multiple intakes for multi-intake patients', function (): void {
    $patient = Patient::factory()->create();
    $intake1 = Intake::factory()->create(['patient_id' => $patient->id, 'child_name' => 'Liam']);
    Intake::factory()->create(['patient_id' => $patient->id, 'child_name' => 'Emma']);

    $this->withSession(['patient_id' => $patient->id, 'intake_id' => $intake1->id])
        ->get(route('intake.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('intake/Dashboard')
            ->has('intakes', 2)
            ->where('intakes.0.child_name', 'Liam')
            ->where('intakes.0.is_current', true)
            ->has('intakes.0.forms')
            ->has('intakes.0.progress')
            ->where('intakes.1.child_name', 'Emma')
            ->where('intakes.1.is_current', false)
            ->has('intakes.1.forms')
            ->has('intakes.1.progress')
        );
});

test('dashboard includes unresolved flags per intake', function (): void {
    $patient = Patient::factory()->create();
    $intake = Intake::factory()->create(['patient_id' => $patient->id]);
    $formResponse = FormResponse::factory()->create([
        'intake_id' => $intake->id,
        'schema_key' => 'demographics',
    ]);
    IntakeFlag::factory()->create([
        'intake_id' => $intake->id,
        'form_response_id' => $formResponse->id,
        'reason' => 'Missing date of birth',
    ]);
    IntakeFlag::factory()->resolved()->create([
        'intake_id' => $intake->id,
        'form_response_id' => $formResponse->id,
    ]);

    $this->withSession(['patient_id' => $patient->id, 'intake_id' => $intake->id])
        ->get(route('intake.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('intake/Dashboard')
            ->has('intakes.0.flags', 1)
            ->where('intakes.0.flags.0.reason', 'Missing date of birth')
            ->has('intakes.0.flags.0.form_response')
        );
});

test('dashboard includes notes at top level', function (): void {
    $patient = Patient::factory()->create();
    $intake = Intake::factory()->create(['patient_id' => $patient->id]);
    $user = User::factory()->create();
    IntakeNote::factory()->create([
        'intake_id' => $intake->id,
        'user_id' => $user->id,
        'body' => 'Staff note',
    ]);
    IntakeNote::factory()->fromPatient()->create([
        'intake_id' => $intake->id,
        'patient_id' => $patient->id,
        'body' => 'Parent note',
    ]);

    $this->withSession(['patient_id' => $patient->id, 'intake_id' => $intake->id])
        ->get(route('intake.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('intake/Dashboard')
            ->has('notes', 2)
        );
});
```

**Step 2: Run tests to verify they fail**

Run: `php artisan test --compact --filter=DashboardTest`
Expected: FAIL — old data shape doesn't match new assertions

**Step 3: Update DashboardController**

Rewrite `app/Http/Controllers/Intake/DashboardController.php` to build per-intake data:

```php
<?php

namespace App\Http\Controllers\Intake;

use App\Enums\FormResponseStatus;
use App\Http\Controllers\Controller;
use App\Models\FormResponse;
use App\Models\Intake;
use App\Models\IntakeNote;
use App\Services\FormSchemaService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, FormSchemaService $formSchemaService): Response
    {
        /** @var int $intakeId */
        $intakeId = $request->session()->get('intake_id');

        /** @var int $patientId */
        $patientId = $request->session()->get('patient_id');

        $schemas = $formSchemaService->all();

        $allIntakes = Intake::query()
            ->where('patient_id', $patientId)
            ->with(['flags' => function ($query): void {
                $query->with('formResponse')->whereNull('resolved_at');
            }])
            ->oldest()
            ->get();

        /** @var list<array<string, mixed>> $intakes */
        $intakes = $allIntakes->map(function (Intake $intake) use ($schemas, $intakeId): array {
            /** @var array<string, FormResponseStatus> $responseStatuses */
            $responseStatuses = FormResponse::query()
                ->where('intake_id', $intake->id)
                ->pluck('status', 'schema_key')
                ->all();

            $forms = array_map(function (array $schema) use ($responseStatuses): array {
                /** @var string $key */
                $key = $schema['key'];

                /** @var string $titleKey */
                $titleKey = $schema['title'];

                return [
                    'key' => $key,
                    'title' => __($titleKey),
                    'icon' => $schema['icon'] ?? null,
                    'estimated_minutes' => $schema['estimated_minutes'] ?? null,
                    'status' => $responseStatuses[$key] ?? FormResponseStatus::NotStarted,
                ];
            }, $schemas);

            $completed = count(array_filter($forms, fn (array $form): bool => $form['status'] === FormResponseStatus::Completed));

            $timeEstimate = array_sum(array_map(
                function (array $form): int {
                    if ($form['status'] === FormResponseStatus::Completed) {
                        return 0;
                    }

                    /** @var int $minutes */
                    $minutes = $form['estimated_minutes'] ?? 0;

                    return $minutes;
                },
                $forms,
            ));

            return [
                'id' => $intake->id,
                'child_name' => $intake->child_name,
                'status' => $intake->status,
                'is_current' => $intake->id === $intakeId,
                'forms' => $forms,
                'progress' => [
                    'completed' => $completed,
                    'total' => count($forms),
                ],
                'time_estimate' => $timeEstimate,
                'flags' => $intake->flags,
            ];
        })->all();

        // Notes across all patient intakes
        $intakeIds = $allIntakes->pluck('id');
        $notes = IntakeNote::query()
            ->whereIn('intake_id', $intakeIds)
            ->with(['user', 'patient'])
            ->latest()
            ->get();

        return Inertia::render('intake/Dashboard', [
            'intakes' => array_values($intakes),
            'notes' => $notes,
        ]);
    }
}
```

**Step 4: Run tests to verify they pass**

Run: `php artisan test --compact --filter=DashboardTest`
Expected: PASS

**Step 5: Run quality gate and commit**

Run: `composer check`

```bash
git add app/Http/Controllers/Intake/DashboardController.php tests/Feature/Intake/DashboardTest.php
git commit -m "refactor: restructure dashboard controller to send per-intake data"
```

---

### Task 2: Simplify IntakeHeader — remove progress ring

**Files:**
- Modify: `resources/js/components/intake/IntakeHeader.svelte`

**Step 1: Update IntakeHeader**

Remove the `progress` prop and the progress ring SVG. Keep: logo, breadcrumbs, locale toggle.

Updated `resources/js/components/intake/IntakeHeader.svelte`:

```svelte
<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import AppLogoIcon from '@/components/AppLogoIcon.svelte';
    import LocaleToggle from '@/components/intake/LocaleToggle.svelte';

    type Breadcrumb = {
        label: string;
        href?: string;
    };

    let {
        breadcrumbs = [],
    }: {
        breadcrumbs?: Breadcrumb[];
    } = $props();

    let t = $derived($page.props.translations as Record<string, string>);
    let locale = $derived($page.props.locale as string);
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
                                {crumb.label}
                            </Link>
                        {:else}
                            <span class="font-medium text-foreground">{crumb.label}</span>
                        {/if}
                    {/each}
                </nav>
            {/if}
        </div>

        <!-- Right: Locale -->
        <div class="flex items-center gap-4">
            <LocaleToggle {locale} />
        </div>
    </div>
</header>
```

**Step 2: Update all IntakeHeader usages**

Search for `<IntakeHeader` across all Svelte files. Update each usage to remove the `progress` prop. The Dashboard.svelte will be fully rewritten in Task 3, so only update other files that use IntakeHeader (like Form.svelte, FormComplete.svelte, etc.).

Check: `grep -r "IntakeHeader" resources/js/ --include="*.svelte" -l`

For each file found (except Dashboard.svelte which is rewritten in Task 3):
- Remove `{progress}` prop from `<IntakeHeader>`
- Remove any `progress` variable that was only used for the header

**Note:** The Form page (`Form.svelte`) passes `progress` to IntakeHeader. After this change, it no longer needs to. However, the Form page may use progress elsewhere — check before removing the variable.

**Step 3: Run `npm run build` to verify no build errors**

Run: `npm run build`
Expected: Build succeeds

**Step 4: Commit**

```bash
git add resources/js/components/intake/IntakeHeader.svelte
git add -u  # any other files updated
git commit -m "refactor: remove progress ring from IntakeHeader"
```

---

### Task 3: Create IntakeCard.svelte component

**Files:**
- Create: `resources/js/components/intake/IntakeCard.svelte`

**Step 1: Create the component**

This is the core new component — a toggle card showing child intake progress with an expandable form list.

Create `resources/js/components/intake/IntakeCard.svelte`:

```svelte
<script lang="ts">
    import { router, page } from '@inertiajs/svelte';
    import { Alert, AlertTitle, AlertDescription } from '@/components/ui/alert';
    import { Button } from '@/components/ui/button';
    import { Card, CardContent } from '@/components/ui/card';
    import { show } from '@/routes/intake/form';
    import { choose } from '@/routes/intake/select';

    type FormItem = {
        key: string;
        title: string;
        icon: string | null;
        estimated_minutes: number | null;
        status: 'not_started' | 'in_progress' | 'completed';
    };

    type FlagItem = {
        id: number;
        reason: string;
        form_response: {
            id: number;
            schema_key: string;
        } | null;
    };

    let {
        intakeId,
        childName,
        isCurrent,
        forms,
        progress,
        timeEstimate,
        flags,
        index,
        expanded = false,
    }: {
        intakeId: number;
        childName: string | null;
        isCurrent: boolean;
        forms: FormItem[];
        progress: { completed: number; total: number };
        timeEstimate: number;
        flags: FlagItem[];
        index: number;
        expanded?: boolean;
    } = $props();

    let t = $derived($page.props.translations as Record<string, string>);

    let isExpanded = $state(expanded);

    let progressPercent = $derived(
        progress.total > 0 ? Math.round((progress.completed / progress.total) * 100) : 0,
    );

    let allCompleted = $derived(progressPercent === 100);

    let nextFormKey = $derived(
        forms.find(f => f.status === 'in_progress')?.key
        ?? forms.find(f => f.status === 'not_started')?.key,
    );

    let label = $derived(childName ?? `Child #${index + 1}`);

    function navigateToForm(schemaKey: string): void {
        if (isCurrent) {
            router.visit(show.url(schemaKey));
        } else {
            router.post(choose.url(intakeId), {}, {
                onSuccess: () => router.visit(show.url(schemaKey)),
            });
        }
    }

    function continueIntake(): void {
        if (nextFormKey) {
            navigateToForm(nextFormKey);
        }
    }

    function statusIcon(status: string): string {
        if (status === 'completed') return 'check';
        if (status === 'in_progress') return 'progress';
        return 'empty';
    }
</script>

<Card class="overflow-hidden transition-all duration-200">
    <!-- Collapsed Header (always visible) -->
    <button
        class="flex w-full items-center justify-between p-4 text-left transition-colors hover:bg-muted/50"
        onclick={() => isExpanded = !isExpanded}
    >
        <div class="flex items-center gap-3">
            <h3 class="font-semibold text-foreground">{label}</h3>
            {#if allCompleted}
                <span class="text-xs font-medium text-primary">{t.complete ?? 'Complete'}</span>
            {:else}
                <span class="text-xs text-muted-foreground">
                    {progress.completed}/{progress.total} {t.forms ?? 'forms'}
                </span>
            {/if}
        </div>
        <div class="flex items-center gap-3">
            <div class="h-1.5 w-24 overflow-hidden rounded-full bg-muted">
                <div
                    class="h-full rounded-full bg-primary transition-all duration-500"
                    style="width: {progressPercent}%"
                ></div>
            </div>
            <svg
                class="size-4 text-muted-foreground transition-transform duration-200"
                class:rotate-180={isExpanded}
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
        </div>
    </button>

    <!-- Expanded Body -->
    {#if isExpanded}
        <CardContent class="border-t px-4 pb-4 pt-3">
            {#if !allCompleted && timeEstimate > 0}
                <p class="mb-3 text-sm text-muted-foreground">
                    ~{timeEstimate} {t.min_remaining ?? 'minutes remaining'}
                </p>
            {/if}

            <!-- Flags -->
            {#if flags.length > 0}
                <Alert variant="destructive" class="mb-3">
                    <AlertTitle>{t.action_needed ?? 'Action needed'}</AlertTitle>
                    <AlertDescription>
                        <ul class="mt-1 list-inside list-disc space-y-1">
                            {#each flags as flag (flag.id)}
                                <li>
                                    {#if flag.form_response}
                                        <button
                                            class="font-medium underline underline-offset-2 hover:text-destructive/80"
                                            onclick={() => navigateToForm(flag.form_response.schema_key)}
                                        >
                                            {forms.find(f => f.key === flag.form_response?.schema_key)?.title ?? flag.form_response.schema_key}
                                        </button>
                                        {#if flag.reason}
                                            &mdash; {flag.reason}
                                        {/if}
                                    {:else}
                                        {flag.reason}
                                    {/if}
                                </li>
                            {/each}
                        </ul>
                    </AlertDescription>
                </Alert>
            {/if}

            <!-- Form Checklist -->
            <div class="space-y-1">
                {#each forms as form (form.key)}
                    <button
                        class="flex w-full items-center gap-3 rounded-md px-3 py-2 text-left text-sm transition-colors hover:bg-muted/50"
                        onclick={() => navigateToForm(form.key)}
                    >
                        {#if statusIcon(form.status) === 'check'}
                            <svg class="size-4 shrink-0 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" /><polyline points="22 4 12 14.01 9 11.01" />
                            </svg>
                        {:else if statusIcon(form.status) === 'progress'}
                            <svg class="size-4 shrink-0 text-amber-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10" /><polyline points="12 6 12 12 16 14" />
                            </svg>
                        {:else}
                            <div class="size-4 shrink-0 rounded-full border-2 border-muted-foreground/30"></div>
                        {/if}
                        <span class="flex-1" class:text-muted-foreground={form.status === 'not_started'}>
                            {form.title}
                        </span>
                        {#if form.estimated_minutes && form.status !== 'completed'}
                            <span class="text-xs text-muted-foreground">~{form.estimated_minutes}m</span>
                        {/if}
                    </button>
                {/each}
            </div>

            <!-- Continue Button -->
            {#if !allCompleted && nextFormKey}
                <div class="mt-3">
                    <Button class="w-full" onclick={continueIntake}>
                        {t.continue ?? 'Continue'}
                    </Button>
                </div>
            {/if}

            <!-- Completed State -->
            {#if allCompleted}
                <div class="mt-3 flex items-center justify-center gap-2 rounded-md bg-primary/5 py-3 text-sm text-primary">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    {t.all_done ?? 'All done!'}
                </div>
            {/if}
        </CardContent>
    {/if}
</Card>
```

**Step 2: Build to verify component compiles**

Run: `npm run build`
Expected: Build succeeds (component isn't used yet, but no syntax errors)

**Step 3: Commit**

```bash
git add resources/js/components/intake/IntakeCard.svelte
git commit -m "feat: add IntakeCard toggle component for dashboard redesign"
```

---

### Task 4: Rewrite Dashboard.svelte

**Files:**
- Modify: `resources/js/pages/intake/Dashboard.svelte`

**Step 1: Rewrite the dashboard page**

Replace the entire dashboard with the new layout: vertical stack of IntakeCards + notes section.

Updated `resources/js/pages/intake/Dashboard.svelte`:

```svelte
<script lang="ts">
    import { onMount } from 'svelte';
    import { page, router, useForm } from '@inertiajs/svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import { Card, CardContent } from '@/components/ui/card';
    import IntakeHeader from '@/components/intake/IntakeHeader.svelte';
    import IntakeCard from '@/components/intake/IntakeCard.svelte';
    import { newMethod as create } from '@/routes/intake/select';
    import { store as storeNote } from '@/routes/intake/notes';

    type FormItem = {
        key: string;
        title: string;
        icon: string | null;
        estimated_minutes: number | null;
        status: 'not_started' | 'in_progress' | 'completed';
    };

    type FlagItem = {
        id: number;
        reason: string;
        form_response: {
            id: number;
            schema_key: string;
        } | null;
    };

    type IntakeData = {
        id: number;
        child_name: string | null;
        status: string;
        is_current: boolean;
        forms: FormItem[];
        progress: { completed: number; total: number };
        time_estimate: number;
        flags: FlagItem[];
    };

    type NoteItem = {
        id: number;
        body: string;
        created_at: string;
        user_id: number | null;
        patient_id: number | null;
        user?: { id: number; name: string } | null;
        patient?: { id: number; name: string | null; email: string } | null;
    };

    let { intakes, notes }: {
        intakes: IntakeData[];
        notes: NoteItem[];
    } = $props();

    let t = $derived($page.props.translations as Record<string, string>);

    const noteForm = useForm({ body: '' });

    function submitNote(): void {
        $noteForm.post(storeNote.url(), {
            onSuccess: () => $noteForm.reset(),
        });
    }

    function getNoteAuthor(note: NoteItem): { name: string; role: 'Staff' | 'Parent' } {
        if (note.user) {
            return { name: note.user.name, role: 'Staff' };
        }
        if (note.patient) {
            return { name: note.patient.name ?? note.patient.email, role: 'Parent' };
        }
        return { name: 'Unknown', role: 'Parent' };
    }

    let mounted = $state(false);
    onMount(() => { mounted = true; });
</script>

<div class="flex min-h-screen flex-col bg-primary/5">
    <IntakeHeader
        breadcrumbs={[
            { label: t.dashboard },
        ]}
    />

    <main class="mx-auto w-full max-w-2xl flex-1 p-4 py-8">
        <!-- Intake Cards -->
        <div class="space-y-3">
            {#each intakes as intake, i (intake.id)}
                <div class="float-up" class:visible={mounted} style="transition-delay: {i * 60}ms">
                    <IntakeCard
                        intakeId={intake.id}
                        childName={intake.child_name}
                        isCurrent={intake.is_current}
                        forms={intake.forms}
                        progress={intake.progress}
                        timeEstimate={intake.time_estimate}
                        flags={intake.flags}
                        index={i}
                        expanded={intakes.length === 1 || intake.is_current}
                    />
                </div>
            {/each}

            <!-- Add Child -->
            <div class="float-up" class:visible={mounted} style="transition-delay: {intakes.length * 60}ms">
                <button
                    class="flex w-full items-center justify-center rounded-lg border border-dashed border-border p-4 text-sm text-muted-foreground transition-all duration-200 hover:-translate-y-0.5 hover:border-primary/50 hover:text-primary"
                    onclick={() => router.post(create.url())}
                >
                    {t.add_child ?? 'Add another child'}
                </button>
            </div>
        </div>

        <!-- Notes Section -->
        <div class="mt-8 space-y-3">
            <h2 class="text-sm font-medium text-muted-foreground">{t.notes ?? 'Notes'}</h2>

            {#each notes as note (note.id)}
                {@const author = getNoteAuthor(note)}
                <Card>
                    <CardContent class="p-4">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium">{author.name}</span>
                            <Badge variant={author.role === 'Staff' ? 'secondary' : 'outline'}>
                                {author.role}
                            </Badge>
                            <span class="text-xs text-muted-foreground">
                                {new Date(note.created_at).toLocaleString()}
                            </span>
                        </div>
                        <p class="mt-2 text-sm text-foreground">{note.body}</p>
                    </CardContent>
                </Card>
            {/each}

            <Card>
                <CardContent class="p-4">
                    <form onsubmit={(e) => { e.preventDefault(); submitNote(); }}>
                        <label for="note-body" class="mb-1 block text-sm font-medium">
                            {t.add_note ?? 'Add a note'}
                        </label>
                        <textarea
                            id="note-body"
                            bind:value={$noteForm.body}
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                            rows="3"
                            placeholder={t.note_placeholder ?? 'Write a note...'}
                        ></textarea>
                        {#if $noteForm.errors.body}
                            <p class="mt-1 text-sm text-destructive">{$noteForm.errors.body}</p>
                        {/if}
                        <div class="mt-2">
                            <Button type="submit" size="sm" disabled={$noteForm.processing}>
                                {$noteForm.processing ? (t.adding ?? 'Adding...') : (t.add_note_button ?? 'Add Note')}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </main>
</div>
```

**Key decisions in this implementation:**
- `expanded={intakes.length === 1 || intake.is_current}` — single child = pre-expanded; multiple children = current intake pre-expanded
- Removed all three conditional states (welcome, in-progress, completed) — the card handles all states
- Removed the `show` import (form navigation now handled inside IntakeCard)
- Removed unused types and variables (`Progress`, `IntakeContext`, `FormItem` at page level, etc.)

**Step 2: Build**

Run: `npm run build`
Expected: Build succeeds

**Step 3: Run backend tests**

Run: `php artisan test --compact --filter=DashboardTest`
Expected: PASS (controller was already updated in Task 1)

**Step 4: Run quality gate and commit**

Run: `composer check`

```bash
git add resources/js/pages/intake/Dashboard.svelte
git commit -m "feat: rewrite dashboard with unified toggle cards"
```

---

### Task 5: Update other IntakeHeader consumers and run full quality gate

**Files:**
- Modify: any Svelte files still passing `progress` to `IntakeHeader`
- Modify: any files referencing old dashboard props

**Step 1: Find and fix all IntakeHeader usages**

Run: `grep -r "IntakeHeader" resources/js/ --include="*.svelte" -l`

For each file (Form.svelte, FormComplete.svelte, etc.):
- Remove the `progress` prop from `<IntakeHeader>` — but ONLY if `progress` isn't still used within that component for other purposes (like the Form page sidebar)
- If `progress` is used elsewhere in the component, keep the variable but stop passing it to IntakeHeader

**Step 2: Search for any remaining references to old props**

Check if any component references `intake.child_name` or `timeEstimate` (old top-level props) from the Dashboard context. These should all have been cleaned up in Task 4.

**Step 3: Run full build**

Run: `npm run build`
Expected: Build succeeds with no errors

**Step 4: Run full quality gate**

Run: `composer check`
Expected: All checks pass

**Step 5: Commit**

```bash
git add -u
git commit -m "chore: update IntakeHeader consumers after progress ring removal"
```

---

### Task 6: Manual visual verification

**Step 1: Build frontend**

Run: `npm run build`

**Step 2: Verify single-child dashboard**

Navigate to the dashboard with a single-child patient. Verify:
- One card, pre-expanded
- Shows form checklist with status indicators
- "Continue" button navigates to next incomplete form
- "Add Child" button visible below
- Notes section at bottom
- No progress ring in header
- No separate progress panel at top

**Step 3: Verify multi-child dashboard**

Navigate with a multi-child patient. Verify:
- Multiple cards, current intake pre-expanded, others collapsed
- Click collapsed card header to expand
- Click a form row in a non-current card → switches intake + navigates to form
- Progress bars accurate per card
- Flags show inside correct card

**Step 4: Verify completed intake**

Test with a fully completed intake. Verify:
- Card shows "Complete" badge
- Expanded state shows checkmarks for all forms
- "All done!" message instead of Continue button
