<?php

use App\Models\Document;
use App\Models\FormResponse;
use App\Models\Intake;

test('document belongs to an intake', function (): void {
    $document = Document::factory()->create();

    expect($document->intake)->toBeInstanceOf(Intake::class);
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
