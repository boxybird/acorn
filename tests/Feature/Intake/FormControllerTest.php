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

test('auto-save extracts child name on child information form', function (): void {
    $patient = Patient::factory()->create();
    $intake = Intake::factory()->withoutChildName()->create(['patient_id' => $patient->id]);

    $this->withSession(['patient_id' => $patient->id, 'intake_id' => $intake->id])
        ->put(route('intake.form.save', 'child_information'), [
            'data' => [
                'child_first_name' => 'Liam',
                'child_last_name' => 'Chen',
            ],
        ])
        ->assertOk();

    $intake->refresh();
    expect($intake->child_name)->toBe('Liam Chen');
});

test('completing child information updates intake child_name', function (): void {
    $patient = Patient::factory()->create();
    $intake = Intake::factory()->withoutChildName()->create(['patient_id' => $patient->id]);

    $this->withSession(['patient_id' => $patient->id, 'intake_id' => $intake->id])
        ->post(route('intake.form.complete', 'child_information'), [
            'data' => [
                'child_first_name' => 'Emma',
                'child_last_name' => 'Garcia',
                'child_dob' => '2020-05-15',
                'child_gender' => 'female',
            ],
        ])
        ->assertRedirect(route('intake.form.completed', 'child_information'));

    $intake->refresh();
    expect($intake->child_name)->toBe('Emma Garcia');
});

test('completing non-child form does not change intake child_name', function (): void {
    $patient = Patient::factory()->create();
    $intake = Intake::factory()->create(['patient_id' => $patient->id, 'child_name' => 'Existing Name']);

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
        ->assertRedirect();

    $intake->refresh();
    expect($intake->child_name)->toBe('Existing Name');
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
