<?php

use App\Models\Patient;

test('intake landing page can be rendered', function (): void {
    $this->get(route('intake.landing'))
        ->assertOk();
});

test('magic link can be requested with valid email', function (): void {
    $this->post(route('intake.request-link'), ['email' => 'parent@example.com'])
        ->assertRedirect();

    $this->assertDatabaseHas('patients', ['email' => 'parent@example.com']);
});

test('magic link request validates email', function (): void {
    $this->post(route('intake.request-link'), ['email' => 'not-an-email'])
        ->assertSessionHasErrors('email');
});

test('valid magic link creates session and redirects to dashboard', function (): void {
    $patient = Patient::factory()->withMagicLink()->create();

    $this->get(route('intake.verify', ['token' => $patient->magic_link_token]))
        ->assertRedirect(route('intake.dashboard'));

    $patient->refresh();
    expect($patient->magic_link_token)->toBeNull();
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

    $this->withSession(['patient_id' => $patient->id])
        ->get(route('intake.dashboard'))
        ->assertOk();
});
