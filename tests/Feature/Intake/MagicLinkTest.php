<?php

use App\Models\Intake;
use App\Models\Patient;

test('intake landing page can be rendered', function (): void {
    $this->get(route('intake.landing'))
        ->assertOk();
});

test('magic link can be requested with valid email', function (): void {
    $this->post(route('intake.request-link'), ['email' => 'parent@example.com'])
        ->assertRedirect()
        ->assertSessionHas('status');

    expect(Patient::query()->whereBlindIndex('email', 'parent@example.com')->exists())->toBeTrue();
});

test('magic link request validates email', function (): void {
    $this->post(route('intake.request-link'), ['email' => 'not-an-email'])
        ->assertSessionHasErrors('email');
});

test('valid magic link creates session and redirects to dashboard', function (): void {
    $patient = Patient::factory()->withMagicLink()->create();

    $this->get(route('intake.verify', ['token' => $patient->magic_link_token]))
        ->assertRedirect(route('intake.dashboard'))
        ->assertSessionHas('patient_id', $patient->id)
        ->assertSessionHas('intake_id');

    $patient->refresh();
    expect($patient->magic_link_token)->toBeNull();

    $this->assertDatabaseHas('intakes', ['patient_id' => $patient->id]);
});

test('valid magic link with existing intake reuses it', function (): void {
    $patient = Patient::factory()->withMagicLink()->create();
    $intake = Intake::factory()->create(['patient_id' => $patient->id]);

    $this->get(route('intake.verify', ['token' => $patient->magic_link_token]))
        ->assertRedirect(route('intake.dashboard'))
        ->assertSessionHas('patient_id', $patient->id)
        ->assertSessionHas('intake_id', $intake->id);
});

test('valid magic link with multiple intakes sets first intake and redirects to dashboard', function (): void {
    $patient = Patient::factory()->withMagicLink()->create();
    $firstIntake = Intake::factory()->create(['patient_id' => $patient->id]);
    Intake::factory()->create(['patient_id' => $patient->id]);

    $this->get(route('intake.verify', ['token' => $patient->magic_link_token]))
        ->assertRedirect(route('intake.dashboard'))
        ->assertSessionHas('patient_id', $patient->id)
        ->assertSessionHas('intake_id', $firstIntake->id);
});

test('expired magic link shows error', function (): void {
    $patient = Patient::factory()->withExpiredMagicLink()->create();

    $this->get(route('intake.verify', ['token' => $patient->magic_link_token]))
        ->assertRedirect(route('intake.landing'));
});

test('invalid magic link shows error', function (): void {
    $this->get(route('intake.verify', ['token' => 'invalid-token']))
        ->assertRedirect(route('intake.landing'));
});

test('patient dashboard requires patient session', function (): void {
    $this->get(route('intake.dashboard'))
        ->assertRedirect(route('intake.landing'));
});

test('patient dashboard is accessible with valid session', function (): void {
    $patient = Patient::factory()->create();
    $intake = Intake::factory()->create(['patient_id' => $patient->id]);

    $this->withSession(['patient_id' => $patient->id, 'intake_id' => $intake->id])
        ->get(route('intake.dashboard'))
        ->assertOk();
});
