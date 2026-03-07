<?php

use App\Models\Document;
use App\Models\FormResponse;
use App\Models\Patient;

test('document belongs to a patient', function (): void {
    $document = Document::factory()->create();

    expect($document->patient)->toBeInstanceOf(Patient::class);
});

test('document belongs to a form response', function (): void {
    $document = Document::factory()->create();

    expect($document->formResponse)->toBeInstanceOf(FormResponse::class);
});

test('document can be created with factory', function (): void {
    $document = Document::factory()->create();

    expect($document)->toBeInstanceOf(Document::class)
        ->and($document->field_key)->toBeString()
        ->and($document->file_path)->toBeString()
        ->and($document->original_filename)->toBeString()
        ->and($document->mime_type)->toBe('application/pdf');
});
