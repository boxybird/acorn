<?php

use App\Actions\CreateChildIntake;
use App\Enums\FormResponseStatus;
use App\Models\FormResponse;
use App\Models\Intake;
use App\Models\Patient;

it('creates a new intake for a patient with no previous intakes', function (): void {
    $patient = Patient::factory()->create();

    $createChildIntake = app(CreateChildIntake::class);
    $intake = $createChildIntake->handle($patient->id);

    expect($intake)->toBeInstanceOf(Intake::class)
        ->and($intake->patient_id)->toBe($patient->id)
        ->and(FormResponse::query()->where('intake_id', $intake->id)->count())->toBe(0);
});

it('creates a new intake and clones parent-level form data from the most recent intake', function (): void {
    $patient = Patient::factory()->create();
    $existingIntake = Intake::factory()->create(['patient_id' => $patient->id]);

    FormResponse::factory()->create([
        'intake_id' => $existingIntake->id,
        'schema_key' => 'demographics',
        'data' => ['first_name' => 'Jane'],
        'status' => FormResponseStatus::Completed,
    ]);

    FormResponse::factory()->create([
        'intake_id' => $existingIntake->id,
        'schema_key' => 'insurance',
        'data' => ['provider' => 'Aetna'],
        'status' => FormResponseStatus::Completed,
    ]);

    $createChildIntake = app(CreateChildIntake::class);
    $newIntake = $createChildIntake->handle($patient->id);

    $clonedResponses = FormResponse::query()
        ->where('intake_id', $newIntake->id)
        ->get();

    expect($clonedResponses)->toHaveCount(2);

    $demographics = $clonedResponses->firstWhere('schema_key', 'demographics');
    expect($demographics->data)->toBe(['first_name' => 'Jane'])
        ->and($demographics->status)->toBe(FormResponseStatus::InProgress);

    $insurance = $clonedResponses->firstWhere('schema_key', 'insurance');
    expect($insurance->data)->toBe(['provider' => 'Aetna'])
        ->and($insurance->status)->toBe(FormResponseStatus::InProgress);
});

it('does not clone non-parent schemas from the most recent intake', function (): void {
    $patient = Patient::factory()->create();
    $existingIntake = Intake::factory()->create(['patient_id' => $patient->id]);

    FormResponse::factory()->create([
        'intake_id' => $existingIntake->id,
        'schema_key' => 'demographics',
        'data' => ['first_name' => 'Jane'],
    ]);

    FormResponse::factory()->create([
        'intake_id' => $existingIntake->id,
        'schema_key' => 'child_information',
        'data' => ['child_first_name' => 'Alex'],
    ]);

    FormResponse::factory()->create([
        'intake_id' => $existingIntake->id,
        'schema_key' => 'medical_history',
        'data' => ['allergies' => 'none'],
    ]);

    $createChildIntake = app(CreateChildIntake::class);
    $newIntake = $createChildIntake->handle($patient->id);

    $clonedResponses = FormResponse::query()
        ->where('intake_id', $newIntake->id)
        ->get();

    expect($clonedResponses)->toHaveCount(1)
        ->and($clonedResponses->first()->schema_key)->toBe('demographics');
});
