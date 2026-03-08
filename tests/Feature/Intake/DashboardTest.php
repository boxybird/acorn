<?php

use App\Models\FormResponse;
use App\Models\Intake;
use App\Models\Patient;

test('dashboard shows all form sections with status', function (): void {
    $patient = Patient::factory()->create();
    $intake = Intake::factory()->create(['patient_id' => $patient->id]);

    $this->withSession(['patient_id' => $patient->id, 'intake_id' => $intake->id])
        ->get(route('intake.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('intake/Dashboard')
            ->has('forms')
            ->has('progress')
        );
});

test('dashboard reflects completed sections', function (): void {
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
            ->where('progress.completed', 1)
        );
});

test('dashboard provides time estimate from incomplete forms', function (): void {
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
            ->has('timeEstimate')
            ->where('timeEstimate', fn ($value): bool => $value > 0)
        );
});

test('dashboard provides all intakes for multi-intake patients', function (): void {
    $patient = Patient::factory()->create();
    $intake1 = Intake::factory()->create(['patient_id' => $patient->id, 'child_name' => 'Liam']);
    Intake::factory()->create(['patient_id' => $patient->id, 'child_name' => 'Emma']);

    $this->withSession(['patient_id' => $patient->id, 'intake_id' => $intake1->id])
        ->get(route('intake.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('intake/Dashboard')
            ->has('allIntakes', 2)
            ->where('allIntakes.0.child_name', 'Liam')
            ->where('allIntakes.0.is_current', true)
            ->where('allIntakes.0.completed_forms_count', 0)
            ->has('allIntakes.0.id')
            ->has('allIntakes.0.status')
            ->where('allIntakes.1.child_name', 'Emma')
            ->where('allIntakes.1.is_current', false)
        );
});

test('dashboard provides single intake for single-intake patients', function (): void {
    $patient = Patient::factory()->create();
    $intake = Intake::factory()->create(['patient_id' => $patient->id]);

    $this->withSession(['patient_id' => $patient->id, 'intake_id' => $intake->id])
        ->get(route('intake.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('intake/Dashboard')
            ->has('allIntakes', 1)
        );
});
