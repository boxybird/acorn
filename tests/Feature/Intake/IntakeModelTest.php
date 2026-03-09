<?php

use App\Enums\IntakeStatus;
use App\Models\FormResponse;
use App\Models\Intake;
use App\Models\Patient;

test('intake belongs to patient', function (): void {
    $patient = Patient::factory()->create();
    $intake = Intake::factory()->create(['patient_id' => $patient->id]);

    expect($intake->patient->id)->toBe($patient->id);
});

test('patient has many intakes', function (): void {
    $patient = Patient::factory()->create();
    Intake::factory()->count(3)->create(['patient_id' => $patient->id]);

    expect($patient->intakes)->toHaveCount(3)
        ->each->toBeInstanceOf(Intake::class);
});

test('intake has child_name and status', function (): void {
    $intake = Intake::factory()->create([
        'child_name' => 'Alex',
        'status' => IntakeStatus::Active,
    ]);

    expect($intake->child_name)->toBe('Alex')
        ->and($intake->status)->toBe(IntakeStatus::Active)
        ->and($intake->isActive())->toBeTrue()
        ->and($intake->isCompleted())->toBeFalse();
});

test('intake completed state works', function (): void {
    $intake = Intake::factory()->completed()->create();

    expect($intake->isCompleted())->toBeTrue()
        ->and($intake->isActive())->toBeFalse();
});

test('intake without child name state works', function (): void {
    $intake = Intake::factory()->withoutChildName()->create();

    expect($intake->child_name)->toBeNull();
});

test('form response belongs to intake', function (): void {
    $intake = Intake::factory()->create();
    $formResponse = FormResponse::factory()->create(['intake_id' => $intake->id]);

    expect($formResponse->intake->id)->toBe($intake->id);
});

test('intake has many form responses', function (): void {
    $intake = Intake::factory()->create();
    FormResponse::factory()->create(['intake_id' => $intake->id, 'schema_key' => 'demographics']);
    FormResponse::factory()->create(['intake_id' => $intake->id, 'schema_key' => 'insurance']);
    FormResponse::factory()->create(['intake_id' => $intake->id, 'schema_key' => 'medical_history']);

    expect($intake->formResponses)->toHaveCount(3);
});
