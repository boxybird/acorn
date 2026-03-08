<?php

use App\Models\FormResponse;
use App\Models\Intake;
use App\Models\Patient;

test('intake selector shows all intakes for patient', function (): void {
    $patient = Patient::factory()->create();
    Intake::factory()->count(2)->create(['patient_id' => $patient->id]);

    $this->withSession(['patient_id' => $patient->id])
        ->get(route('intake.select'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('intake/IntakeSelector')
            ->has('intakes', 2)
        );
});

test('selecting an intake sets intake_id in session', function (): void {
    $patient = Patient::factory()->create();
    $intake = Intake::factory()->create(['patient_id' => $patient->id]);

    $this->withSession(['patient_id' => $patient->id])
        ->post(route('intake.select.choose', $intake))
        ->assertRedirect(route('intake.dashboard'))
        ->assertSessionHas('intake_id', $intake->id);
});

test('cannot select another patients intake', function (): void {
    $patient = Patient::factory()->create();
    $otherPatient = Patient::factory()->create();
    $otherIntake = Intake::factory()->create(['patient_id' => $otherPatient->id]);

    $this->withSession(['patient_id' => $patient->id])
        ->post(route('intake.select.choose', $otherIntake))
        ->assertForbidden();
});

test('creating new intake copies demographics and insurance data', function (): void {
    $patient = Patient::factory()->create();
    $existingIntake = Intake::factory()->create(['patient_id' => $patient->id]);

    FormResponse::factory()->completed()->create([
        'intake_id' => $existingIntake->id,
        'schema_key' => 'demographics',
        'data' => ['first_name' => 'Jane', 'last_name' => 'Doe', 'phone' => '555-1234'],
    ]);

    FormResponse::factory()->completed()->create([
        'intake_id' => $existingIntake->id,
        'schema_key' => 'insurance',
        'data' => ['provider' => 'BlueCross'],
    ]);

    FormResponse::factory()->completed()->create([
        'intake_id' => $existingIntake->id,
        'schema_key' => 'child_information',
        'data' => ['child_first_name' => 'Emma'],
    ]);

    $this->withSession(['patient_id' => $patient->id])
        ->post(route('intake.select.new'))
        ->assertRedirect(route('intake.dashboard'));

    $newIntake = $patient->intakes()->latest('id')->first();

    // Demographics and insurance are pre-filled as drafts
    $demographics = $newIntake->formResponses()->where('schema_key', 'demographics')->first();
    expect($demographics)->not->toBeNull()
        ->and($demographics->data['first_name'])->toBe('Jane')
        ->and($demographics->status)->toBe('in_progress');

    $insurance = $newIntake->formResponses()->where('schema_key', 'insurance')->first();
    expect($insurance)->not->toBeNull()
        ->and($insurance->data['provider'])->toBe('BlueCross')
        ->and($insurance->status)->toBe('in_progress');

    // Child-specific forms are NOT copied
    $childInfo = $newIntake->formResponses()->where('schema_key', 'child_information')->first();
    expect($childInfo)->toBeNull();
});

test('magic link with multiple intakes redirects to selector', function (): void {
    $patient = Patient::factory()->withMagicLink()->create();
    Intake::factory()->count(2)->create(['patient_id' => $patient->id]);

    $this->get(route('intake.verify', ['token' => $patient->magic_link_token]))
        ->assertRedirect(route('intake.select'));
});
