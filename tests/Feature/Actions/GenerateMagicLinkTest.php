<?php

use App\Actions\GenerateMagicLink;
use App\Models\Patient;
use App\Notifications\MagicLinkNotification;
use Illuminate\Support\Facades\Notification;

it('generates a magic link for an existing patient', function (): void {
    Notification::fake();

    $patient = Patient::factory()->create();

    $generateMagicLink = app(GenerateMagicLink::class);
    $generateMagicLink->handle($patient);

    $patient->refresh();

    expect($patient->magic_link_token)->not->toBeNull()
        ->and($patient->magic_link_token)->toHaveLength(64)
        ->and($patient->magic_link_expires_at)->not->toBeNull()
        ->and((int) abs($patient->magic_link_expires_at->diffInMinutes(now())))->toBeBetween(29, 31);

    Notification::assertSentTo($patient, MagicLinkNotification::class);
});

it('creates a patient and sends magic link when email does not exist', function (): void {
    Notification::fake();

    $generateMagicLink = app(GenerateMagicLink::class);
    $generateMagicLink->handleForEmail('newparent@example.com');

    $patient = Patient::query()->whereBlindIndex('email', 'newparent@example.com')->first();

    expect($patient)->not->toBeNull()
        ->and($patient->hasValidMagicLink())->toBeTrue();

    Notification::assertSentTo($patient, MagicLinkNotification::class);
});

it('sends magic link to existing patient when email exists', function (): void {
    Notification::fake();

    Patient::factory()->create(['email' => 'existing@example.com']);

    $generateMagicLink = app(GenerateMagicLink::class);
    $generateMagicLink->handleForEmail('existing@example.com');

    expect(Patient::query()->whereBlindIndex('email', 'existing@example.com')->count())->toBe(1);

    $existing = Patient::query()->whereBlindIndex('email', 'existing@example.com')->first();
    expect($existing->hasValidMagicLink())->toBeTrue();
});
