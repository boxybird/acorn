<?php

use App\Models\Patient;
use App\Notifications\MagicLinkNotification;
use App\Services\MagicLinkService;
use Illuminate\Support\Facades\Notification;

test('magic link service generates token and sends notification', function (): void {
    Notification::fake();

    $patient = Patient::factory()->create();
    $magicLinkService = app(MagicLinkService::class);

    $magicLinkService->send($patient);

    $patient->refresh();

    expect($patient->magic_link_token)->not->toBeNull()
        ->and($patient->magic_link_token)->toHaveLength(64)
        ->and($patient->magic_link_expires_at)->not->toBeNull()
        ->and((int) abs($patient->magic_link_expires_at->diffInMinutes(now())))->toBeBetween(29, 31);

    Notification::assertSentTo($patient, MagicLinkNotification::class);
});

test('magic link service creates new patient if email not found', function (): void {
    Notification::fake();

    $magicLinkService = app(MagicLinkService::class);
    $magicLinkService->sendToEmail('new@example.com');

    $patient = Patient::query()->whereBlindIndex('email', 'new@example.com')->first();

    expect($patient)->not->toBeNull()
        ->and($patient->hasValidMagicLink())->toBeTrue();

    Notification::assertSentTo($patient, MagicLinkNotification::class);
});

test('magic link service reuses existing patient', function (): void {
    Notification::fake();

    Patient::factory()->create(['email' => 'existing@example.com']);
    $magicLinkService = app(MagicLinkService::class);
    $magicLinkService->sendToEmail('existing@example.com');

    expect(Patient::query()->whereBlindIndex('email', 'existing@example.com')->count())->toBe(1);

    $existing = Patient::query()->whereBlindIndex('email', 'existing@example.com')->first();
    expect($existing->hasValidMagicLink())->toBeTrue();
});
