<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntakeFlag extends Model
{
    /** @use HasFactory<\Database\Factories\IntakeFlagFactory> */
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
