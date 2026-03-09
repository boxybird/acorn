<?php

use App\Concerns\HasEncryptedPhi;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    Schema::create('test_phi_models', function ($table): void {
        $table->id();
        $table->text('secret_name')->nullable();
        $table->text('secret_email')->nullable();
        $table->string('secret_email_hash', 64)->nullable()->unique();
        $table->timestamps();
    });
});

afterEach(function (): void {
    Schema::dropIfExists('test_phi_models');
});

function createTestPhiModel(): Model
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
