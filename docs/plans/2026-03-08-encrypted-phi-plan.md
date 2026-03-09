# Encrypted PHI Columns Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Encrypt all PHI columns at rest using Laravel encrypted casts, with a reusable trait convention that future-proofs new PHI fields.

**Architecture:** A `HasEncryptedPhi` trait auto-applies `encrypted` casts and maintains HMAC-SHA256 blind index columns for fields that need database lookups. Models declare `$encryptedPhi` (fields to encrypt) and `$blindIndexed` (fields needing hash-based queries). The trait handles cast merging, hash computation on save, and provides a `whereBlindIndex()` scope.

**Tech Stack:** Laravel 12, PHP 8.4, Pest 4, SQLite (dev)

---

### Task 1: Create the HasEncryptedPhi Trait (Test First)

**Files:**
- Create: `tests/Unit/Concerns/HasEncryptedPhiTest.php`
- Create: `app/Concerns/HasEncryptedPhi.php`

**Step 1: Write the failing tests**

Create the test file with `php artisan make:test --pest --unit Concerns/HasEncryptedPhiTest`, then replace contents:

```php
<?php

use App\Concerns\HasEncryptedPhi;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    Schema::create('test_phi_models', function ($table): void {
        $table->id();
        $table->text('secret_name')->nullable();
        $table->text('secret_email')->nullable();
        $table->string('secret_email_hash')->nullable()->unique();
        $table->timestamps();
    });
});

afterEach(function (): void {
    Schema::dropIfExists('test_phi_models');
});

function createTestPhiModel(): object
{
    return new class extends Model
    {
        use HasEncryptedPhi;

        protected $table = 'test_phi_models';

        protected $fillable = ['secret_name', 'secret_email'];

        /** @var list<string> */
        protected array $encryptedPhi = ['secret_name', 'secret_email'];

        /** @var list<string> */
        protected array $blindIndexed = ['secret_email'];
    };
}

test('encrypted phi fields are automatically cast to encrypted', function (): void {
    $model = createTestPhiModel();

    expect($model->getCasts())->toHaveKey('secret_name', 'encrypted')
        ->toHaveKey('secret_email', 'encrypted');
});

test('saving a model encrypts phi fields in database', function (): void {
    $model = createTestPhiModel();
    $model->secret_name = 'John Doe';
    $model->secret_email = 'john@example.com';
    $model->save();

    $raw = DB::table('test_phi_models')->where('id', $model->id)->first();

    expect($raw->secret_name)->not->toBe('John Doe')
        ->and($raw->secret_email)->not->toBe('john@example.com');
});

test('reading a model decrypts phi fields', function (): void {
    $model = createTestPhiModel();
    $model->secret_name = 'John Doe';
    $model->secret_email = 'john@example.com';
    $model->save();

    $found = $model::query()->find($model->id);

    expect($found->secret_name)->toBe('John Doe')
        ->and($found->secret_email)->toBe('john@example.com');
});

test('blind index hash is computed on save', function (): void {
    $model = createTestPhiModel();
    $model->secret_email = 'john@example.com';
    $model->save();

    $raw = DB::table('test_phi_models')->where('id', $model->id)->first();

    expect($raw->secret_email_hash)->not->toBeNull()
        ->and($raw->secret_email_hash)->toBeString()
        ->and(strlen($raw->secret_email_hash))->toBe(64);
});

test('blind index hash is deterministic', function (): void {
    $model1 = createTestPhiModel();
    $model1->secret_email = 'same@example.com';
    $model1->save();

    $raw1 = DB::table('test_phi_models')->where('id', $model1->id)->first();

    $model2 = createTestPhiModel();
    $model2->secret_email = 'same@example.com';

    expect($model2->computeBlindIndex('secret_email'))->toBe($raw1->secret_email_hash);
});

test('whereBlindIndex scope finds records by plaintext value', function (): void {
    $model = createTestPhiModel();
    $model->secret_email = 'findme@example.com';
    $model->save();

    $found = $model::query()->whereBlindIndex('secret_email', 'findme@example.com')->first();

    expect($found)->not->toBeNull()
        ->and($found->id)->toBe($model->id)
        ->and($found->secret_email)->toBe('findme@example.com');
});

test('blind index updates when field value changes', function (): void {
    $model = createTestPhiModel();
    $model->secret_email = 'old@example.com';
    $model->save();

    $oldHash = DB::table('test_phi_models')->where('id', $model->id)->value('secret_email_hash');

    $model->secret_email = 'new@example.com';
    $model->save();

    $newHash = DB::table('test_phi_models')->where('id', $model->id)->value('secret_email_hash');

    expect($newHash)->not->toBe($oldHash);
});

test('null blind indexed field stores null hash', function (): void {
    $model = createTestPhiModel();
    $model->secret_email = null;
    $model->save();

    $raw = DB::table('test_phi_models')->where('id', $model->id)->first();

    expect($raw->secret_email_hash)->toBeNull();
});

test('existing casts are preserved when trait merges encrypted casts', function (): void {
    $model = new class extends Model
    {
        use HasEncryptedPhi;

        protected $table = 'test_phi_models';

        /** @var list<string> */
        protected array $encryptedPhi = ['secret_name'];

        /** @return array<string, string> */
        protected function casts(): array
        {
            return [
                'secret_email' => 'string',
            ];
        }
    };

    expect($model->getCasts())->toHaveKey('secret_name', 'encrypted')
        ->toHaveKey('secret_email', 'string');
});
```

**Step 2: Run tests to verify they fail**

Run: `php artisan test --compact --filter=HasEncryptedPhiTest`
Expected: FAIL — trait doesn't exist

**Step 3: Write the trait implementation**

Create `app/Concerns/HasEncryptedPhi.php`:

```php
<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin Model
 *
 * @property list<string> $encryptedPhi
 * @property list<string> $blindIndexed
 */
trait HasEncryptedPhi
{
    public function initializeHasEncryptedPhi(): void
    {
        foreach ($this->encryptedPhi ?? [] as $field) {
            $this->mergeCasts([$field => 'encrypted']);
        }
    }

    public static function bootHasEncryptedPhi(): void
    {
        static::saving(function (Model $model): void {
            /** @var list<string> $blindIndexed */
            $blindIndexed = $model->blindIndexed ?? [];

            foreach ($blindIndexed as $field) {
                /** @var string|null $value */
                $value = $model->getAttribute($field);
                $hashColumn = $field.'_hash';

                $model->setAttribute(
                    $hashColumn,
                    $value !== null && $value !== '' ? $model->computeBlindIndex($field) : null,
                );
            }
        });
    }

    public function computeBlindIndex(string $field): string
    {
        /** @var string $value */
        $value = $this->getAttribute($field);

        /** @var string $appKey */
        $appKey = config('app.key');

        return hash_hmac('sha256', mb_strtolower($value), $appKey);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeWhereBlindIndex(Builder $query, string $field, string $value): Builder
    {
        /** @var string $appKey */
        $appKey = config('app.key');

        $hash = hash_hmac('sha256', mb_strtolower($value), $appKey);

        return $query->where($field.'_hash', $hash);
    }
}
```

**Step 4: Run tests to verify they pass**

Run: `php artisan test --compact --filter=HasEncryptedPhiTest`
Expected: All PASS

**Step 5: Run code quality checks**

Run: `composer check`
Expected: PASS

**Step 6: Commit**

```
feat: add HasEncryptedPhi trait for HIPAA-compliant field encryption
```

---

### Task 2: Migration — Add Blind Index and Convert Columns

**Files:**
- Create: migration via `php artisan make:migration encrypt_phi_columns_on_patients_and_intakes --no-interaction`

**Step 1: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $blueprint): void {
            $blueprint->string('email_hash', 64)->nullable()->after('email');
        });

        $this->backfillPatients();

        Schema::table('patients', function (Blueprint $blueprint): void {
            $blueprint->dropUnique(['email']);
            $blueprint->unique('email_hash');
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $blueprint): void {
            $blueprint->dropUnique(['email_hash']);
            $blueprint->unique('email');
            $blueprint->dropColumn('email_hash');
        });
    }

    private function backfillPatients(): void
    {
        /** @var string $appKey */
        $appKey = config('app.key');

        $patients = DB::table('patients')->get();

        foreach ($patients as $patient) {
            $emailHash = hash_hmac('sha256', mb_strtolower($patient->email), $appKey);
            $encryptedEmail = encrypt($patient->email);
            $encryptedName = $patient->name !== null ? encrypt($patient->name) : null;

            DB::table('patients')
                ->where('id', $patient->id)
                ->update([
                    'email' => $encryptedEmail,
                    'name' => $encryptedName,
                    'email_hash' => $emailHash,
                ]);
        }

        $intakes = DB::table('intakes')->whereNotNull('child_name')->get();

        foreach ($intakes as $intake) {
            DB::table('intakes')
                ->where('id', $intake->id)
                ->update([
                    'child_name' => encrypt($intake->child_name),
                ]);
        }
    }
};
```

**Step 2: Run the migration**

Run: `php artisan migrate --no-interaction`
Expected: Migration runs successfully

**Step 3: Verify backfill worked**

Use tinker tool to confirm:
- `DB::table('patients')->first()` — email/name columns should contain encrypted blobs, email_hash should be 64-char hex
- `DB::table('intakes')->whereNotNull('child_name')->first()` — child_name should be encrypted blob

**Step 4: Commit**

```
feat: add migration to encrypt PHI columns and add blind index
```

---

### Task 3: Apply Trait to Patient Model

**Files:**
- Modify: `app/Models/Patient.php`
- Modify: `tests/Feature/Services/MagicLinkServiceTest.php`

**Step 1: Update Patient model**

Add the trait and PHI field declarations to `app/Models/Patient.php`:

```php
<?php

namespace App\Models;

use App\Concerns\HasEncryptedPhi;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

/**
 * @property \Illuminate\Support\Carbon|null $magic_link_expires_at
 */
class Patient extends Model
{
    /** @use HasFactory<\Database\Factories\PatientFactory> */
    use HasFactory;

    use HasEncryptedPhi;
    use Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'name',
        'preferred_locale',
        'magic_link_token',
        'magic_link_expires_at',
    ];

    /** @var list<string> */
    protected array $encryptedPhi = ['email', 'name'];

    /** @var list<string> */
    protected array $blindIndexed = ['email'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'magic_link_expires_at' => 'datetime',
        ];
    }

    /** @return HasMany<Intake, $this> */
    public function intakes(): HasMany
    {
        return $this->hasMany(Intake::class);
    }

    public function hasValidMagicLink(): bool
    {
        return $this->magic_link_token !== null
            && $this->magic_link_expires_at !== null
            && $this->magic_link_expires_at->isFuture();
    }
}
```

**Step 2: Update MagicLinkService to use blind index**

Modify `app/Services/MagicLinkService.php` — replace `firstOrCreate` with blind index lookup:

```php
<?php

namespace App\Services;

use App\Models\Patient;
use App\Notifications\MagicLinkNotification;
use Illuminate\Support\Str;

class MagicLinkService
{
    public function send(Patient $patient): void
    {
        $token = Str::random(64);

        $patient->update([
            'magic_link_token' => $token,
            'magic_link_expires_at' => now()->addMinutes(30),
        ]);

        $patient->notify(new MagicLinkNotification($token));
    }

    public function sendToEmail(string $email): void
    {
        $patient = Patient::query()->whereBlindIndex('email', $email)->first();

        if (! $patient instanceof Patient) {
            $patient = Patient::query()->create(['email' => $email]);
        }

        $this->send($patient);
    }
}
```

**Step 3: Update MagicLinkServiceTest to use blind index**

In `tests/Feature/Services/MagicLinkServiceTest.php`, replace `->where('email', ...)` with `->whereBlindIndex('email', ...)`:

```php
test('magic link service creates new patient if email not found', function (): void {
    Notification::fake();

    $magicLinkService = app(MagicLinkService::class);
    $magicLinkService->sendToEmail('new@example.com');

    $patient = Patient::query()->whereBlindIndex('email', 'new@example.com')->first();

    expect($patient)->not->toBeNull()
        ->and($patient->hasValidMagicLink())->toBeTrue();

    Notification::assertSentTo($patient, MagicLinkNotification::class);
});

test('magic link service reuses existing patient', function (): void {
    Notification::fake();

    Patient::factory()->create(['email' => 'existing@example.com']);
    $magicLinkService = app(MagicLinkService::class);
    $magicLinkService->sendToEmail('existing@example.com');

    expect(Patient::query()->whereBlindIndex('email', 'existing@example.com')->count())->toBe(1);

    $existing = Patient::query()->whereBlindIndex('email', 'existing@example.com')->first();
    expect($existing->hasValidMagicLink())->toBeTrue();
});
```

**Step 4: Run tests**

Run: `php artisan test --compact --filter=MagicLinkService`
Expected: All PASS

**Step 5: Run code quality checks**

Run: `composer check`
Expected: PASS

**Step 6: Commit**

```
feat: apply HasEncryptedPhi trait to Patient model
```

---

### Task 4: Apply Trait to Intake Model

**Files:**
- Modify: `app/Models/Intake.php`

**Step 1: Update Intake model**

```php
<?php

namespace App\Models;

use App\Concerns\HasEncryptedPhi;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property \Illuminate\Support\Carbon|null $synced_at
 */
class Intake extends Model
{
    /** @use HasFactory<\Database\Factories\IntakeFactory> */
    use HasFactory;

    use HasEncryptedPhi;

    /** @var list<string> */
    protected $fillable = ['patient_id', 'child_name', 'status', 'sync_status', 'synced_at'];

    /** @var list<string> */
    protected array $encryptedPhi = ['child_name'];

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

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'synced_at' => 'datetime',
        ];
    }
}
```

**Step 2: Run full test suite**

Run: `php artisan test --compact`
Expected: All PASS — `child_name` has no WHERE queries, so no query changes needed

**Step 3: Run code quality checks**

Run: `composer check`
Expected: PASS

**Step 4: Commit**

```
feat: apply HasEncryptedPhi trait to Intake model
```

---

### Task 5: Verify End-to-End Encryption

**Step 1: Run full test suite**

Run: `php artisan test --compact`
Expected: All PASS

**Step 2: Verify database contents via tinker**

Use tinker to create a patient and verify encryption:
```php
$p = Patient::factory()->create(['email' => 'hipaa@test.com', 'name' => 'Test Person']);
$raw = DB::table('patients')->where('id', $p->id)->first();
// $raw->email should be encrypted blob (not 'hipaa@test.com')
// $raw->name should be encrypted blob (not 'Test Person')
// $raw->email_hash should be 64-char hex string
// $p->email should be 'hipaa@test.com' (decrypted via model)
```

**Step 3: Verify blind index lookup**

```php
$found = Patient::whereBlindIndex('email', 'hipaa@test.com')->first();
// $found->id should match $p->id
```

**Step 4: Run code quality checks**

Run: `composer check`
Expected: PASS

**Step 5: Commit (if any fixes were needed)**

```
fix: resolve any issues found during e2e verification
```
