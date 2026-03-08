<?php

use App\Models\FormResponse;
use App\Models\Intake;
use App\Models\Patient;
use App\Models\Signature;
use Illuminate\Support\Facades\Storage;

test('signature can be captured from base64 data', function (): void {
    Storage::fake('local');

    $patient = Patient::factory()->create();
    $intake = Intake::factory()->create(['patient_id' => $patient->id]);
    $formResponse = FormResponse::factory()->create(['intake_id' => $intake->id]);

    $base64Signature = 'data:image/png;base64,'.base64_encode('fake-png-data');

    $this->withSession(['patient_id' => $patient->id, 'intake_id' => $intake->id])
        ->post(route('intake.signatures.store'), [
            'form_response_id' => $formResponse->id,
            'field_key' => 'consent_signature',
            'signature' => $base64Signature,
        ])
        ->assertCreated();

    expect(Signature::query()->count())->toBe(1);

    $signature = Signature::query()->first();

    expect($signature->field_key)->toBe('consent_signature')
        ->and($signature->intake_id)->toBe($intake->id);

    Storage::disk('local')->assertExists($signature->image_path);
});

test('signature capture validates required fields', function (): void {
    $patient = Patient::factory()->create();
    $intake = Intake::factory()->create(['patient_id' => $patient->id]);

    $this->withSession(['patient_id' => $patient->id, 'intake_id' => $intake->id])
        ->postJson(route('intake.signatures.store'), [])
        ->assertUnprocessable();
});

test('signature capture prevents access to other intake form responses', function (): void {
    $patient = Patient::factory()->create();
    $intake = Intake::factory()->create(['patient_id' => $patient->id]);
    $otherIntake = Intake::factory()->create();
    $otherFormResponse = FormResponse::factory()->create(['intake_id' => $otherIntake->id]);

    $this->withSession(['patient_id' => $patient->id, 'intake_id' => $intake->id])
        ->postJson(route('intake.signatures.store'), [
            'form_response_id' => $otherFormResponse->id,
            'field_key' => 'consent_signature',
            'signature' => 'data:image/png;base64,'.base64_encode('fake-png-data'),
        ])
        ->assertNotFound();
});
