<?php

use App\Models\Patient;
use App\Models\User;

beforeEach(function (): void {
    config()->set('demo.enabled', true);
});

it('logs in as a patient and redirects to intake dashboard', function (): void {
    $patient = Patient::factory()->create();

    $response = $this->post("/demo/login/patient/{$patient->id}");

    $response->assertRedirect(route('intake.dashboard'));
    expect(session('patient_id'))->toBe($patient->id);
});

it('logs in as a staff user and redirects to dashboard', function (): void {
    $user = User::factory()->create();

    $response = $this->post("/demo/login/user/{$user->id}");

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($user);
});

it('logs out both contexts and redirects home', function (): void {
    $user = User::factory()->create();
    $patient = Patient::factory()->create();

    $this->actingAs($user)
        ->withSession(['patient_id' => $patient->id])
        ->post('/demo/logout');

    $this->assertGuest();
    expect(session('patient_id'))->toBeNull();
});

it('returns 403 when demo mode is disabled', function (): void {
    config()->set('demo.enabled', false);
    $patient = Patient::factory()->create();

    $this->post("/demo/login/patient/{$patient->id}")
        ->assertForbidden();
});

it('disables registration route when demo mode is enabled', function (): void {
    config()->set('demo.enabled', true);

    $this->get('/register')->assertNotFound();
});

it('excludes registration from fortify features when demo mode is enabled', function (): void {
    config()->set('demo.enabled', true);

    $features = array_filter([
        config('demo.enabled') ? null : \Laravel\Fortify\Features::registration(),
        \Laravel\Fortify\Features::resetPasswords(),
    ]);

    expect($features)->not->toContain(\Laravel\Fortify\Features::registration());
});

it('includes registration in fortify features when demo mode is disabled', function (): void {
    config()->set('demo.enabled', false);

    $features = array_filter([
        config('demo.enabled') ? null : \Laravel\Fortify\Features::registration(),
        \Laravel\Fortify\Features::resetPasswords(),
    ]);

    expect($features)->toContain(\Laravel\Fortify\Features::registration());
});
