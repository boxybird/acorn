<?php

use App\Models\FormResponse;
use App\Models\Intake;
use App\Models\IntakeFlag;
use App\Models\Patient;

test('dashboard provides per-intake data with forms and progress', function (): void {
    $patient = Patient::factory()->create();
    $intake = Intake::factory()->create(['patient_id' => $patient->id]);

    $this->withSession(['patient_id' => $patient->id, 'intake_id' => $intake->id])
        ->get(route('intake.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('intake/Dashboard')
            ->has('intakes', 1)
            ->has('intakes.0.forms')
            ->has('intakes.0.progress')
            ->has('intakes.0.time_estimate')
            ->has('intakes.0.flags')
            ->where('intakes.0.is_current', true)
        );
});

test('dashboard reflects completed sections per intake', function (): void {
    $patient = Patient::factory()->create();
    $intake = Intake::factory()->create(['patient_id' => $patient->id]);
    FormResponse::factory()->completed()->create([
        'intake_id' => $intake->id,
        'schema_key' => 'demographics',
    ]);

    $this->withSession(['patient_id' => $patient->id, 'intake_id' => $intake->id])
        ->get(route('intake.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('intake/Dashboard')
            ->where('intakes.0.progress.completed', 1)
        );
});

test('dashboard provides time estimate per intake from incomplete forms', function (): void {
    $patient = Patient::factory()->create();
    $intake = Intake::factory()->create(['patient_id' => $patient->id]);
    FormResponse::factory()->completed()->create([
        'intake_id' => $intake->id,
        'schema_key' => 'demographics',
    ]);

    $this->withSession(['patient_id' => $patient->id, 'intake_id' => $intake->id])
        ->get(route('intake.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('intake/Dashboard')
            ->where('intakes.0.time_estimate', fn ($value): bool => $value > 0)
        );
});

test('dashboard provides multiple intakes for multi-intake patients', function (): void {
    $patient = Patient::factory()->create();
    $intake1 = Intake::factory()->create(['patient_id' => $patient->id, 'child_name' => 'Liam']);
    Intake::factory()->create(['patient_id' => $patient->id, 'child_name' => 'Emma']);

    $this->withSession(['patient_id' => $patient->id, 'intake_id' => $intake1->id])
        ->get(route('intake.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('intake/Dashboard')
            ->has('intakes', 2)
            ->where('intakes.0.child_name', 'Liam')
            ->where('intakes.0.is_current', true)
            ->has('intakes.0.forms')
            ->has('intakes.0.progress')
            ->where('intakes.1.child_name', 'Emma')
            ->where('intakes.1.is_current', false)
            ->has('intakes.1.forms')
            ->has('intakes.1.progress')
        );
});

test('dashboard includes unresolved flags per intake', function (): void {
    $patient = Patient::factory()->create();
    $intake = Intake::factory()->create(['patient_id' => $patient->id]);
    $formResponse = FormResponse::factory()->create([
        'intake_id' => $intake->id,
        'schema_key' => 'demographics',
    ]);
    IntakeFlag::factory()->create([
        'intake_id' => $intake->id,
        'form_response_id' => $formResponse->id,
        'reason' => 'Missing date of birth',
    ]);
    IntakeFlag::factory()->resolved()->create([
        'intake_id' => $intake->id,
        'form_response_id' => $formResponse->id,
    ]);

    $this->withSession(['patient_id' => $patient->id, 'intake_id' => $intake->id])
        ->get(route('intake.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('intake/Dashboard')
            ->has('intakes.0.flags', 1)
            ->where('intakes.0.flags.0.reason', 'Missing date of birth')
            ->has('intakes.0.flags.0.form_response')
        );
});
