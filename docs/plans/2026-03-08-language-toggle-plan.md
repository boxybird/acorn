# Language Toggle Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add a persistent EN/ES language toggle visible on every intake page, including the pre-auth landing page.

**Architecture:** Create a reusable `LocaleToggle.svelte` component included in each page's header. Pre-auth pages store locale in session via a guest endpoint; authenticated pages save to the Patient record. The `SetPatientLocale` middleware is extended to check session fallback. On magic link verification, session locale is copied to the Patient record if no preference exists.

**Tech Stack:** Laravel 12, Inertia v2, Svelte 5, Tailwind CSS v4, Pest 4

---

### Task 1: Guest Locale Endpoint & Middleware Update

**Files:**
- Modify: `routes/intake.php`
- Modify: `app/Http/Middleware/SetPatientLocale.php`
- Modify: `app/Http/Controllers/Intake/MagicLinkController.php`
- Test: `tests/Feature/Intake/LocaleTest.php`

**Step 1: Write the failing tests**

Create `tests/Feature/Intake/LocaleTest.php`:

```bash
php artisan make:test --pest Intake/LocaleTest
```

```php
<?php

use App\Models\Intake;
use App\Models\Patient;

test('guest can set locale in session', function (): void {
    $this->post(route('intake.set-locale-guest'), ['locale' => 'es'])
        ->assertOk()
        ->assertJson(['status' => 'ok']);

    $this->get(route('intake.landing'))
        ->assertOk();

    // Verify locale is stored in session
    expect(session('locale'))->toBe('es');
});

test('guest locale validates input', function (): void {
    $this->post(route('intake.set-locale-guest'), ['locale' => 'fr'])
        ->assertSessionHasErrors('locale');
});

test('authenticated locale switch updates patient record', function (): void {
    $patient = Patient::factory()->create(['preferred_locale' => 'en']);
    $intake = Intake::factory()->create(['patient_id' => $patient->id]);

    $this->withSession(['patient_id' => $patient->id, 'intake_id' => $intake->id])
        ->post(route('intake.set-locale'), ['locale' => 'es'])
        ->assertOk();

    expect($patient->refresh()->preferred_locale)->toBe('es');
});

test('middleware uses session locale when no patient exists', function (): void {
    $this->withSession(['locale' => 'es'])
        ->get(route('intake.landing'))
        ->assertOk();

    expect(app()->getLocale())->toBe('es');
});

test('middleware uses patient preferred_locale over session', function (): void {
    $patient = Patient::factory()->create(['preferred_locale' => 'es']);
    $intake = Intake::factory()->create(['patient_id' => $patient->id]);

    $this->withSession(['patient_id' => $patient->id, 'intake_id' => $intake->id, 'locale' => 'en'])
        ->get(route('intake.dashboard'))
        ->assertOk();

    expect(app()->getLocale())->toBe('es');
});

test('magic link verification copies session locale to patient without preference', function (): void {
    $patient = Patient::factory()->withMagicLink()->create(['preferred_locale' => null]);

    $this->withSession(['locale' => 'es'])
        ->get(route('intake.verify', ['token' => $patient->magic_link_token]))
        ->assertRedirect(route('intake.dashboard'));

    expect($patient->refresh()->preferred_locale)->toBe('es');
});

test('magic link verification preserves existing patient locale preference', function (): void {
    $patient = Patient::factory()->withMagicLink()->create(['preferred_locale' => 'en']);

    $this->withSession(['locale' => 'es'])
        ->get(route('intake.verify', ['token' => $patient->magic_link_token]))
        ->assertRedirect(route('intake.dashboard'));

    expect($patient->refresh()->preferred_locale)->toBe('en');
});
```

**Step 2: Run tests to verify they fail**

```bash
php artisan test --compact --filter=LocaleTest
```

Expected: Multiple failures (routes don't exist, middleware doesn't check session)

**Step 3: Add the guest locale route**

In `routes/intake.php`, add inside the `intake` prefix group but OUTSIDE the auth middleware group (after line 22, before the auth middleware group):

```php
Route::post('/set-locale-guest', function (Request $request): JsonResponse {
    $request->validate(['locale' => ['required', 'string', 'in:en,es']]);
    $request->session()->put('locale', $request->input('locale'));

    return response()->json(['status' => 'ok']);
})->name('set-locale-guest');
```

**Step 4: Update SetPatientLocale middleware**

Replace the `handle` method in `app/Http/Middleware/SetPatientLocale.php`:

```php
public function handle(Request $request, Closure $next): Response
{
    $patientId = $request->session()->get('patient_id');

    if ($patientId) {
        $patient = Patient::find($patientId);

        if ($patient instanceof Patient) {
            $locale = $patient->preferred_locale ?? $this->detectLocale($request);
            app()->setLocale($locale);

            return $next($request);
        }
    }

    // Fallback: check session locale (for guest/pre-auth)
    $sessionLocale = $request->session()->get('locale');
    if (is_string($sessionLocale) && in_array($sessionLocale, ['en', 'es'], true)) {
        app()->setLocale($sessionLocale);
    } else {
        app()->setLocale($this->detectLocale($request));
    }

    return $next($request);
}
```

**Step 5: Apply SetPatientLocale middleware to landing route**

In `routes/intake.php`, add the middleware to the landing and guest locale routes. The landing route (line 18) becomes:

```php
Route::get('/', [MagicLinkController::class, 'landing'])->middleware(SetPatientLocale::class)->name('landing');
```

**Step 6: Update MagicLinkController::verify to copy session locale**

In `app/Http/Controllers/Intake/MagicLinkController.php`, after `$request->session()->put('patient_id', $patient->id);` (line 50), add:

```php
// Copy guest locale preference to patient if they don't have one
if (! $patient->preferred_locale) {
    $sessionLocale = $request->session()->get('locale');
    if (is_string($sessionLocale) && in_array($sessionLocale, ['en', 'es'], true)) {
        $patient->update(['preferred_locale' => $sessionLocale]);
    }
}
```

**Step 7: Update MagicLinkController::landing to pass locale**

```php
public function landing(): Response
{
    return Inertia::render('intake/Landing', [
        'locale' => app()->getLocale(),
    ]);
}
```

**Step 8: Run tests to verify they pass**

```bash
php artisan test --compact --filter=LocaleTest
```

Expected: All PASS

**Step 9: Run composer check**

```bash
composer check
```

**Step 10: Commit**

```bash
git add tests/Feature/Intake/LocaleTest.php routes/intake.php app/Http/Middleware/SetPatientLocale.php app/Http/Controllers/Intake/MagicLinkController.php
git commit -m "feat: add guest locale endpoint and middleware session fallback"
```

---

### Task 2: LocaleToggle Svelte Component

**Files:**
- Create: `resources/js/components/intake/LocaleToggle.svelte`

**Step 1: Create the component**

Activate `inertia-svelte-development` and `tailwindcss-development` skills. Check `search-docs` for Inertia Svelte patterns if needed.

Create `resources/js/components/intake/LocaleToggle.svelte`:

```svelte
<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import { setLocale } from '@/routes/intake';
    import { setLocaleGuest } from '@/routes/intake';

    let { locale = 'en', authenticated = true }: {
        locale?: string;
        authenticated?: boolean;
    } = $props();

    function switchLocale() {
        const newLocale = locale === 'en' ? 'es' : 'en';
        const url = authenticated ? setLocale.url() : setLocaleGuest.url();

        router.post(url, { locale: newLocale }, {
            preserveScroll: true,
            onSuccess: () => router.reload(),
        });
    }
</script>

<button
    class="flex items-center gap-1 rounded-md border border-border px-2.5 py-1 text-xs font-medium transition-colors hover:border-primary/50"
    onclick={switchLocale}
    aria-label={locale === 'en' ? 'Switch to Spanish' : 'Switch to English'}
>
    <span class={locale === 'en' ? 'text-foreground' : 'text-muted-foreground'}>EN</span>
    <span class="text-muted-foreground/50">|</span>
    <span class={locale === 'es' ? 'text-foreground' : 'text-muted-foreground'}>ES</span>
</button>
```

**Step 2: Run Wayfinder generation** to ensure `setLocaleGuest` is available:

```bash
php artisan wayfinder:generate
```

Check the generated file at `resources/js/routes/intake.ts` to confirm `setLocaleGuest` is exported. If the function name differs (e.g., `setLocaleGuest` vs `set_locale_guest`), update the import accordingly.

**Step 3: Commit**

```bash
git add resources/js/components/intake/LocaleToggle.svelte
git commit -m "feat: create LocaleToggle component"
```

---

### Task 3: Integrate Toggle into Dashboard

**Files:**
- Modify: `resources/js/pages/intake/Dashboard.svelte`

**Step 1: Replace inline toggle with LocaleToggle component**

In `Dashboard.svelte`:
- Add import: `import LocaleToggle from '@/components/intake/LocaleToggle.svelte';`
- Remove the `switchLocale` function (lines 45-51)
- Remove the `import { setLocale } from '@/routes/intake';` (line 8)
- Replace the inline button (lines 83-88) with: `<LocaleToggle {locale} />`

**Step 2: Build and verify**

```bash
npm run build
```

**Step 3: Commit**

```bash
git add resources/js/pages/intake/Dashboard.svelte
git commit -m "refactor: replace inline locale button with LocaleToggle component on dashboard"
```

---

### Task 4: Integrate Toggle into Landing Page

**Files:**
- Modify: `resources/js/pages/intake/Landing.svelte`
- Test: `tests/Browser/IntakeFlowTest.php`

**Step 1: Write the failing browser test**

Add to `tests/Browser/IntakeFlowTest.php`:

```php
it('shows locale toggle on landing page', function (): void {
    $pendingAwaitablePage = visit('/intake');

    $pendingAwaitablePage->assertSee('EN')
        ->assertSee('ES')
        ->assertNoJavaScriptErrors();
});
```

**Step 2: Run test to verify it fails**

```bash
php artisan test --compact --filter="shows locale toggle on landing page"
```

Expected: FAIL

**Step 3: Add locale prop and toggle to Landing.svelte**

In `Landing.svelte`:
- Add import: `import LocaleToggle from '@/components/intake/LocaleToggle.svelte';`
- Add locale prop: `let { locale = 'en' }: { locale?: string } = $props();`
- Add a fixed-position toggle in the top-right corner. Insert right after the opening `<div class="flex min-h-screen flex-col lg:flex-row">` (line 22):

```svelte
<!-- Locale Toggle -->
<div class="fixed top-4 right-4 z-50">
    <LocaleToggle {locale} authenticated={false} />
</div>
```

**Step 4: Run test to verify it passes**

```bash
php artisan test --compact --filter="shows locale toggle on landing page"
```

Expected: PASS

**Step 5: Commit**

```bash
git add resources/js/pages/intake/Landing.svelte tests/Browser/IntakeFlowTest.php
git commit -m "feat: add locale toggle to landing page"
```

---

### Task 5: Integrate Toggle into Form Page

**Files:**
- Modify: `resources/js/pages/intake/Form.svelte`
- Modify: `resources/js/components/intake/IntakeSidebar.svelte`

**Step 1: Add toggle to Form page mobile header and sidebar**

In `Form.svelte`, add import:
```
import LocaleToggle from '@/components/intake/LocaleToggle.svelte';
```

In the mobile header (between the logo and the close button, around line 64-73), add the toggle:

```svelte
<header class="border-b px-4 py-3 lg:hidden">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
            <AppLogoIcon class="size-6" />
            <span class="text-sm font-bold text-foreground">Acorn</span>
        </div>
        <div class="flex items-center gap-3">
            <LocaleToggle {locale} />
            <Link
                href={dashboard.url()}
                class="text-sm text-muted-foreground transition-colors hover:text-foreground"
            >
                &times;
            </Link>
        </div>
    </div>
</header>
```

In `IntakeSidebar.svelte`, add the toggle to the sidebar header area. Read the file first to find the exact location. Add import and place `<LocaleToggle {locale} />` next to the logo/branding in the sidebar header.

**Step 2: Build and verify**

```bash
npm run build
```

**Step 3: Commit**

```bash
git add resources/js/pages/intake/Form.svelte resources/js/components/intake/IntakeSidebar.svelte
git commit -m "feat: add locale toggle to form page and sidebar"
```

---

### Task 6: Integrate Toggle into FormComplete Page

**Files:**
- Modify: `resources/js/pages/intake/FormComplete.svelte`

**Step 1: Add toggle to FormComplete**

In `FormComplete.svelte`:
- Add import: `import LocaleToggle from '@/components/intake/LocaleToggle.svelte';`
- Add a fixed-position toggle. Insert right after `<div class="flex min-h-screen flex-col items-center justify-center bg-background px-6">` (line 32):

```svelte
<div class="fixed top-4 right-4 z-50">
    <LocaleToggle {locale} />
</div>
```

**Step 2: Build and verify**

```bash
npm run build
```

**Step 3: Commit**

```bash
git add resources/js/pages/intake/FormComplete.svelte
git commit -m "feat: add locale toggle to form complete page"
```

---

### Task 7: Make Landing Page Bilingual

**Files:**
- Modify: `resources/js/pages/intake/Landing.svelte`
- Modify: `app/Http/Controllers/Intake/MagicLinkController.php`
- Test: `tests/Feature/Intake/LocaleTest.php`

**Step 1: Write the failing test**

Add to `tests/Feature/Intake/LocaleTest.php`:

```php
test('landing page renders bilingual content based on locale', function (): void {
    $this->withSession(['locale' => 'es'])
        ->get(route('intake.landing'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('intake/Landing')
            ->where('locale', 'es')
        );
});
```

**Step 2: Run test to verify it fails**

```bash
php artisan test --compact --filter="landing page renders bilingual content"
```

Expected: FAIL (locale not passed as prop yet — this was added in Task 1 Step 7, but verify)

**Step 3: Make Landing.svelte bilingual**

Replace all hardcoded English strings in `Landing.svelte` with inline bilingual objects. Define a translations constant in the script block:

```typescript
const t = {
    brand: 'Acorn',
    org: 'JumpStart Autism Collective',
    getStarted: { en: 'Get Started', es: 'Comenzar' },
    enterEmail: {
        en: "Enter your email address to begin your intake forms. We'll send you a secure link to access your paperwork.",
        es: 'Ingrese su correo electrónico para comenzar sus formularios. Le enviaremos un enlace seguro para acceder a su documentación.',
    },
    emailLabel: { en: 'Email Address', es: 'Correo Electrónico' },
    emailPlaceholder: { en: 'parent@example.com', es: 'padre@ejemplo.com' },
    sending: { en: 'Sending...', es: 'Enviando...' },
    sendLink: { en: 'Send Secure Link', es: 'Enviar Enlace Seguro' },
    alreadyHaveLink: {
        en: 'Already have a link? Check your email for a previous access link.',
        es: '¿Ya tiene un enlace? Revise su correo electrónico para un enlace de acceso anterior.',
    },
    welcomeTitle: { en: 'Welcome to Your Intake Journey', es: 'Bienvenido a Su Proceso de Admisión' },
    welcomeDesc: {
        en: "We've made the intake process simple and secure. Complete your forms at your own pace from any device.",
        es: 'Hemos simplificado el proceso de admisión. Complete sus formularios a su propio ritmo desde cualquier dispositivo.',
    },
    secureTitle: { en: 'Secure & Private', es: 'Seguro y Privado' },
    secureDesc: {
        en: 'Your information is encrypted and HIPAA compliant',
        es: 'Su información está encriptada y cumple con HIPAA',
    },
    saveTitle: { en: 'Save & Resume', es: 'Guardar y Continuar' },
    saveDesc: {
        en: 'Your progress is saved automatically — pick up where you left off',
        es: 'Su progreso se guarda automáticamente — continúe donde lo dejó',
    },
    bilingualTitle: { en: 'Bilingual Support', es: 'Soporte Bilingüe' },
    bilingualDesc: {
        en: 'Available in English and Spanish',
        es: 'Disponible en inglés y español',
    },
} as const;
```

Then replace each hardcoded string in the template. For example:
- `"Get Started"` → `{t.getStarted[locale]}`
- `"Email Address"` → `{t.emailLabel[locale]}`
- `{processing ? 'Sending...' : 'Send Secure Link'}` → `{processing ? t.sending[locale] : t.sendLink[locale]}`

Brand names (`Acorn`, `JumpStart Autism Collective`) stay as-is — they don't translate.

**Step 4: Run test to verify it passes**

```bash
php artisan test --compact --filter="landing page renders bilingual content"
```

Expected: PASS

**Step 5: Commit**

```bash
git add resources/js/pages/intake/Landing.svelte tests/Feature/Intake/LocaleTest.php
git commit -m "feat: make landing page bilingual"
```

---

### Task 8: Make Dashboard Bilingual

**Files:**
- Modify: `resources/js/pages/intake/Dashboard.svelte`

**Step 1: Add translations to Dashboard.svelte**

Define a translations constant in the script block:

```typescript
const t = {
    welcome: { en: 'Welcome!', es: '¡Bienvenido!' },
    welcomeDesc: {
        en: 'Complete {count} short forms at your own pace. Your progress saves automatically — come back anytime.',
        es: 'Complete {count} formularios cortos a su propio ritmo. Su progreso se guarda automáticamente — regrese cuando quiera.',
    },
    estimatedTime: { en: 'Estimated time: ~{min} minutes', es: 'Tiempo estimado: ~{min} minutos' },
    getStarted: { en: 'Get Started', es: 'Comenzar' },
    allDone: { en: 'All Done!', es: '¡Todo Listo!' },
    allDoneDesc: {
        en: "All {total} forms are complete. Your information is being processed — we'll be in touch soon.",
        es: 'Los {total} formularios están completos. Su información está siendo procesada — nos pondremos en contacto pronto.',
    },
    intake: { en: "'s Intake", es: ' — Admisión' },
    yourIntake: { en: 'Your Intake', es: 'Su Admisión' },
    pickUp: { en: 'Pick up where you left off.', es: 'Continúe donde lo dejó.' },
    formsComplete: { en: 'forms complete', es: 'formularios completos' },
    minRemaining: { en: 'min remaining', es: 'min restantes' },
    continue_: { en: 'Continue', es: 'Continuar' },
    yourChildren: { en: 'Your Children', es: 'Sus Hijos' },
    complete: { en: 'Complete', es: 'Completo' },
    switchTo: { en: 'Switch to', es: 'Cambiar a' },
    addChild: { en: '+ Add another child', es: '+ Agregar otro hijo' },
} as const;
```

Replace all hardcoded English strings in the template with `t.key[locale]` lookups. For strings with interpolation, use template literals or helper functions.

**Step 2: Build and verify**

```bash
npm run build
```

**Step 3: Commit**

```bash
git add resources/js/pages/intake/Dashboard.svelte
git commit -m "feat: make dashboard bilingual"
```

---

### Task 9: Make FormComplete Bilingual

**Files:**
- Modify: `resources/js/pages/intake/FormComplete.svelte`

**Step 1: Add translations to FormComplete.svelte**

```typescript
const t = {
    complete: { en: 'Complete!', es: '¡Completo!' },
    allDone: {
        en: "You're all done! Your intake paperwork has been submitted. The JumpStart team will review your information and reach out soon.",
        es: '¡Ha terminado! Su documentación ha sido enviada. El equipo de JumpStart revisará su información y se pondrá en contacto pronto.',
    },
    of: { en: 'of', es: 'de' },
    formsComplete: { en: 'forms complete', es: 'formularios completos' },
    oneMore: { en: 'just 1 more to go!', es: '¡solo queda 1 más!' },
    moreToGo: { en: 'more to go!', es: 'más por completar.' },
    backToDashboard: { en: 'Back to Dashboard', es: 'Volver al Panel' },
    continueTo: { en: 'Continue to', es: 'Continuar a' },
} as const;
```

Replace hardcoded strings in the template.

**Step 2: Build and verify**

```bash
npm run build
```

**Step 3: Commit**

```bash
git add resources/js/pages/intake/FormComplete.svelte
git commit -m "feat: make form complete page bilingual"
```

---

### Task 10: Make IntakeSidebar Bilingual

**Files:**
- Modify: `resources/js/components/intake/IntakeSidebar.svelte`

**Step 1: Add translations and replace hardcoded strings**

Read the file first. Replace hardcoded strings like "forms complete", "Back to Dashboard", "of" with locale-aware lookups using the same inline object pattern.

**Step 2: Build and verify**

```bash
npm run build
```

**Step 3: Commit**

```bash
git add resources/js/components/intake/IntakeSidebar.svelte
git commit -m "feat: make intake sidebar bilingual"
```

---

### Task 11: Standardize Existing Inline Ternaries

**Files:**
- Modify: `resources/js/components/intake/FormRenderer.svelte`
- Modify: `resources/js/components/intake/IntakeBottomNav.svelte`
- Modify: `resources/js/components/intake/fields/SignatureField.svelte`

**Step 1: Refactor inline ternaries to object lookups**

These files already have bilingual strings but use `locale === 'es' ? 'Spanish' : 'English'` ternary pattern. Refactor to `{ en: 'English', es: 'Spanish' }[locale]` for consistency and future language extensibility.

For example in `FormRenderer.svelte`, change:
```svelte
{locale === 'es' ? 'Guardando...' : 'Saving...'}
```
to:
```svelte
{{ en: 'Saving...', es: 'Guardando...' }[locale]}
```

Apply this pattern to all inline ternaries in:
- `FormRenderer.svelte`: Saving, Saved, Save & Exit, Previous, Complete Form, Next
- `IntakeBottomNav.svelte`: Previous, Step, Complete, Next
- `SignatureField.svelte`: Clear

**Step 2: Build and verify**

```bash
npm run build
```

**Step 3: Commit**

```bash
git add resources/js/components/intake/FormRenderer.svelte resources/js/components/intake/IntakeBottomNav.svelte resources/js/components/intake/fields/SignatureField.svelte
git commit -m "refactor: standardize locale strings to object lookup pattern"
```

---

### Task 12: Make MagicLinkController Flash Messages Bilingual

**Files:**
- Modify: `app/Http/Controllers/Intake/MagicLinkController.php`

**Step 1: Update flash messages to use locale**

In `MagicLinkController.php`:

```php
public function requestLink(RequestMagicLinkRequest $requestMagicLinkRequest, MagicLinkService $magicLinkService): RedirectResponse
{
    /** @var string $email */
    $email = $requestMagicLinkRequest->validated('email');

    $magicLinkService->sendToEmail($email);

    $message = app()->getLocale() === 'es'
        ? 'Revise su correo electrónico para un enlace de acceso.'
        : 'Check your email for a magic link.';

    return back()->with('status', $message);
}
```

For the verify error message:

```php
$message = app()->getLocale() === 'es'
    ? 'Este enlace es inválido o ha expirado.'
    : 'This link is invalid or has expired.';

return redirect()->route('intake.landing')
    ->with('error', $message);
```

**Step 2: Run existing tests**

```bash
php artisan test --compact --filter=MagicLinkTest
```

Expected: PASS (existing tests don't assert on exact flash message text — verify this. If they do, update assertions or add locale-specific tests.)

**Step 3: Commit**

```bash
git add app/Http/Controllers/Intake/MagicLinkController.php
git commit -m "feat: make magic link flash messages bilingual"
```

---

### Task 13: Browser Test for Locale Toggle Flow

**Files:**
- Modify: `tests/Browser/IntakeFlowTest.php`

**Step 1: Add browser test for locale switching**

Add to `tests/Browser/IntakeFlowTest.php`:

```php
it('switches language on landing page', function (): void {
    $pendingAwaitablePage = visit('/intake');

    $pendingAwaitablePage->assertSee('Get Started')
        ->assertSee('Send Secure Link')
        ->click('ES')
        ->assertSee('Comenzar')
        ->assertSee('Enviar Enlace Seguro')
        ->assertNoJavaScriptErrors();
});

it('switches language on dashboard and persists', function (): void {
    $patient = Patient::factory()->create([
        'magic_link_token' => 'test-locale-toggle',
        'magic_link_expires_at' => now()->addMinutes(30),
    ]);

    $pendingAwaitablePage = visit('/intake/verify/test-locale-toggle');

    $pendingAwaitablePage->assertPathIs('/intake/dashboard')
        ->assertSee('Welcome!')
        ->click('ES')
        ->assertSee('¡Bienvenido!')
        ->assertNoJavaScriptErrors();
});

it('preserves form data when switching language mid-form', function (): void {
    $patient = Patient::factory()->create([
        'magic_link_token' => 'test-locale-form',
        'magic_link_expires_at' => now()->addMinutes(30),
    ]);

    $pendingAwaitablePage = visit('/intake/verify/test-locale-form');

    $pendingAwaitablePage->click('Get Started')
        ->assertSee('First Name')
        ->fill('parent_first_name', 'Maria')
        ->click('ES')
        ->assertSee('Nombre')
        ->assertNoJavaScriptErrors();
});
```

**Step 2: Run browser tests**

```bash
php artisan test --compact --filter=IntakeFlowTest
```

Expected: All PASS

**Step 3: Commit**

```bash
git add tests/Browser/IntakeFlowTest.php
git commit -m "test: add browser tests for locale toggle flow"
```

---

### Task 14: Final Verification

**Step 1: Run full composer check**

```bash
composer check
```

This runs Rector → Pint → PHPStan → Tests. Fix any issues.

**Step 2: Build frontend**

```bash
npm run build
```

**Step 3: Final commit if any fixes were needed**

```bash
git add -A
git commit -m "chore: fix code quality issues from composer check"
```
