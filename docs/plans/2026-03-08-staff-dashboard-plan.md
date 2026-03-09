# Staff Dashboard Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Build a lightweight intake-centric staff dashboard for reviewing parent-submitted intakes, with status tracking, notes, flagging, notifications, and PDF export.

**Architecture:** Replace the existing patient-centric staff views with an intake-centric dashboard. Add `IntakeStatus` enum, `IntakeNote` and `IntakeFlag` models, staff notification emails, parent re-entry flow for corrections, and PDF export via `barryvdh/laravel-dompdf`.

**Tech Stack:** Laravel 12, Inertia.js v2, Svelte, Pest 4, Tailwind CSS v4, barryvdh/laravel-dompdf

---

### Task 1: Create IntakeStatus Enum

**Files:**
- Create: `app/Enums/IntakeStatus.php`

**Step 1: Create the enum using artisan**

Run: `php artisan make:enum IntakeStatus --no-interaction`

If artisan doesn't support `make:enum`, create manually.

**Step 2: Implement the enum**

```php
<?php

namespace App\Enums;

enum IntakeStatus: string
{
    case Active = 'active';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Flagged = 'flagged';
    case CorrectionSubmitted = 'correction_submitted';
    case Approved = 'approved';
    case SyncedToMonday = 'synced_to_monday';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Submitted => 'Submitted',
            self::UnderReview => 'Under Review',
            self::Flagged => 'Flagged',
            self::CorrectionSubmitted => 'Corrections Submitted',
            self::Approved => 'Approved',
            self::SyncedToMonday => 'Synced to Monday',
        };
    }

    /**
     * @return list<self>
     */
    public static function staffActionable(): array
    {
        return [self::Submitted, self::CorrectionSubmitted];
    }
}
```

**Step 3: Write the test**

Create: `tests/Unit/Enums/IntakeStatusTest.php`

```php
<?php

use App\Enums\IntakeStatus;

it('has the expected cases', function (): void {
    expect(IntakeStatus::cases())->toHaveCount(7);
});

it('returns a human-readable label', function (): void {
    expect(IntakeStatus::UnderReview->label())->toBe('Under Review');
    expect(IntakeStatus::CorrectionSubmitted->label())->toBe('Corrections Submitted');
});

it('returns staff actionable statuses', function (): void {
    expect(IntakeStatus::staffActionable())->toBe([
        IntakeStatus::Submitted,
        IntakeStatus::CorrectionSubmitted,
    ]);
});
```

**Step 4: Run test**

Run: `php artisan test --compact --filter=IntakeStatusTest`
Expected: PASS

**Step 5: Update Intake model to cast status as IntakeStatus**

Modify: `app/Models/Intake.php`

Add `IntakeStatus` import and update the `casts()` method:

```php
use App\Enums\IntakeStatus;

protected function casts(): array
{
    return [
        'status' => IntakeStatus::class,
        'synced_at' => 'datetime',
    ];
}
```

Update `isCompleted()` and `isActive()` to use the enum:

```php
public function isCompleted(): bool
{
    return $this->status === IntakeStatus::Approved
        || $this->status === IntakeStatus::SyncedToMonday;
}

public function isActive(): bool
{
    return $this->status === IntakeStatus::Active;
}
```

Update `$fillable` to ensure `status` is still fillable.

**Step 6: Update IntakeFactory to use enum**

Modify: `database/factories/IntakeFactory.php`

```php
use App\Enums\IntakeStatus;

// In definition():
'status' => IntakeStatus::Active,

// In completed():
'status' => IntakeStatus::Approved,
```

Add new factory states:

```php
public function submitted(): static
{
    return $this->state(fn (): array => [
        'status' => IntakeStatus::Submitted,
    ]);
}

public function flagged(): static
{
    return $this->state(fn (): array => [
        'status' => IntakeStatus::Flagged,
    ]);
}

public function correctionSubmitted(): static
{
    return $this->state(fn (): array => [
        'status' => IntakeStatus::CorrectionSubmitted,
    ]);
}
```

**Step 7: Update existing code that compares status as strings**

Search codebase for `'completed'`, `'active'`, `'syncing'`, `'synced'`, `'failed'` status comparisons on Intake and update to use enum values where applicable. The `sync_status` field is separate from `status` and should NOT use this enum.

Key files to check and update:
- `app/Http/Controllers/Intake/FormController.php` — `checkAndDispatchSync` may reference status
- `app/Jobs/SyncIntakeToMonday.php` — uses `sync_status`, not `status`, so likely no changes needed

**Step 8: Run full test suite**

Run: `php artisan test --compact`
Expected: PASS

**Step 9: Run code quality checks**

Run: `composer check`
Expected: PASS

**Step 10: Commit**

```bash
git add app/Enums/IntakeStatus.php app/Models/Intake.php database/factories/IntakeFactory.php tests/Unit/Enums/IntakeStatusTest.php
git commit -m "feat: add IntakeStatus enum and cast on Intake model"
```

---

### Task 2: Create IntakeNote Model, Migration, and Factory

**Files:**
- Create: `app/Models/IntakeNote.php`
- Create: migration for `intake_notes` table
- Create: `database/factories/IntakeNoteFactory.php`
- Create: `tests/Unit/Models/IntakeNoteTest.php`

**Step 1: Generate model with migration and factory**

Run: `php artisan make:model IntakeNote -mf --no-interaction`

**Step 2: Write the migration**

```php
Schema::create('intake_notes', function (Blueprint $blueprint): void {
    $blueprint->id();
    $blueprint->foreignId('intake_id')->constrained()->cascadeOnDelete();
    $blueprint->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
    $blueprint->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
    $blueprint->text('body');
    $blueprint->timestamps();
});
```

**Step 3: Implement the model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntakeNote extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $fillable = ['intake_id', 'user_id', 'patient_id', 'body'];

    /** @return BelongsTo<Intake, $this> */
    public function intake(): BelongsTo
    {
        return $this->belongsTo(Intake::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Patient, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function isFromStaff(): bool
    {
        return $this->user_id !== null;
    }
}
```

**Step 4: Add relationship to Intake model**

```php
/** @return HasMany<IntakeNote, $this> */
public function notes(): HasMany
{
    return $this->hasMany(IntakeNote::class);
}
```

**Step 5: Write the factory**

```php
public function definition(): array
{
    return [
        'intake_id' => Intake::factory(),
        'user_id' => User::factory(),
        'patient_id' => null,
        'body' => fake()->sentence(),
    ];
}

public function fromPatient(): static
{
    return $this->state(fn (): array => [
        'user_id' => null,
        'patient_id' => Patient::factory(),
    ]);
}
```

**Step 6: Write tests**

```php
<?php

use App\Models\Intake;
use App\Models\IntakeNote;
use App\Models\Patient;
use App\Models\User;

it('belongs to an intake', function (): void {
    $note = IntakeNote::factory()->create();
    expect($note->intake)->toBeInstanceOf(Intake::class);
});

it('can be from staff', function (): void {
    $note = IntakeNote::factory()->create();
    expect($note->isFromStaff())->toBeTrue();
    expect($note->user)->toBeInstanceOf(User::class);
});

it('can be from a patient', function (): void {
    $note = IntakeNote::factory()->fromPatient()->create();
    expect($note->isFromStaff())->toBeFalse();
    expect($note->patient)->toBeInstanceOf(Patient::class);
});

it('is accessible via intake relationship', function (): void {
    $intake = Intake::factory()->create();
    IntakeNote::factory()->for($intake)->count(3)->create();
    expect($intake->notes)->toHaveCount(3);
});
```

**Step 7: Run migration and tests**

Run: `php artisan migrate && php artisan test --compact --filter=IntakeNoteTest`
Expected: PASS

**Step 8: Run code quality checks**

Run: `composer check`
Expected: PASS

**Step 9: Commit**

```bash
git add app/Models/IntakeNote.php app/Models/Intake.php database/migrations/*create_intake_notes* database/factories/IntakeNoteFactory.php tests/Unit/Models/IntakeNoteTest.php
git commit -m "feat: add IntakeNote model with migration, factory, and tests"
```

---

### Task 3: Create IntakeFlag Model, Migration, and Factory

**Files:**
- Create: `app/Models/IntakeFlag.php`
- Create: migration for `intake_flags` table
- Create: `database/factories/IntakeFlagFactory.php`
- Create: `tests/Unit/Models/IntakeFlagTest.php`

**Step 1: Generate model with migration and factory**

Run: `php artisan make:model IntakeFlag -mf --no-interaction`

**Step 2: Write the migration**

```php
Schema::create('intake_flags', function (Blueprint $blueprint): void {
    $blueprint->id();
    $blueprint->foreignId('intake_id')->constrained()->cascadeOnDelete();
    $blueprint->foreignId('form_response_id')->constrained()->cascadeOnDelete();
    $blueprint->foreignId('user_id')->constrained()->cascadeOnDelete();
    $blueprint->text('reason');
    $blueprint->timestamp('resolved_at')->nullable();
    $blueprint->timestamps();
});
```

**Step 3: Implement the model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntakeFlag extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $fillable = ['intake_id', 'form_response_id', 'user_id', 'reason', 'resolved_at'];

    /** @return BelongsTo<Intake, $this> */
    public function intake(): BelongsTo
    {
        return $this->belongsTo(Intake::class);
    }

    /** @return BelongsTo<FormResponse, $this> */
    public function formResponse(): BelongsTo
    {
        return $this->belongsTo(FormResponse::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isResolved(): bool
    {
        return $this->resolved_at !== null;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }
}
```

**Step 4: Add relationships to Intake and FormResponse models**

In `app/Models/Intake.php`:
```php
/** @return HasMany<IntakeFlag, $this> */
public function flags(): HasMany
{
    return $this->hasMany(IntakeFlag::class);
}
```

In `app/Models/FormResponse.php`:
```php
/** @return HasMany<IntakeFlag, $this> */
public function flags(): HasMany
{
    return $this->hasMany(IntakeFlag::class);
}
```

**Step 5: Write the factory**

```php
public function definition(): array
{
    return [
        'intake_id' => Intake::factory(),
        'form_response_id' => FormResponse::factory(),
        'user_id' => User::factory(),
        'reason' => fake()->sentence(),
        'resolved_at' => null,
    ];
}

public function resolved(): static
{
    return $this->state(fn (): array => [
        'resolved_at' => now(),
    ]);
}
```

**Step 6: Write tests**

```php
<?php

use App\Models\FormResponse;
use App\Models\Intake;
use App\Models\IntakeFlag;

it('belongs to an intake and form response', function (): void {
    $flag = IntakeFlag::factory()->create();
    expect($flag->intake)->toBeInstanceOf(Intake::class);
    expect($flag->formResponse)->toBeInstanceOf(FormResponse::class);
});

it('knows if it is resolved', function (): void {
    $unresolved = IntakeFlag::factory()->create();
    $resolved = IntakeFlag::factory()->resolved()->create();
    expect($unresolved->isResolved())->toBeFalse();
    expect($resolved->isResolved())->toBeTrue();
});

it('is accessible via intake relationship', function (): void {
    $intake = Intake::factory()->create();
    IntakeFlag::factory()->for($intake)->count(2)->create();
    expect($intake->flags)->toHaveCount(2);
});
```

**Step 7: Run migration and tests**

Run: `php artisan migrate && php artisan test --compact --filter=IntakeFlagTest`
Expected: PASS

**Step 8: Run code quality checks**

Run: `composer check`
Expected: PASS

**Step 9: Commit**

```bash
git add app/Models/IntakeFlag.php app/Models/Intake.php app/Models/FormResponse.php database/migrations/*create_intake_flags* database/factories/IntakeFlagFactory.php tests/Unit/Models/IntakeFlagTest.php
git commit -m "feat: add IntakeFlag model with migration, factory, and tests"
```

---

### Task 4: Update Intake Status Transition — Mark Submitted When All Forms Complete

**Files:**
- Modify: `app/Http/Controllers/Intake/FormController.php`
- Create: `tests/Feature/Http/Controllers/Intake/FormControllerStatusTest.php`

**Step 1: Write failing test**

```php
<?php

use App\Enums\IntakeStatus;
use App\Models\Intake;
use App\Models\Patient;
use App\Services\FormSchemaService;

it('sets intake status to submitted when all forms are completed', function (): void {
    $patient = Patient::factory()->withMagicLink()->create();
    $intake = Intake::factory()->for($patient)->create(['status' => IntakeStatus::Active]);

    $formSchemaService = app(FormSchemaService::class);
    $allSchemas = $formSchemaService->all();

    // Complete all forms except the last
    foreach (array_slice($allSchemas, 0, -1) as $schema) {
        $intake->formResponses()->create([
            'schema_key' => $schema['key'],
            'data' => ['test' => 'data'],
            'status' => 'completed',
        ]);
    }

    $lastSchema = end($allSchemas);

    $this->withSession(['patient_id' => $patient->id, 'intake_id' => $intake->id])
        ->post("/intake/form/{$lastSchema['key']}/complete", [
            'data' => array_fill_keys(
                array_keys($formSchemaService->validationRules($lastSchema['key'])),
                'test value',
            ),
        ]);

    expect($intake->fresh()->status)->toBe(IntakeStatus::Submitted);
});
```

Note: This test may need adjustment based on the actual validation rules. The key behavior is: when the last form is completed, intake status transitions from `Active` to `Submitted`.

**Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter="sets intake status to submitted"`
Expected: FAIL

**Step 3: Update `checkAndDispatchSync` in FormController**

In `app/Http/Controllers/Intake/FormController.php`, update `checkAndDispatchSync` to also set intake status to `Submitted`:

```php
private function checkAndDispatchSync(int $intakeId, FormSchemaService $formSchemaService): void
{
    $totalSchemas = count($formSchemaService->all());
    $completedCount = FormResponse::query()
        ->where('intake_id', $intakeId)
        ->where('status', 'completed')
        ->count();

    if ($completedCount >= $totalSchemas) {
        /** @var Intake $intake */
        $intake = Intake::query()->findOrFail($intakeId);
        $intake->update(['status' => IntakeStatus::Submitted]);

        if (config('services.monday.api_token')) {
            SyncIntakeToMonday::dispatch($intake);
        }
    }
}
```

**Step 4: Run tests**

Run: `php artisan test --compact --filter=FormController`
Expected: PASS

**Step 5: Run code quality checks**

Run: `composer check`
Expected: PASS

**Step 6: Commit**

```bash
git add app/Http/Controllers/Intake/FormController.php tests/Feature/Http/Controllers/Intake/FormControllerStatusTest.php
git commit -m "feat: transition intake to submitted status when all forms complete"
```

---

### Task 5: Staff Intake List Controller and Route

**Files:**
- Create: `app/Http/Controllers/Staff/IntakeController.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/Http/Controllers/Staff/IntakeControllerTest.php`

**Step 1: Write the tests**

```php
<?php

use App\Enums\IntakeStatus;
use App\Models\Intake;
use App\Models\Patient;
use App\Models\User;

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

it('displays the intake list page', function (): void {
    Intake::factory()->submitted()->count(3)->create();

    $this->get('/staff/intakes')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('staff/IntakeList')
            ->has('intakes.data', 3)
            ->has('statusCounts')
        );
});

it('filters intakes by status', function (): void {
    Intake::factory()->submitted()->count(2)->create();
    Intake::factory()->flagged()->count(1)->create();

    $this->get('/staff/intakes?status=submitted')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('intakes.data', 2)
        );
});

it('searches intakes by child name', function (): void {
    Intake::factory()->submitted()->create(['child_name' => 'Alice Smith']);
    Intake::factory()->submitted()->create(['child_name' => 'Bob Jones']);

    $this->get('/staff/intakes?search=Alice')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('intakes.data', 1)
        );
});

it('returns status counts for all statuses', function (): void {
    Intake::factory()->submitted()->count(3)->create();
    Intake::factory()->flagged()->count(1)->create();

    $this->get('/staff/intakes')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('statusCounts.submitted', 3)
            ->where('statusCounts.flagged', 1)
        );
});

it('requires authentication', function (): void {
    auth()->logout();
    $this->get('/staff/intakes')->assertRedirect('/login');
});
```

**Step 2: Run tests to verify they fail**

Run: `php artisan test --compact --filter=IntakeControllerTest`
Expected: FAIL

**Step 3: Generate the controller**

Run: `php artisan make:controller Staff/IntakeController --no-interaction`

**Step 4: Implement the controller**

```php
<?php

namespace App\Http\Controllers\Staff;

use App\Enums\IntakeStatus;
use App\Http\Controllers\Controller;
use App\Models\Intake;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IntakeController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Intake::query()
            ->with('patient')
            ->whereNot('status', IntakeStatus::Active)
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where('child_name', 'like', "%{$search}%");
        }

        /** @var array<string, int> $statusCounts */
        $statusCounts = [];
        foreach (IntakeStatus::cases() as $status) {
            if ($status === IntakeStatus::Active) {
                continue;
            }
            $statusCounts[$status->value] = Intake::query()
                ->where('status', $status)
                ->count();
        }

        return Inertia::render('staff/IntakeList', [
            'intakes' => $query->paginate(20),
            'statusCounts' => $statusCounts,
            'filters' => [
                'status' => $request->string('status')->toString(),
                'search' => $request->string('search')->toString(),
            ],
        ]);
    }
}
```

Note: The search uses `like` on `child_name` for now. Since `child_name` is encrypted with HasEncryptedPhi, this won't work as a simple `like` query. We may need to add a blind index for `child_name` on Intake (like `email_hash` on Patient) or search by decrypting. Evaluate during implementation — if blind index is needed, add a migration and update HasEncryptedPhi on Intake. Alternatively, search by parent name/email via the patient relationship using the existing `whereBlindIndex` scope.

**Step 5: Add routes**

In `routes/web.php`, inside the `staff` prefix group:

```php
use App\Http\Controllers\Staff\IntakeController;

Route::get('/intakes', [IntakeController::class, 'index'])->name('intakes.index');
```

**Step 6: Run tests**

Run: `php artisan test --compact --filter=IntakeControllerTest`
Expected: PASS (adjust search test if blind index needed)

**Step 7: Run code quality checks**

Run: `composer check`
Expected: PASS

**Step 8: Commit**

```bash
git add app/Http/Controllers/Staff/IntakeController.php routes/web.php tests/Feature/Http/Controllers/Staff/IntakeControllerTest.php
git commit -m "feat: add staff intake list controller with filtering and search"
```

---

### Task 6: Staff Intake Detail Controller

**Files:**
- Modify: `app/Http/Controllers/Staff/IntakeController.php`
- Modify: `routes/web.php`
- Modify: `tests/Feature/Http/Controllers/Staff/IntakeControllerTest.php`

**Step 1: Write the tests**

Append to `IntakeControllerTest.php`:

```php
it('displays the intake detail page', function (): void {
    $intake = Intake::factory()->submitted()->create();
    $intake->formResponses()->create([
        'schema_key' => 'demographics',
        'data' => ['first_name' => 'Jane'],
        'status' => 'completed',
    ]);

    $this->get("/staff/intakes/{$intake->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('staff/IntakeDetail')
            ->has('intake')
            ->has('formResponses')
            ->has('notes')
            ->has('flags')
            ->has('schemas')
        );
});

it('auto-transitions intake to under review when staff views it', function (): void {
    $intake = Intake::factory()->submitted()->create();

    $this->get("/staff/intakes/{$intake->id}");

    expect($intake->fresh()->status)->toBe(IntakeStatus::UnderReview);
});

it('does not transition non-submitted intakes to under review', function (): void {
    $intake = Intake::factory()->flagged()->create();

    $this->get("/staff/intakes/{$intake->id}");

    expect($intake->fresh()->status)->toBe(IntakeStatus::Flagged);
});
```

**Step 2: Run tests to verify failure**

Run: `php artisan test --compact --filter=IntakeControllerTest`
Expected: new tests FAIL

**Step 3: Add `show` method to IntakeController**

```php
use App\Services\FormSchemaService;

public function show(Intake $intake, FormSchemaService $formSchemaService): Response
{
    if ($intake->status === IntakeStatus::Submitted) {
        $intake->update(['status' => IntakeStatus::UnderReview]);
    }

    $intake->load(['patient', 'formResponses', 'notes.user', 'notes.patient', 'flags.formResponse', 'flags.user']);

    $schemas = collect($formSchemaService->all())
        ->map(fn (array $schema): array => [
            'key' => $schema['key'],
            'title' => __($schema['title']),
            'order' => $schema['order'],
        ])
        ->sortBy('order')
        ->values()
        ->all();

    return Inertia::render('staff/IntakeDetail', [
        'intake' => $intake,
        'formResponses' => $intake->formResponses,
        'notes' => $intake->notes->sortBy('created_at')->values(),
        'flags' => $intake->flags,
        'schemas' => $schemas,
    ]);
}
```

**Step 4: Add route**

In `routes/web.php`, inside the `staff` prefix group:

```php
Route::get('/intakes/{intake}', [IntakeController::class, 'show'])->name('intakes.show');
```

**Step 5: Run tests**

Run: `php artisan test --compact --filter=IntakeControllerTest`
Expected: PASS

**Step 6: Run code quality checks**

Run: `composer check`
Expected: PASS

**Step 7: Commit**

```bash
git add app/Http/Controllers/Staff/IntakeController.php routes/web.php tests/Feature/Http/Controllers/Staff/IntakeControllerTest.php
git commit -m "feat: add staff intake detail view with auto-transition to under review"
```

---

### Task 7: Staff Actions — Approve, Flag, Resolve Flag

**Files:**
- Modify: `app/Http/Controllers/Staff/IntakeController.php`
- Create: `app/Http/Requests/Staff/FlagFormRequest.php`
- Modify: `routes/web.php`
- Modify: `tests/Feature/Http/Controllers/Staff/IntakeControllerTest.php`

**Step 1: Write the tests**

```php
it('allows staff to approve an intake', function (): void {
    $intake = Intake::factory()->create(['status' => IntakeStatus::UnderReview]);

    $this->post("/staff/intakes/{$intake->id}/approve")
        ->assertRedirect();

    expect($intake->fresh()->status)->toBe(IntakeStatus::Approved);
});

it('allows staff to flag a form on an intake', function (): void {
    $intake = Intake::factory()->create(['status' => IntakeStatus::UnderReview]);
    $formResponse = $intake->formResponses()->create([
        'schema_key' => 'demographics',
        'data' => ['first_name' => 'Jane'],
        'status' => 'completed',
    ]);

    $this->post("/staff/intakes/{$intake->id}/flag", [
        'form_response_id' => $formResponse->id,
        'reason' => 'Missing last name',
    ])->assertRedirect();

    expect($intake->fresh()->status)->toBe(IntakeStatus::Flagged);
    expect($intake->flags)->toHaveCount(1);
    expect($intake->flags->first()->reason)->toBe('Missing last name');
});

it('allows staff to resolve a flag', function (): void {
    $intake = Intake::factory()->flagged()->create();
    $flag = IntakeFlag::factory()->for($intake)->create();

    $this->post("/staff/intakes/{$intake->id}/flags/{$flag->id}/resolve")
        ->assertRedirect();

    expect($flag->fresh()->resolved_at)->not->toBeNull();
});
```

**Step 2: Run tests to verify failure**

Run: `php artisan test --compact --filter=IntakeControllerTest`

**Step 3: Create FlagFormRequest**

Run: `php artisan make:request Staff/FlagFormRequest --no-interaction`

```php
<?php

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

class FlagFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'form_response_id' => ['required', 'exists:form_responses,id'],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
```

**Step 4: Add action methods to IntakeController**

```php
use App\Http\Requests\Staff\FlagFormRequest;
use App\Models\IntakeFlag;
use Illuminate\Http\RedirectResponse;

public function approve(Intake $intake): RedirectResponse
{
    $intake->update(['status' => IntakeStatus::Approved]);

    if (config('services.monday.api_token')) {
        SyncIntakeToMonday::dispatch($intake);
    }

    return back();
}

public function flag(Intake $intake, FlagFormRequest $flagFormRequest): RedirectResponse
{
    IntakeFlag::query()->create([
        'intake_id' => $intake->id,
        'form_response_id' => $flagFormRequest->validated('form_response_id'),
        'user_id' => auth()->id(),
        'reason' => $flagFormRequest->validated('reason'),
    ]);

    $intake->update(['status' => IntakeStatus::Flagged]);

    // TODO: Task 10 will add notification to parent here

    return back();
}

public function resolveFlag(Intake $intake, IntakeFlag $flag): RedirectResponse
{
    $flag->update(['resolved_at' => now()]);

    // If all flags resolved, consider transitioning status back
    $unresolvedCount = $intake->flags()->whereNull('resolved_at')->count();
    if ($unresolvedCount === 0) {
        $intake->update(['status' => IntakeStatus::UnderReview]);
    }

    return back();
}
```

**Step 5: Add routes**

```php
Route::post('/intakes/{intake}/approve', [IntakeController::class, 'approve'])->name('intakes.approve');
Route::post('/intakes/{intake}/flag', [IntakeController::class, 'flag'])->name('intakes.flag');
Route::post('/intakes/{intake}/flags/{flag}/resolve', [IntakeController::class, 'resolveFlag'])->name('intakes.flags.resolve');
```

**Step 6: Run tests**

Run: `php artisan test --compact --filter=IntakeControllerTest`
Expected: PASS

**Step 7: Run code quality checks**

Run: `composer check`
Expected: PASS

**Step 8: Commit**

```bash
git add app/Http/Controllers/Staff/IntakeController.php app/Http/Requests/Staff/FlagFormRequest.php routes/web.php tests/Feature/Http/Controllers/Staff/IntakeControllerTest.php
git commit -m "feat: add staff intake actions — approve, flag, and resolve flag"
```

---

### Task 8: Staff Add Note Endpoint

**Files:**
- Modify: `app/Http/Controllers/Staff/IntakeController.php`
- Create: `app/Http/Requests/Staff/StoreNoteRequest.php`
- Modify: `routes/web.php`
- Modify: `tests/Feature/Http/Controllers/Staff/IntakeControllerTest.php`

**Step 1: Write the test**

```php
it('allows staff to add a note', function (): void {
    $intake = Intake::factory()->submitted()->create();

    $this->post("/staff/intakes/{$intake->id}/notes", [
        'body' => 'Looks good, just need to verify insurance.',
    ])->assertRedirect();

    expect($intake->notes)->toHaveCount(1);
    expect($intake->notes->first()->body)->toBe('Looks good, just need to verify insurance.');
    expect($intake->notes->first()->user_id)->toBe(auth()->id());
});
```

**Step 2: Run test to verify failure**

Run: `php artisan test --compact --filter="allows staff to add a note"`

**Step 3: Create StoreNoteRequest**

Run: `php artisan make:request Staff/StoreNoteRequest --no-interaction`

```php
<?php

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

class StoreNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:5000'],
        ];
    }
}
```

**Step 4: Add `storeNote` method to IntakeController**

```php
use App\Http\Requests\Staff\StoreNoteRequest;
use App\Models\IntakeNote;

public function storeNote(Intake $intake, StoreNoteRequest $storeNoteRequest): RedirectResponse
{
    IntakeNote::query()->create([
        'intake_id' => $intake->id,
        'user_id' => auth()->id(),
        'body' => $storeNoteRequest->validated('body'),
    ]);

    return back();
}
```

**Step 5: Add route**

```php
Route::post('/intakes/{intake}/notes', [IntakeController::class, 'storeNote'])->name('intakes.notes.store');
```

**Step 6: Run tests**

Run: `php artisan test --compact --filter=IntakeControllerTest`
Expected: PASS

**Step 7: Run code quality checks**

Run: `composer check`
Expected: PASS

**Step 8: Commit**

```bash
git add app/Http/Controllers/Staff/IntakeController.php app/Http/Requests/Staff/StoreNoteRequest.php routes/web.php tests/Feature/Http/Controllers/Staff/IntakeControllerTest.php
git commit -m "feat: add staff note creation endpoint"
```

---

### Task 9: Intake List Svelte Page

**Files:**
- Create: `resources/js/pages/staff/IntakeList.svelte`

Activate `inertia-svelte-development` and `tailwindcss-development` skills.

**Step 1: Search docs for Inertia Svelte patterns**

Use `search-docs` with queries: `['inertia link', 'inertia pagination']` filtered to `inertia-svelte`.

**Step 2: Build the IntakeList page**

Use `AppSidebarLayout` (matching existing staff pages). Reference `resources/js/pages/staff/PatientList.svelte` for the table/pagination pattern.

Key elements:
- Status filter tabs using existing `Badge` component — one per status with count
- Active/default filter: show `Submitted` + `Corrections Submitted`
- Sortable table: Child Name, Parent Name, Submitted Date, Status (as Badge), Last Updated
- Each row links to `/staff/intakes/{id}`
- Search input at top
- Pagination links at bottom
- Use Inertia `Link` for navigation, pass `status` and `search` as query params

Type definitions needed:
```typescript
type IntakeItem = {
    id: number;
    child_name: string | null;
    status: string;
    created_at: string;
    updated_at: string;
    patient: {
        id: number;
        name: string | null;
        email: string;
    };
};

type StatusCounts = Record<string, number>;

type Filters = {
    status: string;
    search: string;
};
```

Status badge color mapping:
```typescript
const statusConfig: Record<string, { label: string; variant: 'default' | 'secondary' | 'outline' | 'destructive' }> = {
    submitted: { label: 'Submitted', variant: 'default' },
    under_review: { label: 'Under Review', variant: 'secondary' },
    flagged: { label: 'Flagged', variant: 'destructive' },
    correction_submitted: { label: 'Corrections Submitted', variant: 'outline' },
    approved: { label: 'Approved', variant: 'default' },
    synced_to_monday: { label: 'Synced', variant: 'secondary' },
};
```

**Step 3: Run build**

Run: `npm run build`
Expected: No errors

**Step 4: Commit**

```bash
git add resources/js/pages/staff/IntakeList.svelte
git commit -m "feat: add staff intake list Svelte page"
```

---

### Task 10: Intake Detail Svelte Page

**Files:**
- Create: `resources/js/pages/staff/IntakeDetail.svelte`

Activate `inertia-svelte-development` and `tailwindcss-development` skills.

**Step 1: Search docs**

Use `search-docs` with queries: `['inertia forms', 'inertia useForm']` filtered to `inertia-svelte`.

**Step 2: Build the IntakeDetail page**

Use `AppSidebarLayout`. Reference `resources/js/pages/staff/PatientDetail.svelte` for the layout pattern.

Three sections stacked:

**Top bar:**
- Child name (large heading), parent name/email below
- Status badge (using same `statusConfig`)
- Action buttons: "Approve" (green), "Export PDF" (outline)
- Use Inertia `useForm` for the approve action: `POST /staff/intakes/{id}/approve`

**Form responses section:**
- Use `Collapsible` + `CollapsibleTrigger` + `CollapsibleContent` from UI components for accordion
- Each form: title (from `schemas` prop), completion badge, flag indicator
- Expanded view: key/value pairs from `data` in a definition list (`dl/dt/dd`)
- "Flag this form" button → opens inline form with textarea for reason + submit
- Use `useForm` for flag: `POST /staff/intakes/{id}/flag` with `{ form_response_id, reason }`
- Flagged forms: show reason text and "Resolve" button
- Use `useForm` for resolve: `POST /staff/intakes/{id}/flags/{flagId}/resolve`

**Notes section:**
- Chronological list of notes
- Each note: author name, role badge ("Staff" or "Parent"), timestamp, body text
- Add note form at bottom: textarea + submit button
- Use `useForm` for note: `POST /staff/intakes/{id}/notes` with `{ body }`

**Step 3: Run build**

Run: `npm run build`
Expected: No errors

**Step 4: Commit**

```bash
git add resources/js/pages/staff/IntakeDetail.svelte
git commit -m "feat: add staff intake detail Svelte page with actions"
```

---

### Task 11: Notifications — Flag Notification to Parent

**Files:**
- Create: `app/Notifications/IntakeFlaggedNotification.php`
- Modify: `app/Http/Controllers/Staff/IntakeController.php` (flag method)
- Create: `tests/Feature/Notifications/IntakeFlaggedNotificationTest.php`

**Step 1: Write the test**

```php
<?php

use App\Enums\IntakeStatus;
use App\Models\Intake;
use App\Models\Patient;
use App\Models\User;
use App\Notifications\IntakeFlaggedNotification;
use Illuminate\Support\Facades\Notification;

it('sends a notification to the parent when a form is flagged', function (): void {
    Notification::fake();

    $this->actingAs(User::factory()->create());
    $patient = Patient::factory()->create();
    $intake = Intake::factory()->for($patient)->create(['status' => IntakeStatus::UnderReview]);
    $formResponse = $intake->formResponses()->create([
        'schema_key' => 'demographics',
        'data' => ['first_name' => 'Jane'],
        'status' => 'completed',
    ]);

    $this->post("/staff/intakes/{$intake->id}/flag", [
        'form_response_id' => $formResponse->id,
        'reason' => 'Missing information',
    ]);

    Notification::assertSentTo($patient, IntakeFlaggedNotification::class);
});
```

**Step 2: Run test to verify failure**

**Step 3: Create the notification**

Run: `php artisan make:notification IntakeFlaggedNotification --no-interaction`

```php
<?php

namespace App\Notifications;

use App\Models\Intake;
use App\Models\IntakeFlag;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class IntakeFlaggedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Intake $intake,
        private readonly IntakeFlag $flag,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $childName = $this->intake->child_name ?? 'your child';
        $formTitle = $this->flag->formResponse->schema_key;

        return (new MailMessage)
            ->subject("Action Needed: {$childName}'s Intake")
            ->greeting('Hello!')
            ->line("A form in {$childName}'s intake requires your attention.")
            ->line("**Form:** {$formTitle}")
            ->line("**Reason:** {$this->flag->reason}")
            ->action('Review & Update', url('/intake'))
            ->line('Please log in to review and correct the flagged form.');
    }
}
```

**Step 4: Update the `flag` method in IntakeController**

Add after creating the flag and updating status:

```php
$intake->patient->notify(new IntakeFlaggedNotification($intake, $intakeFlag));
```

Where `$intakeFlag` is the created flag record (update the create call to capture the return).

**Step 5: Run tests**

Run: `php artisan test --compact --filter=IntakeFlaggedNotificationTest`
Expected: PASS

**Step 6: Run code quality checks**

Run: `composer check`
Expected: PASS

**Step 7: Commit**

```bash
git add app/Notifications/IntakeFlaggedNotification.php app/Http/Controllers/Staff/IntakeController.php tests/Feature/Notifications/IntakeFlaggedNotificationTest.php
git commit -m "feat: notify parent when intake form is flagged"
```

---

### Task 12: Notifications — Correction Submitted Notification to Staff

**Files:**
- Create: `app/Notifications/CorrectionSubmittedNotification.php`
- Modify: `app/Http/Controllers/Intake/FormController.php`
- Create: `tests/Feature/Notifications/CorrectionSubmittedNotificationTest.php`

**Step 1: Write the test**

```php
<?php

use App\Enums\IntakeStatus;
use App\Models\Intake;
use App\Models\Patient;
use App\Models\User;
use App\Notifications\CorrectionSubmittedNotification;
use Illuminate\Support\Facades\Notification;

it('notifies staff when a parent resubmits a corrected form', function (): void {
    Notification::fake();

    $staffUser = User::factory()->create();
    $patient = Patient::factory()->withMagicLink()->create();
    $intake = Intake::factory()->for($patient)->create(['status' => IntakeStatus::Flagged]);

    // Simulate the parent completing a corrected form
    // This should trigger the notification and status change to CorrectionSubmitted

    // The exact mechanism depends on how FormController detects the intake is flagged
    // and transitions the status. See implementation step.
});
```

Note: The exact test will depend on how the FormController detects the correction flow. The key behavior: when a parent completes a form on a `Flagged` intake, the status moves to `CorrectionSubmitted` and staff users are notified.

**Step 2: Create the notification**

Run: `php artisan make:notification CorrectionSubmittedNotification --no-interaction`

```php
<?php

namespace App\Notifications;

use App\Models\Intake;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CorrectionSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Intake $intake) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $childName = $this->intake->child_name ?? 'Unknown';

        return (new MailMessage)
            ->subject("Intake Updated: {$childName}")
            ->greeting('Hello!')
            ->line("{$childName}'s intake has been updated with corrections.")
            ->line('Please review the updated submission.')
            ->action('Review Intake', url("/staff/intakes/{$this->intake->id}"));
    }
}
```

**Step 3: Update FormController to handle correction flow**

In `FormController::complete()`, after `updateOrCreate`, check if the intake was flagged:

```php
$intake = Intake::query()->findOrFail($intakeId);

if ($intake->status === IntakeStatus::Flagged) {
    $intake->update(['status' => IntakeStatus::CorrectionSubmitted]);

    // Notify all staff users
    $staffUsers = User::all();
    Notification::send($staffUsers, new CorrectionSubmittedNotification($intake));
}
```

**Step 4: Run tests**

Run: `php artisan test --compact --filter=CorrectionSubmittedNotificationTest`
Expected: PASS

**Step 5: Run code quality checks**

Run: `composer check`
Expected: PASS

**Step 6: Commit**

```bash
git add app/Notifications/CorrectionSubmittedNotification.php app/Http/Controllers/Intake/FormController.php tests/Feature/Notifications/CorrectionSubmittedNotificationTest.php
git commit -m "feat: notify staff when parent submits corrections"
```

---

### Task 13: Parent Notes — Add Note from Parent Side

**Files:**
- Create: `app/Http/Controllers/Intake/NoteController.php`
- Modify: `routes/intake.php`
- Modify: intake Dashboard or Detail page to show notes
- Create: `tests/Feature/Http/Controllers/Intake/NoteControllerTest.php`

**Step 1: Write the test**

```php
<?php

use App\Models\Intake;
use App\Models\IntakeNote;
use App\Models\Patient;

it('allows a parent to add a note to their intake', function (): void {
    $patient = Patient::factory()->withMagicLink()->create();
    $intake = Intake::factory()->for($patient)->create();

    $this->withSession(['patient_id' => $patient->id, 'intake_id' => $intake->id])
        ->post('/intake/notes', [
            'body' => 'I have a question about the insurance form.',
        ])
        ->assertRedirect();

    expect($intake->notes)->toHaveCount(1);
    expect($intake->notes->first()->patient_id)->toBe($patient->id);
    expect($intake->notes->first()->user_id)->toBeNull();
});
```

**Step 2: Create the controller**

Run: `php artisan make:controller Intake/NoteController --no-interaction`

```php
<?php

namespace App\Http\Controllers\Intake;

use App\Http\Controllers\Controller;
use App\Models\IntakeNote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        /** @var int $intakeId */
        $intakeId = $request->session()->get('intake_id');
        /** @var int $patientId */
        $patientId = $request->session()->get('patient_id');

        IntakeNote::query()->create([
            'intake_id' => $intakeId,
            'patient_id' => $patientId,
            'body' => $request->validated('body'),
        ]);

        return back();
    }
}
```

**Step 3: Add route to `routes/intake.php`**

Inside the authenticated patient group:

```php
use App\Http\Controllers\Intake\NoteController;

Route::post('/notes', [NoteController::class, 'store'])->name('intake.notes.store');
```

**Step 4: Run tests**

Run: `php artisan test --compact --filter=NoteControllerTest`
Expected: PASS

**Step 5: Run code quality checks**

Run: `composer check`
Expected: PASS

**Step 6: Commit**

```bash
git add app/Http/Controllers/Intake/NoteController.php routes/intake.php tests/Feature/Http/Controllers/Intake/NoteControllerTest.php
git commit -m "feat: allow parents to add notes to their intake"
```

---

### Task 14: PDF Export

**Files:**
- Install: `barryvdh/laravel-dompdf`
- Create: `app/Http/Controllers/Staff/IntakePdfController.php`
- Create: `resources/views/pdf/intake-summary.blade.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/Http/Controllers/Staff/IntakePdfControllerTest.php`

**Step 1: Install dompdf**

Run: `composer require barryvdh/laravel-dompdf --no-interaction`

**Step 2: Search docs**

Use `search-docs` with queries: `['dompdf pdf generation']`.

**Step 3: Write the test**

```php
<?php

use App\Enums\IntakeStatus;
use App\Models\Intake;
use App\Models\User;

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

it('generates a PDF for an intake', function (): void {
    $intake = Intake::factory()->submitted()->create();
    $intake->formResponses()->create([
        'schema_key' => 'demographics',
        'data' => ['first_name' => 'Jane', 'last_name' => 'Doe'],
        'status' => 'completed',
    ]);

    $this->get("/staff/intakes/{$intake->id}/pdf")
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});
```

**Step 4: Create the controller**

Run: `php artisan make:controller Staff/IntakePdfController --no-interaction`

```php
<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Intake;
use App\Services\FormSchemaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class IntakePdfController extends Controller
{
    public function __invoke(Intake $intake, FormSchemaService $formSchemaService): Response
    {
        $intake->load(['patient', 'formResponses', 'notes.user', 'notes.patient', 'signatures']);

        $schemas = collect($formSchemaService->all())
            ->sortBy('order')
            ->values()
            ->all();

        /** @var \Barryvdh\DomPDF\PDF $pdf */
        $pdf = Pdf::loadView('pdf.intake-summary', [
            'intake' => $intake,
            'schemas' => $schemas,
        ]);

        $childName = $intake->child_name ?? 'intake';
        $filename = str($childName)->slug()->append('-intake-summary.pdf')->toString();

        return $pdf->download($filename);
    }
}
```

**Step 5: Create the Blade view**

Create `resources/views/pdf/intake-summary.blade.php` — a clean, printable layout:

```blade
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Intake Summary</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #333; }
        h1 { font-size: 20px; margin-bottom: 4px; }
        h2 { font-size: 16px; border-bottom: 1px solid #ccc; padding-bottom: 4px; margin-top: 24px; }
        .header { margin-bottom: 24px; }
        .meta { color: #666; font-size: 11px; }
        .field { margin-bottom: 8px; }
        .field-label { font-weight: bold; color: #555; }
        .note { border-left: 3px solid #ddd; padding-left: 8px; margin-bottom: 8px; }
        .note-meta { font-size: 10px; color: #888; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Intake Summary</h1>
        <p class="meta">Generated {{ now()->format('F j, Y') }}</p>
        <p><strong>Child:</strong> {{ $intake->child_name ?? '—' }}</p>
        <p><strong>Parent:</strong> {{ $intake->patient->name ?? $intake->patient->email }}</p>
        <p><strong>Status:</strong> {{ $intake->status->label() }}</p>
        <p><strong>Submitted:</strong> {{ $intake->created_at->format('F j, Y') }}</p>
    </div>

    @foreach ($schemas as $schema)
        @php
            $response = $intake->formResponses->firstWhere('schema_key', $schema['key']);
        @endphp
        <h2>{{ __($schema['title']) }}</h2>
        @if ($response)
            @foreach ($response->data ?? [] as $key => $value)
                <div class="field">
                    <span class="field-label">{{ str($key)->replace('_', ' ')->title() }}:</span>
                    @if (is_array($value))
                        {{ implode(', ', $value) }}
                    @else
                        {{ $value }}
                    @endif
                </div>
            @endforeach
        @else
            <p class="meta">Not completed</p>
        @endif
    @endforeach

    @if ($intake->notes->isNotEmpty())
        <h2>Notes</h2>
        @foreach ($intake->notes->sortBy('created_at') as $note)
            <div class="note">
                <p class="note-meta">
                    {{ $note->user?->name ?? $note->patient?->name ?? 'Unknown' }}
                    ({{ $note->isFromStaff() ? 'Staff' : 'Parent' }})
                    — {{ $note->created_at->format('M j, Y g:i A') }}
                </p>
                <p>{{ $note->body }}</p>
            </div>
        @endforeach
    @endif
</body>
</html>
```

**Step 6: Add route**

```php
use App\Http\Controllers\Staff\IntakePdfController;

Route::get('/intakes/{intake}/pdf', IntakePdfController::class)->name('intakes.pdf');
```

**Step 7: Run tests**

Run: `php artisan test --compact --filter=IntakePdfControllerTest`
Expected: PASS

**Step 8: Run code quality checks**

Run: `composer check`
Expected: PASS

**Step 9: Commit**

```bash
git add app/Http/Controllers/Staff/IntakePdfController.php resources/views/pdf/intake-summary.blade.php routes/web.php tests/Feature/Http/Controllers/Staff/IntakePdfControllerTest.php
git commit -m "feat: add PDF export for intake summaries"
```

---

### Task 15: Update Sidebar Navigation

**Files:**
- Modify: sidebar navigation component (check `resources/js/components/` or layouts for nav items)

**Step 1: Find the sidebar nav configuration**

Search for where sidebar navigation items are defined. Likely in `resources/js/layouts/app/` or a component within.

**Step 2: Add "Intakes" link to staff nav**

Add a nav item pointing to `/staff/intakes` with an appropriate icon (e.g., clipboard or file icon). Place it above or alongside the existing "Patients" link.

**Step 3: Run build**

Run: `npm run build`
Expected: No errors

**Step 4: Commit**

```bash
git add <modified sidebar files>
git commit -m "feat: add intakes link to staff sidebar navigation"
```

---

### Task 16: Clean Up Old Staff Patient Views (Optional)

**Files:**
- Evaluate: `resources/js/pages/staff/PatientList.svelte`
- Evaluate: `resources/js/pages/staff/PatientDetail.svelte`
- Evaluate: `app/Http/Controllers/Staff/PatientController.php`

**Step 1: Decide whether to keep or remove**

The old patient-centric views may still be useful for looking up patients directly. If the intake-centric dashboard fully replaces the need:
- Remove old Svelte pages
- Remove PatientController
- Remove routes

If keeping both, no action needed. Discuss with user.

**Step 2: Run code quality checks**

Run: `composer check`
Expected: PASS

**Step 3: Commit if changes made**

---

### Task 17: Parent Dashboard — Show Flags and Notes

**Files:**
- Modify: `app/Http/Controllers/Intake/DashboardController.php`
- Modify: `resources/js/pages/intake/Dashboard.svelte`

**Step 1: Update DashboardController to pass flags and notes**

Add to the Inertia props:
```php
'flags' => $intake->flags()->with('formResponse')->whereNull('resolved_at')->get(),
'notes' => $intake->notes()->with(['user', 'patient'])->latest()->get(),
```

**Step 2: Update Dashboard.svelte**

- Show a banner/alert when there are unresolved flags: "Action needed — [form name] needs corrections" with link to the form
- Show a notes section where parent can read and add notes
- Use existing `Alert`, `AlertTitle`, `AlertDescription` components for the flag banner

**Step 3: Run build**

Run: `npm run build`
Expected: No errors

**Step 4: Commit**

```bash
git add app/Http/Controllers/Intake/DashboardController.php resources/js/pages/intake/Dashboard.svelte
git commit -m "feat: show flags and notes on parent intake dashboard"
```

---

### Task 18: Generate Wayfinder Routes

After all routes are defined, regenerate Wayfinder TypeScript bindings.

**Step 1: Run Wayfinder generation**

Run: `php artisan wayfinder:generate` (or check the exact command via `list-artisan-commands`)

**Step 2: Update Svelte pages to use Wayfinder imports**

Replace hardcoded URLs in the new staff pages with Wayfinder route functions.

**Step 3: Run build**

Run: `npm run build`
Expected: No errors

**Step 4: Commit**

```bash
git add resources/js/actions/ resources/js/routes/ resources/js/pages/staff/
git commit -m "feat: use Wayfinder route functions in staff pages"
```

---

### Task 19: Final Integration Test and Code Quality

**Step 1: Run full test suite**

Run: `php artisan test --compact`
Expected: ALL PASS

**Step 2: Run full code quality pipeline**

Run: `composer check`
Expected: ALL PASS

**Step 3: Final commit if any fixes needed**

```bash
git add -A
git commit -m "chore: fix code quality issues from integration"
```
