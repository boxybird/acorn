<?php

namespace App\Models;

use App\Concerns\HasEncryptedPhi;
use App\Enums\IntakeStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property IntakeStatus $status
 * @property \Illuminate\Support\Carbon|null $synced_at
 */
class Intake extends Model
{
    use HasEncryptedPhi;

    /** @use HasFactory<\Database\Factories\IntakeFactory> */
    use HasFactory;

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

    /** @return HasMany<IntakeFlag, $this> */
    public function flags(): HasMany
    {
        return $this->hasMany(IntakeFlag::class);
    }

    public function isCompleted(): bool
    {
        return in_array($this->status, [IntakeStatus::Approved, IntakeStatus::SyncedToMonday], true);
    }

    public function isActive(): bool
    {
        return $this->status === IntakeStatus::Active;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => IntakeStatus::class,
            'synced_at' => 'datetime',
        ];
    }
}
