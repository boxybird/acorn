<?php

use App\Models\Intake;
use App\Models\Patient;

it('loads the intake landing page', function (): void {
    $pendingAwaitablePage = visit('/intake');

    $pendingAwaitablePage->assertSee('Get Started')
        ->assertSee('Send Secure Link')
        ->assertNoJavaScriptErrors();
});

it('loads the intake landing page on mobile', function (): void {
    $on = visit('/intake')->on()->mobile();

    $on->assertSee('Get Started')
        ->assertSee('Send Secure Link')
        ->assertDontSee('Welcome to Your Intake Journey')
        ->assertNoJavaScriptErrors();
});

it('shows validation error for empty email', function (): void {
    $pendingAwaitablePage = visit('/intake');

    $pendingAwaitablePage->click('Send Secure Link')
        ->assertSee('email')
        ->assertNoJavaScriptErrors();
});

it('shows confirmation message after requesting magic link', function (): void {
    $pendingAwaitablePage = visit('/intake');

    $pendingAwaitablePage->fill('email', 'parent@example.com')
        ->click('Send Secure Link')
        ->assertSee('Check your email')
        ->assertNoJavaScriptErrors();
});

it('authenticates via magic link and reaches dashboard', function (): void {
    $patient = Patient::factory()->create([
        'magic_link_token' => 'test-browser-token',
        'magic_link_expires_at' => now()->addMinutes(30),
    ]);

    $pendingAwaitablePage = visit('/intake/verify/test-browser-token');

    $pendingAwaitablePage->assertPathIs('/intake/dashboard')
        ->assertSee('Welcome!')
        ->assertSee('Get Started')
        ->assertNoJavaScriptErrors();
});

it('navigates to first form via Get Started button', function (): void {
    $patient = Patient::factory()->create([
        'magic_link_token' => 'test-form-token',
        'magic_link_expires_at' => now()->addMinutes(30),
    ]);

    $pendingAwaitablePage = visit('/intake/verify/test-form-token');

    $pendingAwaitablePage->assertPathIs('/intake/dashboard')
        ->click('Get Started')
        ->assertSee('Parent/Guardian Information')
        ->assertSee('First Name')
        ->assertSee('Last Name')
        ->assertNoJavaScriptErrors();
});

it('navigates between form sections', function (): void {
    $patient = Patient::factory()->create([
        'magic_link_token' => 'test-nav-token',
        'magic_link_expires_at' => now()->addMinutes(30),
    ]);

    $pendingAwaitablePage = visit('/intake/verify/test-nav-token');

    $pendingAwaitablePage->click('Get Started')
        ->assertSee('Parent/Guardian Information')
        ->click('Next')
        ->assertSee('Referral Information')
        ->assertSee('How did you hear about us?')
        ->click('Previous')
        ->assertSee('Parent/Guardian Information')
        ->assertNoJavaScriptErrors();
});

it('shows the form on mobile with bottom navigation', function (): void {
    $patient = Patient::factory()->create([
        'magic_link_token' => 'test-mobile-token',
        'magic_link_expires_at' => now()->addMinutes(30),
    ]);

    $on = visit('/intake/verify/test-mobile-token')->on()->mobile();

    $on->click('Get Started')
        ->assertSee('Parent/Guardian Information')
        ->assertSee('Step 1 / 2')
        ->assertNoJavaScriptErrors();
});

it('smoke tests all intake pages', function (): void {
    $patient = Patient::factory()->create([
        'magic_link_token' => 'test-smoke-token',
        'magic_link_expires_at' => now()->addMinutes(30),
    ]);

    // Authenticate first
    $pendingAwaitablePage = visit('/intake/verify/test-smoke-token');
    $pendingAwaitablePage->assertPathIs('/intake/dashboard');

    // Visit key pages — session is maintained
    $pendingAwaitablePage->navigate('/intake/form/demographics')
        ->assertSee('Parent/Guardian Information')
        ->assertNoJavaScriptErrors();

    $pendingAwaitablePage->navigate('/intake/form/insurance')
        ->assertSee('Insurance')
        ->assertNoJavaScriptErrors();

    $pendingAwaitablePage->navigate('/intake/form/child_information')
        ->assertSee('Child Information')
        ->assertNoJavaScriptErrors();

    $pendingAwaitablePage->navigate('/intake/dashboard')
        ->assertSee('Your Intake')
        ->assertNoJavaScriptErrors();
});

it('auto-creates first intake on magic link verification', function (): void {
    $patient = Patient::factory()->create([
        'magic_link_token' => 'test-auto-intake',
        'magic_link_expires_at' => now()->addMinutes(30),
    ]);

    $pendingAwaitablePage = visit('/intake/verify/test-auto-intake');

    $pendingAwaitablePage->assertPathIs('/intake/dashboard')
        ->assertSee('Welcome!')
        ->assertNoJavaScriptErrors();

    expect(Intake::query()->where('patient_id', $patient->id)->count())->toBe(1);
});

it('shows locale toggle on landing page', function (): void {
    $pendingAwaitablePage = visit('/intake');

    $pendingAwaitablePage->assertSee('EN')
        ->assertSee('ES')
        ->assertNoJavaScriptErrors();
});

it('switches language on landing page', function (): void {
    $pendingAwaitablePage = visit('/intake');

    $pendingAwaitablePage->assertSee('Get Started')
        ->assertSee('Send Secure Link')
        ->click('ES')
        ->assertSee('Comenzar')
        ->assertSee('Enviar Enlace Seguro')
        ->assertNoJavaScriptErrors();
});

it('switches language on dashboard and persists', function (): void {
    $patient = Patient::factory()->create([
        'magic_link_token' => 'test-locale-toggle',
        'magic_link_expires_at' => now()->addMinutes(30),
    ]);

    $pendingAwaitablePage = visit('/intake/verify/test-locale-toggle');

    $pendingAwaitablePage->assertPathIs('/intake/dashboard')
        ->assertSee('Welcome!')
        ->click('ES')
        ->assertSee('¡Bienvenido!')
        ->assertNoJavaScriptErrors();
});

it('shows child cards on hub for patients with multiple intakes', function (): void {
    $patient = Patient::factory()->create([
        'magic_link_token' => 'test-multi-intake',
        'magic_link_expires_at' => now()->addMinutes(30),
    ]);
    Intake::factory()->create(['patient_id' => $patient->id, 'child_name' => 'Liam']);
    Intake::factory()->create(['patient_id' => $patient->id, 'child_name' => 'Emma']);

    $pendingAwaitablePage = visit('/intake/verify/test-multi-intake');

    $pendingAwaitablePage->assertPathIs('/intake/dashboard')
        ->assertSee('Liam')
        ->assertSee('Emma')
        ->assertSee('Add child')
        ->assertNoJavaScriptErrors();
});

it('shows welcome card when no forms started', function (): void {
    $patient = Patient::factory()->create([
        'magic_link_token' => 'test-welcome',
        'magic_link_expires_at' => now()->addMinutes(30),
    ]);

    $pendingAwaitablePage = visit('/intake/verify/test-welcome');

    $pendingAwaitablePage->assertPathIs('/intake/dashboard')
        ->assertSee('Welcome!')
        ->assertSee('Your progress saves automatically')
        ->assertNoJavaScriptErrors();
});

it('shows time estimate on hub', function (): void {
    $patient = Patient::factory()->create([
        'magic_link_token' => 'test-time-estimate',
        'magic_link_expires_at' => now()->addMinutes(30),
    ]);

    $pendingAwaitablePage = visit('/intake/verify/test-time-estimate');

    $pendingAwaitablePage->assertPathIs('/intake/dashboard')
        ->assertSee('minutes')
        ->assertNoJavaScriptErrors();
});
