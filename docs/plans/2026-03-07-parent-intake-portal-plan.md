# Parent Intake Portal Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Build a schema-driven parent intake portal for JumpStart Autism Collective that reduces friction for families completing early paperwork.

**Architecture:** Schema-driven forms defined as PHP config files, rendered dynamically by a Svelte form engine. Parents authenticate via magic links, complete sections in any order with auto-save, and data syncs to Monday.com on completion. Staff have a lightweight read-only dashboard.

**Tech Stack:** Laravel 12, PHP 8.4, Svelte 5, Inertia.js v2, Tailwind CSS v4, Pest 4, Wayfinder, Monday.com API

---

## Phase 1: Patient Model & Magic Link Auth

### Task 1: Create Patient Model

**Files:**
- Create: `app/Models/Patient.php` (via artisan)
- Create: `database/migrations/xxxx_create_patients_table.php` (via artisan)
- Create: `database/factories/PatientFactory.php` (via artisan)
- Create: `database/seeders/PatientSeeder.php` (via artisan)
- Test: `tests/Feature/Models/PatientTest.php`

**Step 1: Generate model with migration, factory, seeder**

Run: `php artisan make:model Patient -mfs --no-interaction`

**Step 2: Write the migration**

```php
Schema::create('patients', function (Blueprint $blueprint): void {
    $blueprint->id();
    $blueprint->string('email')->unique();
    $blueprint->string('name')->nullable();
    $blueprint->string('preferred_locale', 5)->default('en');
    $blueprint->string('magic_link_token', 64)->nullable()->unique();
    $blueprint->timestamp('magic_link_expires_at')->nullable();
    $blueprint->string('sync_status')->default('pending'); // pending, syncing, synced, failed
    $blueprint->timestamp('synced_at')->nullable();
    $blueprint->timestamps();
});
```

**Step 3: Write the Patient model**

```php
class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'name',
        'preferred_locale',
        'magic_link_token',
        'magic_link_expires_at',
        'sync_status',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'magic_link_expires_at' => 'datetime',
            'synced_at' => 'datetime',
        ];
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

    public function hasValidMagicLink(): bool
    {
        return $this->magic_link_token !== null
            && $this->magic_link_expires_at !== null
            && $this->magic_link_expires_at->isFuture();
    }
}
```

**Step 4: Write the factory**

```php
public function definition(): array
{
    return [
        'email' => fake()->unique()->safeEmail(),
        'name' => fake()->name(),
        'preferred_locale' => 'en',
        'magic_link_token' => null,
        'magic_link_expires_at' => null,
        'sync_status' => 'pending',
        'synced_at' => null,
    ];
}

public function withMagicLink(): static
{
    return $this->state(fn (): array => [
        'magic_link_token' => Str::random(64),
        'magic_link_expires_at' => now()->addMinutes(30),
    ]);
}

public function withExpiredMagicLink(): static
{
    return $this->state(fn (): array => [
        'magic_link_token' => Str::random(64),
        'magic_link_expires_at' => now()->subMinute(),
    ]);
}

public function spanishSpeaking(): static
{
    return $this->state(fn (): array => [
        'preferred_locale' => 'es',
    ]);
}
```

**Step 5: Write the failing test**

```php
// tests/Feature/Models/PatientTest.php
use App\Models\Patient;

test('patient can be created with factory', function (): void {
    $patient = Patient::factory()->create();

    expect($patient)->toBeInstanceOf(Patient::class)
        ->and($patient->email)->not->toBeEmpty()
        ->and($patient->preferred_locale)->toBe('en')
        ->and($patient->sync_status)->toBe('pending');
});

test('patient with magic link factory state works', function (): void {
    $patient = Patient::factory()->withMagicLink()->create();

    expect($patient->magic_link_token)->not->toBeNull()
        ->and($patient->magic_link_expires_at)->not->toBeNull()
        ->and($patient->hasValidMagicLink())->toBeTrue();
});

test('expired magic link is not valid', function (): void {
    $patient = Patient::factory()->withExpiredMagicLink()->create();

    expect($patient->hasValidMagicLink())->toBeFalse();
});
```

**Step 6: Run migration and tests**

Run: `php artisan migrate && php artisan test --compact --filter=PatientTest`
Expected: All 3 tests PASS

**Step 7: Run composer check**

Run: `composer check`
Expected: PASS (Rector, Pint, PHPStan, tests)

**Step 8: Commit**

```bash
git add -A && git commit -m "Add Patient model with migration, factory, and tests"
```

---

### Task 2: Magic Link Generation Service

**Files:**
- Create: `app/Services/MagicLinkService.php`
- Create: `app/Notifications/MagicLinkNotification.php` (via artisan)
- Test: `tests/Feature/Services/MagicLinkServiceTest.php`

**Step 1: Write the failing test**

```php
// tests/Feature/Services/MagicLinkServiceTest.php
use App\Models\Patient;
use App\Notifications\MagicLinkNotification;
use App\Services\MagicLinkService;
use Illuminate\Support\Facades\Notification;

test('magic link service generates token and sends notification', function (): void {
    Notification::fake();

    $patient = Patient::factory()->create();
    $service = app(MagicLinkService::class);

    $service->send($patient);

    $patient->refresh();

    expect($patient->magic_link_token)->not->toBeNull()
        ->and($patient->magic_link_token)->toHaveLength(64)
        ->and($patient->magic_link_expires_at)->not->toBeNull()
        ->and($patient->magic_link_expires_at->diffInMinutes(now()))->toBeBetween(29, 31);

    Notification::assertSentTo($patient, MagicLinkNotification::class);
});

test('magic link service creates new patient if email not found', function (): void {
    Notification::fake();

    $service = app(MagicLinkService::class);
    $service->sendToEmail('new@example.com');

    $patient = Patient::query()->where('email', 'new@example.com')->first();

    expect($patient)->not->toBeNull()
        ->and($patient->hasValidMagicLink())->toBeTrue();

    Notification::assertSentTo($patient, MagicLinkNotification::class);
});

test('magic link service reuses existing patient', function (): void {
    Notification::fake();

    $existing = Patient::factory()->create(['email' => 'existing@example.com']);
    $service = app(MagicLinkService::class);
    $service->sendToEmail('existing@example.com');

    expect(Patient::query()->where('email', 'existing@example.com')->count())->toBe(1);

    $existing->refresh();
    expect($existing->hasValidMagicLink())->toBeTrue();
});
```

**Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=MagicLinkServiceTest`
Expected: FAIL

**Step 3: Generate notification**

Run: `php artisan make:notification MagicLinkNotification --no-interaction`

**Step 4: Implement MagicLinkNotification**

```php
// app/Notifications/MagicLinkNotification.php
class MagicLinkNotification extends Notification
{
    public function __construct(private readonly string $token) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = url('/intake/verify/' . $this->token);

        return (new MailMessage)
            ->subject('Continue Your JumpStart Intake')
            ->greeting('Hello!')
            ->line('Click the button below to continue your intake forms.')
            ->action('Continue Your Intake', $url)
            ->line('This link will expire in 30 minutes.')
            ->line('If you did not request this, no action is needed.');
    }
}
```

**Step 5: Implement MagicLinkService**

```php
// app/Services/MagicLinkService.php
class MagicLinkService
{
    public function send(Patient $patient): void
    {
        $patient->update([
            'magic_link_token' => Str::random(64),
            'magic_link_expires_at' => now()->addMinutes(30),
        ]);

        $patient->notify(new MagicLinkNotification($patient->magic_link_token));
    }

    public function sendToEmail(string $email): void
    {
        $patient = Patient::query()->firstOrCreate(
            ['email' => $email],
        );

        $this->send($patient);
    }
}
```

Note: Patient model must `use Notifiable;` trait.

**Step 6: Run tests**

Run: `php artisan test --compact --filter=MagicLinkServiceTest`
Expected: PASS

**Step 7: Run composer check, then commit**

Run: `composer check`

```bash
git add -A && git commit -m "Add MagicLinkService and MagicLinkNotification"
```

---

### Task 3: Magic Link Verification & Session

**Files:**
- Create: `app/Http/Controllers/Intake/MagicLinkController.php`
- Create: `app/Http/Requests/Intake/RequestMagicLinkRequest.php`
- Create: `app/Http/Middleware/AuthenticatePatient.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Intake/MagicLinkTest.php`

**Step 1: Write the failing test**

```php
// tests/Feature/Intake/MagicLinkTest.php
use App\Models\Patient;

test('intake landing page can be rendered', function (): void {
    $this->get(route('intake.landing'))
        ->assertOk();
});

test('magic link can be requested with valid email', function (): void {
    $this->post(route('intake.request-link'), ['email' => 'parent@example.com'])
        ->assertRedirect();

    $this->assertDatabaseHas('patients', ['email' => 'parent@example.com']);
});

test('magic link request is rate limited', function (): void {
    for ($i = 0; $i < 3; $i++) {
        $this->post(route('intake.request-link'), ['email' => 'parent@example.com']);
    }

    $this->post(route('intake.request-link'), ['email' => 'parent@example.com'])
        ->assertTooManyRequests();
});

test('valid magic link creates session and redirects to dashboard', function (): void {
    $patient = Patient::factory()->withMagicLink()->create();

    $this->get(route('intake.verify', ['token' => $patient->magic_link_token]))
        ->assertRedirect(route('intake.dashboard'));

    // Token should be consumed (single-use)
    $patient->refresh();
    expect($patient->magic_link_token)->toBeNull();
});

test('expired magic link shows error', function (): void {
    $patient = Patient::factory()->withExpiredMagicLink()->create();

    $this->get(route('intake.verify', ['token' => $patient->magic_link_token]))
        ->assertRedirect(route('intake.landing'));
});

test('invalid magic link shows error', function (): void {
    $this->get(route('intake.verify', ['token' => 'invalid-token']))
        ->assertRedirect(route('intake.landing'));
});

test('patient dashboard requires patient session', function (): void {
    $this->get(route('intake.dashboard'))
        ->assertRedirect(route('intake.landing'));
});
```

**Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=MagicLinkTest`
Expected: FAIL

**Step 3: Create form request**

Run: `php artisan make:request Intake/RequestMagicLinkRequest --no-interaction`

```php
public function authorize(): bool { return true; }

public function rules(): array
{
    return [
        'email' => ['required', 'email', 'max:255'],
    ];
}
```

**Step 4: Create AuthenticatePatient middleware**

```php
// app/Http/Middleware/AuthenticatePatient.php
class AuthenticatePatient
{
    public function handle(Request $request, Closure $next): Response
    {
        $patientId = $request->session()->get('patient_id');

        if (! $patientId || ! Patient::find($patientId)) {
            return redirect()->route('intake.landing');
        }

        return $next($request);
    }
}
```

**Step 5: Create MagicLinkController**

```php
// app/Http/Controllers/Intake/MagicLinkController.php
class MagicLinkController extends Controller
{
    public function landing(): Response
    {
        return Inertia::render('intake/Landing');
    }

    public function requestLink(RequestMagicLinkRequest $request, MagicLinkService $service): RedirectResponse
    {
        $service->sendToEmail($request->validated('email'));

        return back()->with('status', 'Check your email for a magic link.');
    }

    public function verify(string $token, Request $request): RedirectResponse
    {
        $patient = Patient::query()
            ->where('magic_link_token', $token)
            ->first();

        if (! $patient || ! $patient->hasValidMagicLink()) {
            return redirect()->route('intake.landing')
                ->with('error', 'This link is invalid or has expired.');
        }

        // Consume token (single-use)
        $patient->update([
            'magic_link_token' => null,
            'magic_link_expires_at' => null,
        ]);

        // Create patient session
        $request->session()->put('patient_id', $patient->id);

        return redirect()->route('intake.dashboard');
    }
}
```

**Step 6: Add routes**

Add to `routes/web.php`:

```php
// Intake routes (parent-facing)
Route::prefix('intake')->name('intake.')->group(function (): void {
    Route::get('/', [MagicLinkController::class, 'landing'])->name('landing');
    Route::post('/request-link', [MagicLinkController::class, 'requestLink'])
        ->middleware('throttle:3,1')
        ->name('request-link');
    Route::get('/verify/{token}', [MagicLinkController::class, 'verify'])->name('verify');
});
```

**Step 7: Create a minimal Landing.svelte placeholder**

Create `resources/js/pages/intake/Landing.svelte` with a basic template so the Inertia render doesn't fail.

**Step 8: Register middleware, add dashboard route placeholder**

Register `AuthenticatePatient` middleware alias in `bootstrap/app.php`. Add the dashboard route inside a middleware-protected group:

```php
Route::middleware(AuthenticatePatient::class)->prefix('intake')->name('intake.')->group(function (): void {
    Route::get('/dashboard', fn () => Inertia::render('intake/Dashboard'))->name('dashboard');
});
```

**Step 9: Run tests**

Run: `php artisan test --compact --filter=MagicLinkTest`
Expected: PASS

**Step 10: Run composer check, then commit**

Run: `composer check`

```bash
git add -A && git commit -m "Add magic link authentication for patient intake"
```

---

## Phase 2: Form Schema System

### Task 4: Form Schema Loader Service

**Files:**
- Create: `app/Services/FormSchemaService.php`
- Create: `config/forms/demographics.php` (sample schema)
- Test: `tests/Feature/Services/FormSchemaServiceTest.php`

**Step 1: Write the failing test**

```php
// tests/Feature/Services/FormSchemaServiceTest.php
use App\Services\FormSchemaService;

test('form schema service loads all schemas', function (): void {
    $service = app(FormSchemaService::class);
    $schemas = $service->all();

    expect($schemas)->toBeArray()
        ->and($schemas)->not->toBeEmpty();
});

test('form schema service loads a schema by key', function (): void {
    $service = app(FormSchemaService::class);
    $schema = $service->get('demographics');

    expect($schema)->not->toBeNull()
        ->and($schema['key'])->toBe('demographics')
        ->and($schema['title'])->toBeArray()
        ->and($schema['title']['en'])->toBeString()
        ->and($schema['sections'])->toBeArray();
});

test('form schema service returns null for unknown key', function (): void {
    $service = app(FormSchemaService::class);

    expect($service->get('nonexistent'))->toBeNull();
});

test('form schema service extracts validation rules', function (): void {
    $service = app(FormSchemaService::class);
    $rules = $service->validationRules('demographics');

    expect($rules)->toBeArray()
        ->and($rules)->toHaveKey('first_name');
});

test('form schema service extracts conditional validation rules', function (): void {
    $service = app(FormSchemaService::class);
    $rules = $service->validationRules('demographics');

    // Fields with conditions should have conditional validation
    expect($rules)->toHaveKey('secondary_guardian_name');
});

test('schemas are returned ordered', function (): void {
    $service = app(FormSchemaService::class);
    $schemas = $service->all();

    $orders = array_column($schemas, 'order');
    $sorted = $orders;
    sort($sorted);

    expect($orders)->toBe($sorted);
});
```

**Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=FormSchemaServiceTest`
Expected: FAIL

**Step 3: Create the demographics sample schema**

Create `config/forms/demographics.php` — a full example schema with sections, fields, conditions, validation rules, monday_field mappings, and i18n labels. Include at least one conditional field (e.g., secondary guardian) to exercise the conditional logic.

**Step 4: Implement FormSchemaService**

```php
// app/Services/FormSchemaService.php
class FormSchemaService
{
    /** @var array<string, array<string, mixed>>|null */
    private ?array $schemas = null;

    /** @return list<array<string, mixed>> */
    public function all(): array
    {
        $schemas = array_values($this->loadSchemas());
        usort($schemas, fn (array $a, array $b): int => $a['order'] <=> $b['order']);
        return $schemas;
    }

    /** @return array<string, mixed>|null */
    public function get(string $key): ?array
    {
        return $this->loadSchemas()[$key] ?? null;
    }

    /** @return array<string, array<int, string>> */
    public function validationRules(string $key): array
    {
        $schema = $this->get($key);
        if (! $schema) { return []; }

        $rules = [];
        foreach ($schema['sections'] as $section) {
            foreach ($section['fields'] as $field) {
                if (isset($field['validation'])) {
                    $rules[$field['key']] = $field['validation'];
                }
            }
        }
        return $rules;
    }

    /** @return array<string, array<string, mixed>> */
    private function loadSchemas(): array
    {
        if ($this->schemas !== null) { return $this->schemas; }

        $this->schemas = [];
        $path = config_path('forms');

        if (! is_dir($path)) { return $this->schemas; }

        foreach (glob($path . '/*.php') as $file) {
            $schema = require $file;
            $this->schemas[$schema['key']] = $schema;
        }

        return $this->schemas;
    }
}
```

**Step 5: Run tests**

Run: `php artisan test --compact --filter=FormSchemaServiceTest`
Expected: PASS

**Step 6: Run composer check, then commit**

Run: `composer check`

```bash
git add -A && git commit -m "Add FormSchemaService and demographics sample schema"
```

---

### Task 5: FormResponse Model

**Files:**
- Create: `app/Models/FormResponse.php` (via artisan)
- Create: `database/migrations/xxxx_create_form_responses_table.php` (via artisan)
- Create: `database/factories/FormResponseFactory.php` (via artisan)
- Test: `tests/Feature/Models/FormResponseTest.php`

**Step 1: Generate model**

Run: `php artisan make:model FormResponse -mf --no-interaction`

**Step 2: Write the migration**

```php
Schema::create('form_responses', function (Blueprint $blueprint): void {
    $blueprint->id();
    $blueprint->foreignId('patient_id')->constrained()->cascadeOnDelete();
    $blueprint->string('schema_key'); // e.g., 'demographics'
    $blueprint->text('data'); // encrypted JSON
    $blueprint->string('status')->default('in_progress'); // in_progress, completed
    $blueprint->timestamps();

    $blueprint->unique(['patient_id', 'schema_key']);
});
```

**Step 3: Write the model**

```php
class FormResponse extends Model
{
    use HasFactory;

    protected $fillable = ['patient_id', 'schema_key', 'data', 'status'];

    protected function casts(): array
    {
        return [
            'data' => 'encrypted:array',
        ];
    }

    /** @return BelongsTo<Patient, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
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
}
```

**Step 4: Write the factory**

```php
public function definition(): array
{
    return [
        'patient_id' => Patient::factory(),
        'schema_key' => 'demographics',
        'data' => ['first_name' => fake()->firstName()],
        'status' => 'in_progress',
    ];
}

public function completed(): static
{
    return $this->state(fn (): array => ['status' => 'completed']);
}
```

**Step 5: Write tests**

```php
use App\Models\FormResponse;
use App\Models\Patient;

test('form response belongs to a patient', function (): void {
    $response = FormResponse::factory()->create();

    expect($response->patient)->toBeInstanceOf(Patient::class);
});

test('form response data is encrypted', function (): void {
    $response = FormResponse::factory()->create([
        'data' => ['first_name' => 'Jane', 'last_name' => 'Doe'],
    ]);

    // Reload from DB — data should decrypt transparently
    $response->refresh();
    expect($response->data)->toBe(['first_name' => 'Jane', 'last_name' => 'Doe']);

    // Raw DB value should NOT be readable JSON
    $raw = DB::table('form_responses')->where('id', $response->id)->value('data');
    expect($raw)->not->toContain('Jane');
});

test('patient can only have one response per schema key', function (): void {
    $patient = Patient::factory()->create();
    FormResponse::factory()->create(['patient_id' => $patient->id, 'schema_key' => 'demographics']);

    expect(fn () => FormResponse::factory()->create([
        'patient_id' => $patient->id,
        'schema_key' => 'demographics',
    ]))->toThrow(Exception::class);
});

test('form response can be marked completed', function (): void {
    $response = FormResponse::factory()->completed()->create();

    expect($response->isCompleted())->toBeTrue();
});
```

**Step 6: Run migration and tests**

Run: `php artisan migrate && php artisan test --compact --filter=FormResponseTest`
Expected: PASS

**Step 7: Run composer check, then commit**

Run: `composer check`

```bash
git add -A && git commit -m "Add FormResponse model with encrypted data storage"
```

---

### Task 6: Document & Signature Models

**Files:**
- Create: `app/Models/Document.php`, `app/Models/Signature.php` (via artisan)
- Create: migrations, factories (via artisan)
- Test: `tests/Feature/Models/DocumentTest.php`, `tests/Feature/Models/SignatureTest.php`

**Step 1: Generate models**

Run:
```bash
php artisan make:model Document -mf --no-interaction
php artisan make:model Signature -mf --no-interaction
```

**Step 2: Write Document migration**

```php
Schema::create('documents', function (Blueprint $blueprint): void {
    $blueprint->id();
    $blueprint->foreignId('patient_id')->constrained()->cascadeOnDelete();
    $blueprint->foreignId('form_response_id')->constrained()->cascadeOnDelete();
    $blueprint->string('field_key'); // which schema field this belongs to
    $blueprint->string('file_path');
    $blueprint->string('original_filename');
    $blueprint->string('mime_type');
    $blueprint->unsignedBigInteger('file_size');
    $blueprint->timestamps();
});
```

**Step 3: Write Signature migration**

```php
Schema::create('signatures', function (Blueprint $blueprint): void {
    $blueprint->id();
    $blueprint->foreignId('patient_id')->constrained()->cascadeOnDelete();
    $blueprint->foreignId('form_response_id')->constrained()->cascadeOnDelete();
    $blueprint->string('field_key');
    $blueprint->string('image_path');
    $blueprint->timestamps();
});
```

**Step 4: Write models with relationships, factories, and tests**

Follow the same pattern as Tasks 1 and 5 — model with fillable/casts/relationships, factory with sensible defaults, feature tests verifying relationships and creation.

**Step 5: Run migration and tests**

Run: `php artisan migrate && php artisan test --compact --filter="DocumentTest|SignatureTest"`
Expected: PASS

**Step 6: Run composer check, then commit**

Run: `composer check`

```bash
git add -A && git commit -m "Add Document and Signature models"
```

---

## Phase 3: Form Engine (Backend)

### Task 7: Intake Dashboard Controller

**Files:**
- Create: `app/Http/Controllers/Intake/DashboardController.php`
- Modify: `routes/web.php` (update dashboard route)
- Test: `tests/Feature/Intake/DashboardTest.php`

**Step 1: Write the failing test**

```php
use App\Models\FormResponse;
use App\Models\Patient;

test('dashboard shows all form sections with status', function (): void {
    $patient = Patient::factory()->create();
    session(['patient_id' => $patient->id]);

    $response = $this->get(route('intake.dashboard'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('intake/Dashboard')
            ->has('forms')
            ->has('progress')
        );
});

test('dashboard reflects completed sections', function (): void {
    $patient = Patient::factory()->create();
    FormResponse::factory()->completed()->create([
        'patient_id' => $patient->id,
        'schema_key' => 'demographics',
    ]);
    session(['patient_id' => $patient->id]);

    $response = $this->get(route('intake.dashboard'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('intake/Dashboard')
            ->where('progress.completed', 1)
        );
});
```

**Step 2: Implement DashboardController**

The controller should:
- Load the patient from session (via middleware)
- Load all schemas via `FormSchemaService`
- Load the patient's form responses
- Map each schema to a status (not_started / in_progress / completed)
- Calculate overall progress (completed count / total count)
- Pass to Inertia as `forms` and `progress`

**Step 3: Run tests, composer check, commit**

```bash
git add -A && git commit -m "Add intake dashboard controller with progress tracking"
```

---

### Task 8: Form Display & Auto-Save Endpoints

**Files:**
- Create: `app/Http/Controllers/Intake/FormController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Intake/FormControllerTest.php`

**Step 1: Write failing tests**

```php
test('form show returns schema and saved data', function (): void {
    $patient = Patient::factory()->create();
    session(['patient_id' => $patient->id]);

    $response = $this->get(route('intake.form.show', 'demographics'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('intake/Form')
            ->has('schema')
            ->has('savedData')
        );
});

test('form show returns 404 for unknown schema', function (): void {
    $patient = Patient::factory()->create();
    session(['patient_id' => $patient->id]);

    $this->get(route('intake.form.show', 'nonexistent'))
        ->assertNotFound();
});

test('auto-save stores partial data', function (): void {
    $patient = Patient::factory()->create();
    session(['patient_id' => $patient->id]);

    $this->put(route('intake.form.save', 'demographics'), [
        'data' => ['first_name' => 'Jane'],
    ])->assertOk();

    expect($patient->formResponses()->where('schema_key', 'demographics')->first())
        ->not->toBeNull()
        ->data->first_name->toBe('Jane');
});

test('auto-save merges with existing data', function (): void {
    $patient = Patient::factory()->create();
    FormResponse::factory()->create([
        'patient_id' => $patient->id,
        'schema_key' => 'demographics',
        'data' => ['first_name' => 'Jane'],
    ]);
    session(['patient_id' => $patient->id]);

    $this->put(route('intake.form.save', 'demographics'), [
        'data' => ['last_name' => 'Doe'],
    ])->assertOk();

    $response = $patient->formResponses()->where('schema_key', 'demographics')->first();
    expect($response->data)->toBe(['first_name' => 'Jane', 'last_name' => 'Doe']);
});

test('mark complete validates all required fields', function (): void {
    $patient = Patient::factory()->create();
    session(['patient_id' => $patient->id]);

    $this->post(route('intake.form.complete', 'demographics'), ['data' => []])
        ->assertSessionHasErrors();
});

test('mark complete succeeds with valid data', function (): void {
    $patient = Patient::factory()->create();
    session(['patient_id' => $patient->id]);

    // Provide all required fields from demographics schema
    $this->post(route('intake.form.complete', 'demographics'), [
        'data' => [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            // ... other required fields from demographics schema
        ],
    ])->assertRedirect(route('intake.dashboard'));

    $response = $patient->formResponses()->where('schema_key', 'demographics')->first();
    expect($response->isCompleted())->toBeTrue();
});
```

**Step 2: Implement FormController**

Three actions:
- `show(string $schemaKey)` — loads schema + saved data, renders `intake/Form`
- `save(string $schemaKey, Request $request)` — auto-save (upsert, merge data), no full validation
- `complete(string $schemaKey, Request $request)` — full validation using `FormSchemaService::validationRules()`, marks status as completed

The `save` action uses `updateOrCreate` on `FormResponse` with `patient_id` + `schema_key`, merging the incoming data with existing data.

The `complete` action dynamically builds validation from the schema, respecting conditional field rules.

**Step 3: Add routes**

```php
Route::middleware(AuthenticatePatient::class)->prefix('intake')->name('intake.')->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/form/{schemaKey}', [FormController::class, 'show'])->name('form.show');
    Route::put('/form/{schemaKey}', [FormController::class, 'save'])->name('form.save');
    Route::post('/form/{schemaKey}/complete', [FormController::class, 'complete'])->name('form.complete');
});
```

**Step 4: Run tests, composer check, commit**

```bash
git add -A && git commit -m "Add form display, auto-save, and completion endpoints"
```

---

### Task 9: File Upload & Signature Capture Endpoints

**Files:**
- Create: `app/Http/Controllers/Intake/DocumentController.php`
- Create: `app/Http/Controllers/Intake/SignatureController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Intake/DocumentUploadTest.php`
- Test: `tests/Feature/Intake/SignatureCaptureTest.php`

**Step 1: Write failing tests for document upload**

Test that a file can be uploaded, stored privately, linked to a form response, and that the original filename/mime type are recorded.

**Step 2: Write failing tests for signature capture**

Test that a base64 signature image can be submitted, stored as a file, and linked to a form response.

**Step 3: Implement controllers**

- `DocumentController::store` — validates file, stores to private disk under `documents/{patient_id}/`, creates Document record
- `SignatureController::store` — accepts base64 PNG data, decodes and stores to private disk under `signatures/{patient_id}/`, creates Signature record

**Step 4: Add routes, run tests, composer check, commit**

```bash
git add -A && git commit -m "Add document upload and signature capture endpoints"
```

---

## Phase 4: Frontend Rendering Engine

### Task 10: Svelte Form Renderer Component

**Files:**
- Create: `resources/js/components/intake/FormRenderer.svelte`
- Create: `resources/js/components/intake/FormSection.svelte`
- Create: `resources/js/components/intake/fields/TextField.svelte`
- Create: `resources/js/components/intake/fields/SelectField.svelte`
- Create: `resources/js/components/intake/fields/CheckboxField.svelte`
- Create: `resources/js/components/intake/fields/RadioField.svelte`
- Create: `resources/js/components/intake/fields/DateField.svelte`
- Create: `resources/js/components/intake/fields/TextareaField.svelte`
- Create: `resources/js/components/intake/fields/FileField.svelte`
- Create: `resources/js/components/intake/fields/SignatureField.svelte`
- Create: `resources/js/components/intake/fields/PhoneField.svelte`
- Create: `resources/js/components/intake/fields/EmailField.svelte`
- Create: `resources/js/components/intake/fields/AddressField.svelte`
- Create: `resources/js/components/intake/fields/index.ts`

**Docs to check:**
- @inertia-svelte-development skill for Svelte + Inertia patterns
- @tailwindcss-development skill for styling
- @wayfinder-development skill for route function usage

**Step 1: Build the field components**

Each field component receives:
- `field` — the field schema object (key, type, label, validation, etc.)
- `value` — current value (bindable)
- `locale` — current locale for label rendering
- `error` — validation error message if any

Use existing UI components from `resources/js/components/ui/` (Input, Label, Checkbox, etc.) where available.

**Step 2: Build the conditional logic engine**

In `FormRenderer.svelte`, implement a `shouldShow(field, formData)` function that evaluates the `conditions` array. Use Svelte reactivity so fields appear/disappear as the user fills in data.

**Step 3: Build auto-save**

On field blur, debounce and send a PUT request to `intake.form.save` via Wayfinder route functions. Show a subtle "Saving..." / "Saved" indicator.

**Step 4: Build the FormRenderer**

The main component that:
- Receives `schema` and `savedData` props
- Iterates sections, renders `FormSection` for each
- `FormSection` iterates fields, renders the appropriate field component based on `field.type`
- Manages form state reactively
- Handles auto-save on blur
- Provides "Save & Exit" and "Mark as Complete" buttons

**Step 5: Commit**

```bash
git add -A && git commit -m "Add Svelte form rendering engine with field components and auto-save"
```

---

### Task 11: Intake Pages (Landing, Dashboard, Form)

**Files:**
- Create: `resources/js/pages/intake/Landing.svelte`
- Create: `resources/js/pages/intake/Dashboard.svelte`
- Create: `resources/js/pages/intake/Form.svelte`

**Docs to check:**
- @inertia-svelte-development skill for page patterns
- @tailwindcss-development skill for mobile-first styling
- @wayfinder-development skill for route functions

**Step 1: Build Landing page**

- Clean, welcoming design with JumpStart branding placeholder
- Language toggle (EN/ES) in corner, auto-detected from browser
- Single email input field with "Get Started" button
- Uses Wayfinder `Form` component to POST to `intake.request-link`
- Success state: "Check your email for a link to continue"

**Step 2: Build Dashboard page**

- Warm greeting with progress bar
- Checklist of form sections as cards
- Each card shows: icon, title (localized), status badge, estimated time
- Cards link to `intake.form.show` via Wayfinder
- Completion state when all sections done

**Step 3: Build Form page**

- Uses `FormRenderer` component
- Passes `schema` and `savedData` props from controller
- Back button to dashboard
- Progress indicator within the form

**Step 4: Commit**

```bash
git add -A && git commit -m "Add intake Landing, Dashboard, and Form pages"
```

---

## Phase 5: i18n Support

### Task 12: Locale Detection & Switching

**Files:**
- Create: `app/Http/Middleware/SetPatientLocale.php`
- Modify: `bootstrap/app.php` (register middleware)
- Modify: `resources/js/pages/intake/Landing.svelte` (language toggle)
- Test: `tests/Feature/Intake/LocaleTest.php`

**Step 1: Write failing tests**

```php
test('locale defaults to browser accept-language', function (): void {
    $patient = Patient::factory()->create(['preferred_locale' => 'en']);
    session(['patient_id' => $patient->id]);

    $this->get(route('intake.dashboard'), ['Accept-Language' => 'es'])
        ->assertOk();

    // First visit should detect browser locale
});

test('patient preferred locale is persisted', function (): void {
    $patient = Patient::factory()->create();
    session(['patient_id' => $patient->id]);

    $this->post(route('intake.set-locale'), ['locale' => 'es'])
        ->assertOk();

    $patient->refresh();
    expect($patient->preferred_locale)->toBe('es');
});
```

**Step 2: Implement SetPatientLocale middleware**

Reads `preferred_locale` from the patient model (loaded via session), sets `app()->setLocale()`. On first visit with no preference, detect from `Accept-Language` header.

**Step 3: Add locale switching endpoint**

Small controller or closure route that updates the patient's `preferred_locale`.

**Step 4: Run tests, composer check, commit**

```bash
git add -A && git commit -m "Add locale detection and switching for patient intake"
```

---

## Phase 6: Staff Dashboard

### Task 13: Staff Patient List & Detail Views

**Files:**
- Create: `app/Http/Controllers/Staff/PatientController.php`
- Create: `resources/js/pages/staff/PatientList.svelte`
- Create: `resources/js/pages/staff/PatientDetail.svelte`
- Modify: `routes/web.php`
- Test: `tests/Feature/Staff/PatientControllerTest.php`

**Step 1: Write failing tests**

```php
use App\Models\Patient;
use App\Models\User;

test('staff can view patient list', function (): void {
    $user = User::factory()->create();
    Patient::factory()->count(3)->create();

    $this->actingAs($user)
        ->get(route('staff.patients.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('staff/PatientList')
            ->has('patients.data', 3)
        );
});

test('staff can view patient detail', function (): void {
    $user = User::factory()->create();
    $patient = Patient::factory()->create();

    $this->actingAs($user)
        ->get(route('staff.patients.show', $patient))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('staff/PatientDetail')
            ->has('patient')
            ->has('formResponses')
        );
});

test('unauthenticated users cannot access staff dashboard', function (): void {
    $this->get(route('staff.patients.index'))
        ->assertRedirect(route('login'));
});
```

**Step 2: Implement controller**

- `index` — paginated list of patients with status filtering, ordered by most recent
- `show` — patient detail with all form responses (decrypted), documents, signatures

**Step 3: Add routes**

```php
Route::middleware(['auth', 'verified'])->prefix('staff')->name('staff.')->group(function (): void {
    Route::get('/patients', [PatientController::class, 'index'])->name('patients.index');
    Route::get('/patients/{patient}', [PatientController::class, 'show'])->name('patients.show');
});
```

**Step 4: Build Svelte pages**

- `PatientList.svelte` — table/list of patients with status badges, search, status filter
- `PatientDetail.svelte` — read-only view of submitted form data, organized by section, with document download links

**Step 5: Run tests, composer check, commit**

```bash
git add -A && git commit -m "Add staff patient list and detail views"
```

---

## Phase 7: Monday.com Integration

### Task 14: Monday.com Sync Service & Job

**Files:**
- Create: `app/Services/MondayService.php`
- Create: `app/Jobs/SyncPatientToMonday.php` (via artisan)
- Modify: `config/services.php` (add Monday.com config)
- Test: `tests/Feature/Services/MondayServiceTest.php`
- Test: `tests/Feature/Jobs/SyncPatientToMondayTest.php`

**Step 1: Add Monday.com config**

Add to `config/services.php`:

```php
'monday' => [
    'api_token' => env('MONDAY_API_TOKEN'),
    'board_id' => env('MONDAY_BOARD_ID'),
],
```

**Step 2: Write failing tests**

```php
// tests/Feature/Jobs/SyncPatientToMondayTest.php
use App\Jobs\SyncPatientToMonday;
use App\Models\Patient;
use App\Services\MondayService;

test('sync job updates patient status on success', function (): void {
    $patient = Patient::factory()->create(['sync_status' => 'pending']);

    $mock = Mockery::mock(MondayService::class);
    $mock->shouldReceive('createItem')->once()->andReturn('12345');
    $mock->shouldReceive('uploadFiles')->once();
    app()->instance(MondayService::class, $mock);

    SyncPatientToMonday::dispatch($patient);

    $patient->refresh();
    expect($patient->sync_status)->toBe('synced')
        ->and($patient->synced_at)->not->toBeNull();
});

test('sync job marks patient as failed on exception', function (): void {
    $patient = Patient::factory()->create(['sync_status' => 'pending']);

    $mock = Mockery::mock(MondayService::class);
    $mock->shouldReceive('createItem')->once()->andThrow(new Exception('API error'));
    app()->instance(MondayService::class, $mock);

    try {
        SyncPatientToMonday::dispatchSync($patient);
    } catch (Exception) {}

    $patient->refresh();
    expect($patient->sync_status)->toBe('failed');
});
```

**Step 3: Implement MondayService**

```php
class MondayService
{
    public function __construct(private readonly string $apiToken, private readonly string $boardId) {}

    public function createItem(Patient $patient, array $columnValues): string
    {
        // GraphQL mutation to Monday.com API
        // Maps schema field values to Monday.com column values
        // Returns the created item ID
    }

    public function uploadFiles(string $itemId, array $documents): void
    {
        // Upload documents as file attachments to the Monday.com item
    }
}
```

**Step 4: Implement SyncPatientToMonday job**

```php
class SyncPatientToMonday implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public array $backoff = [60, 300, 900];

    public function __construct(private readonly Patient $patient) {}

    public function handle(MondayService $monday, FormSchemaService $schemas): void
    {
        $this->patient->update(['sync_status' => 'syncing']);

        // Build column values from form responses using schema monday_field mappings
        $columnValues = $this->buildColumnValues($schemas);

        $itemId = $monday->createItem($this->patient, $columnValues);

        // Upload documents
        $documents = $this->patient->documents()->get();
        if ($documents->isNotEmpty()) {
            $monday->uploadFiles($itemId, $documents->all());
        }

        $this->patient->update([
            'sync_status' => 'synced',
            'synced_at' => now(),
        ]);
    }

    public function failed(Throwable $exception): void
    {
        $this->patient->update(['sync_status' => 'failed']);
    }
}
```

**Step 5: Dispatch job when all forms are completed**

In `FormController::complete`, after marking a section complete, check if ALL schemas are now completed. If so, dispatch `SyncPatientToMonday`.

**Step 6: Run tests, composer check, commit**

```bash
git add -A && git commit -m "Add Monday.com sync service and queued job"
```

---

## Phase 8: Polish & Security

### Task 15: HIPAA Security Hardening

**Files:**
- Modify: `app/Models/FormResponse.php` (verify encrypted cast)
- Modify: `config/session.php` (session timeout)
- Modify: `config/filesystems.php` (private disk config)
- Test: `tests/Feature/Security/HipaaSecurityTest.php`

**Step 1: Write security tests**

```php
test('form response data is encrypted at rest', function (): void {
    $response = FormResponse::factory()->create([
        'data' => ['ssn' => '123-45-6789'],
    ]);

    $raw = DB::table('form_responses')->where('id', $response->id)->value('data');
    expect($raw)->not->toContain('123-45-6789');
});

test('magic link tokens are single use', function (): void {
    $patient = Patient::factory()->withMagicLink()->create();
    $token = $patient->magic_link_token;

    $this->get(route('intake.verify', ['token' => $token]))->assertRedirect();
    $this->get(route('intake.verify', ['token' => $token]))->assertRedirect(route('intake.landing'));
});

test('patient cannot access another patients data', function (): void {
    $patient1 = Patient::factory()->create();
    $patient2 = Patient::factory()->create();
    FormResponse::factory()->create(['patient_id' => $patient2->id]);
    session(['patient_id' => $patient1->id]);

    $this->get(route('intake.form.show', 'demographics'))
        ->assertOk();
    // Verify only patient1's data is returned, not patient2's
});

test('documents are stored privately', function (): void {
    // Verify uploaded files are not publicly accessible
});
```

**Step 2: Configure session timeout**

Set `lifetime` in `config/session.php` to 60 minutes.

**Step 3: Run tests, composer check, commit**

```bash
git add -A && git commit -m "Add HIPAA security hardening and tests"
```

---

### Task 16: Additional Form Schemas

**Files:**
- Create: `config/forms/insurance.php`
- Create: `config/forms/child_information.php`
- Create: `config/forms/medical_history.php`
- Create: `config/forms/developmental_concerns.php`
- Create: `config/forms/consent.php`

**Step 1: Define schemas**

Work with the client to define the actual fields for each form. For now, create reasonable placeholder schemas based on standard autism services intake forms:

- **Insurance** — insurance provider, policy number, group number, policyholder info, insurance card upload (front/back)
- **Child Information** — child's name, DOB, gender, address, pediatrician, school
- **Medical History** — diagnoses, medications, allergies, hospitalizations, prior evaluations
- **Developmental Concerns** — developmental milestones, current concerns, behavioral observations
- **Consent** — consent for evaluation, consent for information sharing, signatures

Each schema should include conditional fields, i18n labels, validation rules, and Monday.com field mappings.

**Step 2: Test that all schemas load**

```php
test('all form schemas load without error', function (): void {
    $service = app(FormSchemaService::class);
    $schemas = $service->all();

    expect($schemas)->toHaveCount(6) // demographics + 5 new ones
        ->and(array_column($schemas, 'key'))->toContain(
            'demographics', 'insurance', 'child_information',
            'medical_history', 'developmental_concerns', 'consent'
        );
});
```

**Step 3: Run tests, composer check, commit**

```bash
git add -A && git commit -m "Add intake form schemas for insurance, child info, medical history, concerns, and consent"
```

---

## Summary of Tasks

| # | Task | Phase |
|---|------|-------|
| 1 | Patient model, migration, factory | Phase 1 |
| 2 | Magic link generation service | Phase 1 |
| 3 | Magic link verification & session | Phase 1 |
| 4 | Form schema loader service | Phase 2 |
| 5 | FormResponse model | Phase 2 |
| 6 | Document & Signature models | Phase 2 |
| 7 | Intake dashboard controller | Phase 3 |
| 8 | Form display & auto-save endpoints | Phase 3 |
| 9 | File upload & signature capture | Phase 3 |
| 10 | Svelte form renderer component | Phase 4 |
| 11 | Intake pages (Landing, Dashboard, Form) | Phase 4 |
| 12 | Locale detection & switching | Phase 5 |
| 13 | Staff patient list & detail views | Phase 6 |
| 14 | Monday.com sync service & job | Phase 7 |
| 15 | HIPAA security hardening | Phase 8 |
| 16 | Additional form schemas | Phase 8 |
