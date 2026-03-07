<?php

use App\Models\FormResponse;
use App\Models\Patient;

test('dashboard shows all form sections with status', function (): void {
    $patient = Patient::factory()->create();

    $this->withSession(['patient_id' => $patient->id])
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
    FormResponse::factory()->completed()->create([
        'patient_id' => $patient->id,
        'schema_key' => 'demographics',
    ]);

    $this->withSession(['patient_id' => $patient->id])
        ->get(route('intake.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('intake/Dashboard')
            ->where('progress.completed', 1)
        );
});
