# Intake Model Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Introduce an `Intake` model between `Patient` and `FormResponse` so a parent can complete separate intake paperwork for multiple children.

**Architecture:** `Patient` stays the auth identity (parent). New `Intake` model becomes the container for one child's forms, documents, and signatures. All existing `patient_id` FKs on `form_responses`, `documents`, and `signatures` get replaced with `intake_id`. Session stores both `patient_id` and `intake_id`. When multiple intakes exist, dashboard shows an intake selector.

**Tech Stack:** Laravel 12, Pest 4, Inertia v2 + Svelte 5, Tailwind v4

**Design doc:** `docs/plans/2026-03-08-intake-model-design.md`

---

### Task 1: Create Intake Model, Migration, and Factory

**Files:**
- Create: `database/migrations/YYYY_MM_DD_HHMMSS_create_intakes_table.php`
- Create: `app/Models/Intake.php`
- Create: `database/factories/IntakeFactory.php`
- Modify: `app/Models/Patient.php`
- Test: `tests/Feature/Intake/IntakeModelTest.php`

**Step 1: Write the failing test**

Create `tests/Feature/Intake/IntakeModelTest.php`:

```php
<?php

use App\Models\Intake;
use App\Models\Patient;

test('intake belongs to patient', function (): void {
    $patient = Patient::factory()->create();
    $intake = Intake::factory()->create(['patient_id' => $patient->id]);

    expect($intake->patient->id)->toBe($patient->id);
});

test('patient has many intakes', function (): void {
    $patient = Patient::factory()->create();
    Intake::factory()->count(2)->create(['patient_id' => $patient->id]);

    expect($patient->intakes)->toHaveCount(2);
});

test('intake has child_name and status', function (): void {
    $intake = Intake::factory()->create([
        'child_name' => 'Emma',
        'status' => 'active',
    ]);

    expect($intake->child_name)->toBe('Emma')
        ->and($intake->status)->toBe('active');
});
```

**Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=IntakeModelTest`
Expected: FAIL — `Intake` class not found

**Step 3: Create the migration**

Run: `php artisan make:migration create_intakes_table --no-interaction`

Edit the generated migration:

```php
Schema::create('intakes', function (Blueprint $blueprint): void {
    $blueprint->id();
    $blueprint->foreignId('patient_id')->constrained()->cascadeOnDelete();
    $blueprint->string('child_name')->nullable();
    $blueprint->string('status')->default('active');
    $blueprint->timestamps();
});
```

**Step 4: Create the model**

Run: `php artisan make:class App/Models/Intake --no-interaction`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Intake extends Model
{
    /** @use HasFactory<\Database\Factories\IntakeFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = ['patient_id', 'child_name', 'status'];

    /** @return BelongsTo<Patient, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /** @return HasMany<FormResponse, $this> */
    public function formResponses(): HasMany
    {
        return $this->hasMany(FormResponse::class);
    }

    /** @return HasMany<Document, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /** @return HasMany<Signature, $this> */
    public function signatures(): HasMany
    {
        return $this->hasMany(Signature::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
```

**Step 5: Create the factory**

Run: `php artisan make:factory IntakeFactory --no-interaction`

```php
<?php

namespace Database\Factories;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Intake>
 */
class IntakeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'child_name' => fake()->firstName(),
            'status' => 'active',
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (): array => ['status' => 'completed']);
    }

    public function withoutChildName(): static
    {
        return $this->state(fn (): array => ['child_name' => null]);
    }
}
```

**Step 6: Add `intakes()` relationship to Patient model**

In `app/Models/Patient.php`, add:

```php
/** @return HasMany<Intake, $this> */
public function intakes(): HasMany
{
    return $this->hasMany(Intake::class);
}
```

**Step 7: Run tests to verify they pass**

Run: `php artisan test --compact --filter=IntakeModelTest`
Expected: PASS (3 tests)

**Step 8: Run quality gate and commit**

Run: `composer check`

```bash
git add -A
git commit -m "feat: add Intake model, migration, and factory"
```

---

### Task 2: Migrate FK References from patient_id to intake_id

**Files:**
- Create: `database/migrations/YYYY_MM_DD_HHMMSS_add_intake_id_to_form_responses_documents_signatures.php`
- Modify: `app/Models/FormResponse.php`
- Modify: `app/Models/Document.php`
- Modify: `app/Models/Signature.php`
- Modify: `database/factories/FormResponseFactory.php`
- Test: `tests/Feature/Intake/IntakeModelTest.php` (add more tests)

**Step 1: Write the failing tests**

Add to `tests/Feature/Intake/IntakeModelTest.php`:

```php
use App\Models\FormResponse;
use App\Models\Intake;

test('form response belongs to intake', function (): void {
    $intake = Intake::factory()->create();
    $formResponse = FormResponse::factory()->create(['intake_id' => $intake->id]);

    expect($formResponse->intake->id)->toBe($intake->id);
});

test('intake has many form responses', function (): void {
    $intake = Intake::factory()->create();
    FormResponse::factory()->count(3)->create(['intake_id' => $intake->id]);

    expect($intake->formResponses)->toHaveCount(3);
});
```

**Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=IntakeModelTest`
Expected: FAIL — `intake_id` column doesn't exist

**Step 3: Create the migration**

Run: `php artisan make:migration add_intake_id_to_form_responses_documents_signatures --no-interaction`

The migration must:
1. Add `intake_id` FK to all three tables
2. Create an intake per patient for existing data, then backfill the `intake_id`
3. Drop old `patient_id` columns and constraints
4. Add new unique constraint on `form_responses`

```php
<?php

use App\Models\Intake;
use App\Models\Patient;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Add intake_id columns (nullable initially for backfill)
        Schema::table('form_responses', function (Blueprint $blueprint): void {
            $blueprint->foreignId('intake_id')->nullable()->after('id');
        });

        Schema::table('documents', function (Blueprint $blueprint): void {
            $blueprint->foreignId('intake_id')->nullable()->after('id');
        });

        Schema::table('signatures', function (Blueprint $blueprint): void {
            $blueprint->foreignId('intake_id')->nullable()->after('id');
        });

        // Step 2: Create an intake for each patient that has data, backfill intake_id
        $patientIds = DB::table('form_responses')->distinct()->pluck('patient_id');

        foreach ($patientIds as $patientId) {
            $intake = Intake::query()->create([
                'patient_id' => $patientId,
                'status' => 'active',
            ]);

            DB::table('form_responses')
                ->where('patient_id', $patientId)
                ->update(['intake_id' => $intake->id]);

            DB::table('documents')
                ->where('patient_id', $patientId)
                ->update(['intake_id' => $intake->id]);

            DB::table('signatures')
                ->where('patient_id', $patientId)
                ->update(['intake_id' => $intake->id]);
        }

        // Step 3: Make intake_id non-nullable, add FK constraints, drop patient_id
        Schema::table('form_responses', function (Blueprint $blueprint): void {
            $blueprint->dropForeign(['patient_id']);
            $blueprint->dropUnique(['patient_id', 'schema_key']);
            $blueprint->dropColumn('patient_id');
            $blueprint->foreignId('intake_id')->nullable(false)->constrained()->cascadeOnDelete()->change();
            $blueprint->unique(['intake_id', 'schema_key']);
        });

        Schema::table('documents', function (Blueprint $blueprint): void {
            $blueprint->dropForeign(['patient_id']);
            $blueprint->dropColumn('patient_id');
            $blueprint->foreignId('intake_id')->nullable(false)->constrained()->cascadeOnDelete()->change();
        });

        Schema::table('signatures', function (Blueprint $blueprint): void {
            $blueprint->dropForeign(['patient_id']);
            $blueprint->dropColumn('patient_id');
            $blueprint->foreignId('intake_id')->nullable(false)->constrained()->cascadeOnDelete()->change();
        });
    }

    public function down(): void
    {
        // Re-add patient_id columns, backfill from intake, drop intake_id
        Schema::table('form_responses', function (Blueprint $blueprint): void {
            $blueprint->foreignId('patient_id')->nullable()->after('id');
        });

        DB::table('form_responses')
            ->join('intakes', 'form_responses.intake_id', '=', 'intakes.id')
            ->update(['form_responses.patient_id' => DB::raw('intakes.patient_id')]);

        Schema::table('form_responses', function (Blueprint $blueprint): void {
            $blueprint->dropUnique(['intake_id', 'schema_key']);
            $blueprint->dropForeign(['intake_id']);
            $blueprint->dropColumn('intake_id');
            $blueprint->foreignId('patient_id')->nullable(false)->constrained()->cascadeOnDelete()->change();
            $blueprint->unique(['patient_id', 'schema_key']);
        });

        // Repeat for documents and signatures...
        Schema::table('documents', function (Blueprint $blueprint): void {
            $blueprint->foreignId('patient_id')->nullable()->after('id');
        });

        DB::table('documents')
            ->join('intakes', 'documents.intake_id', '=', 'intakes.id')
            ->update(['documents.patient_id' => DB::raw('intakes.patient_id')]);

        Schema::table('documents', function (Blueprint $blueprint): void {
            $blueprint->dropForeign(['intake_id']);
            $blueprint->dropColumn('intake_id');
            $blueprint->foreignId('patient_id')->nullable(false)->constrained()->cascadeOnDelete()->change();
        });

        Schema::table('signatures', function (Blueprint $blueprint): void {
            $blueprint->foreignId('patient_id')->nullable()->after('id');
        });

        DB::table('signatures')
            ->join('intakes', 'signatures.intake_id', '=', 'intakes.id')
            ->update(['signatures.patient_id' => DB::raw('intakes.patient_id')]);

        Schema::table('signatures', function (Blueprint $blueprint): void {
            $blueprint->dropForeign(['intake_id']);
            $blueprint->dropColumn('intake_id');
            $blueprint->foreignId('patient_id')->nullable(false)->constrained()->cascadeOnDelete()->change();
        });
    }
};
```

**Step 4: Update models**

In `FormResponse.php`:
- Replace `patient_id` with `intake_id` in `$fillable`
- Replace `patient()` relationship with `intake()` returning `BelongsTo<Intake, $this>`

In `Document.php`:
- Replace `patient_id` with `intake_id` in `$fillable`
- Replace `patient()` relationship with `intake()` returning `BelongsTo<Intake, $this>`

In `Signature.php`:
- Replace `patient_id` with `intake_id` in `$fillable`
- Replace `patient()` relationship with `intake()` returning `BelongsTo<Intake, $this>`

**Step 5: Update FormResponseFactory**

In `database/factories/FormResponseFactory.php`, change `patient_id` to `intake_id`:

```php
public function definition(): array
{
    return [
        'intake_id' => \App\Models\Intake::factory(),
        'schema_key' => 'demographics',
        'data' => ['first_name' => fake()->firstName()],
        'status' => 'in_progress',
    ];
}
```

**Step 6: Run tests to verify model tests pass**

Run: `php artisan test --compact --filter=IntakeModelTest`
Expected: PASS (5 tests)

**Step 7: Run quality gate and commit**

Run: `composer check`
Expected: FAIL — existing tests reference `patient_id` on FormResponse. That's expected; we fix those in Task 3.

Commit just the migration, models, and factory:
```bash
git add app/Models/ database/ tests/Feature/Intake/IntakeModelTest.php
git commit -m "feat: migrate form_responses, documents, signatures from patient_id to intake_id"
```

---

### Task 3: Update All Controllers to Use intake_id from Session

**Files:**
- Modify: `app/Http/Middleware/AuthenticatePatient.php`
- Modify: `app/Http/Controllers/Intake/MagicLinkController.php`
- Modify: `app/Http/Controllers/Intake/DashboardController.php`
- Modify: `app/Http/Controllers/Intake/FormController.php`
- Modify: `app/Http/Controllers/Intake/FormCompleteController.php`
- Modify: `app/Http/Controllers/Intake/DocumentController.php`
- Modify: `app/Http/Controllers/Intake/SignatureController.php`
- Modify: `routes/intake.php` (set-locale closure)

**Key changes:**

**AuthenticatePatient middleware:** Validate both `patient_id` and `intake_id` exist in session. Verify intake belongs to patient. If `patient_id` exists but `intake_id` doesn't (returning patient with no active intake), let them through — they'll hit the intake selector.

```php
public function handle(Request $request, Closure $next): Response
{
    $patientId = $request->session()->get('patient_id');

    if (! $patientId || ! Patient::find($patientId)) {
        return redirect()->route('intake.landing');
    }

    return $next($request);
}
```

The middleware stays mostly the same — it validates the patient. The intake_id is checked per-controller where needed.

**MagicLinkController::verify():** After setting `patient_id`, check how many intakes the patient has:
- 0 intakes → create one, set `intake_id` in session, redirect to dashboard
- 1 intake → set that `intake_id` in session, redirect to dashboard
- 2+ intakes → redirect to intake selector (a new route `intake.select`)

```php
public function verify(string $token, Request $request): RedirectResponse
{
    $patient = Patient::query()
        ->where('magic_link_token', $token)
        ->first();

    if (! $patient || ! $patient->hasValidMagicLink()) {
        return redirect()->route('intake.landing')
            ->with('error', 'This link is invalid or has expired.');
    }

    $patient->update([
        'magic_link_token' => null,
        'magic_link_expires_at' => null,
    ]);

    $request->session()->invalidate();
    $request->session()->regenerateToken();
    $request->session()->put('patient_id', $patient->id);

    $intakes = $patient->intakes;

    if ($intakes->isEmpty()) {
        $intake = Intake::query()->create(['patient_id' => $patient->id]);
        $request->session()->put('intake_id', $intake->id);

        return redirect()->route('intake.dashboard');
    }

    if ($intakes->count() === 1) {
        $request->session()->put('intake_id', $intakes->first()->id);

        return redirect()->route('intake.dashboard');
    }

    return redirect()->route('intake.select');
}
```

**DashboardController:** Query `FormResponse` by `intake_id` instead of `patient_id`.

**FormController (show, save, complete):** Query by `intake_id` instead of `patient_id`.

**FormCompleteController:** Query by `intake_id` instead of `patient_id`. Also update `child_name` on the intake when child_information form is completed (extract from form data).

**DocumentController:** Query/create by `intake_id` instead of `patient_id`. Verify document belongs to current intake (not patient). File storage path uses intake_id: `documents/{intake_id}/`.

**SignatureController:** Query/create by `intake_id` instead of `patient_id`. File storage path uses intake_id: `signatures/{intake_id}/`.

**Step 1: Update all controllers**

Make all changes listed above. Every `$request->session()->get('patient_id')` used for data queries becomes `$request->session()->get('intake_id')`. The `patient_id` session value is only used for authentication.

**Step 2: Update the set-locale route closure in `routes/intake.php`**

This one stays on `patient_id` — locale is a patient-level preference, not intake-specific.

**Step 3: Run full test suite**

Run: `php artisan test --compact`
Expected: Multiple failures — existing tests don't set `intake_id` in session.

**Step 4: Don't fix tests yet — commit controller changes**

```bash
git add app/Http/ routes/
git commit -m "refactor: update controllers to use intake_id from session"
```

---

### Task 4: Update All Existing Tests for Intake Model

**Files:**
- Modify: `tests/Feature/Intake/MagicLinkTest.php`
- Modify: `tests/Feature/Intake/DashboardTest.php`
- Modify: `tests/Feature/Intake/FormControllerTest.php`
- Modify: `tests/Feature/Intake/DocumentUploadTest.php`
- Modify: `tests/Feature/Intake/SignatureCaptureTest.php`
- Modify: `tests/Feature/Intake/LocaleTest.php`
- Modify: `tests/Browser/IntakeFlowTest.php`

**Key pattern:** Every test that currently does `withSession(['patient_id' => $patient->id])` and creates `FormResponse` with `patient_id` needs updating:

1. Create an `Intake` for the patient
2. Set both `patient_id` AND `intake_id` in session
3. Create `FormResponse` with `intake_id` instead of `patient_id`

Example before:
```php
$patient = Patient::factory()->create();
FormResponse::factory()->create(['patient_id' => $patient->id, 'schema_key' => 'demographics']);

$this->withSession(['patient_id' => $patient->id])
    ->get(route('intake.dashboard'));
```

Example after:
```php
$patient = Patient::factory()->create();
$intake = Intake::factory()->create(['patient_id' => $patient->id]);
FormResponse::factory()->create(['intake_id' => $intake->id, 'schema_key' => 'demographics']);

$this->withSession(['patient_id' => $patient->id, 'intake_id' => $intake->id])
    ->get(route('intake.dashboard'));
```

**MagicLinkTest.php specific updates:**
- `valid magic link creates session and redirects to dashboard` — should also assert `intake_id` is in session
- Add test: `magic link with multiple intakes redirects to selector`

**DocumentUploadTest.php and SignatureCaptureTest.php:**
- Change `patient_id` references on Document/Signature creation to `intake_id`
- Update authorization checks: verify document/signature belongs to current intake
- File storage paths change from `documents/{patient_id}/` to `documents/{intake_id}/`

**Browser tests:**
- Browser tests authenticate via magic link URL, which auto-creates the first intake. These should mostly work without changes, since the verify flow now auto-creates an intake. Verify they still pass.

**Step 1: Update all tests**

Apply the pattern above to every test file.

**Step 2: Run full test suite**

Run: `composer check`
Expected: ALL PASS

**Step 3: Commit**

```bash
git add tests/
git commit -m "test: update all tests for intake model"
```

---

### Task 5: Add Intake Selector Page

**Files:**
- Create: `app/Http/Controllers/Intake/IntakeSelectorController.php`
- Create: `resources/js/pages/intake/IntakeSelector.svelte`
- Modify: `routes/intake.php`
- Test: `tests/Feature/Intake/IntakeSelectorTest.php`

**Step 1: Write the failing tests**

Create `tests/Feature/Intake/IntakeSelectorTest.php`:

```php
<?php

use App\Models\Intake;
use App\Models\Patient;

test('intake selector shows all intakes for patient', function (): void {
    $patient = Patient::factory()->create();
    Intake::factory()->count(2)->create(['patient_id' => $patient->id]);

    $this->withSession(['patient_id' => $patient->id])
        ->get(route('intake.select'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('intake/IntakeSelector')
            ->has('intakes', 2)
        );
});

test('selecting an intake sets intake_id in session', function (): void {
    $patient = Patient::factory()->create();
    $intake = Intake::factory()->create(['patient_id' => $patient->id]);

    $this->withSession(['patient_id' => $patient->id])
        ->post(route('intake.select.choose', $intake))
        ->assertRedirect(route('intake.dashboard'))
        ->assertSessionHas('intake_id', $intake->id);
});

test('cannot select another patients intake', function (): void {
    $patient = Patient::factory()->create();
    $otherPatient = Patient::factory()->create();
    $otherIntake = Intake::factory()->create(['patient_id' => $otherPatient->id]);

    $this->withSession(['patient_id' => $patient->id])
        ->post(route('intake.select.choose', $otherIntake))
        ->assertForbidden();
});

test('creating new intake copies demographics and insurance data', function (): void {
    $patient = Patient::factory()->create();
    $existingIntake = Intake::factory()->create(['patient_id' => $patient->id]);

    \App\Models\FormResponse::factory()->completed()->create([
        'intake_id' => $existingIntake->id,
        'schema_key' => 'demographics',
        'data' => ['first_name' => 'Jane', 'last_name' => 'Doe', 'phone' => '555-1234'],
    ]);

    \App\Models\FormResponse::factory()->completed()->create([
        'intake_id' => $existingIntake->id,
        'schema_key' => 'insurance',
        'data' => ['provider' => 'BlueCross'],
    ]);

    \App\Models\FormResponse::factory()->completed()->create([
        'intake_id' => $existingIntake->id,
        'schema_key' => 'child_information',
        'data' => ['child_first_name' => 'Emma'],
    ]);

    $this->withSession(['patient_id' => $patient->id])
        ->post(route('intake.select.new'))
        ->assertRedirect(route('intake.dashboard'));

    $newIntake = $patient->intakes()->latest('id')->first();

    // Demographics and insurance are pre-filled as drafts
    $demographics = $newIntake->formResponses()->where('schema_key', 'demographics')->first();
    expect($demographics)->not->toBeNull()
        ->and($demographics->data['first_name'])->toBe('Jane')
        ->and($demographics->status)->toBe('in_progress');

    $insurance = $newIntake->formResponses()->where('schema_key', 'insurance')->first();
    expect($insurance)->not->toBeNull()
        ->and($insurance->data['provider'])->toBe('BlueCross')
        ->and($insurance->status)->toBe('in_progress');

    // Child-specific forms are NOT copied
    $childInfo = $newIntake->formResponses()->where('schema_key', 'child_information')->first();
    expect($childInfo)->toBeNull();
});
```

**Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=IntakeSelectorTest`
Expected: FAIL — route not defined

**Step 3: Add routes**

In `routes/intake.php`, inside the authenticated group:

```php
Route::get('/select', [IntakeSelectorController::class, 'index'])->name('select');
Route::post('/select/{intake}', [IntakeSelectorController::class, 'choose'])->name('select.choose');
Route::post('/select/new', [IntakeSelectorController::class, 'create'])->name('select.new');
```

Note: The `new` route must come before `{intake}` or use a constraint so they don't conflict. Place it first.

**Step 4: Create the controller**

Run: `php artisan make:controller Intake/IntakeSelectorController --no-interaction`

```php
<?php

namespace App\Http\Controllers\Intake;

use App\Http\Controllers\Controller;
use App\Models\FormResponse;
use App\Models\Intake;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IntakeSelectorController extends Controller
{
    /** @var list<string> */
    private const array PARENT_LEVEL_SCHEMAS = ['demographics', 'insurance'];

    public function index(Request $request): Response
    {
        /** @var int $patientId */
        $patientId = $request->session()->get('patient_id');

        $intakes = Intake::query()
            ->where('patient_id', $patientId)
            ->withCount([
                'formResponses',
                'formResponses as completed_forms_count' => function ($query): void {
                    $query->where('status', 'completed');
                },
            ])
            ->latest()
            ->get()
            ->map(fn (Intake $intake): array => [
                'id' => $intake->id,
                'child_name' => $intake->child_name,
                'status' => $intake->status,
                'progress' => [
                    'completed' => $intake->completed_forms_count,
                    'total' => 6,
                ],
                'created_at' => $intake->created_at?->diffForHumans(),
                'updated_at' => $intake->updated_at?->diffForHumans(),
            ]);

        return Inertia::render('intake/IntakeSelector', [
            'intakes' => $intakes,
        ]);
    }

    public function choose(Intake $intake, Request $request): RedirectResponse
    {
        /** @var int $patientId */
        $patientId = $request->session()->get('patient_id');

        if ($intake->patient_id !== $patientId) {
            abort(403);
        }

        $request->session()->put('intake_id', $intake->id);

        return redirect()->route('intake.dashboard');
    }

    public function create(Request $request): RedirectResponse
    {
        /** @var int $patientId */
        $patientId = $request->session()->get('patient_id');

        $newIntake = Intake::query()->create(['patient_id' => $patientId]);

        // Copy parent-level form data from the most recent intake
        $sourceIntake = Intake::query()
            ->where('patient_id', $patientId)
            ->where('id', '!=', $newIntake->id)
            ->latest()
            ->first();

        if ($sourceIntake instanceof Intake) {
            $parentForms = FormResponse::query()
                ->where('intake_id', $sourceIntake->id)
                ->whereIn('schema_key', self::PARENT_LEVEL_SCHEMAS)
                ->get();

            foreach ($parentForms as $form) {
                FormResponse::query()->create([
                    'intake_id' => $newIntake->id,
                    'schema_key' => $form->schema_key,
                    'data' => $form->data,
                    'status' => 'in_progress',
                ]);
            }
        }

        $request->session()->put('intake_id', $newIntake->id);

        return redirect()->route('intake.dashboard');
    }
}
```

**Step 5: Create the Svelte page**

Create `resources/js/pages/intake/IntakeSelector.svelte`:

This page shows:
- A card per intake with child name (or "Child #N"), progress bar, status badge, "Continue" button
- A "Start intake for another child" button at the bottom
- Header with Acorn branding

Use the same visual style as the Dashboard (cards, badges, progress indicators). Use the existing `Card`, `CardContent`, `Button`, `Badge` components.

Interaction:
- "Continue" button on each card → POST to `intake.select.choose` (sets intake_id, redirects to dashboard)
- "Start intake for another child" → POST to `intake.select.new` (creates intake with pre-filled data, redirects to dashboard)

**Step 6: Run tests and verify**

Run: `php artisan test --compact --filter=IntakeSelectorTest`
Expected: PASS

**Step 7: Run quality gate and commit**

Run: `composer check`

```bash
git add -A
git commit -m "feat: add intake selector page with multi-child support"
```

---

### Task 6: Update Monday.com Sync for Intake Model

**Files:**
- Modify: `app/Jobs/SyncPatientToMonday.php`
- Modify: `app/Http/Controllers/Intake/FormController.php` (the dispatch call)
- Modify: `app/Models/Patient.php` (move sync_status/synced_at to Intake if desired, or keep on Patient)

**Key changes:**

The sync job currently takes a `Patient` and iterates `$patient->formResponses()`. It needs to take an `Intake` instead, since each child's intake is a separate Monday.com item.

- Rename/refactor `SyncPatientToMonday` to `SyncIntakeToMonday`
- Constructor takes `Intake` instead of `Patient`
- Queries `$intake->formResponses()` instead of `$patient->formResponses()`
- Queries `$intake->documents()` instead of `$patient->documents()`
- Sync status fields (`sync_status`, `synced_at`) should move to the `intakes` table

**Step 1: Add sync columns to intakes table**

Run: `php artisan make:migration add_sync_columns_to_intakes_table --no-interaction`

```php
Schema::table('intakes', function (Blueprint $blueprint): void {
    $blueprint->string('sync_status')->default('pending')->after('status');
    $blueprint->timestamp('synced_at')->nullable()->after('sync_status');
});
```

Optionally remove from patients table, or leave for backward compat.

**Step 2: Create `SyncIntakeToMonday` job**

Copy/refactor from `SyncPatientToMonday`:
- Replace `Patient $patient` with `Intake $intake`
- Use `$this->intake->formResponses()` and `$this->intake->documents()`
- Update sync status on intake model
- Pass patient to `$mondayService->createItem()` since it needs patient name/email

**Step 3: Update FormController::complete()**

Change `SyncPatientToMonday::dispatch($patient)` to `SyncIntakeToMonday::dispatch($intake)`.

**Step 4: Update/create tests**

Ensure sync job tests reference intake model.

**Step 5: Run quality gate and commit**

Run: `composer check`

```bash
git add -A
git commit -m "refactor: update Monday.com sync to use Intake model"
```

---

### Task 7: Update Dashboard to Show Intake Context

**Files:**
- Modify: `app/Http/Controllers/Intake/DashboardController.php`
- Modify: `resources/js/pages/intake/Dashboard.svelte`

**Key changes:**

Dashboard should show which child's intake is being worked on. Add:
- A subtitle or badge showing child name (if set) below "Your Intake Dashboard"
- A "Switch child" link if the patient has multiple intakes (links to `intake.select`)

**DashboardController:** Pass `intake` data (child_name, has_multiple_intakes flag) to the Inertia page.

```php
$intake = Intake::query()->find($intakeId);
$hasMultipleIntakes = Intake::query()->where('patient_id', $patientId)->count() > 1;

return Inertia::render('intake/Dashboard', [
    'forms' => $forms,
    'progress' => [...],
    'intake' => [
        'child_name' => $intake->child_name,
    ],
    'hasMultipleIntakes' => $hasMultipleIntakes,
]);
```

**Dashboard.svelte:** Show child name context and "Switch child" link.

**Step 1: Update controller and frontend**

**Step 2: Run quality gate and commit**

Run: `composer check`

```bash
git add -A
git commit -m "feat: show intake context on dashboard with switch-child link"
```

---

### Task 8: Extract Child Name from Form Completion

**Files:**
- Modify: `app/Http/Controllers/Intake/FormController.php`

**Key change:**

When the `child_information` form is completed, extract the child's name from the form data and set it on the intake's `child_name` field.

In `FormController::complete()`, after saving the form response:

```php
if ($schemaKey === 'child_information') {
    /** @var string|null $childFirstName */
    $childFirstName = $validatedData['child_first_name'] ?? null;
    /** @var string|null $childLastName */
    $childLastName = $validatedData['child_last_name'] ?? null;

    $childName = trim(($childFirstName ?? '') . ' ' . ($childLastName ?? ''));

    if ($childName !== '') {
        Intake::query()->where('id', $intakeId)->update(['child_name' => $childName]);
    }
}
```

Check the actual field keys in `config/forms/child_information.php` to use the correct keys.

**Step 1: Write test**

Add to `tests/Feature/Intake/FormControllerTest.php`:

```php
test('completing child information updates intake child_name', function (): void {
    $patient = Patient::factory()->create();
    $intake = Intake::factory()->withoutChildName()->create(['patient_id' => $patient->id]);

    // Submit child_information form with child name
    // (use the actual required fields from the schema)
    $this->withSession(['patient_id' => $patient->id, 'intake_id' => $intake->id])
        ->post(route('intake.form.complete', 'child_information'), [
            'data' => [/* required fields including child name */],
        ])
        ->assertRedirect();

    $intake->refresh();
    expect($intake->child_name)->not->toBeNull();
});
```

Check `config/forms/child_information.php` for the exact required fields and field keys.

**Step 2: Implement and run quality gate**

Run: `composer check`

```bash
git add -A
git commit -m "feat: extract child name from child_information form on completion"
```

---

### Task 9: Browser Tests for Multi-Intake Flow

**Files:**
- Modify: `tests/Browser/IntakeFlowTest.php`

**Add browser tests:**

```php
it('auto-creates first intake on magic link verification', function (): void {
    $patient = Patient::factory()->create([
        'magic_link_token' => 'test-auto-intake',
        'magic_link_expires_at' => now()->addMinutes(30),
    ]);

    $pendingAwaitablePage = visit('/intake/verify/test-auto-intake');

    $pendingAwaitablePage->assertPathIs('/intake/dashboard')
        ->assertSee('Your Intake Dashboard')
        ->assertNoJavaScriptErrors();

    expect(\App\Models\Intake::where('patient_id', $patient->id)->count())->toBe(1);
});

it('shows intake selector for patients with multiple intakes', function (): void {
    $patient = Patient::factory()->create([
        'magic_link_token' => 'test-multi-intake',
        'magic_link_expires_at' => now()->addMinutes(30),
    ]);
    \App\Models\Intake::factory()->count(2)->create(['patient_id' => $patient->id]);

    $pendingAwaitablePage = visit('/intake/verify/test-multi-intake');

    $pendingAwaitablePage->assertPathIs('/intake/select')
        ->assertSee('Start intake for another child')
        ->assertNoJavaScriptErrors();
});
```

**Step 1: Write and run browser tests**

Run: `npm run build && php artisan test --compact tests/Browser/`

**Step 2: Run quality gate and commit**

Run: `composer check`

```bash
git add tests/Browser/
git commit -m "test: add browser tests for multi-intake flow"
```
