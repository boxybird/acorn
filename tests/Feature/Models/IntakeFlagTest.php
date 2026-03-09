<?php

use App\Models\FormResponse;
use App\Models\Intake;
use App\Models\IntakeFlag;
use App\Models\User;

it('belongs to an intake and form response', function (): void {
    $flag = IntakeFlag::factory()->create();

    expect($flag->intake)->toBeInstanceOf(Intake::class)
        ->and($flag->formResponse)->toBeInstanceOf(FormResponse::class)
        ->and($flag->user)->toBeInstanceOf(User::class);
});

it('knows if it is resolved', function (): void {
    $unresolved = IntakeFlag::factory()->create();
    $resolved = IntakeFlag::factory()->resolved()->create();

    expect($unresolved->isResolved())->toBeFalse()
        ->and($resolved->isResolved())->toBeTrue();
});

it('is accessible via intake relationship', function (): void {
    $intake = Intake::factory()->create();
    IntakeFlag::factory()->for($intake)->count(2)->create();

    expect($intake->flags)->toHaveCount(2);
});

it('is accessible via form response relationship', function (): void {
    $formResponse = FormResponse::factory()->create();
    IntakeFlag::factory()->for($formResponse)->count(3)->create();

    expect($formResponse->flags)->toHaveCount(3);
});
