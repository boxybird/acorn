<?php

use App\Models\FormResponse;
use App\Models\Intake;
use App\Models\Patient;

test('form show returns schema and saved data', function (): void {
    $patient = Patient::factory()->create();
    $intake = Intake::factory()->create(['patient_id' => $patient->id]);

    $this->withSession(['patient_id' => $patient->id, 'intake_id' => $intake->id])
        ->get(route('intake.form.show', 'demographics'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('intake/Form')
            ->has('schema')
            ->has('savedData')
            ->has('forms')
            ->has('progress')
            ->where('progress.total', 6)
        );
});

test('form show returns 404 for unknown schema', function (): void {
    $patient = Patient::factory()->create();
    $intake = Intake::factory()->create(['patient_id' => $patient->id]);

    $this->withSession(['patient_id' => $patient->id, 'intake_id' => $intake->id])
        ->get(route('intake.form.show', 'nonexistent'))
        ->assertNotFound();
});

test('auto-save stores partial data', function (): void {
    $patient = Patient::factory()->create();
    $intake = Intake::factory()->create(['patient_id' => $patient->id]);

    $this->withSession(['patient_id' => $patient->id, 'intake_id' => $intake->id])
        ->put(route('intake.form.save', 'demographics'), [
            'data' => ['first_name' => 'Jane'],
        ])
        ->assertOk();

    $formResponse = $intake->formResponses()->where('schema_key', 'demographics')->first();

    expect($formResponse)->not->toBeNull()
        ->and($formResponse->data['first_name'])->toBe('Jane');
});

test('auto-save merges with existing data', function (): void {
    $patient = Patient::factory()->create();
    $intake = Intake::factory()->create(['patient_id' => $patient->id]);
    FormResponse::factory()->create([
        'intake_id' => $intake->id,
        'schema_key' => 'demographics',
        'data' => ['first_name' => 'Jane'],
    ]);

    $this->withSession(['patient_id' => $patient->id, 'intake_id' => $intake->id])
        ->put(route('intake.form.save', 'demographics'), [
            'data' => ['last_name' => 'Doe'],
        ])
        ->assertOk();

    $formResponse = $intake->formResponses()->where('schema_key', 'demographics')->first();

    expect($formResponse->data)->toBe(['first_name' => 'Jane', 'last_name' => 'Doe']);
});

test('mark complete validates all required fields', function (): void {
    $patient = Patient::factory()->create();
    $intake = Intake::factory()->create(['patient_id' => $patient->id]);

    $this->withSession(['patient_id' => $patient->id, 'intake_id' => $intake->id])
        ->post(route('intake.form.complete', 'demographics'), ['data' => []])
        ->assertSessionHasErrors();
});

test('mark complete succeeds with valid data', function (): void {
    $patient = Patient::factory()->create();
    $intake = Intake::factory()->create(['patient_id' => $patient->id]);

    $this->withSession(['patient_id' => $patient->id, 'intake_id' => $intake->id])
        ->post(route('intake.form.complete', 'demographics'), [
            'data' => [
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'phone' => '505-555-1234',
                'email' => 'jane@example.com',
                'address' => '123 Main St, Albuquerque, NM 87101',
                'preferred_language' => 'en',
                'referral_source' => 'pediatrician',
            ],
        ])
        ->assertRedirect(route('intake.form.completed', 'demographics'));

    $formResponse = $intake->formResponses()->where('schema_key', 'demographics')->first();

    expect($formResponse->isCompleted())->toBeTrue();
});

test('completion page shows completed form with next form', function (): void {
    $patient = Patient::factory()->create();
    $intake = Intake::factory()->create(['patient_id' => $patient->id]);
    FormResponse::factory()->create([
        'intake_id' => $intake->id,
        'schema_key' => 'demographics',
        'status' => 'completed',
    ]);

    $this->withSession(['patient_id' => $patient->id, 'intake_id' => $intake->id])
        ->get(route('intake.form.completed', 'demographics'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('intake/FormComplete')
            ->has('completedForm')
            ->has('nextForm')
            ->has('progress')
            ->where('completedForm.key', 'demographics')
            ->has('completedForm.title')
            ->where('nextForm.key', 'insurance')
        );
});
