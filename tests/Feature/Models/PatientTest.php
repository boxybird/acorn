<?php

use App\Models\Patient;

test('patient can be created with factory', function (): void {
    $patient = Patient::factory()->create();

    expect($patient)->toBeInstanceOf(Patient::class)
        ->and($patient->email)->not->toBeEmpty()
        ->and($patient->preferred_locale)->toBe('en');
});

test('patient with magic link factory state works', function (): void {
    $patient = Patient::factory()->withMagicLink()->create();

    expect($patient->magic_link_token)->not->toBeNull()
        ->and($patient->magic_link_expires_at)->not->toBeNull()
        ->and($patient->hasValidMagicLink())->toBeTrue();
});

test('expired magic link is not valid', function (): void {
    $patient = Patient::factory()->withExpiredMagicLink()->create();

    expect($patient->hasValidMagicLink())->toBeFalse();
});
