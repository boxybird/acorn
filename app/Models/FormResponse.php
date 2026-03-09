<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormResponse extends Model
{
    /** @use HasFactory<\Database\Factories\FormResponseFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = ['intake_id', 'schema_key', 'data', 'status'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data' => 'encrypted:array',
        ];
    }

    /** @return BelongsTo<Intake, $this> */
    public function intake(): BelongsTo
    {
        return $this->belongsTo(Intake::class);
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
        return $this->status === 'completed';
    }
}
