<?php

use App\Models\Document;
use App\Models\FormResponse;
use App\Models\Patient;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('document can be uploaded', function (): void {
    Storage::fake('local');

    $patient = Patient::factory()->create();
    $formResponse = FormResponse::factory()->create(['patient_id' => $patient->id]);

    $this->withSession(['patient_id' => $patient->id])
        ->post(route('intake.documents.store'), [
            'form_response_id' => $formResponse->id,
            'field_key' => 'insurance_card',
            'file' => UploadedFile::fake()->create('insurance.pdf', 1024, 'application/pdf'),
        ])
        ->assertCreated();

    expect(Document::query()->count())->toBe(1);

    $document = Document::query()->first();

    expect($document->original_filename)->toBe('insurance.pdf')
        ->and($document->mime_type)->toBe('application/pdf')
        ->and($document->patient_id)->toBe($patient->id);

    Storage::disk('local')->assertExists($document->file_path);
});

test('document upload validates required fields', function (): void {
    $patient = Patient::factory()->create();

    $this->withSession(['patient_id' => $patient->id])
        ->postJson(route('intake.documents.store'), [])
        ->assertUnprocessable();
});

test('document upload rejects files over 10mb', function (): void {
    Storage::fake('local');

    $patient = Patient::factory()->create();
    $formResponse = FormResponse::factory()->create(['patient_id' => $patient->id]);

    $this->withSession(['patient_id' => $patient->id])
        ->postJson(route('intake.documents.store'), [
            'form_response_id' => $formResponse->id,
            'field_key' => 'insurance_card',
            'file' => UploadedFile::fake()->create('large.pdf', 11000, 'application/pdf'),
        ])
        ->assertUnprocessable();
});

test('document can be deleted by owner', function (): void {
    Storage::fake('local');

    $patient = Patient::factory()->create();
    $formResponse = FormResponse::factory()->create(['patient_id' => $patient->id]);

    $file = UploadedFile::fake()->create('insurance.pdf', 1024, 'application/pdf');

    /** @var string $path */
    $path = $file->store('documents/'.$patient->id, 'local');

    $document = Document::factory()->create([
        'patient_id' => $patient->id,
        'form_response_id' => $formResponse->id,
        'file_path' => $path,
    ]);

    $this->withSession(['patient_id' => $patient->id])
        ->delete(route('intake.documents.destroy', $document))
        ->assertOk();

    expect(Document::query()->count())->toBe(0);
    Storage::disk('local')->assertMissing($path);
});
