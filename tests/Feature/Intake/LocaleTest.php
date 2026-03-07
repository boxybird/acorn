<?php

use App\Models\Patient;

test('patient preferred locale can be updated', function (): void {
    $patient = Patient::factory()->create();

    $this->withSession(['patient_id' => $patient->id])
        ->post(route('intake.set-locale'), ['locale' => 'es'])
        ->assertOk();

    $patient->refresh();

    expect($patient->preferred_locale)->toBe('es');
});

test('locale validation rejects invalid values', function (): void {
    $patient = Patient::factory()->create();

    $this->withSession(['patient_id' => $patient->id])
        ->postJson(route('intake.set-locale'), ['locale' => 'fr'])
        ->assertUnprocessable();
});

test('set locale requires authentication', function (): void {
    $this->post(route('intake.set-locale'), ['locale' => 'es'])
        ->assertRedirect(route('intake.landing'));
});
