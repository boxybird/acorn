<?php

use App\Models\Intake;
use App\Models\Patient;

test('patient preferred locale can be updated', function (): void {
    $patient = Patient::factory()->create();
    $intake = Intake::factory()->create(['patient_id' => $patient->id]);

    $this->withSession(['patient_id' => $patient->id, 'intake_id' => $intake->id])
        ->post(route('intake.set-locale'), ['locale' => 'es'])
        ->assertOk();

    $patient->refresh();

    expect($patient->preferred_locale)->toBe('es');
});

test('locale validation rejects invalid values', function (): void {
    $patient = Patient::factory()->create();
    $intake = Intake::factory()->create(['patient_id' => $patient->id]);

    $this->withSession(['patient_id' => $patient->id, 'intake_id' => $intake->id])
        ->postJson(route('intake.set-locale'), ['locale' => 'fr'])
        ->assertUnprocessable();
});

test('set locale requires authentication', function (): void {
    $this->post(route('intake.set-locale'), ['locale' => 'es'])
        ->assertRedirect(route('intake.landing'));
});

test('guest can set locale in session', function (): void {
    $this->post(route('intake.set-locale-guest'), ['locale' => 'es'])
        ->assertOk();

    $this->get(route('intake.landing'))
        ->assertOk();

    expect(session('locale'))->toBe('es');
});

test('guest locale validation rejects invalid values', function (): void {
    $this->postJson(route('intake.set-locale-guest'), ['locale' => 'fr'])
        ->assertUnprocessable();
});

test('guest locale validation requires locale field', function (): void {
    $this->postJson(route('intake.set-locale-guest'), [])
        ->assertUnprocessable();
});

test('landing page receives locale prop', function (): void {
    $this->get(route('intake.landing'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('intake/Landing')
            ->has('locale')
            ->where('locale', 'en')
        );
});

test('landing page reflects session locale via middleware', function (): void {
    $this->withSession(['locale' => 'es'])
        ->get(route('intake.landing'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('intake/Landing')
            ->where('locale', 'es')
        );
});

test('middleware uses patient preferred locale when authenticated', function (): void {
    $patient = Patient::factory()->create(['preferred_locale' => 'es']);
    $intake = Intake::factory()->create(['patient_id' => $patient->id]);

    $this->withSession(['patient_id' => $patient->id, 'intake_id' => $intake->id])
        ->get(route('intake.dashboard'))
        ->assertOk();

    expect(app()->getLocale())->toBe('es');
});

test('middleware falls back to accept-language header when no session locale', function (): void {
    $this->withHeaders(['Accept-Language' => 'es-MX,es;q=0.9'])
        ->get(route('intake.landing'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('intake/Landing')
            ->where('locale', 'es')
        );
});

test('middleware defaults to english when no preference exists', function (): void {
    $this->get(route('intake.landing'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('intake/Landing')
            ->where('locale', 'en')
        );
});

test('session locale is preserved across magic link verification', function (): void {
    $patient = Patient::factory()->withMagicLink()->create();

    $this->withSession(['locale' => 'es'])
        ->get(route('intake.verify', ['token' => $patient->magic_link_token]))
        ->assertRedirect(route('intake.dashboard'))
        ->assertSessionHas('locale', 'es');
});

test('session locale is copied to patient record on magic link verify when patient has no locale preference', function (): void {
    $patient = Patient::factory()->withMagicLink()->create(['preferred_locale' => null]);

    $this->withSession(['locale' => 'es'])
        ->get(route('intake.verify', ['token' => $patient->magic_link_token]))
        ->assertRedirect(route('intake.dashboard'));

    $patient->refresh();
    expect($patient->preferred_locale)->toBe('es');
});

test('session locale does not overwrite existing patient locale preference of es on magic link verify', function (): void {
    $patient = Patient::factory()->withMagicLink()->create(['preferred_locale' => 'es']);

    $this->withSession(['locale' => 'en'])
        ->get(route('intake.verify', ['token' => $patient->magic_link_token]))
        ->assertRedirect(route('intake.dashboard'));

    $patient->refresh();
    expect($patient->preferred_locale)->toBe('es');
});

test('session locale does not overwrite existing patient locale preference of en on magic link verify', function (): void {
    $patient = Patient::factory()->withMagicLink()->create(['preferred_locale' => 'en']);

    $this->withSession(['locale' => 'es'])
        ->get(route('intake.verify', ['token' => $patient->magic_link_token]))
        ->assertRedirect(route('intake.dashboard'));

    $patient->refresh();
    expect($patient->preferred_locale)->toBe('en');
});

test('magic link verify without session locale does not change patient locale', function (): void {
    $patient = Patient::factory()->withMagicLink()->create(['preferred_locale' => 'en']);

    $this->get(route('intake.verify', ['token' => $patient->magic_link_token]))
        ->assertRedirect(route('intake.dashboard'));

    $patient->refresh();
    expect($patient->preferred_locale)->toBe('en');
});
