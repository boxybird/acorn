# Demo Mode Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Build a self-contained demo mode with floating panel for client exploration of all user personas.

**Architecture:** Blade-injected standalone Svelte island, gated by `DEMO_MODE` env var. Dedicated config, route file, middleware, and controller. Three Svelte components mounted outside the Inertia app. Existing code touched in 3 places with small conditional blocks.

**Tech Stack:** Laravel 12, Inertia v2, Svelte 5, Tailwind CSS v4, Fortify

---

### Task 1: Config & Environment

**Files:**
- Create: `config/demo.php`
- Modify: `.env.example`
- Modify: `.env` (local)

**Step 1: Create config file**

```php
<?php

return [
    'enabled' => (bool) env('DEMO_MODE', false),
];
```

**Step 2: Add env var to `.env.example`**

Add after the `VITE_APP_NAME` line:

```
DEMO_MODE=false
```

**Step 3: Set `DEMO_MODE=true` in local `.env`**

**Step 4: Verify config works**

Run: `php artisan tinker --execute="dump(config('demo.enabled'));"` — should output `true`.

**Step 5: Commit**

```
feat: add demo mode config and env var
```

---

### Task 2: DemoMode Middleware

**Files:**
- Create: `app/Http/Middleware/DemoMode.php`
- Test: `tests/Feature/Http/Middleware/DemoModeTest.php`

**Step 1: Write the failing test**

Create `tests/Feature/Http/Middleware/DemoModeTest.php`:

```php
<?php

use App\Http\Middleware\DemoMode;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

it('allows request when demo mode is enabled', function (): void {
    config()->set('demo.enabled', true);

    $middleware = new DemoMode;
    $response = $middleware->handle(Request::create('/demo/test'), fn () => new Response('ok'));

    expect($response->getStatusCode())->toBe(200);
});

it('aborts with 403 when demo mode is disabled', function (): void {
    config()->set('demo.enabled', false);

    $middleware = new DemoMode;
    $middleware->handle(Request::create('/demo/test'), fn () => new Response('ok'));
})->throws(\Symfony\Component\HttpKernel\Exception\HttpException::class);
```

**Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=DemoModeTest`
Expected: FAIL — class not found.

**Step 3: Create middleware via artisan**

Run: `php artisan make:middleware DemoMode --no-interaction`

Then replace contents of `app/Http/Middleware/DemoMode.php`:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DemoMode
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(config('demo.enabled'), 403);

        return $next($request);
    }
}
```

**Step 4: Run test to verify it passes**

Run: `php artisan test --compact --filter=DemoModeTest`
Expected: PASS

**Step 5: Commit**

```
feat: add DemoMode middleware
```

---

### Task 3: DemoController & Routes

**Files:**
- Create: `app/Http/Controllers/DemoController.php`
- Create: `routes/demo.php`
- Modify: `bootstrap/app.php` (add conditional route loading)
- Test: `tests/Feature/Http/Controllers/DemoControllerTest.php`

**Step 1: Write the failing tests**

Create `tests/Feature/Http/Controllers/DemoControllerTest.php`:

```php
<?php

use App\Models\Patient;
use App\Models\User;

beforeEach(function (): void {
    config()->set('demo.enabled', true);
});

it('logs in as a patient and redirects to intake dashboard', function (): void {
    $patient = Patient::factory()->create();

    $response = $this->post("/demo/login/patient/{$patient->id}");

    $response->assertRedirect(route('intake.dashboard'));
    expect(session('patient_id'))->toBe($patient->id);
});

it('logs in as a staff user and redirects to dashboard', function (): void {
    $user = User::factory()->create();

    $response = $this->post("/demo/login/user/{$user->id}");

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($user);
});

it('logs out both contexts and redirects home', function (): void {
    $user = User::factory()->create();
    $patient = Patient::factory()->create();

    $this->actingAs($user)
        ->withSession(['patient_id' => $patient->id])
        ->post('/demo/logout');

    $this->assertGuest();
    expect(session('patient_id'))->toBeNull();
});

it('returns 403 when demo mode is disabled', function (): void {
    config()->set('demo.enabled', false);
    $patient = Patient::factory()->create();

    $this->post("/demo/login/patient/{$patient->id}")
        ->assertForbidden();
});
```

**Step 2: Run tests to verify they fail**

Run: `php artisan test --compact --filter=DemoControllerTest`
Expected: FAIL — routes not found.

**Step 3: Create the controller**

Run: `php artisan make:controller DemoController --no-interaction`

Replace contents of `app/Http/Controllers/DemoController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DemoController extends Controller
{
    public function loginAsPatient(Request $request, Patient $patient): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $request->session()->put('patient_id', $patient->id);

        return redirect()->route('intake.dashboard');
    }

    public function loginAsUser(Request $request, User $user): RedirectResponse
    {
        $request->session()->forget('patient_id');

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
```

**Step 4: Create the route file**

Create `routes/demo.php`:

```php
<?php

use App\Http\Controllers\DemoController;
use App\Http\Middleware\DemoMode;
use Illuminate\Support\Facades\Route;

Route::prefix('demo')->middleware(['web', DemoMode::class])->group(function (): void {
    Route::post('/login/patient/{patient}', [DemoController::class, 'loginAsPatient'])->name('demo.login.patient');
    Route::post('/login/user/{user}', [DemoController::class, 'loginAsUser'])->name('demo.login.user');
    Route::post('/logout', [DemoController::class, 'logout'])->name('demo.logout');
});
```

**Step 5: Register routes conditionally in `bootstrap/app.php`**

In the `withRouting` call, add a `then` callback:

```php
->withRouting(
    web: __DIR__.'/../routes/web.php',
    commands: __DIR__.'/../routes/console.php',
    health: '/up',
    then: function (): void {
        if (config('demo.enabled')) {
            require base_path('routes/demo.php');
        }
    },
)
```

**Step 6: Run tests to verify they pass**

Run: `php artisan test --compact --filter=DemoControllerTest`
Expected: PASS

**Step 7: Commit**

```
feat: add DemoController and demo routes
```

---

### Task 4: Disable Registration in Demo Mode

**Files:**
- Modify: `config/fortify.php`
- Test: `tests/Feature/Http/Controllers/DemoControllerTest.php` (add test)

**Step 1: Write the failing test**

Add to `DemoControllerTest.php`:

```php
it('disables registration when demo mode is enabled', function (): void {
    config()->set('demo.enabled', true);

    $this->get('/register')->assertNotFound();
});

it('allows registration when demo mode is disabled', function (): void {
    config()->set('demo.enabled', false);

    $this->get('/register')->assertOk();
});
```

**Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter="disables registration"`
Expected: FAIL — register page returns 200.

**Step 3: Conditionally remove registration feature**

In `config/fortify.php`, replace the features array:

```php
'features' => array_filter([
    config('demo.enabled') ? null : Features::registration(),
    Features::resetPasswords(),
    Features::emailVerification(),
    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]),
]),
```

**Step 4: Run tests to verify they pass**

Run: `php artisan test --compact --filter="registration when demo"`
Expected: PASS

**Step 5: Commit**

```
feat: disable registration when demo mode is enabled
```

---

### Task 5: Share Demo Data via Inertia

**Files:**
- Modify: `app/Http/Middleware/HandleInertiaRequests.php`
- Test: `tests/Feature/Http/Middleware/DemoModeTest.php` (add tests)

**Step 1: Write the failing test**

Add to `DemoModeTest.php`:

```php
use App\Models\Patient;
use App\Models\User;

it('shares demo data as inertia prop when demo mode is enabled', function (): void {
    config()->set('demo.enabled', true);

    $user = User::factory()->create(['name' => 'Test Staff']);
    $patient = Patient::factory()->create(['name' => 'Test Parent']);

    $response = $this->get('/');

    $response->assertInertia(fn ($page) => $page
        ->has('demo')
        ->has('demo.patients')
        ->has('demo.users')
    );
});

it('does not share demo data when demo mode is disabled', function (): void {
    config()->set('demo.enabled', false);

    $response = $this->get('/');

    $response->assertInertia(fn ($page) => $page
        ->missing('demo')
    );
});
```

**Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter="shares demo data"`
Expected: FAIL — `demo` prop missing.

**Step 3: Add conditional sharing to HandleInertiaRequests**

At the end of the `share()` method in `app/Http/Middleware/HandleInertiaRequests.php`, before `return $shared;`, add:

```php
if (config('demo.enabled')) {
    $shared['demo'] = fn (): array => [
        'patients' => Patient::with('intakes.formResponses')->get()->map(fn (Patient $patient): array => [
            'id' => $patient->id,
            'name' => $patient->name,
            'intakes' => $patient->intakes->map(fn (Intake $intake): array => [
                'child_name' => $intake->child_name ?: 'Not yet named',
                'status' => $intake->status->label(),
                'form_count' => $intake->formResponses->count(),
                'completed_count' => $intake->formResponses->where('status', FormResponseStatus::Completed)->count(),
            ])->all(),
        ])->all(),
        'users' => User::all()->map(fn (User $user): array => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ])->all(),
    ];
}
```

Add the necessary imports at the top of the file:

```php
use App\Enums\FormResponseStatus;
use App\Models\Intake;
use App\Models\Patient;
use App\Models\User;
```

**Step 4: Run test to verify it passes**

Run: `php artisan test --compact --filter="demo data"`
Expected: PASS

**Step 5: Commit**

```
feat: share demo accounts data via Inertia when demo mode enabled
```

---

### Task 6: Blade Injection Point

**Files:**
- Modify: `resources/views/app.blade.php`

**Step 1: Add the Svelte mount point**

After the `@inertia` directive, add:

```blade
@if(config('demo.enabled'))
    <div id="demo-panel"></div>
    @vite(['resources/js/demo.ts'])
@endif
```

**Step 2: Verify no errors with `npm run build`**

This will fail because `demo.ts` doesn't exist yet — that's expected. We'll create it in the next task. Just verify the Blade file renders correctly when demo mode is off.

Run: visit any page with `DEMO_MODE=false` — confirm no errors.

**Step 3: Commit**

```
feat: add demo panel Blade injection point
```

---

### Task 7: Demo Svelte Entry Point & DemoPanel Component

**Files:**
- Create: `resources/js/demo.ts`
- Create: `resources/js/components/demo/DemoPanel.svelte`
- Modify: `vite.config.ts` (add demo.ts as entry point)

**Step 1: Check current vite.config.ts for entry point pattern**

Read `vite.config.ts` to understand how entry points are configured.

**Step 2: Add `demo.ts` entry point to vite config**

Add `'resources/js/demo.ts'` to the Laravel plugin's input array.

**Step 3: Create the demo entry point**

Create `resources/js/demo.ts`:

```ts
import { mount } from 'svelte';
import DemoPanel from './components/demo/DemoPanel.svelte';

const target = document.getElementById('demo-panel');

if (target) {
    mount(DemoPanel, { target });
}
```

**Step 4: Create DemoPanel.svelte**

Create `resources/js/components/demo/DemoPanel.svelte`:

```svelte
<script lang="ts">
    import DemoAbout from './DemoAbout.svelte';
    import DemoAccountSwitcher from './DemoAccountSwitcher.svelte';

    let open = $state(false);

    function toggle() {
        open = !open;
    }

    function handleKeydown(event: KeyboardEvent) {
        if (event.key === 'Escape' && open) {
            open = false;
        }
    }
</script>

<svelte:window onkeydown={handleKeydown} />

<!-- Floating Action Button -->
<button
    type="button"
    class="fixed bottom-6 left-6 z-[9999] flex h-12 w-12 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-lg transition-transform hover:scale-110 active:scale-95"
    onclick={toggle}
    aria-label="Open demo panel"
>
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M9 3h6l1 7H8L9 3z" />
        <path d="M8 10h8v2a4 4 0 0 1-8 0v-2z" />
        <path d="M12 14v8" />
        <path d="M8 22h8" />
    </svg>
</button>

<!-- Modal Overlay -->
{#if open}
    <div class="fixed inset-0 z-[10000]">
        <!-- Backdrop -->
        <!-- svelte-ignore a11y_no_static_element_interactions -->
        <div
            class="absolute inset-0 bg-black/60 backdrop-blur-sm animate-in fade-in duration-300"
            onclick={() => (open = false)}
            onkeydown={(e) => e.key === 'Enter' && (open = false)}
        ></div>

        <!-- Panel -->
        <div
            class="absolute inset-4 md:inset-8 lg:inset-12 z-10 flex flex-col overflow-hidden rounded-2xl border bg-background shadow-2xl animate-in zoom-in-95 fade-in duration-300"
            role="dialog"
            aria-modal="true"
            aria-label="Demo Panel"
        >
            <!-- Header -->
            <div class="flex items-center justify-between border-b px-6 py-4">
                <div>
                    <h2 class="text-xl font-semibold">Acorn Demo Panel</h2>
                    <p class="text-sm text-muted-foreground">Explore the intake portal from any perspective</p>
                </div>
                <button
                    type="button"
                    class="rounded-md p-2 hover:bg-muted transition-colors"
                    onclick={() => (open = false)}
                    aria-label="Close demo panel"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6 6 18" />
                        <path d="m6 6 12 12" />
                    </svg>
                </button>
            </div>

            <!-- Body -->
            <div class="flex flex-1 overflow-hidden">
                <!-- Left: About -->
                <div class="flex-1 overflow-y-auto border-r p-6">
                    <DemoAbout />
                </div>

                <!-- Right: Account Switcher -->
                <div class="w-full max-w-sm overflow-y-auto p-6 lg:max-w-md">
                    <DemoAccountSwitcher />
                </div>
            </div>
        </div>
    </div>
{/if}
```

**Step 5: Verify it builds**

Run: `npm run build`
Expected: builds successfully (DemoAbout and DemoAccountSwitcher will be empty placeholder components for now).

**Step 6: Commit**

```
feat: add DemoPanel component with floating button and modal
```

---

### Task 8: DemoAbout Component

**Files:**
- Create: `resources/js/components/demo/DemoAbout.svelte`

**Step 1: Create the about content component**

Create `resources/js/components/demo/DemoAbout.svelte`:

```svelte
<script lang="ts">
    const sections = [
        {
            title: 'What is Acorn?',
            content:
                'Acorn is a parent intake portal for JumpStart Autism Collective. It replaces the long, paper-based intake process with a guided digital experience that parents can complete at their own pace — from any device, in English or Spanish.',
        },
        {
            title: 'The Problem',
            content:
                'Only about 1 in 3 referred families complete the intake process. The current paperwork is overwhelming, especially for families already navigating a new diagnosis. Acorn reduces friction so more children get into services faster.',
        },
        {
            title: 'How It Works',
            content:
                'Parents receive a magic link via email — no passwords needed. They land on a dashboard showing all required forms as a checklist. Each form saves progress automatically. When everything is complete, the intake is submitted for staff review.',
        },
        {
            title: 'Staff Experience',
            content:
                'Staff see submitted intakes in their dashboard. They can review each form, flag items that need correction, add notes, and approve completed intakes. Approved intakes will eventually sync to Monday.com.',
        },
        {
            title: 'Current Status',
            content:
                'This is a working prototype with test data. Magic link emails are not yet configured — use the account switcher on the right to explore as different users. All data is test data and can be freely modified.',
        },
    ];
</script>

<div class="space-y-6">
    <div class="flex items-center gap-3">
        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 3h6l1 7H8L9 3z" />
                <path d="M8 10h8v2a4 4 0 0 1-8 0v-2z" />
                <path d="M12 14v8" />
                <path d="M8 22h8" />
            </svg>
        </div>
        <div>
            <h3 class="font-semibold">About This Tool</h3>
            <p class="text-xs text-muted-foreground">Last updated: March 2026</p>
        </div>
    </div>

    {#each sections as section}
        <div>
            <h4 class="mb-1.5 text-sm font-semibold">{section.title}</h4>
            <p class="text-sm leading-relaxed text-muted-foreground">{section.content}</p>
        </div>
    {/each}
</div>
```

**Step 2: Verify it builds**

Run: `npm run build`
Expected: PASS

**Step 3: Commit**

```
feat: add DemoAbout component with project overview content
```

---

### Task 9: DemoAccountSwitcher Component

**Files:**
- Create: `resources/js/components/demo/DemoAccountSwitcher.svelte`

**Step 1: Create the account switcher component**

This component reads the `demo` prop from the Inertia page store. Since DemoPanel is mounted outside the Inertia app, it cannot use `usePage()`. Instead, it reads the data from a `window.__demoData` global that we'll set in the Blade template.

Update the Blade injection in `resources/views/app.blade.php` to pass data:

```blade
@if(config('demo.enabled'))
    <script>
        window.__demoData = @json(\App\Http\Middleware\HandleInertiaRequests::demoData());
    </script>
    <div id="demo-panel"></div>
    @vite(['resources/js/demo.ts'])
@endif
```

**Wait — this adds complexity.** Better approach: since we're injecting into Blade and the data is available server-side, pass it as a data attribute on the mount div, or as a global. Let's use the simplest approach: a `<script>` tag with JSON that the Svelte component reads.

Actually, the cleanest approach: pass data as props to the Svelte mount in `demo.ts`, reading from a `<script type="application/json">` block.

**Revised approach for `app.blade.php`:**

```blade
@if(config('demo.enabled'))
    <script id="demo-data" type="application/json">
        @php
            $patients = \App\Models\Patient::with('intakes.formResponses')->get();
            $users = \App\Models\User::all();
        @endphp
        {!! json_encode([
            'patients' => $patients->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'intakes' => $p->intakes->map(fn ($i) => [
                    'child_name' => $i->child_name ?: 'Not yet named',
                    'status' => $i->status->label(),
                    'form_count' => $i->formResponses->count(),
                    'completed_count' => $i->formResponses->where('status', \App\Enums\FormResponseStatus::Completed)->count(),
                ])->all(),
            ])->all(),
            'users' => $users->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
            ])->all(),
        ]) !!}
    </script>
    <div id="demo-panel"></div>
    @vite(['resources/js/demo.ts'])
@endif
```

**Update `demo.ts` to read data and pass as props:**

```ts
import { mount } from 'svelte';
import DemoPanel from './components/demo/DemoPanel.svelte';

const target = document.getElementById('demo-panel');
const dataEl = document.getElementById('demo-data');

if (target && dataEl) {
    const data = JSON.parse(dataEl.textContent || '{}');
    mount(DemoPanel, { target, props: { patients: data.patients, users: data.users } });
}
```

**Update DemoPanel.svelte props to accept and pass data:**

Add to DemoPanel script:

```ts
let { patients = [], users = [] }: { patients: any[]; users: any[] } = $props();
```

Pass to child: `<DemoAccountSwitcher {patients} {users} />`

**Now create the DemoAccountSwitcher component:**

Create `resources/js/components/demo/DemoAccountSwitcher.svelte`:

```svelte
<script lang="ts">
    type Intake = {
        child_name: string;
        status: string;
        form_count: number;
        completed_count: number;
    };

    type PatientAccount = {
        id: number;
        name: string;
        intakes: Intake[];
    };

    type StaffAccount = {
        id: number;
        name: string;
        email: string;
    };

    let { patients = [], users = [] }: { patients: PatientAccount[]; users: StaffAccount[] } = $props();
    let loading = $state<number | null>(null);

    function loginAsPatient(id: number) {
        loading = id;
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/demo/login/patient/${id}`;

        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            || document.querySelector('input[name="_token"]')?.getAttribute('value')
            || '';
        const token = document.createElement('input');
        token.type = 'hidden';
        token.name = '_token';
        token.value = csrf;
        form.appendChild(token);

        document.body.appendChild(form);
        form.submit();
    }

    function loginAsUser(id: number) {
        loading = id + 10000;
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/demo/login/user/${id}`;

        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            || document.querySelector('input[name="_token"]')?.getAttribute('value')
            || '';
        const token = document.createElement('input');
        token.type = 'hidden';
        token.name = '_token';
        token.value = csrf;
        form.appendChild(token);

        document.body.appendChild(form);
        form.submit();
    }

    function logout() {
        loading = -1;
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/demo/logout';

        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            || document.querySelector('input[name="_token"]')?.getAttribute('value')
            || '';
        const token = document.createElement('input');
        token.type = 'hidden';
        token.name = '_token';
        token.value = csrf;
        form.appendChild(token);

        document.body.appendChild(form);
        form.submit();
    }
</script>

<div class="space-y-6">
    <!-- Parent Accounts -->
    <div>
        <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-muted-foreground">Parent Accounts</h3>
        <div class="space-y-2">
            {#each patients as patient}
                <button
                    type="button"
                    class="w-full rounded-lg border p-3 text-left transition-colors hover:bg-muted disabled:opacity-50"
                    onclick={() => loginAsPatient(patient.id)}
                    disabled={loading !== null}
                >
                    <div class="font-medium">{patient.name}</div>
                    {#each patient.intakes as intake}
                        <div class="mt-1 text-xs text-muted-foreground">
                            {intake.child_name} — {intake.status} ({intake.completed_count}/{intake.form_count} forms)
                        </div>
                    {/each}
                    {#if patient.intakes.length === 0}
                        <div class="mt-1 text-xs text-muted-foreground">No intakes started</div>
                    {/if}
                </button>
            {/each}
        </div>
    </div>

    <!-- Staff Accounts -->
    <div>
        <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-muted-foreground">Staff Accounts</h3>
        <div class="space-y-2">
            {#each users as user}
                <button
                    type="button"
                    class="w-full rounded-lg border p-3 text-left transition-colors hover:bg-muted disabled:opacity-50"
                    onclick={() => loginAsUser(user.id)}
                    disabled={loading !== null}
                >
                    <div class="font-medium">{user.name}</div>
                    <div class="text-xs text-muted-foreground">{user.email}</div>
                </button>
            {/each}
        </div>
    </div>

    <!-- Logout -->
    <div class="border-t pt-4">
        <button
            type="button"
            class="w-full rounded-lg border border-destructive/30 p-3 text-center text-sm font-medium text-destructive transition-colors hover:bg-destructive/10 disabled:opacity-50"
            onclick={logout}
            disabled={loading !== null}
        >
            Log Out & Return Home
        </button>
    </div>
</div>
```

**Step 2: Add CSRF meta tag to Blade layout**

In `resources/views/app.blade.php`, add in the `<head>`:

```html
<meta name="csrf-token" content="{{ csrf_token() }}">
```

**Step 3: Verify it builds**

Run: `npm run build`
Expected: PASS

**Step 4: Commit**

```
feat: add DemoAccountSwitcher with one-click login for all personas
```

---

### Task 10: Remove HandleInertiaRequests Demo Sharing

Since we moved data injection to Blade (Task 9), we don't need the Inertia shared prop approach from Task 5. Skip the `HandleInertiaRequests` modification — the Blade `<script>` tag handles data delivery instead.

**Update the tests from Task 5** to test the Blade output instead, or remove them if the Blade approach makes them unnecessary. The DemoController tests already cover the route-level behavior.

**Step 1: Remove the demo sharing tests from DemoModeTest**

Remove the two Inertia prop tests added in Task 5 (they test the wrong approach now).

**Step 2: Commit**

```
refactor: use Blade script injection for demo data instead of Inertia prop
```

---

### Task 11: Run Code Quality Checks

**Step 1: Run Pint**

Run: `vendor/bin/pint --dirty --format agent`

**Step 2: Run full check**

Run: `composer check`

Fix any issues that arise.

**Step 3: Commit any fixes**

```
chore: fix code quality issues
```

---

### Task 12: Manual Verification

**Step 1: Seed the database**

Run: `php artisan migrate:fresh --seed`

**Step 2: Build frontend**

Run: `npm run build`

**Step 3: Verify floating button appears**

Visit the app — confirm the beaker icon appears in the bottom-left corner on every page.

**Step 4: Verify modal opens/closes**

Click the button — modal should animate in. Click backdrop or X — should animate out. Press Escape — should close.

**Step 5: Verify account switching**

Click a parent account — should redirect to their intake dashboard. Click a staff account — should redirect to staff dashboard. Click logout — should return to welcome page.

**Step 6: Verify demo mode off**

Set `DEMO_MODE=false` in `.env`, clear config cache. Confirm:
- No floating button visible
- `/demo/*` routes return 403
- Registration link appears again

**Step 7: Commit any final fixes**

---

## Notes for Implementer

- **@skill tailwindcss-development** — activate when styling the modal and components
- **@skill inertia-svelte-development** — activate when creating Svelte components
- The Dialog UI components exist in the project but are Inertia-context-dependent (use `setContext`/`getContext`). The demo panel is mounted outside Inertia, so it builds its own modal markup rather than reusing the Dialog component.
- The `tw-animate-css` import in `app.css` provides `animate-in`, `fade-in`, `zoom-in-95` utilities.
- Tasks 5 and 10 conflict — Task 10 revises the approach. When executing, either skip Task 5 entirely and do the Blade approach from Task 9 from the start, or do Task 5 then refactor in Task 10.
- The CSRF token for form submissions: the Blade layout should have a `<meta name="csrf-token">` tag. Check if it already exists before adding.
