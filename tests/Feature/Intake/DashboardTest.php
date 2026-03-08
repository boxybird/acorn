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
