<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    /** @use HasFactory<\Database\Factories\DocumentFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'patient_id',
        'form_response_id',
        'field_key',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
    ];

    /** @return BelongsTo<Patient, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /** @return BelongsTo<FormResponse, $this> */
    public function formResponse(): BelongsTo
    {
        return $this->belongsTo(FormResponse::class);
    }
}
