# Intake Form Redesign Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Redesign the intake form experience with split-screen layout, section-per-step navigation, completion interstitials, and polished visual design.

**Architecture:** Frontend-only redesign. The backend `FormController::show` needs one small change: pass all form statuses alongside the current schema so the sidebar can render the full intake checklist. The frontend gets a new `IntakeFormLayout` component with sidebar + step navigation, and `FormRenderer` is refactored to show one section at a time. A new `FormComplete` Inertia page handles the interstitial.

**Tech Stack:** Svelte 5 (runes), Inertia.js v2, Tailwind CSS v4, shadcn-svelte components, Laravel 12

---

### Task 1: Add Form Context to FormController

The sidebar needs to know all forms and their statuses, plus which form is active. Currently `FormController::show` only passes `schema` and `savedData`. We need to also pass `forms` (the same data the Dashboard returns) and `progress`.

**Files:**
- Modify: `app/Http/Controllers/Intake/FormController.php:19-39`
- Modify: `tests/Feature/Intake/FormControllerTest.php`

**Step 1: Update the existing test to expect new props**

Add assertions for the new `forms` and `progress` props in the existing "form show returns schema and saved data" test.

Open `tests/Feature/Intake/FormControllerTest.php` and update the first test:

```php
test('form show returns schema and saved data', function (): void {
    $patient = Patient::factory()->create();

    $this->withSession(['patient_id' => $patient->id])
        ->get(route('intake.form.show', 'demographics'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('intake/Form')
            ->has('schema')
            ->has('savedData')
            ->has('forms')
            ->has('progress')
            ->where('progress.total', 6)
        );
});
```

**Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter="form show returns schema and saved data"`
Expected: FAIL — `forms` and `progress` props not found

**Step 3: Update FormController::show to pass form context**

Open `app/Http/Controllers/Intake/FormController.php` and update the `show` method to inject `FormSchemaService`, query all form statuses, and pass them as props. Reuse the same aggregation logic from `DashboardController`:

```php
public function show(string $schemaKey, Request $request, FormSchemaService $formSchemaService): Response
{
    $schema = $formSchemaService->get($schemaKey);

    if ($schema === null) {
        throw new NotFoundHttpException;
    }

    /** @var int $patientId */
    $patientId = $request->session()->get('patient_id');

    $formResponse = FormResponse::query()
        ->where('patient_id', $patientId)
        ->where('schema_key', $schemaKey)
        ->first();

    /** @var array<string, string> $responseStatuses */
    $responseStatuses = FormResponse::query()
        ->where('patient_id', $patientId)
        ->pluck('status', 'schema_key')
        ->all();

    $allSchemas = $formSchemaService->all();

    $forms = array_map(function (array $s) use ($responseStatuses): array {
        /** @var string $key */
        $key = $s['key'];

        return [
            'key' => $key,
            'title' => $s['title'],
            'sections' => array_map(fn (array $section): array => [
                'key' => $section['key'],
                'title' => $section['title'],
            ], $s['sections']),
            'status' => $responseStatuses[$key] ?? 'not_started',
        ];
    }, $allSchemas);

    $completed = count(array_filter($forms, fn (array $form): bool => $form['status'] === 'completed'));

    return Inertia::render('intake/Form', [
        'schema' => $schema,
        'savedData' => $formResponse instanceof FormResponse ? $formResponse->data : [],
        'forms' => $forms,
        'progress' => [
            'completed' => $completed,
            'total' => count($forms),
        ],
    ]);
}
```

**Step 4: Run test to verify it passes**

Run: `php artisan test --compact --filter="form show returns schema and saved data"`
Expected: PASS

**Step 5: Run full quality gate and commit**

Run: `composer check`

```bash
git add app/Http/Controllers/Intake/FormController.php tests/Feature/Intake/FormControllerTest.php
git commit -m "feat: pass form context to intake form page for sidebar navigation"
```

---

### Task 2: Create the Completion Interstitial Backend

When a form is marked complete, instead of redirecting to the dashboard, redirect to a new completion page that shows what was finished and what's next.

**Files:**
- Create: `app/Http/Controllers/Intake/FormCompleteController.php`
- Modify: `app/Http/Controllers/Intake/FormController.php:77-110` (change redirect target)
- Modify: `routes/intake.php` (add new route)
- Modify: `tests/Feature/Intake/FormControllerTest.php`

**Step 1: Update the "mark complete succeeds" test to expect new redirect**

In `tests/Feature/Intake/FormControllerTest.php`, update the redirect target:

```php
test('mark complete succeeds with valid data', function (): void {
    $patient = Patient::factory()->create();

    $this->withSession(['patient_id' => $patient->id])
        ->post(route('intake.form.complete', 'demographics'), [
            'data' => [
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'phone' => '505-555-1234',
                'email' => 'jane@example.com',
                'address' => '123 Main St, Albuquerque, NM 87101',
                'preferred_language' => 'en',
                'referral_source' => 'pediatrician',
            ],
        ])
        ->assertRedirect(route('intake.form.completed', 'demographics'));

    $formResponse = $patient->formResponses()->where('schema_key', 'demographics')->first();

    expect($formResponse->isCompleted())->toBeTrue();
});
```

**Step 2: Add a test for the completion page**

Add to `tests/Feature/Intake/FormControllerTest.php`:

```php
test('completion page shows completed form with next form', function (): void {
    $patient = Patient::factory()->create();
    FormResponse::factory()->create([
        'patient_id' => $patient->id,
        'schema_key' => 'demographics',
        'status' => 'completed',
    ]);

    $this->withSession(['patient_id' => $patient->id])
        ->get(route('intake.form.completed', 'demographics'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('intake/FormComplete')
            ->has('completedForm')
            ->has('nextForm')
            ->has('progress')
            ->where('completedForm.key', 'demographics')
        );
});
```

**Step 3: Run tests to verify they fail**

Run: `php artisan test --compact --filter="FormControllerTest"`
Expected: FAIL — route and controller don't exist yet

**Step 4: Add the route**

In `routes/intake.php`, inside the authenticated middleware group, add after the `form.complete` route:

```php
Route::get('/form/{schemaKey}/completed', [FormCompleteController::class, 'show'])->name('form.completed');
```

Add the import at the top:

```php
use App\Http\Controllers\Intake\FormCompleteController;
```

**Step 5: Create the controller**

Run: `php artisan make:controller Intake/FormCompleteController --no-interaction`

Then replace the contents of `app/Http/Controllers/Intake/FormCompleteController.php` with:

```php
<?php

namespace App\Http\Controllers\Intake;

use App\Http\Controllers\Controller;
use App\Models\FormResponse;
use App\Services\FormSchemaService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FormCompleteController extends Controller
{
    public function show(string $schemaKey, Request $request, FormSchemaService $formSchemaService): Response
    {
        $schema = $formSchemaService->get($schemaKey);

        if ($schema === null) {
            throw new NotFoundHttpException;
        }

        /** @var int $patientId */
        $patientId = $request->session()->get('patient_id');

        /** @var array<string, string> $responseStatuses */
        $responseStatuses = FormResponse::query()
            ->where('patient_id', $patientId)
            ->pluck('status', 'schema_key')
            ->all();

        $allSchemas = $formSchemaService->all();
        $completed = 0;
        $nextForm = null;
        $foundCurrent = false;

        foreach ($allSchemas as $s) {
            /** @var string $key */
            $key = $s['key'];
            $isCompleted = ($responseStatuses[$key] ?? null) === 'completed';

            if ($isCompleted) {
                $completed++;
            }

            if ($foundCurrent && $nextForm === null && ! $isCompleted) {
                $nextForm = [
                    'key' => $key,
                    'title' => $s['title'],
                ];
            }

            if ($key === $schemaKey) {
                $foundCurrent = true;
            }
        }

        return Inertia::render('intake/FormComplete', [
            'completedForm' => [
                'key' => $schema['key'],
                'title' => $schema['title'],
            ],
            'nextForm' => $nextForm,
            'progress' => [
                'completed' => $completed,
                'total' => count($allSchemas),
            ],
        ]);
    }
}
```

**Step 6: Update FormController::complete redirect**

In `app/Http/Controllers/Intake/FormController.php`, change the redirect in the `complete` method from:

```php
return redirect()->route('intake.dashboard');
```

to:

```php
return redirect()->route('intake.form.completed', $schemaKey);
```

**Step 7: Run tests to verify they pass**

Run: `php artisan test --compact --filter="FormControllerTest"`
Expected: PASS

**Step 8: Run full quality gate and commit**

Run: `composer check`

```bash
git add app/Http/Controllers/Intake/FormCompleteController.php app/Http/Controllers/Intake/FormController.php routes/intake.php tests/Feature/Intake/FormControllerTest.php
git commit -m "feat: add form completion interstitial page with next-form navigation"
```

---

### Task 3: Create the Intake Sidebar Component

This is the desktop sidebar showing all forms with their statuses and the active form's sections.

**Files:**
- Create: `resources/js/components/intake/IntakeSidebar.svelte`

**Step 1: Create the component**

Create `resources/js/components/intake/IntakeSidebar.svelte`:

```svelte
<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import { Separator } from '@/components/ui/separator';
    import AppLogoIcon from '@/components/AppLogoIcon.svelte';
    import { show } from '@/routes/intake/form';
    import { dashboard } from '@/routes/intake';

    type FormItem = {
        key: string;
        title: Record<string, string>;
        sections: { key: string; title: Record<string, string> }[];
        status: 'not_started' | 'in_progress' | 'completed';
    };

    type Progress = {
        completed: number;
        total: number;
    };

    let {
        forms,
        progress,
        activeFormKey,
        activeSectionIndex = 0,
        locale = 'en',
        onSectionClick,
    }: {
        forms: FormItem[];
        progress: Progress;
        activeFormKey: string;
        activeSectionIndex?: number;
        locale?: string;
        onSectionClick?: (index: number) => void;
    } = $props();

    let progressPercent = $derived(
        progress.total > 0 ? Math.round((progress.completed / progress.total) * 100) : 0,
    );

    const circumference = 2 * Math.PI * 18;
    let strokeDashoffset = $derived(circumference - (progressPercent / 100) * circumference);
</script>

<aside class="flex h-screen w-[280px] shrink-0 flex-col border-r bg-primary/5">
    <!-- Header -->
    <div class="flex items-center gap-3 px-5 py-5">
        <AppLogoIcon class="size-7" />
        <span class="text-base font-bold text-foreground">Acorn</span>
    </div>

    <Separator />

    <!-- Progress Ring -->
    <div class="flex items-center gap-3 px-5 py-4">
        <svg class="size-10 -rotate-90" viewBox="0 0 40 40">
            <circle cx="20" cy="20" r="18" fill="none" stroke="currentColor" stroke-width="3" class="text-border" />
            <circle
                cx="20" cy="20" r="18" fill="none" stroke="currentColor" stroke-width="3"
                class="text-primary transition-all duration-500"
                stroke-dasharray={circumference}
                stroke-dashoffset={strokeDashoffset}
                stroke-linecap="round"
            />
        </svg>
        <div>
            <p class="text-sm font-medium text-foreground">{progress.completed} of {progress.total}</p>
            <p class="text-xs text-muted-foreground">forms complete</p>
        </div>
    </div>

    <Separator />

    <!-- Form List -->
    <nav class="flex-1 overflow-y-auto px-3 py-3">
        <ul class="space-y-1">
            {#each forms as form (form.key)}
                {@const isActive = form.key === activeFormKey}
                {@const isCompleted = form.status === 'completed'}
                <li>
                    <Link
                        href={show.url(form.key)}
                        class="flex items-center gap-3 rounded-md px-3 py-2 text-sm transition-colors
                            {isActive ? 'bg-primary/10 font-medium text-foreground' : 'text-muted-foreground hover:bg-primary/5 hover:text-foreground'}"
                    >
                        <!-- Status indicator -->
                        {#if isCompleted}
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4 shrink-0 text-primary" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                            </svg>
                        {:else if isActive}
                            <div class="size-4 shrink-0 rounded-full border-2 border-primary bg-primary/20"></div>
                        {:else}
                            <div class="size-4 shrink-0 rounded-full border-2 border-muted-foreground/30"></div>
                        {/if}

                        <span class="truncate">{form.title[locale]}</span>
                    </Link>

                    <!-- Section sub-steps (only for active form) -->
                    {#if isActive && form.sections.length > 1}
                        <ul class="ml-5 mt-1 space-y-0.5 border-l-2 border-border pl-4">
                            {#each form.sections as section, i (section.key)}
                                <li>
                                    <button
                                        type="button"
                                        onclick={() => onSectionClick?.(i)}
                                        class="w-full rounded-md px-2 py-1.5 text-left text-xs transition-colors
                                            {i === activeSectionIndex ? 'font-medium text-primary' : 'text-muted-foreground hover:text-foreground'}"
                                    >
                                        {section.title[locale]}
                                    </button>
                                </li>
                            {/each}
                        </ul>
                    {/if}
                </li>
            {/each}
        </ul>
    </nav>

    <Separator />

    <!-- Footer -->
    <div class="px-5 py-4">
        <Link
            href={dashboard.url()}
            class="text-xs text-muted-foreground transition-colors hover:text-foreground"
        >
            &larr; Back to Dashboard
        </Link>
    </div>
</aside>
```

**Step 2: Build to verify no compile errors**

Run: `npm run build`
Expected: Build succeeds

**Step 3: Commit**

```bash
git add resources/js/components/intake/IntakeSidebar.svelte
git commit -m "feat: create IntakeSidebar component with form checklist and section navigation"
```

---

### Task 4: Create the Mobile Bottom Nav Component

Sticky bottom navigation for mobile with Previous/Next and a progress ring.

**Files:**
- Create: `resources/js/components/intake/IntakeBottomNav.svelte`

**Step 1: Create the component**

Create `resources/js/components/intake/IntakeBottomNav.svelte`:

```svelte
<script lang="ts">
    import { Button } from '@/components/ui/button';

    let {
        currentStep,
        totalSteps,
        progressPercent = 0,
        isLastSection = false,
        locale = 'en',
        onPrevious,
        onNext,
        onComplete,
    }: {
        currentStep: number;
        totalSteps: number;
        progressPercent?: number;
        isLastSection?: boolean;
        locale?: string;
        onPrevious?: () => void;
        onNext?: () => void;
        onComplete?: () => void;
    } = $props();

    const circumference = 2 * Math.PI * 10;
    let strokeDashoffset = $derived(circumference - (progressPercent / 100) * circumference);
</script>

<div class="fixed inset-x-0 bottom-0 z-50 border-t bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/80 lg:hidden">
    <div class="flex items-center justify-between px-4 py-3">
        <!-- Previous -->
        <div class="w-24">
            {#if currentStep > 1}
                <Button variant="ghost" size="sm" onclick={onPrevious}>
                    {locale === 'es' ? 'Anterior' : 'Previous'}
                </Button>
            {/if}
        </div>

        <!-- Center: Step indicator + progress ring -->
        <div class="flex items-center gap-2">
            <svg class="size-6 -rotate-90" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2" class="text-border" />
                <circle
                    cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2"
                    class="text-primary transition-all duration-500"
                    stroke-dasharray={circumference}
                    stroke-dashoffset={strokeDashoffset}
                    stroke-linecap="round"
                />
            </svg>
            <span class="text-xs text-muted-foreground">
                {locale === 'es' ? 'Paso' : 'Step'} {currentStep} / {totalSteps}
            </span>
        </div>

        <!-- Next / Complete -->
        <div class="w-24 text-right">
            {#if isLastSection}
                <Button size="sm" onclick={onComplete}>
                    {locale === 'es' ? 'Completar' : 'Complete'}
                </Button>
            {:else}
                <Button variant="outline" size="sm" onclick={onNext}>
                    {locale === 'es' ? 'Siguiente' : 'Next'}
                </Button>
            {/if}
        </div>
    </div>
</div>
```

**Step 2: Build to verify no compile errors**

Run: `npm run build`
Expected: Build succeeds

**Step 3: Commit**

```bash
git add resources/js/components/intake/IntakeBottomNav.svelte
git commit -m "feat: create IntakeBottomNav component for mobile step navigation"
```

---

### Task 5: Refactor FormRenderer for Section-Per-Step Navigation

The core change: `FormRenderer` now shows one section at a time with transition animations and section-level validation.

**Files:**
- Modify: `resources/js/components/intake/FormRenderer.svelte` (full rewrite)

**Step 1: Rewrite FormRenderer**

Replace the contents of `resources/js/components/intake/FormRenderer.svelte` with:

```svelte
<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import { Button } from '@/components/ui/button';
    import { Card, CardContent } from '@/components/ui/card';
    import FormSection from './FormSection.svelte';

    let {
        schema,
        savedData = {},
        locale = 'en',
        saveUrl,
        completeUrl,
        dashboardUrl,
        currentSectionIndex = $bindable(0),
        onSectionChange,
    }: {
        schema: Record<string, any>;
        savedData: Record<string, any>;
        locale: string;
        saveUrl: string;
        completeUrl: string;
        dashboardUrl: string;
        currentSectionIndex?: number;
        onSectionChange?: (index: number) => void;
    } = $props();

    let sections: any[] = $derived(schema.sections ?? []);
    let currentSection = $derived(sections[currentSectionIndex]);
    let isFirstSection = $derived(currentSectionIndex === 0);
    let isLastSection = $derived(currentSectionIndex === sections.length - 1);
    let transitioning = $state(false);

    function initializeFormData(): Record<string, any> {
        const data: Record<string, any> = { ...savedData };
        for (const section of schema.sections ?? []) {
            for (const field of section.fields ?? []) {
                if (data[field.key] === undefined) {
                    data[field.key] = field.type === 'checkbox' ? false : '';
                }
            }
        }
        return data;
    }

    let formData = $state<Record<string, any>>(initializeFormData());
    let errors = $state<Record<string, string>>({});
    let saveStatus = $state<'idle' | 'saving' | 'saved'>('idle');
    let saveTimeout: ReturnType<typeof setTimeout>;

    function autoSave() {
        clearTimeout(saveTimeout);
        saveStatus = 'saving';

        saveTimeout = setTimeout(() => {
            fetch(saveUrl, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-XSRF-TOKEN': getCsrfToken(),
                },
                body: JSON.stringify({ data: formData }),
                credentials: 'same-origin',
            }).then(() => {
                saveStatus = 'saved';
                setTimeout(() => { saveStatus = 'idle'; }, 2000);
            }).catch(() => {
                saveStatus = 'idle';
            });
        }, 1000);
    }

    function getCsrfToken(): string {
        const cookie = document.cookie
            .split('; ')
            .find((row) => row.startsWith('XSRF-TOKEN='));
        return cookie ? decodeURIComponent(cookie.split('=')[1]) : '';
    }

    function goToSection(index: number) {
        if (index === currentSectionIndex || index < 0 || index >= sections.length) return;

        transitioning = true;
        setTimeout(() => {
            currentSectionIndex = index;
            onSectionChange?.(index);
            transitioning = false;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }, 150);

        autoSave();
    }

    function handleNext() {
        if (!isLastSection) {
            goToSection(currentSectionIndex + 1);
        }
    }

    function handlePrevious() {
        if (!isFirstSection) {
            goToSection(currentSectionIndex - 1);
        }
    }

    function handleComplete() {
        router.post(completeUrl, { data: formData }, {
            onError: (formErrors) => {
                errors = {};
                for (const [key, message] of Object.entries(formErrors)) {
                    const fieldKey = key.replace('data.', '');
                    errors[fieldKey] = message as string;
                }

                // Navigate to the first section that has errors
                const currentSectionFields = currentSection.fields.map((f: any) => f.key);
                const hasErrorInCurrent = currentSectionFields.some((k: string) => errors[k]);
                if (!hasErrorInCurrent) {
                    for (let i = 0; i < sections.length; i++) {
                        const sectionFields = sections[i].fields.map((f: any) => f.key);
                        if (sectionFields.some((k: string) => errors[k])) {
                            goToSection(i);
                            break;
                        }
                    }
                }
            },
        });
    }

    export function navigateToSection(index: number) {
        goToSection(index);
    }

    export { handleNext, handlePrevious, handleComplete, isLastSection, isFirstSection, sections, currentSectionIndex, formData };
</script>

<div class="space-y-6">
    <!-- Header with title and save status -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-foreground">{schema.title[locale]}</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                {currentSection?.title[locale]}
            </p>
        </div>
        <div class="flex items-center gap-3">
            {#if saveStatus === 'saving'}
                <span class="text-xs text-muted-foreground">
                    {locale === 'es' ? 'Guardando...' : 'Saving...'}
                </span>
            {:else if saveStatus === 'saved'}
                <span class="flex items-center gap-1 text-xs text-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                    </svg>
                    {locale === 'es' ? 'Guardado' : 'Saved'}
                </span>
            {/if}
        </div>
    </div>

    <!-- Section content with fade transition -->
    <Card>
        <CardContent class="p-6 sm:p-8">
            <div
                class="transition-opacity duration-150"
                class:opacity-0={transitioning}
                class:opacity-100={!transitioning}
            >
                {#if currentSection}
                    {#key currentSection.key}
                        <FormSection
                            section={currentSection}
                            bind:formData
                            {locale}
                            {errors}
                            onFieldBlur={() => autoSave()}
                        />
                    {/key}
                {/if}
            </div>
        </CardContent>
    </Card>

    <!-- Desktop navigation buttons -->
    <div class="hidden items-center justify-between lg:flex">
        <div>
            {#if isFirstSection}
                <Button variant="outline" onclick={() => router.visit(dashboardUrl)}>
                    {locale === 'es' ? 'Guardar y Salir' : 'Save & Exit'}
                </Button>
            {:else}
                <Button variant="outline" onclick={handlePrevious}>
                    {locale === 'es' ? 'Anterior' : 'Previous'}
                </Button>
            {/if}
        </div>
        <div>
            {#if isLastSection}
                <Button onclick={handleComplete}>
                    {locale === 'es' ? 'Completar Formulario' : 'Complete Form'}
                </Button>
            {:else}
                <Button onclick={handleNext}>
                    {locale === 'es' ? 'Siguiente' : 'Next'}
                </Button>
            {/if}
        </div>
    </div>
</div>
```

**Step 2: Build to verify no compile errors**

Run: `npm run build`
Expected: Build succeeds (there may be warnings about unused exports, which is fine)

**Step 3: Commit**

```bash
git add resources/js/components/intake/FormRenderer.svelte
git commit -m "refactor: FormRenderer to section-per-step navigation with fade transitions"
```

---

### Task 6: Rewrite the Form Page with Split Layout

The main page component that brings together the sidebar, form renderer, and bottom nav.

**Files:**
- Modify: `resources/js/pages/intake/Form.svelte` (full rewrite)

**Step 1: Rewrite the Form page**

Replace the contents of `resources/js/pages/intake/Form.svelte`:

```svelte
<script lang="ts">
    import FormRenderer from '@/components/intake/FormRenderer.svelte';
    import IntakeSidebar from '@/components/intake/IntakeSidebar.svelte';
    import IntakeBottomNav from '@/components/intake/IntakeBottomNav.svelte';
    import AppLogoIcon from '@/components/AppLogoIcon.svelte';
    import { Link } from '@inertiajs/svelte';
    import { save, complete } from '@/routes/intake/form';
    import { dashboard } from '@/routes/intake';

    type FormItem = {
        key: string;
        title: Record<string, string>;
        sections: { key: string; title: Record<string, string> }[];
        status: 'not_started' | 'in_progress' | 'completed';
    };

    type Progress = {
        completed: number;
        total: number;
    };

    let {
        schema,
        savedData,
        forms,
        progress,
    }: {
        schema: Record<string, any>;
        savedData: Record<string, any>;
        forms: FormItem[];
        progress: Progress;
    } = $props();

    const locale = 'en';
    const schemaKey = schema.key as string;
    const totalSections = (schema.sections ?? []).length;

    let currentSectionIndex = $state(0);
    let progressPercent = $derived(
        progress.total > 0 ? Math.round((progress.completed / progress.total) * 100) : 0,
    );

    let formRenderer: FormRenderer;

    function handleSectionClick(index: number) {
        currentSectionIndex = index;
    }
</script>

<div class="flex min-h-screen bg-background">
    <!-- Desktop Sidebar -->
    <div class="hidden lg:block">
        <IntakeSidebar
            {forms}
            {progress}
            activeFormKey={schemaKey}
            activeSectionIndex={currentSectionIndex}
            {locale}
            onSectionClick={handleSectionClick}
        />
    </div>

    <!-- Main Content -->
    <div class="flex min-h-screen flex-1 flex-col">
        <!-- Mobile Header -->
        <header class="border-b px-4 py-3 lg:hidden">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <AppLogoIcon class="size-6" />
                    <span class="text-sm font-bold text-foreground">Acorn</span>
                </div>
                <Link
                    href={dashboard.url()}
                    class="text-sm text-muted-foreground transition-colors hover:text-foreground"
                >
                    &times;
                </Link>
            </div>
        </header>

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

**Step 2: Build to verify no compile errors**

Run: `npm run build`
Expected: Build succeeds

**Step 3: Run existing tests**

Run: `php artisan test --compact --filter="FormControllerTest"`
Expected: PASS — backend tests still pass since the Inertia component name hasn't changed

**Step 4: Commit**

```bash
git add resources/js/pages/intake/Form.svelte
git commit -m "feat: rewrite Form page with split-screen layout, sidebar, and mobile bottom nav"
```

---

### Task 7: Create the Completion Interstitial Page

The full-page interstitial shown after completing a form.

**Files:**
- Create: `resources/js/pages/intake/FormComplete.svelte`

**Step 1: Create the page component**

Create `resources/js/pages/intake/FormComplete.svelte`:

```svelte
<script lang="ts">
    import { onMount } from 'svelte';
    import { Link } from '@inertiajs/svelte';
    import { Button } from '@/components/ui/button';
    import AppLogoIcon from '@/components/AppLogoIcon.svelte';
    import { show } from '@/routes/intake/form';
    import { dashboard } from '@/routes/intake';

    let {
        completedForm,
        nextForm,
        progress,
    }: {
        completedForm: { key: string; title: Record<string, string> };
        nextForm: { key: string; title: Record<string, string> } | null;
        progress: { completed: number; total: number };
    } = $props();

    const locale = 'en';
    const remaining = progress.total - progress.completed;
    const allDone = remaining === 0;

    let visible = $state(false);
    let checkVisible = $state(false);

    onMount(() => {
        setTimeout(() => { checkVisible = true; }, 100);
        setTimeout(() => { visible = true; }, 400);
    });
</script>

<div class="flex min-h-screen flex-col items-center justify-center bg-background px-6">
    <div class="w-full max-w-md space-y-8 text-center">
        <!-- Animated checkmark -->
        <div class="flex justify-center">
            <div
                class="flex size-20 items-center justify-center rounded-full bg-primary/10 transition-all duration-500"
                class:scale-100={checkVisible}
                class:scale-0={!checkVisible}
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="size-10 text-primary"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                >
                    <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                </svg>
            </div>
        </div>

        <!-- Content -->
        <div
            class="space-y-3 transition-all duration-500"
            class:opacity-0={!visible}
            class:translate-y-4={!visible}
            class:opacity-100={visible}
            class:translate-y-0={visible}
        >
            <h1 class="text-2xl font-bold text-foreground">
                {completedForm.title[locale]}
            </h1>
            <p class="text-lg text-primary">Complete!</p>

            {#if allDone}
                <p class="text-sm text-muted-foreground">
                    You're all done! Your intake paperwork has been submitted. The JumpStart team will review your information and reach out soon.
                </p>
            {:else}
                <p class="text-sm text-muted-foreground">
                    {progress.completed} of {progress.total} forms complete &mdash;
                    {remaining === 1 ? 'just 1 more to go!' : `${remaining} more to go!`}
                </p>
            {/if}
        </div>

        <!-- Actions -->
        <div
            class="flex flex-col gap-3 transition-all duration-500 delay-100"
            class:opacity-0={!visible}
            class:translate-y-4={!visible}
            class:opacity-100={visible}
            class:translate-y-0={visible}
        >
            {#if allDone}
                <Button asChild size="lg" class="w-full">
                    {#snippet children(props)}
                        <Link href={dashboard.url()} {...props}>
                            Back to Dashboard
                        </Link>
                    {/snippet}
                </Button>
            {:else if nextForm}
                <Button asChild size="lg" class="w-full">
                    {#snippet children(props)}
                        <Link href={show.url(nextForm.key)} {...props}>
                            Continue to {nextForm.title[locale]}
                        </Link>
                    {/snippet}
                </Button>
                <Button asChild variant="outline" size="lg" class="w-full">
                    {#snippet children(props)}
                        <Link href={dashboard.url()} {...props}>
                            Back to Dashboard
                        </Link>
                    {/snippet}
                </Button>
            {:else}
                <Button asChild size="lg" class="w-full">
                    {#snippet children(props)}
                        <Link href={dashboard.url()} {...props}>
                            Back to Dashboard
                        </Link>
                    {/snippet}
                </Button>
            {/if}
        </div>

        <!-- Branding -->
        <div
            class="flex items-center justify-center gap-2 pt-4 transition-all duration-500 delay-200"
            class:opacity-0={!visible}
            class:opacity-100={visible}
        >
            <AppLogoIcon class="size-5" />
            <span class="text-xs text-muted-foreground">JumpStart Autism Collective</span>
        </div>
    </div>
</div>
```

**Step 2: Build to verify no compile errors**

Run: `npm run build`
Expected: Build succeeds

**Step 3: Run tests to confirm the interstitial page renders**

Run: `php artisan test --compact --filter="completion page"`
Expected: PASS (test was added in Task 2)

**Step 4: Commit**

```bash
git add resources/js/pages/intake/FormComplete.svelte
git commit -m "feat: create FormComplete interstitial page with animated checkmark and next-form navigation"
```

---

### Task 8: Update FormSection Visual Polish

Add card styling and better spacing to FormSection, and update it to use the non-deprecated dynamic component syntax.

**Files:**
- Modify: `resources/js/components/intake/FormSection.svelte`

**Step 1: Update FormSection**

Replace the template portion of `resources/js/components/intake/FormSection.svelte` (keep the script tag as-is, just update the HTML):

Change the closing template from:

```svelte
<div class="space-y-6">
    <h2 class="text-lg font-semibold text-foreground">{section.title[locale]}</h2>
    {#each section.fields as field (field.key)}
        {#if shouldShow(field)}
            <svelte:component
                this={getComponent(field.type)}
                {field}
                bind:value={formData[field.key]}
                {locale}
                error={errors[field.key] ?? ''}
                onblur={() => onFieldBlur?.(field.key)}
            />
        {/if}
    {/each}
</div>
```

To:

```svelte
<div class="space-y-6">
    {#each section.fields as field (field.key)}
        {#if shouldShow(field)}
            {@const FieldComponent = getComponent(field.type)}
            <FieldComponent
                {field}
                bind:value={formData[field.key]}
                {locale}
                error={errors[field.key] ?? ''}
                onblur={() => onFieldBlur?.(field.key)}
            />
        {/if}
    {/each}
</div>
```

Note: The section title is now rendered by `FormRenderer` above the card, so we remove it from `FormSection` to avoid duplication.

**Step 2: Build to verify no compile errors**

Run: `npm run build`
Expected: Build succeeds, and the `svelte:component` deprecation warning should be gone

**Step 3: Commit**

```bash
git add resources/js/components/intake/FormSection.svelte
git commit -m "refactor: update FormSection to modern dynamic component syntax, remove duplicate title"
```

---

### Task 9: Full Integration Test

Run the complete test suite and build to make sure everything works together.

**Step 1: Run the full quality gate**

Run: `composer check`
Expected: Rector OK, Pint OK, PHPStan OK, all tests pass

**Step 2: Run frontend build**

Run: `npm run build`
Expected: Build succeeds with no errors

**Step 3: Final commit if any formatting changes**

If `composer check` made any automatic fixes:

```bash
git add -A
git commit -m "chore: apply formatting and static analysis fixes"
```
