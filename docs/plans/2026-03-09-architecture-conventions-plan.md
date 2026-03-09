# Architecture Conventions Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Extract business logic into Action classes, update CLAUDE.md with architecture conventions, and create an `/architecture-review` skill.

**Architecture:** Systematic refactor — extract business logic from controllers into single-responsibility Action classes with `handle()` methods. Controllers become thin routing glue. Conventions codified in CLAUDE.md and enforced by a review skill.

**Tech Stack:** PHP 8.4, Laravel 12, Pest 4

---

### Task 1: Create the ApproveIntake Action

**Files:**
- Create: `app/Actions/ApproveIntake.php`
- Modify: `app/Http/Controllers/Staff/IntakeController.php:93-104`
- Create: `tests/Feature/Actions/ApproveIntakeTest.php`

**Step 1: Write the failing test**

Create `tests/Feature/Actions/ApproveIntakeTest.php`:

```php
<?php

use App\Actions\ApproveIntake;
use App\Enums\IntakeStatus;
use App\Jobs\SyncIntakeToMonday;
use App\Models\Intake;
use Illuminate\Support\Facades\Bus;

it('approves an intake and dispatches sync job', function (): void {
    Bus::fake();
    config()->set('services.monday.api_token', 'fake-token');

    $intake = Intake::factory()->submitted()->create();

    $action = app(ApproveIntake::class);
    $result = $action->handle($intake);

    expect($result->fresh()->status)->toBe(IntakeStatus::Approved);

    Bus::assertDispatched(SyncIntakeToMonday::class);
});

it('does not dispatch sync job when monday api token is missing', function (): void {
    Bus::fake();
    config()->set('services.monday.api_token', null);

    $intake = Intake::factory()->submitted()->create();

    $action = app(ApproveIntake::class);
    $action->handle($intake);

    Bus::assertNotDispatched(SyncIntakeToMonday::class);
});
```

**Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=ApproveIntakeTest`
Expected: FAIL — class `App\Actions\ApproveIntake` not found

**Step 3: Write the Action class**

Create `app/Actions/ApproveIntake.php`:

```php
<?php

namespace App\Actions;

use App\Enums\IntakeStatus;
use App\Jobs\SyncIntakeToMonday;
use App\Models\Intake;

class ApproveIntake
{
    public function handle(Intake $intake): Intake
    {
        $intake->update(['status' => IntakeStatus::Approved]);

        if (config('services.monday.api_token')) {
            SyncIntakeToMonday::dispatch($intake);
        }

        return $intake;
    }
}
```

**Step 4: Update the controller**

In `app/Http/Controllers/Staff/IntakeController.php`, replace the `approve` method:

```php
public function approve(Intake $intake, ApproveIntake $approveIntake): RedirectResponse
{
    abort_if($intake->status === IntakeStatus::Active, 422, 'Cannot approve an intake that is still in progress.');

    $approveIntake->handle($intake);

    return back();
}
```

Remove the `SyncIntakeToMonday` import from the controller if no longer used there.

**Step 5: Run tests to verify they pass**

Run: `php artisan test --compact --filter=ApproveIntakeTest`
Expected: PASS

Run: `php artisan test --compact --filter=IntakeControllerTest`
Expected: PASS (existing controller tests still work)

**Step 6: Run quality gate and commit**

Run: `composer check`
Expected: All checks pass

```bash
git add app/Actions/ApproveIntake.php tests/Feature/Actions/ApproveIntakeTest.php app/Http/Controllers/Staff/IntakeController.php
git commit -m "refactor: extract ApproveIntake action from staff controller"
```

---

### Task 2: Create the FlagFormResponse Action

**Files:**
- Create: `app/Actions/FlagFormResponse.php`
- Modify: `app/Http/Controllers/Staff/IntakeController.php:106-123`
- Create: `tests/Feature/Actions/FlagFormResponseTest.php`

**Step 1: Write the failing test**

Create `tests/Feature/Actions/FlagFormResponseTest.php`:

```php
<?php

use App\Actions\FlagFormResponse;
use App\Enums\IntakeStatus;
use App\Models\FormResponse;
use App\Models\Intake;
use App\Models\Patient;
use App\Models\User;
use App\Notifications\IntakeFlaggedNotification;
use Illuminate\Support\Facades\Notification;

it('flags a form response and notifies the patient', function (): void {
    Notification::fake();

    $user = User::factory()->create();
    $patient = Patient::factory()->create();
    $intake = Intake::factory()->submitted()->create(['patient_id' => $patient->id]);
    $formResponse = FormResponse::factory()->completed()->create(['intake_id' => $intake->id]);

    $action = app(FlagFormResponse::class);
    $intakeFlag = $action->handle(
        intake: $intake,
        formResponseId: $formResponse->id,
        userId: $user->id,
        reason: 'Missing date of birth',
    );

    expect($intake->fresh()->status)->toBe(IntakeStatus::Flagged);
    expect($intakeFlag->reason)->toBe('Missing date of birth');
    expect($intakeFlag->form_response_id)->toBe($formResponse->id);
    expect($intakeFlag->user_id)->toBe($user->id);

    Notification::assertSentTo($patient, IntakeFlaggedNotification::class);
});
```

**Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=FlagFormResponseTest`
Expected: FAIL — class `App\Actions\FlagFormResponse` not found

**Step 3: Write the Action class**

Create `app/Actions/FlagFormResponse.php`:

```php
<?php

namespace App\Actions;

use App\Enums\IntakeStatus;
use App\Models\Intake;
use App\Models\IntakeFlag;
use App\Notifications\IntakeFlaggedNotification;

class FlagFormResponse
{
    public function handle(Intake $intake, int $formResponseId, int $userId, string $reason): IntakeFlag
    {
        $intakeFlag = IntakeFlag::query()->create([
            'intake_id' => $intake->id,
            'form_response_id' => $formResponseId,
            'user_id' => $userId,
            'reason' => $reason,
        ]);

        $intake->update(['status' => IntakeStatus::Flagged]);

        $intake->load('patient');
        $intake->patient?->notify(new IntakeFlaggedNotification($intake, $intakeFlag));

        return $intakeFlag;
    }
}
```

**Step 4: Update the controller**

In `app/Http/Controllers/Staff/IntakeController.php`, replace the `flag` method:

```php
public function flag(Intake $intake, FlagFormRequest $flagFormRequest, FlagFormResponse $flagFormResponse): RedirectResponse
{
    abort_if($intake->status === IntakeStatus::Active, 422, 'Cannot flag an intake that is still in progress.');

    $flagFormResponse->handle(
        intake: $intake,
        formResponseId: $flagFormRequest->validated('form_response_id'),
        userId: auth()->id(),
        reason: $flagFormRequest->validated('reason'),
    );

    return back();
}
```

Remove unused imports from the controller (`IntakeFlag`, `IntakeFlaggedNotification`).

**Step 5: Run tests to verify they pass**

Run: `php artisan test --compact --filter=FlagFormResponseTest`
Expected: PASS

Run: `php artisan test --compact --filter=IntakeControllerTest`
Expected: PASS

**Step 6: Run quality gate and commit**

Run: `composer check`
Expected: All checks pass

```bash
git add app/Actions/FlagFormResponse.php tests/Feature/Actions/FlagFormResponseTest.php app/Http/Controllers/Staff/IntakeController.php
git commit -m "refactor: extract FlagFormResponse action from staff controller"
```

---

### Task 3: Create the ResolveIntakeFlag Action

**Files:**
- Create: `app/Actions/ResolveIntakeFlag.php`
- Modify: `app/Http/Controllers/Staff/IntakeController.php:125-136`
- Create: `tests/Feature/Actions/ResolveIntakeFlagTest.php`

**Step 1: Write the failing test**

Create `tests/Feature/Actions/ResolveIntakeFlagTest.php`:

```php
<?php

use App\Actions\ResolveIntakeFlag;
use App\Enums\IntakeStatus;
use App\Models\Intake;
use App\Models\IntakeFlag;

it('resolves a flag and transitions intake to under review when no unresolved flags remain', function (): void {
    $intake = Intake::factory()->flagged()->create();
    $flag = IntakeFlag::factory()->create(['intake_id' => $intake->id]);

    $action = app(ResolveIntakeFlag::class);
    $action->handle($intake, $flag);

    expect($flag->fresh()->resolved_at)->not->toBeNull();
    expect($intake->fresh()->status)->toBe(IntakeStatus::UnderReview);
});

it('keeps intake flagged when unresolved flags remain', function (): void {
    $intake = Intake::factory()->flagged()->create();
    $flag1 = IntakeFlag::factory()->create(['intake_id' => $intake->id]);
    $flag2 = IntakeFlag::factory()->create(['intake_id' => $intake->id]);

    $action = app(ResolveIntakeFlag::class);
    $action->handle($intake, $flag1);

    expect($flag1->fresh()->resolved_at)->not->toBeNull();
    expect($intake->fresh()->status)->toBe(IntakeStatus::Flagged);
});
```

**Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=ResolveIntakeFlagTest`
Expected: FAIL — class `App\Actions\ResolveIntakeFlag` not found

**Step 3: Write the Action class**

Create `app/Actions/ResolveIntakeFlag.php`:

```php
<?php

namespace App\Actions;

use App\Enums\IntakeStatus;
use App\Models\Intake;
use App\Models\IntakeFlag;

class ResolveIntakeFlag
{
    public function handle(Intake $intake, IntakeFlag $intakeFlag): void
    {
        $intakeFlag->update(['resolved_at' => now()]);

        $unresolvedCount = $intake->flags()->whereNull('resolved_at')->count();

        if ($unresolvedCount === 0) {
            $intake->update(['status' => IntakeStatus::UnderReview]);
        }
    }
}
```

**Step 4: Update the controller**

In `app/Http/Controllers/Staff/IntakeController.php`, replace the `resolveFlag` method:

```php
public function resolveFlag(Intake $intake, IntakeFlag $intakeFlag, ResolveIntakeFlag $resolveIntakeFlag): RedirectResponse
{
    $resolveIntakeFlag->handle($intake, $intakeFlag);

    return back();
}
```

**Step 5: Run tests to verify they pass**

Run: `php artisan test --compact --filter=ResolveIntakeFlagTest`
Expected: PASS

Run: `php artisan test --compact --filter=IntakeControllerTest`
Expected: PASS

**Step 6: Run quality gate and commit**

Run: `composer check`
Expected: All checks pass

```bash
git add app/Actions/ResolveIntakeFlag.php tests/Feature/Actions/ResolveIntakeFlagTest.php app/Http/Controllers/Staff/IntakeController.php
git commit -m "refactor: extract ResolveIntakeFlag action from staff controller"
```

---

### Task 4: Create the CompleteForm Action

**Files:**
- Create: `app/Actions/CompleteForm.php`
- Modify: `app/Http/Controllers/Intake/FormController.php:126-172` and private methods
- Create: `tests/Feature/Actions/CompleteFormTest.php`

**Step 1: Write the failing test**

Create `tests/Feature/Actions/CompleteFormTest.php`:

```php
<?php

use App\Actions\CompleteForm;
use App\Enums\IntakeStatus;
use App\Jobs\SyncIntakeToMonday;
use App\Models\FormResponse;
use App\Models\Intake;
use App\Models\User;
use App\Notifications\CorrectionSubmittedNotification;
use App\Services\FormSchemaService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Notification;

it('completes a form response and extracts child name', function (): void {
    $intake = Intake::factory()->create();

    $action = app(CompleteForm::class);
    $action->handle(
        intakeId: $intake->id,
        schemaKey: 'child_information',
        data: ['child_first_name' => 'Jane', 'child_last_name' => 'Doe'],
    );

    $formResponse = FormResponse::query()
        ->where('intake_id', $intake->id)
        ->where('schema_key', 'child_information')
        ->first();

    expect($formResponse->status)->toBe('completed');
    expect($formResponse->data)->toBe(['child_first_name' => 'Jane', 'child_last_name' => 'Doe']);
    expect($intake->fresh()->child_name)->toBe('Jane Doe');
});

it('submits intake when all forms are completed', function (): void {
    Bus::fake();
    config()->set('services.monday.api_token', 'fake-token');

    $intake = Intake::factory()->create();

    $formSchemaService = app(FormSchemaService::class);
    $allSchemas = $formSchemaService->all();

    // Complete all schemas except the last one via factory
    foreach (array_slice($allSchemas, 0, -1) as $schema) {
        FormResponse::factory()->completed()->create([
            'intake_id' => $intake->id,
            'schema_key' => $schema['key'],
        ]);
    }

    $lastSchema = end($allSchemas);

    $action = app(CompleteForm::class);
    $action->handle(
        intakeId: $intake->id,
        schemaKey: $lastSchema['key'],
        data: ['some_field' => 'value'],
    );

    expect($intake->fresh()->status)->toBe(IntakeStatus::Submitted);
    Bus::assertDispatched(SyncIntakeToMonday::class);
});

it('transitions flagged intake to correction submitted and notifies staff', function (): void {
    Notification::fake();

    $staffUser = User::factory()->create();
    $intake = Intake::factory()->flagged()->create();

    $action = app(CompleteForm::class);
    $action->handle(
        intakeId: $intake->id,
        schemaKey: 'demographics',
        data: ['first_name' => 'Updated'],
    );

    expect($intake->fresh()->status)->toBe(IntakeStatus::CorrectionSubmitted);
    Notification::assertSentTo($staffUser, CorrectionSubmittedNotification::class);
});
```

**Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=CompleteFormTest`
Expected: FAIL — class `App\Actions\CompleteForm` not found

**Step 3: Write the Action class**

Create `app/Actions/CompleteForm.php`:

```php
<?php

namespace App\Actions;

use App\Enums\IntakeStatus;
use App\Jobs\SyncIntakeToMonday;
use App\Models\FormResponse;
use App\Models\Intake;
use App\Models\User;
use App\Notifications\CorrectionSubmittedNotification;
use App\Services\FormSchemaService;
use Illuminate\Support\Facades\Notification;

class CompleteForm
{
    public function __construct(
        private FormSchemaService $formSchemaService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(int $intakeId, string $schemaKey, array $data): FormResponse
    {
        $formResponse = FormResponse::query()->updateOrCreate(
            ['intake_id' => $intakeId, 'schema_key' => $schemaKey],
            ['data' => $data, 'status' => 'completed'],
        );

        if ($schemaKey === 'child_information') {
            $this->extractChildName($intakeId, $data);
        }

        $intake = Intake::query()->findOrFail($intakeId);

        if ($intake->status === IntakeStatus::Flagged) {
            $intake->update(['status' => IntakeStatus::CorrectionSubmitted]);

            $staffUsers = User::all();
            Notification::send($staffUsers, new CorrectionSubmittedNotification($intake));
        }

        $this->checkAndDispatchSync($intakeId);

        return $formResponse;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function extractChildName(int $intakeId, array $data): void
    {
        /** @var string|null $firstName */
        $firstName = $data['child_first_name'] ?? null;
        /** @var string|null $lastName */
        $lastName = $data['child_last_name'] ?? null;

        $childName = trim(($firstName ?? '').' '.($lastName ?? ''));

        if ($childName !== '') {
            /** @var Intake $intake */
            $intake = Intake::query()->findOrFail($intakeId);
            $intake->update(['child_name' => $childName]);
        }
    }

    private function checkAndDispatchSync(int $intakeId): void
    {
        $totalSchemas = count($this->formSchemaService->all());
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
}
```

**Step 4: Update the controller**

In `app/Http/Controllers/Intake/FormController.php`, replace the `complete` method and remove private methods:

```php
public function complete(string $schemaKey, Request $request, FormSchemaService $formSchemaService, CompleteForm $completeForm): RedirectResponse
{
    $schema = $formSchemaService->get($schemaKey);

    if ($schema === null) {
        throw new NotFoundHttpException;
    }

    /** @var int $intakeId */
    $intakeId = $request->session()->get('intake_id');

    $rules = $formSchemaService->validationRules($schemaKey);

    /** @var array<string, list<string>> $prefixedRules */
    $prefixedRules = [];

    foreach ($rules as $fieldKey => $fieldRules) {
        $prefixedRules['data.'.$fieldKey] = $fieldRules;
    }

    $request->validate($prefixedRules);

    /** @var array<string, mixed> $validatedData */
    $validatedData = $request->input('data', []);

    $completeForm->handle(
        intakeId: $intakeId,
        schemaKey: $schemaKey,
        data: $validatedData,
    );

    return redirect()->route('intake.form.completed', $schemaKey);
}
```

Remove the `extractChildName` and `checkAndDispatchSync` private methods from the controller.

Remove unused imports: `IntakeStatus`, `SyncIntakeToMonday`, `User`, `CorrectionSubmittedNotification`, `Notification`, `FormResponse`.

Also update the `save` method to still call `extractChildName` — but since that logic is now in the Action, we need to keep a lightweight version in `save`. Actually, looking at the `save` method: it calls `extractChildName` for auto-save, which is partial data. The Action's `extractChildName` handles completion. Keep a simple inline version in `save`:

```php
if ($schemaKey === 'child_information') {
    /** @var string|null $firstName */
    $firstName = $incomingData['child_first_name'] ?? null;
    /** @var string|null $lastName */
    $lastName = $incomingData['child_last_name'] ?? null;
    $childName = trim(($firstName ?? '').' '.($lastName ?? ''));

    if ($childName !== '') {
        Intake::query()->findOrFail($intakeId)->update(['child_name' => $childName]);
    }
}
```

**Step 5: Run tests to verify they pass**

Run: `php artisan test --compact --filter=CompleteFormTest`
Expected: PASS

Run: `php artisan test --compact --filter=FormControllerTest`
Expected: PASS

**Step 6: Run quality gate and commit**

Run: `composer check`
Expected: All checks pass

```bash
git add app/Actions/CompleteForm.php tests/Feature/Actions/CompleteFormTest.php app/Http/Controllers/Intake/FormController.php
git commit -m "refactor: extract CompleteForm action from form controller"
```

---

### Task 5: Create the GenerateMagicLink Action

**Files:**
- Create: `app/Actions/GenerateMagicLink.php`
- Modify: `app/Services/MagicLinkService.php` (remove, replace with Action)
- Modify: any files that reference `MagicLinkService`
- Create: `tests/Feature/Actions/GenerateMagicLinkTest.php`

**Step 1: Find all references to MagicLinkService**

Run: `grep -r "MagicLinkService" app/ tests/ --include="*.php" -l`

This will show which files import or use the service. Update each one.

**Step 2: Write the failing test**

Create `tests/Feature/Actions/GenerateMagicLinkTest.php`:

```php
<?php

use App\Actions\GenerateMagicLink;
use App\Models\Patient;
use App\Notifications\MagicLinkNotification;
use Illuminate\Support\Facades\Notification;

it('generates a magic link for an existing patient', function (): void {
    Notification::fake();

    $patient = Patient::factory()->create();

    $action = app(GenerateMagicLink::class);
    $action->handle($patient);

    expect($patient->fresh()->magic_link_token)->not->toBeNull();
    expect($patient->fresh()->magic_link_expires_at)->not->toBeNull();

    Notification::assertSentTo($patient, MagicLinkNotification::class);
});

it('creates a patient and sends magic link when email does not exist', function (): void {
    Notification::fake();

    $action = app(GenerateMagicLink::class);
    $action->handleForEmail('newparent@example.com');

    $patient = Patient::query()->whereBlindIndex('email', 'newparent@example.com')->first();

    expect($patient)->not->toBeNull();
    expect($patient->magic_link_token)->not->toBeNull();

    Notification::assertSentTo($patient, MagicLinkNotification::class);
});

it('sends magic link to existing patient when email exists', function (): void {
    Notification::fake();

    $patient = Patient::factory()->create(['email' => 'existing@example.com']);

    $action = app(GenerateMagicLink::class);
    $action->handleForEmail('existing@example.com');

    Notification::assertSentTo($patient, MagicLinkNotification::class);
});
```

**Step 3: Run test to verify it fails**

Run: `php artisan test --compact --filter=GenerateMagicLinkTest`
Expected: FAIL — class `App\Actions\GenerateMagicLink` not found

**Step 4: Create the Action and remove the Service**

Create `app/Actions/GenerateMagicLink.php`:

```php
<?php

namespace App\Actions;

use App\Models\Patient;
use App\Notifications\MagicLinkNotification;
use Illuminate\Support\Str;

class GenerateMagicLink
{
    public function handle(Patient $patient): void
    {
        $token = Str::random(64);

        $patient->update([
            'magic_link_token' => $token,
            'magic_link_expires_at' => now()->addMinutes(30),
        ]);

        $patient->notify(new MagicLinkNotification($token));
    }

    public function handleForEmail(string $email): void
    {
        $patient = Patient::query()->whereBlindIndex('email', $email)->first();

        if (! $patient instanceof Patient) {
            $patient = Patient::query()->create(['email' => $email]);
        }

        $this->handle($patient);
    }
}
```

Delete `app/Services/MagicLinkService.php`.

Update all references from `MagicLinkService` to `GenerateMagicLink` (found in step 1). Replace `$service->send(...)` with `$action->handle(...)` and `$service->sendToEmail(...)` with `$action->handleForEmail(...)`.

**Step 5: Run tests to verify they pass**

Run: `php artisan test --compact --filter=GenerateMagicLinkTest`
Expected: PASS

Run: `php artisan test --compact --filter=MagicLink`
Expected: PASS (existing tests updated)

**Step 6: Run quality gate and commit**

Run: `composer check`
Expected: All checks pass

```bash
git add app/Actions/GenerateMagicLink.php tests/Feature/Actions/GenerateMagicLinkTest.php
git add -u  # pick up deleted service and modified references
git commit -m "refactor: replace MagicLinkService with GenerateMagicLink action"
```

---

### Task 6: Update CLAUDE.md with Architecture Conventions

**Files:**
- Modify: `CLAUDE.md`

**Step 1: Add conventions after the closing `</laravel-boost-guidelines>` tag**

Append the following after the `# Project Rules` section in CLAUDE.md:

```markdown
## Architecture Conventions

### Actions (`app/Actions/`)
- One class per business operation, named as `VerbNoun` (e.g., `ApproveIntake`, `FlagFormResponse`)
- Constructor receives dependencies via injection; single public `handle()` method receives runtime data
- Create an Action when: business logic beyond CRUD, multiple entry points possible, or has side effects
- Do NOT create an Action for: simple persistence (controller + Form Request), pure queries, async work (use a Job)
- A Job can call an Action internally if the Job needs the same business logic

### Enums (`app/Enums/`)
- Always string-backed with TitleCase keys
- `label(): string` method for human-readable display
- Group-query helpers live on the enum (e.g., `staffActionable()`)
- If you write `=== 'some_status'` anywhere, it should be an enum case

### Traits (`app/Concerns/`)
- Reusable capabilities for unrelated classes; named `HasX` or `VerbsNoun`
- Must be used by 2+ unrelated classes — inline if only one consumer
- Traits must not depend on other traits
- If classes share a parent, use a base class instead

### Value Objects (`app/ValueObjects/`)
- Immutable (`readonly` properties), no identity, named as nouns
- Use when a concept has structure + validation but no database table
- Convention only — introduce when a clear need arises, not preemptively

### Skills Activation (Architecture)
- `architecture-review` — Audits code against architecture conventions. Use after completing a feature or before committing to verify patterns are followed.
```

**Step 2: Verify CLAUDE.md is well-formed**

Read the file and confirm the additions are properly placed and don't duplicate existing content.

**Step 3: Commit**

```bash
git add CLAUDE.md
git commit -m "docs: add architecture conventions to CLAUDE.md"
```

---

### Task 7: Create the `/architecture-review` Skill

**Files:**
- Create: `.claude/skills/architecture-review/SKILL.md`

**Step 1: Create the skill directory**

Run: `mkdir -p .claude/skills/architecture-review`

**Step 2: Write the skill file**

Create `.claude/skills/architecture-review/SKILL.md`:

```markdown
---
name: architecture-review
description: Audits code against project architecture conventions. Use after completing a feature or before committing to verify patterns are followed.
---

# Architecture Review

Audit the codebase against the project's architecture conventions (Actions, Enums, Traits, Value Objects).

## When to Use

- After completing a feature implementation
- Before committing to verify pattern compliance
- When reviewing code for architectural consistency
- When user invokes `/architecture-review`

## Process

### 1. Scan Controllers for Business Logic

Read all controller files in `app/Http/Controllers/`.

Flag any controller method that:
- Dispatches jobs directly (should be in an Action)
- Sends notifications directly (should be in an Action)
- Has more than one model write operation (should be in an Action)
- Contains conditional business logic beyond simple guards/aborts (should be in an Action)

**Acceptable in controllers:** validation via Form Requests, single model create/update for CRUD, abort_if guards, returning responses.

### 2. Verify Action Conventions

Read all files in `app/Actions/`.

For each Action, verify:
- [ ] Named as `VerbNoun` (not `NounVerber` or `NounAction`)
- [ ] Has a single public `handle()` method
- [ ] Dependencies injected via constructor (not passed to `handle()`)
- [ ] Runtime data passed to `handle()` (not injected via constructor)
- [ ] Return type is declared
- [ ] No direct HTTP concerns (no `request()`, no `redirect()`, no `back()`)

### 3. Verify Enum Conventions

Read all files in `app/Enums/`.

For each Enum, verify:
- [ ] String-backed (`: string`)
- [ ] TitleCase keys
- [ ] Has a `label(): string` method
- [ ] Group-query helpers are defined on the enum, not scattered in controllers

Then search the codebase for hardcoded status strings:
- `grep -r "=== '" app/ --include="*.php"` — flag any that match enum values
- `grep -r "== '" app/ --include="*.php"` — same check

### 4. Verify Trait Conventions

Read all files in `app/Concerns/`.

For each Trait, verify:
- [ ] Named as `HasX` or `VerbsNoun`
- [ ] Used by 2+ classes (search for `use TraitName` across `app/`)
- [ ] Does not import/use other traits from `app/Concerns/`

### 5. Check for Value Object Candidates

Search for patterns that suggest a Value Object is needed:
- Same group of 3+ related parameters passed together in multiple methods
- Repeated validation of the same structure (phone format, address components)

This is advisory only — flag as suggestions, not violations.

### 6. Report

Present findings grouped by category:

```
## Architecture Review Results

### Actions
- [VIOLATION] app/Http/Controllers/Foo.php:42 — dispatches job directly, extract to Action
- [OK] All Actions follow conventions

### Enums
- [VIOLATION] app/Http/Controllers/Bar.php:15 — hardcoded string 'approved' should use IntakeStatus::Approved
- [OK] All Enums follow conventions

### Traits
- [WARNING] app/Concerns/SomeTrait.php — only used by one class, consider inlining
- [OK] All Traits follow conventions

### Value Object Candidates
- [SUGGESTION] PhoneNumber appears in 3 methods with formatting logic — consider a Value Object

### Summary
N violations, N warnings, N suggestions
```
```

**Step 3: Commit**

```bash
git add .claude/skills/architecture-review/SKILL.md
git commit -m "feat: add /architecture-review skill for convention auditing"
```

---

### Task 8: Run Full Test Suite and Final Quality Gate

**Step 1: Run the complete quality gate**

Run: `composer check`
Expected: All checks pass (Rector, Pint, PHPStan, Tests)

**Step 2: Run the full test suite explicitly**

Run: `php artisan test --compact`
Expected: All tests pass

**Step 3: Run the new `/architecture-review` skill**

Invoke `/architecture-review` to verify the refactored codebase passes its own audit.

Expected: Zero violations (the refactor should have addressed all patterns).
