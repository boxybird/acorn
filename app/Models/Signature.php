<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Signature extends Model
{
    /** @use HasFactory<\Database\Factories\SignatureFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'intake_id',
        'form_response_id',
        'field_key',
        'image_path',
    ];

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
}
