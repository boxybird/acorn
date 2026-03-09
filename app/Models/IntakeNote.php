<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntakeNote extends Model
{
    /** @use HasFactory<\Database\Factories\IntakeNoteFactory> */
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
